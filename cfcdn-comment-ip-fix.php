<?php
/**
 * @wordpress-plugin
 * Plugin Name:       CfCDN Comment IP Fix
 * Plugin URI:        https://github.com/guguan123/cfcdn-comment-ip-fix
 * Description:       修复评论者的IP信息，适用于使用 Cloudflare CDN 的网站。（本插件不是 Cloudflare 开发的！）
 * Version:           0.1.1
 * Author:            GuGuan123
 * Author URI:        https://guguan.us.kg
 * License:           MIT
 * License URI:       https://choosealicense.com/licenses/mit/
 * Text Domain:       cfcdn-comment-ip-fix
 * Requires at least: 6.7.2
 * Tested up to:      6.0
 * PHP Version:       8.2
 * Requires PHP:      7.0
 * Changelog:         https://github.com/guguan123/cfcdn-comment-ip-fix/releases
 * Support:           https://github.com/guguan123/cfcdn-comment-ip-fix/issues
 */

if (!defined('ABSPATH')) {
	exit; // 防止直接访问
}

class Corrected_Commenter_IP_CfCDN {

	// 定义缓存的唯一选项键
	const CDN_IP_CACHE_KEY = 'cdn_ip_cache';

	public function __construct() {
		// 加载第三方库文件
		if (file_exists(__DIR__ . '/vendor/autoload.php')) {
			require_once __DIR__ . '/vendor/autoload.php';
		}

		// 插件激活时注册定时任务
		register_activation_hook(__FILE__, ['Corrected_Commenter_IP_CfCDN', 'cf_schedule_cron_job']);
		// 插件停用时清理定时任务
		register_deactivation_hook(__FILE__, ['Corrected_Commenter_IP_CfCDN', 'cf_clear_cron_job']);
		// 卸载插件后清理缓存数据
		register_uninstall_hook(__FILE__, ['Corrected_Commenter_IP_CfCDN', 'cf_uninstall']);
		// 绑定评论发布时保存真实 IP
		add_action('preprocess_comment', [$this, 'save_real_ip_on_comment']);
		// 定义定时任务的 Hook
		add_action('cf_update_cloudflare_ips', 'cf_fetch_and_save_cloudflare_ips');
		// 添加管理页面
		add_action('admin_menu', [$this, 'Corrected_Commenter_IP_CfCDN_admin_menu']);
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
		delete_option(self::CDN_IP_CACHE_KEY);
		// 清除计划的cron任务
		self::cf_clear_cron_job();
	}

	public function Corrected_Commenter_IP_CfCDN_admin_menu() {
		// 添加顶级菜单页面
		add_submenu_page(
			'options-general.php', // “设置”菜单的 slug
			'Cloudflare 修正评论者 IP',
			'Cloudflare IP fix',
			'manage_options',
			'corrected-commenter-ip-cloudflare',
			[$this, 'Corrected_Commenter_IP_CfCDN_admin_page']
		);
	}

	// 渲染管理页面
	public function Corrected_Commenter_IP_CfCDN_admin_page() {
		wp_enqueue_script('jquery');
		require_once 'settings_page.php';
	}

	// 处理更新按钮的表单提交
	public function handle_form_submission() {
		if (isset($_POST['update_cloudflare_ips']) && 
			current_user_can('manage_options') && 
			check_admin_referer('cloudflare_ip_settings-options')) {

			$cf_ip_data = $this->cf_fetch_and_save_cloudflare_ips();
			if ($cf_ip_data['status'] === 'success')  {
				add_settings_error(
					'cloudflare_ip_messages',
					'cache_updated',
					'Cloudflare IP 缓存已成功更新！',
					'success'
				);
			} else {
				add_settings_error(
					'cloudflare_ip_messages',
					'cache_updated',
					'Cloudflare IP 更新失败：' . $cf_ip_data['message'],
					'error'
				);
			}
		}

		if (isset($_POST['additional_cdn_ips']) && 
			current_user_can('manage_options') && 
			check_admin_referer('cloudflare_ip_settings-options')) {

			if (!empty($_POST['additional_cdn_ips'])) {
				$additional_ips = explode(',', sanitize_text_field(wp_unslash($_POST['additional_cdn_ips'])));
			}

			// 获取现有缓存数据
			$cached_data = json_decode(get_option(self::CDN_IP_CACHE_KEY), true);
			if (isset($cached_data['cloudflare']) && !empty($_POST['additional_cdn_ips'])) {
				$cached_data['other_cidrs'] = $additional_ips;
			} elseif (!empty($_POST['additional_cdn_ips'])) {
				$cached_data = array('other_cidrs' => $additional_ips);
			} else {
				$cached_data = array('cloudflare' => $cached_data['cloudflare']);
			}
			update_option(self::CDN_IP_CACHE_KEY, json_encode($cached_data));
			add_settings_error(
				'cloudflare_ip_messages',
				'cache_updated',
				'已成功更新额外 CDN IP！',
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
		$cf_ip_data = $this->cf_fetch_and_save_cloudflare_ips();

		if ($cf_ip_data['status'] === 'success')  {
			// 返回成功响应
			wp_send_json_success([
				'message' => 'Cloudflare IP 缓存已成功更新！',
				'cache_data' => print_r(json_decode(get_option(self::CDN_IP_CACHE_KEY), true), true) // 返回更新后的缓存数据
			]);
		} else {
			// 返回错误响应
			wp_send_json_error(['message' => 'Cloudflare IP 缓存更新失败：' . $cf_ip_data['message']]);
		}
	}

	// 获取并缓存 Cloudflare IP 范围的函数
	public function cf_fetch_and_save_cloudflare_ips() {
		// Cloudflare API 地址
		$api_url = 'https://api.cloudflare.com/client/v4/ips';

		// 从 API 获取新数据
		$response = wp_remote_get($api_url);
		if (is_wp_error($response)) {
			return ['status' => 'error', 'message' => '获取 Cloudflare IP 数据失败' . $response->get_error_message()];
		}
		$response_body = wp_remote_retrieve_body($response);
		$new_cloudflare_data = json_decode($response_body, true);
		if (!$new_cloudflare_data['success']) {
			return ['status' => 'error', 'message' => 'Cloudflare API 返回错误：' . json_encode($new_cloudflare_data['errors'], JSON_UNESCAPED_UNICODE)];
		}

		// 获取现有缓存数据
		$cached_data = get_option(self::CDN_IP_CACHE_KEY);

		// 如果已有缓存，检查 etag 以判断是否需要更新
		if ($cached_data) {
			$cached_data = json_decode($cached_data, true); // 将缓存数据解码为数组

			// 如果 etag 不一致，则更新缓存
			if (isset($cached_data['cloudflare']['etag']) && isset($new_cloudflare_data['result']['etag']) && $cached_data['cloudflare']['etag'] !== $new_cloudflare_data['result']['etag']) {
				$cached_data['cloudflare'] = $new_cloudflare_data['result'];
			}
		} else {
			// 如果没有缓存，直接保存新数据
			$cached_data = array("cloudflare" => $new_cloudflare_data['result']);
		}
		update_option(self::CDN_IP_CACHE_KEY, json_encode($cached_data));
		return ['status' => 'success', 'data' => json_encode($cached_data)];
	}

	/**
	 * 检查一个IP是否在特定IP范围内
	 * @param string $ip
	 * @param array $ranges
	 * @return bool
	 */
	protected function isIpInRange($ip, $ranges) {
		$ipAddress = \IPLib\Factory::addressFromString($ip);
		foreach ($ranges as $range) {
			if ($range->contains($ipAddress)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 获取 CDN 的 IP 范围列表
	 *
	 * @return array 包含 IP 范围的数组，例如 ['199.27.128.0/21', '173.245.48.0/20', ...]
	 */
	private function get_cdn_ip_ranges() {
		// 从缓存中获取 Cloudflare IP 数据
		$ips_json_data = get_option(self::CDN_IP_CACHE_KEY);
	
		if (!$ips_json_data) {
			$get_ips_data = $this->cf_fetch_and_save_cloudflare_ips();
			$ips_json_data = $get_ips_data['data'];
		}
		// 解码缓存数据
		$decoded_data = json_decode($ips_json_data, true);

		// 合并地址段
		if (isset($decoded_data['other_cidrs'])) {
			$cdn_cidrs = array_merge($decoded_data['cloudflare']['ipv4_cidrs'], $decoded_data['cloudflare']['ipv6_cidrs'], $decoded_data['other_cidrs']);
		} else {
			$cdn_cidrs = array_merge($decoded_data['cloudflare']['ipv4_cidrs'], $decoded_data['cloudflare']['ipv6_cidrs']);
		}

		// 解析为 Range 对象
		return array_map(function ($cidr) {
			return \IPLib\Factory::parseRangeString($cidr);
		}, $cdn_cidrs);
	}

	/**
	 * 处理 'Forwarded' 头部的 IP
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getForwardedIp($cdnIpRanges) {
		if (!empty($_SERVER['HTTP_FORWARDED']) && $this->isIpInRange($_SERVER['REMOTE_ADDR'], $cdnIpRanges)) {
			foreach (array_map('trim', explode(',', $_SERVER['HTTP_FORWARDED'])) as $part) {
				if (stripos($part, 'for=') !== false) {
					return trim(str_ireplace('for=', '', $part));
				}
			}
		}
		return false;
	}

	/**
	 * 处理 'X-Forwarded-For' 头部的 IP
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getXForwardedForIp($cdnIpRanges) {
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $this->isIpInRange($_SERVER['REMOTE_ADDR'], $cdnIpRanges)) {
			foreach (array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])) as $ip) {
				if ($this->isIpInRange($ip, $cdnIpRanges)) {
					continue;
				}
				return $ip;
			}
		}
		return false;
	}

	/**
	 * 处理 'CF-Connecting-IP' 头部的 IP
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getCfConnectingIp($cdnIpRanges) {
		return !empty($_SERVER['HTTP_CF_CONNECTING_IP']) && $this->isIpInRange($_SERVER['REMOTE_ADDR'], $cdnIpRanges)
			? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * 在评论元数据中保存真实 IP 和地理位置信息
	 *
	 * @param int $commentdata
	 */
	public function save_real_ip_on_comment($commentdata) {
		// 检查依赖库是否可用
		if (!class_exists('\IPLib\Factory')) {
			error_log(__('IPLib not found. Real IP validation skipped.', 'cfcdn-comment-ip-fix'));
			return $commentdata;
		}

		$cdnIpRanges = $this->get_cdn_ip_ranges();

		$set = 'X-Forwarded-For';
		switch ($set) {
			case 'Forwarded':
				$fix_ip = $this->getForwardedIp($cdnIpRanges);
				break;
			case 'X-Forwarded-For':
				$fix_ip = $this->getXForwardedForIp($cdnIpRanges);
				break;
			case 'CF-Connecting-IP':
			default:
				$fix_ip = $this->getCfConnectingIp($cdnIpRanges);
				break;
		}

		if ($fix_ip) {
			// 将访客真实 IP 存储为评论的元数据
			$commentdata['comment_author_IP'] = $fix_ip;
		}
		return $commentdata;
	}

}

// 初始化插件
new Corrected_Commenter_IP_CfCDN();
