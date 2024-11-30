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
	 * @return bool true 表示请求来自 Cloudflare，false 表示不是
	 */
	private function is_request_from_cloudflare($cf_connecting_ip) {
		if (!$cf_connecting_ip) {
			return false;
		}
		foreach ($this->get_cloudflare_ips() as $ip_range) {
			if ($this->ip_in_cidr($cf_connecting_ip, $ip_range)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 检查一个 IP 是否在给定的 CIDR 范围内
	 *
	 * @param string $ip 需要检查的 IP 地址
	 * @param string $cidr CIDR 表示的地址范围（例如：192.168.0.0/24）
	 * @return bool 如果 IP 在 CIDR 范围内返回 true，否则返回 false
	 */
	private function ip_in_cidr($ip, $cidr) {
		list($subnet, $mask) = explode('/', $cidr);

		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			// 处理 IPv4
			$ip_dec = ip2long($ip);
			$subnet_dec = ip2long($subnet);
			$mask_dec = ~((1 << (32 - $mask)) - 1);
			return ($ip_dec & $mask_dec) === ($subnet_dec & $mask_dec);
		} elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			// 处理 IPv6
			$ip_bin = inet_pton($ip);
			$subnet_bin = inet_pton($subnet);
			$mask_bin = str_repeat('f', $mask / 4) . str_repeat('0', (128 - $mask) / 4);
			$mask_bin = inet_pton(pack('H*', $mask_bin));
			return ($ip_bin & $mask_bin) === ($subnet_bin & $mask_bin);
		}

		return false;
	}

	/**
	 * 获取 Cloudflare 的 IP 范围列表
	 *  (需要定期更新 IP 列表)
	 *
	 * @return array 包含 IP 范围的数组，例如 ['199.27.128.0/21', '173.245.48.0/20', ...]
	 */
	private function get_cloudflare_ips() {
		$ips = [
			// ... IPv4 范围
			'173.245.48.0/20',
			'103.21.244.0/22',
			'103.22.200.0/22',
			'103.31.4.0/22',
			'141.101.64.0/18',
			'108.162.192.0/18',
			'190.93.240.0/20',
			'188.114.96.0/20',
			'197.234.240.0/22',
			'198.41.128.0/17',
			'162.158.0.0/15',
			'104.16.0.0/13',
			'104.24.0.0/14',
			'172.64.0.0/13',
			'131.0.72.0/22',
			// ... IPv6 范围
			'2a06:98c0::/29',
			'2400:cb00::/32',
			'2606:4700::/32',
			'2803:f800::/32',
			'2405:b500::/32',
			'2405:8100::/32',
			'2c0f:f248::/32',
		];
		return $ips;
	}

	/**
	 * 在评论元数据中保存真实 IP 和地理位置信息
	 *
	 * @param int $comment_id 评论 ID
	 */
	public function save_real_ip_on_comment($comment_id) {
		if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP) && $this->is_request_from_cloudflare($_SERVER['HTTP_CF_CONNECTING_IP'])) {
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
