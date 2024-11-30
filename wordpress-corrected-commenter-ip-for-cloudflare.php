<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Corrected commenter IP for Cloudflare
 * Plugin URI:        https://github.com/guguan123/wordpress-corrected-commenter-ip-for-cloudflare
 * Description:       修复评论者的IP信息，适用于使用Cloudflare CDN的网站。
 * Version:           1.0.0
 * Author:            GuGuan123
 * Author URI:        https://github.com/guguan123/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wordpress-corrected-commenter-ip-for-cloudflare
 */

// Include original plugin license information if available
/**
 * Original Plugin Name: Real IP and Geo for Cloudflare
 * Original Plugin URI: http://wordpress.org/plugins/cloudflare-real-ip-and-geo/
 * Original Description: Saves and displays visitors' real IP and location, instead of Cloudflare's
 * Original Version: 1.0
 * Original Author: RaMMicHaeL
 * Original Author URI: http://rammichael.com/
 */

if (!defined('ABSPATH')) {
	exit; // 防止直接访问
}

require_once __DIR__ . '/vendor/autoload.php';

class Corrected_Commenter_IP_Cloudflare {

	public function __construct() {
		// 绑定评论发布时保存真实 IP
		add_action('comment_post', [$this, 'save_real_ip_on_comment']);
		// 绑定后台显示评论真实 IP
		add_filter('the_comments', [$this, 'display_real_ip_in_admin']);
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
		$ip_address = \IPLib\Factory::addressFromString($cf_connecting_ip);
		foreach ($this->get_cloudflare_ip_ranges() as $ip_range) {
			if ($ip_range->contains($ip_address)) {
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
	 * @param int $comment_id 评论 ID
	 */
	public function save_real_ip_on_comment($comment_id) {
		// 检查依赖库是否可用
		if (!class_exists('\IPLib\Factory')) {
			wp_die(__('Missing IPLib dependency. Please install the required libraries.', 'wordpress-corrected-commenter-ip-for-cloudflare'));
		}

		if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP) && $this->is_request_from_cloudflare($_SERVER['REMOTE_ADDR'])) {
			// 将访客真实 IP 存储为评论的元数据
			update_comment_meta($comment_id, 'cf_connecting_ip', $_SERVER['HTTP_CF_CONNECTING_IP']);
			if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
				// 将访客国家代码存储为评论的元数据
				update_comment_meta($comment_id, 'cf_ipcountry', $_SERVER['HTTP_CF_IPCOUNTRY']);
			}
		}
	}



	/**
	 * 在 WordPress 后台评论管理页面中显示访客的真实 IP 和国家信息
	 *
	 * @param array $objects 包含评论信息的对象数组
	 * @return array 修改后的评论对象数组
	 */
	public function display_real_ip_in_admin($objects) {
		// 仅在 WordPress 后台的评论管理页面中执行该功能
		if (is_admin()) {
			// 获取当前屏幕对象
			$current_screen = function_exists('get_current_screen') ? get_current_screen() : null;
			if ($current_screen && $current_screen->id === 'edit-comments') {
				// 遍历所有评论对象
				foreach ($objects as $object) {
					// 获取评论的真实 IP 信息（如果存在）
					$cf_connecting_ip = get_comment_meta($object->comment_ID, 'cf_connecting_ip', true);
					if ($cf_connecting_ip) {
						// 获取评论的国家代码（如果存在），如果不存在则设置为 "N/A"
						$cf_ipcountry = get_comment_meta($object->comment_ID, 'cf_ipcountry', true) ?: 'N/A';
						// 将真实 IP 和国家信息整合到评论作者 IP 字段
						// 格式为：[国家] 真实IP地址 (cf:代理IP地址)
						$object->comment_author_IP = "[$cf_ipcountry] $cf_connecting_ip (cf:{$object->comment_author_IP})";
					}
				}
			}
		}

		// 返回修改后的评论对象数组
		return $objects;
	}
}

// 初始化插件
new Corrected_Commenter_IP_Cloudflare();
