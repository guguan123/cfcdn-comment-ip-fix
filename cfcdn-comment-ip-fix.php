<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Corrected commenter IP for Cloudflare CDN
 * Plugin URI:        https://github.com/guguan123/cfcdn-comment-ip-fix
 * Description:       修复评论者的IP信息，适用于使用 Cloudflare CDN 的网站。（🚨本插件不属于 Cloudflare 官方！）
 * Version:           0.1.1
 * Author:            GuGuan123
 * Author URI:        https://github.com/guguan123
 * License:           MIT
 * License URI:       https://choosealicense.com/licenses/mit/
 * Text Domain:       cfcdn-comment-ip-fix
 * Requires at least: 6.0
 * Tested up to:      6.7
 * PHP Version:       8.2
 * Requires PHP:      7.0
 * Changelog:         https://github.com/guguan123/cfcdn-comment-ip-fix/releases
 * Support:           https://github.com/guguan123/cfcdn-comment-ip-fix/issues
 */

if (!defined('ABSPATH')) {
	exit; // 防止直接访问
}

ini_set("display_errors", "On");//打开错误提示
ini_set("error_reporting", E_ALL);//显示所有错误

class Corrected_Commenter_IP_CfCDN {

	// 定义缓存的唯一选项键
	const CDN_IP_CACHE_KEY = 'cfcdnipfix_cdn_ip_cache';

	public function __construct() {
		// 加载第三方库文件
		if (file_exists(__DIR__ . '/vendor/autoload.php')) {
			require_once __DIR__ . '/vendor/autoload.php';
		} else {
			error_log(__('IPLib not found. Plugin functionality disabled.', 'cfcdn-comment-ip-fix'));
			return; // 停止构造
		}

		// 插件激活时注册定时任务
		register_activation_hook(__FILE__, ['Corrected_Commenter_IP_CfCDN', 'cfcdnipfix_schedule_cron_job']);
		// 插件停用时清理定时任务
		register_deactivation_hook(__FILE__, ['Corrected_Commenter_IP_CfCDN', 'cfcdnipfix_clear_cron_job']);
		// 卸载插件后清理缓存数据
		register_uninstall_hook(__FILE__, ['Corrected_Commenter_IP_CfCDN', 'cfcdnipfix_uninstall']);
		// 绑定评论发布时保存真实 IP
		add_action('preprocess_comment', [$this, 'cfcdnipfix_save_real_ip_on_comment']);
		// 定义定时任务的 Hook
		add_action('cfcdnipfix_update_cloudflare_ips', 'cfcdnipfix_fetch_and_save_cloudflare_ips');
		// 添加管理页面
		add_action('admin_menu', [$this, 'cfcdnipfix_admin_menu']);
		// 处理表单提交
		add_action('admin_init', [$this, 'cfcdnipfix_handle_form_submission']);
		// 添加 AJAX 动作
		add_action('wp_ajax_cfcdnipfix_update_cloudflare_ips', [$this, 'cfcdnipfix_handle_ajax_update']);
	}

	/**
	 * 计划每日执行的cron任务来更新Cloudflare IP。
	 */
	public static function cfcdnipfix_schedule_cron_job() {
		// 检查cron任务是否已经计划
		if (!wp_next_scheduled('cfcdnipfix_update_cloudflare_ips')) {
			// 计划cron任务每日执行
			wp_schedule_event(time(), 'daily', 'cfcdnipfix_update_cloudflare_ips');
		}
	}

	/**
	 * 清除计划的cron任务来更新Cloudflare IP。
	 */
	public static function cfcdnipfix_clear_cron_job() {
		// 获取下一次计划的cron任务的时间戳
		$timestamp = wp_next_scheduled('cfcdnipfix_update_cloudflare_ips');
		// 如果cron任务已经计划，取消计划
		if ($timestamp) {
			wp_unschedule_event($timestamp, 'cfcdnipfix_update_cloudflare_ips');
		}
	}

	/**
	 * 卸载插件，删除缓存的Cloudflare IP和清除cron任务。
	 */
	public static function cfcdnipfix_uninstall() {
		// 删除缓存的Cloudflare IP
		delete_option(self::CDN_IP_CACHE_KEY);
		// 清除计划的cron任务
		self::cfcdnipfix_clear_cron_job();
	}



	public function cfcdnipfix_admin_menu() {
		// 添加顶级菜单页面
		add_submenu_page(
			'options-general.php', // “设置”菜单的 slug
			'Cloudflare 修正评论者 IP',
			'Cloudflare IP fix',
			'manage_options',
			'corrected-commenter-ip-cloudflare',
			[$this, 'cfcdnipfix_admin_page']
		);
	}

	// 渲染管理页面
	public function cfcdnipfix_admin_page() {
		wp_enqueue_script('jquery');
		require_once 'settings_page.php';
	}

	// 处理更新按钮的表单提交
	public function cfcdnipfix_handle_form_submission() {
		if (isset($_POST['cfcdnipfix_update_cloudflare_ips']) && 
			current_user_can('manage_options') && 
			check_admin_referer('cfcdnipfix_settings-options')) {

			$cf_ip_data = $this->cfcdnipfix_fetch_and_save_cloudflare_ips();
			if ($cf_ip_data['status'] === 'success')  {
				add_settings_error(
					'cfcdnipfix_messages',
					'cache_updated',
					'Cloudflare IP 缓存已成功更新！',
					'success'
				);
			} else {
				add_settings_error(
					'cfcdnipfix_messages',
					'cache_updated',
					'Cloudflare IP 更新失败：' . esc_html($cf_ip_data['message']),
					'error'
				);
			}
		}

		if (isset($_POST['cfcdnipfix_additional_cdn_ips']) && 
			current_user_can('manage_options') && 
			check_admin_referer('cfcdnipfix_settings-options')) {

			if (!empty($_POST['cfcdnipfix_additional_cdn_ips'])) {
				$potential_ips = explode(',', sanitize_text_field(wp_unslash($_POST['cfcdnipfix_additional_cdn_ips'])));

				// 验证每个IP地址或CIDR
				foreach ($potential_ips as $potential_ip) {
					$potential_ip = trim($potential_ip); // 移除多余空格
					
					// 检查是否是有效的IPv4/IPv6地址或CIDR
					if ($this->is_valid_ip_or_cidr($potential_ip)) {
						$additional_ips[] = $potential_ip;
					} else {
						add_settings_error(
							'cfcdnipfix_messages',
							'invalid_ip_format',
							'输入包含无效的IP地址或CIDR格式，已被忽略：' . esc_html($potential_ip),
							'warning'
						);
					}
				}
			}

			// 获取现有缓存数据
			$cached_data = json_decode(get_option(self::CDN_IP_CACHE_KEY), true);
			if (isset($cached_data['cloudflare']) && !empty($_POST['cfcdnipfix_additional_cdn_ips'])) {
				$new_cached_data['other_cidrs'] = $additional_ips;
			} elseif (!empty($_POST['cfcdnipfix_additional_cdn_ips'])) {
				$new_cached_data = array('other_cidrs' => $additional_ips);
			} elseif (!empty($cached_data['cloudflare'])) {
				$new_cached_data = array('cloudflare' => $cached_data['cloudflare']);
			}
			if (empty($new_cached_data)) {
				delete_option(self::CDN_IP_CACHE_KEY);
			} else {
				update_option(self::CDN_IP_CACHE_KEY, json_encode($cached_data));
			}
			add_settings_error(
				'cfcdnipfix_messages',
				'cache_updated',
				'已成功更新额外 CDN IP！',
				'success'
			);
		}
	}

	// 处理 AJAX 请求
	public function cfcdnipfix_handle_ajax_update() {
		// 验证权限和 nonce
		if (!current_user_can('manage_options') ||
			!check_ajax_referer('cfcdnipfix_settings-options', 'nonce', false)) {
			wp_send_json_error(['message' => '权限不足或请求无效。']);
		}

		// 执行更新操作
		$cf_ip_data = $this->cfcdnipfix_fetch_and_save_cloudflare_ips();

		if ($cf_ip_data['status'] === 'success')  {
			// 返回成功响应
			wp_send_json_success([
				'message' => 'Cloudflare IP 缓存已成功更新！',
				'cache_data' => print_r(json_decode(get_option(self::CDN_IP_CACHE_KEY), true), true) // 返回更新后的缓存数据
			]);
		} else {
			// 返回错误响应
			wp_send_json_error(['message' => 'Cloudflare IP 缓存更新失败：' . esc_html($cf_ip_data['message'])]);
		}
	}



	// 添加验证函数到类中
	private function is_valid_ip_or_cidr($input) {
		// 检查是否是CIDR格式
		if (strpos($input, '/') !== false) {
			$parts = explode('/', $input);
			if (count($parts) !== 2) {
				return false;
			}
			
			$ip = $parts[0];
			$netmask = $parts[1];
			
			// 验证netmask是数字且在合理范围内
			if (!is_numeric($netmask) || $netmask < 0) {
				return false;
			}
			
			// 检查IPv4 CIDR
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				return $netmask <= 32;
			}
			
			// 检查IPv6 CIDR
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				return $netmask <= 128;
			}
			
			return false;
		}
		
		// 如果不是CIDR，检查是否是单纯的IP地址
		return filter_var($input, FILTER_VALIDATE_IP) !== false;
	}

	// 获取并缓存 Cloudflare IP 范围的函数
	public function cfcdnipfix_fetch_and_save_cloudflare_ips() {
		// Cloudflare API 地址
		$api_url = 'https://api.cloudflare.com/client/v4/ips';

		// 从 API 获取新数据
		$response = wp_remote_get($api_url);
		if (is_wp_error($response)) {
			return ['status' => 'error', 'message' => '获取 Cloudflare IP 数据失败：' . $response->get_error_message()];
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
			$get_ips_data = $this->cfcdnipfix_fetch_and_save_cloudflare_ips();
			$ips_json_data = $get_ips_data['data'];
		}
		// 解码缓存数据
		$decoded_data = json_decode($ips_json_data, true);

		// 合并地址段
		$cdn_cidrs = array_merge(
			isset($decoded_data['cloudflare']['ipv4_cidrs']) ? $decoded_data['cloudflare']['ipv4_cidrs'] : [],
			isset($decoded_data['cloudflare']['ipv6_cidrs']) ? $decoded_data['cloudflare']['ipv6_cidrs'] : [],
			isset($decoded_data['other_cidrs']) ? $decoded_data['other_cidrs'] : []
		);

		// 解析为 Range 对象
		return array_map(function ($cidr) {
			return \IPLib\Factory::parseRangeString($cidr);
		}, $cdn_cidrs);
	}

	/**
	 * 处理 'Forwarded' 头部的 IP（未完善）
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getForwardedIp($cdnIpRanges) {
		if (empty($_SERVER['HTTP_FORWARDED']) || empty($_SERVER['REMOTE_ADDR'])) {
			return false;
		} else {
			$remote_ip_address = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$forwarded_data = sanitize_text_field($_SERVER['HTTP_FORWARDED']);
		}

		if ($this->is_valid_ip_or_cidr($remote_ip_address) != false && $this->isIpInRange($remote_ip_address, $cdnIpRanges)) {
			foreach (array_map('trim', explode(',', $forwarded_data)) as $part) {
				if (stripos($part, 'for=') !== false) {
					$forwarded_ip = trim(str_ireplace('for=', '', $part));
					if ($this->is_valid_ip_or_cidr($forwarded_ip) != false) return $forwarded_ip;
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
		if (empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return false;
		} else {
			$x_forwarded_for_ips = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
		}
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$remote_ip_address = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			if ($this->is_valid_ip_or_cidr($remote_ip_address) != false) $ips[] = $remote_ip_address;
		}

		$ips = array_merge($ips ?? [], array_map('trim', explode(',', $x_forwarded_for_ips)));

		foreach ($ips as $ip) {
			if ($this->isIpInRange($ip, $cdnIpRanges)) {
				continue;
			}
			if ($this->is_valid_ip_or_cidr($ip) != false) return $ip;
		}
		return false;
	}

	/**
	 * 处理 'CF-Connecting-IP' 头部的 IP
	 * @param array $cdnIpRanges
	 * @return string
	 */
	protected function getCfConnectingIp($cdnIpRanges) {
		if (isset($_SERVER['REMOTE_ADDR']) && isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			$remote_ip_address = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$cf_connecting_ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
			return $this->is_valid_ip_or_cidr($remote_ip_address) != false && $this->is_valid_ip_or_cidr($cf_connecting_ip) != false && $this->isIpInRange($remote_ip_address, $cdnIpRanges)
				? $cf_connecting_ip : false;
		}
		return false;
	}

	/**
	 * 在评论元数据中保存真实 IP 和地理位置信息
	 *
	 * @param int $commentdata
	 */
	public function cfcdnipfix_save_real_ip_on_comment($commentdata) {
		// 检查依赖库是否可用
		if (!class_exists('\IPLib\Factory')) {
			error_log(__('IPLib not found. Real IP validation skipped.', 'cfcdn-comment-ip-fix'));
			return $commentdata;
		}

		$cdnIpRanges = $this->get_cdn_ip_ranges();
		if (empty($cdnIpRanges)) {
			error_log(__('No CDN IP ranges found. Real IP validation skipped.', 'cfcdn-comment-ip-fix'));
			return $commentdata;
		}

		$set = 'X-Forwarded-For';
		switch ($set) {
			case 'Forwarded':
				// $fix_ip = $this->getForwardedIp($cdnIpRanges);
				break;
			case 'X-Forwarded-For':
				$fix_ip = $this->getXForwardedForIp($cdnIpRanges);
				break;
			case 'CF-Connecting-IP':
			default:
				$fix_ip = $this->getCfConnectingIp($cdnIpRanges);
				break;
		}

		if (isset($fix_ip) && $fix_ip) {
			// 将访客真实 IP 存储为评论的元数据
			$commentdata['comment_author_IP'] = $fix_ip;
		}
		return $commentdata;
	}

}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	return;
}

// 初始化插件
new Corrected_Commenter_IP_CfCDN();
