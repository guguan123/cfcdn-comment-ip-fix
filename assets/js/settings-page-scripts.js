// Version: 0.1.1

jQuery(document).ready(function($) {
	$('#cfcdnipfix_update_cloudflare_ips').on('click', function(e) {
		e.preventDefault(); // 阻止默认表单提交

		var $button = $(this);
		$button.prop('disabled', true).val('Updating...'); // 禁用按钮并显示加载状态

		// 获取表单数据，包括 nonce
		var data = {
			action: 'cfcdnipfix_update_cloudflare_ips', // AJAX 操作名称
			nonce: $('#_wpnonce').val() // 从 settings_fields 生成的 nonce
		};

		$.post(cfcdnipfix_params.ajaxurl, data, function(response) {
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