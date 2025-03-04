<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Corrected commenter IP for Cloudflare CDN
 * Plugin URI:        https://github.com/guguan123/wordpress-cfcdn-comment-ip-fix
 * Description:       修复评论者的IP信息，适用于使用Cloudflare CDN的网站。
 * Version:           0.1.0
 * Author:            GuGuan123
 * Author URI:        https://github.com/guguan123/
 * License:           MIT
 * License URI:       https://choosealicense.com/licenses/mit/
 * Text Domain:       wordpress-cfcdn-comment-ip-fix
 */

if (!defined('ABSPATH')) {
	exit; // 防止直接访问
}

require_once __DIR__ . '/vendor/autoload.php';

class Corrected_Commenter_IP_Cloudflare {

	// 定义缓存的唯一选项键
	const CLOUDFLARE_IP_CACHE_KEY = 'cloudflare_ip_cache';

	public function __construct() {
		// 插件激活时注册定时任务
		register_activation_hook(__FILE__, ['Corrected_Commenter_IP_Cloudflare', 'cf_schedule_cron_job']);
		// 插件停用时清理定时任务
		register_deactivation_hook(__FILE__, ['Corrected_Commenter_IP_Cloudflare', 'cf_clear_cron_job']);
		// 卸载插件后清理缓存数据
		register_uninstall_hook(__FILE__, ['Corrected_Commenter_IP_Cloudflare', 'cf_uninstall']);
		// 绑定评论发布时保存真实 IP
		add_action('preprocess_comment', [$this, 'save_real_ip_on_comment']);
		// 定义定时任务的 Hook
		add_action('cf_update_cloudflare_ips', 'cf_fetch_and_save_cloudflare_ips');
		// 添加管理页面
		add_action('admin_menu', [$this, 'corrected_commenter_ip_cloudflare_admin_menu']);
		// 处理表单提交
		add_action('admin_init', [$this, 'handle_form_submission']);
		// 添加 AJAX 动作
		add_action('wp_ajax_update_cloudflare_ips', [$this, 'handle_ajax_update']);
	}

	/**
	 * 计划每日执行的cron任务来更新Cloudflare IP。
	 */
	public static function cf_schedule_cron_job() {
		// 检查cron任务是否已经计划
		if (!wp_next_scheduled('cf_update_cloudflare_ips')) {
			// 计划cron任务每日执行
			wp_schedule_event(time(), 'daily', 'cf_update_cloudflare_ips');
		}
	}

	/**
	 * 清除计划的cron任务来更新Cloudflare IP。
	 */
	public static function cf_clear_cron_job() {
		// 获取下一次计划的cron任务的时间戳
		$timestamp = wp_next_scheduled('cf_update_cloudflare_ips');
		// 如果cron任务已经计划，取消计划
		if ($timestamp) {
			wp_unschedule_event($timestamp, 'cf_update_cloudflare_ips');
		}
	}

	/**
	 * 卸载插件，删除缓存的Cloudflare IP和清除cron任务。
	 */
	public static function cf_uninstall() {
		// 删除缓存的Cloudflare IP
		delete_option(self::CLOUDFLARE_IP_CACHE_KEY);
		// 清除计划的cron任务
		self::cf_clear_cron_job();
	}


	public function corrected_commenter_ip_cloudflare_admin_menu() {
		// 添加顶级菜单页面
		add_submenu_page(
			'options-general.php', // “设置”菜单的 slug
			'Cloudflare IP 修正评论者',
			'Cloudflare IP',
			'manage_options',
			'corrected-commenter-ip-cloudflare',
			[$this, 'corrected_commenter_ip_cloudflare_admin_page']
		);
	}

	// 渲染管理页面
	public function corrected_commenter_ip_cloudflare_admin_page() {
		wp_enqueue_script('jquery');
		require_once 'settings_page.php';
	}

	// 处理更新按钮的表单提交
	public function handle_form_submission() {
		if (isset($_POST['update_cloudflare_ips']) && 
			current_user_can('manage_options') && 
			check_admin_referer('cloudflare_ip_settings-options')) {
			$this->cf_fetch_and_save_cloudflare_ips();
			add_settings_error(
				'cloudflare_ip_messages',
				'cache_updated',
				'Cloudflare IP 缓存已成功更新！',
				'success'
			);
		}
	}

	// 处理 AJAX 请求
    public function handle_ajax_update() {
        // 验证权限和 nonce
        if (!current_user_can('manage_options') || 
            !check_ajax_referer('cloudflare_ip_settings-options', 'nonce', false)) {
            wp_send_json_error(['message' => '权限不足或请求无效。']);
        }

        // 执行更新操作
        $this->cf_fetch_and_save_cloudflare_ips();

        // 返回成功响应
        wp_send_json_success([
            'message' => 'Cloudflare IP 缓存已成功更新！',
            'cache_data' => print_r(get_option(self::CLOUDFLARE_IP_CACHE_KEY), true) // 返回更新后的缓存数据
        ]);
    }

	// 获取并缓存 Cloudflare IP 范围的函数
	public function cf_fetch_and_save_cloudflare_ips() {
		// Cloudflare API 地址
		$api_url = 'https://api.cloudflare.com/client/v4/ips';

		// 从 API 获取新数据
		$response = wp_remote_get($api_url);
		if (is_wp_error($response)) {
			error_log('获取 Cloudflare IP 数据失败: ' . $response->get_error_message());
			return false;
		}
		$response_body = wp_remote_retrieve_body($response);
		$new_data = json_decode($response_body, true);
		if (!$new_data['success']) {
			error_log('Cloudflare API 返回错误。');
			return false;
		}

		// 获取现有缓存数据
		$cached_data = get_option(self::CLOUDFLARE_IP_CACHE_KEY);

		// 如果已有缓存，检查 etag 以判断是否需要更新
		if ($cached_data) {
			$cached_data = json_decode($cached_data, true); // 将缓存数据解码为数组
			$cached_etag = $cached_data['etag'] ?? null;

			$new_etag = $new_data['result']['etag'] ?? null;

			// 如果 etag 不一致，则更新缓存
			if ($cached_etag !== $new_etag) {
				// 用新数据更新缓存
				update_option(self::CLOUDFLARE_IP_CACHE_KEY, json_encode($new_data['result']));
			}
		} else {
			// 如果没有缓存，直接保存新数据
			update_option(self::CLOUDFLARE_IP_CACHE_KEY, json_encode($new_data['result']));
		}
		return json_encode($new_data['result']);
	}



	/**
	 * 验证请求是否来自 Cloudflare
	 *
	 * @param string $connecting_ip 需要检查的 IP 地址
	 * @return bool true 表示请求来自 Cloudflare，false 表示不是
	 */
	private function is_request_from_cdn($connecting_ip) {
		if (empty($connecting_ip)) {
			return false;
		}
		foreach ($this->get_ip_ranges() as $ip_range) {
			if ($ip_range->contains(\IPLib\Factory::addressFromString($connecting_ip))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 获取 Cloudflare 的 IP 范围列表
	 *  (需要定期更新 IP 列表)
	 *
	 * @return array 包含 IP 范围的数组，例如 ['199.27.128.0/21', '173.245.48.0/20', ...]
	 */
	private function get_ip_ranges() {
		// 从缓存中获取 Cloudflare IP 数据
		$ips_json_data = get_option(self::CLOUDFLARE_IP_CACHE_KEY);
	
		if (!$ips_json_data) {
			$ips_json_data = $this->cf_fetch_and_save_cloudflare_ips();
		}
	
		// 解码缓存数据
		$decoded_data = json_decode($ips_json_data, true);
	
		// 合并 IPv4 和 IPv6 地址段，并解析为 Range 对象
		return array_map(function ($cidr) {
			return \IPLib\Factory::parseRangeString($cidr);
		}, array_merge($decoded_data['ipv4_cidrs'], $decoded_data['ipv6_cidrs']));
	}


	/**
	 * 在评论元数据中保存真实 IP 和地理位置信息
	 *
	 * @param int $commentdata
	 */
	public function save_real_ip_on_comment($commentdata) {
		// 检查依赖库是否可用
		if (!class_exists('\IPLib\Factory')) {
			error_log(__('IPLib not found. Real IP validation skipped.', 'wordpress-corrected-commenter-ip-for-cloudflare'));
			return $commentdata;
		}

		if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP) && $this->is_request_from_cdn($_SERVER['REMOTE_ADDR'])) {
			// 将访客真实 IP 存储为评论的元数据
			$commentdata['comment_author_IP'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		return $commentdata;
	}
}

// 初始化插件
new Corrected_Commenter_IP_Cloudflare();
