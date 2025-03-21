<?php
if (!defined('ABSPATH')) {
	exit; // 防止直接访问
}
// 页面内容
$ip_cache = json_decode(get_option(self::CDN_IP_CACHE_KEY), true);
if (!class_exists('\IPLib\Factory')) $now_ip = $this->getXForwardedForIp($this->get_cdn_ip_ranges());
?>

<div class="wrap">
	<h1>修正评论者 IP</h1>
	<p>这是修正评论者 IP 插件的管理页面。</p>
	<p>识别到的 IP：<?php echo esc_html($now_ip ?: 'N/A'); ?></p>
	<hr />
	<form method="post" id="cfcdnipfix-update-cloudflare-ips-form">
		<?php settings_fields('cfcdnipfix_settings'); // 添加 nonce 等字段 ?>
		<?php submit_button('更新 Cloudflare IP 缓存', 'secondary', 'cfcdnipfix_update_cloudflare_ips', false); ?>
	</form>
	<form method="post" id="cfcdnipfix-update-other-ips-form">
		<?php settings_fields('cfcdnipfix_settings'); // 添加 nonce 等字段 ?>
		<h2>额外 CDN IP</h2>
		<p>请输入额外的 CDN IP 地址（多个地址请用逗号分隔）：</p>
		<input type="text" name="cfcdnipfix_additional_cdn_ips" value="<?php if (isset($ip_cache['other_cidrs'])) echo esc_attr(implode(',', $ip_cache['other_cidrs'])); ?>" class="regular-text" />

		<?php submit_button('更新额外 CDN IPs', 'secondary', 'cfcdnipfix_update_additional_cdn_ips', false); ?>
	</form>
	<hr />
	<h2>当前存储的 IP 信息</h2>
	<pre>
		<?php echo esc_html(print_r($ip_cache, true)); ?>
	</pre>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#cfcdnipfix_update_cloudflare_ips').on('click', function(e) {
		e.preventDefault(); // 阻止默认表单提交

		var $button = $(this);
		$button.prop('disabled', true).val('正在更新...'); // 禁用按钮并显示加载状态

		// 获取表单数据，包括 nonce
		var data = {
			action: 'cfcdnipfix_update_cloudflare_ips', // AJAX 操作名称
			nonce: $('#_wpnonce').val() // 从 settings_fields 生成的 nonce
		};

		$.post(ajaxurl, data, function(response) {
			if (response.success) {
				// 更新成功
				$('#setting-error-cfcdnipfix_messages').remove(); // 移除旧消息
				$('.wrap h1').after('<div id="setting-error-cfcdnipfix_messages" class="notice notice-success is-dismissible"><p><strong>' + response.data.message + '</strong></p></div>');
				// 刷新缓存数据显示
				$('.wrap pre').text(response.data.cache_data);
			} else {
				// 更新失败
				$('.wrap h1').after('<div id="setting-error-cfcdnipfix_messages" class="notice notice-error is-dismissible"><p><strong>' + response.data.message + '</strong></p></div>');
			}
		}).fail(function() {
			alert('请求失败，请稍后重试。');
		}).always(function() {
			$button.prop('disabled', false).val('更新 Cloudflare IP 缓存'); // 恢复按钮
		});
	});
});
</script>
