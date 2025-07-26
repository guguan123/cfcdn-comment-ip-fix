<?php
if (!defined('ABSPATH')) {
	exit; // 防止直接访问
}
// 页面内容
$ip_cache = json_decode(get_option(self::CDN_IP_CACHE_KEY), true);
?>

<div class="wrap">
	<h1><?php _e('Correct Commenter IP', 'cfcdn-comment-ip-fix'); ?></h1>
	<p><?php _e('This is the management page for the Commenter IP Fix plugin.', 'cfcdn-comment-ip-fix'); ?></p>
	<p><?php printf(__('Detected IP: %s', 'cfcdn-comment-ip-fix'), esc_html($this->cfcdnipfix_get_fix_ip() ?? 'N/A')); ?></p>
	<hr />

	<form method="post" id="cfcdnipfix-update-cloudflare-ips-form">
		<?php settings_fields('cfcdnipfix_settings'); // 添加 nonce 等字段 ?>
		<?php submit_button(__('Update Cloudflare IP Cache', 'cfcdn-comment-ip-fix'), 'secondary', 'cfcdnipfix_update_cloudflare_ips', false); ?>
	</form>

	<form method="post" id="cfcdnipfix-update-other-ips-form">
		<?php settings_fields('cfcdnipfix_settings'); // 添加 nonce 等字段 ?>
		<h2><?php _e('Additional CDN IPs', 'cfcdn-comment-ip-fix'); ?></h2>
		<p><?php _e('Enter additional CDN IP addresses (separate multiple addresses with commas):', 'cfcdn-comment-ip-fix'); ?></p>
		<input type="text" name="cfcdnipfix_additional_cdn_ips" value="<?php if (isset($ip_cache['other_cidrs'])) echo esc_attr(implode(',', $ip_cache['other_cidrs'])); ?>" class="regular-text" />

		<?php submit_button(__('Update Additional CDN IPs', 'cfcdn-comment-ip-fix'), 'secondary', 'cfcdnipfix_update_additional_cdn_ips', false); ?>
	</form>

	<hr />
	<h2><?php _e('Current Stored IP Information', 'cfcdn-comment-ip-fix'); ?></h2>
	<pre>
		<?php echo esc_html(print_r($ip_cache, true)); ?>
	</pre>
</div>
