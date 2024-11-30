<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Corrected commenter IP for Cloudflare
 * Plugin URI:        https://github.com/guguan123/wordpress-corrected-commenter-ip-for-cloudflare
 * Description:       修复评论者的IP信息，适用于使用Cloudflare CDN的网站。
 * Version:           0.1.0
 * Author:            GuGuan123
 * Author URI:        https://github.com/guguan123/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wordpress-corrected-commenter-ip-for-cloudflare
 */

if (!defined('ABSPATH')) {
	exit; // 防止直接访问
}

require_once __DIR__ . '/vendor/autoload.php';

class Corrected_Commenter_IP_Cloudflare {

	public function __construct() {
		// 绑定评论发布时保存真实 IP
		add_action('preprocess_comment', [$this, 'save_real_ip_on_comment']);
	}


	/**
	 * 验证请求是否来自 Cloudflare
	 *
	 * @param string $cf_connecting_ip 需要检查的 IP 地址
	 * @return bool true 表示请求来自 Cloudflare，false 表示不是
	 */
	private function is_request_from_cloudflare($cf_connecting_ip) {
		if (!$cf_connecting_ip) {
			return false;
		}
		foreach ($this->get_cloudflare_ip_ranges() as $ip_range) {
			if ($ip_range->contains(\IPLib\Factory::addressFromString($cf_connecting_ip))) {
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
	private function get_cloudflare_ip_ranges() {
		// 读取 IP 范围列表并创建 Range 数组
		return array_map(function ($cidr) {
			return \IPLib\Factory::parseRangeString($cidr);
		}, array_merge($this->load_ip_ranges(__DIR__ . '/cdn-ips.txt')));
	}

	/**
	 * 从文件加载 CDN IP 地址列表
	 * @param string $filePath
	 * @return array
	 */
	private function load_ip_ranges($filePath) {
		if (!file_exists($filePath)) {
			return [];
		}

		$ips = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		return array_filter($ips, function ($ip) {
			return filter_var($ip, FILTER_VALIDATE_IP) || strpos($ip, '/') !== false; // 处理IP段
		});
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

		if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP) && $this->is_request_from_cloudflare($_SERVER['REMOTE_ADDR'])) {
			// 将访客真实 IP 存储为评论的元数据
			$commentdata['comment_author_IP'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		return $commentdata;
	}
}

// 初始化插件
new Corrected_Commenter_IP_Cloudflare();
