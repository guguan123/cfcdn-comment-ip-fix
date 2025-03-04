<?php
// 页面内容
?>
<div class="wrap">
    <h1>Cloudflare IP 修正评论者</h1>
    <p>这是 Cloudflare IP 修正评论者插件的管理页面。</p>
    <pre>
        <?php print_r(get_option(self::CLOUDFLARE_IP_CACHE_KEY)); ?>
    </pre>
    <?php settings_errors('cloudflare_ip_messages'); // 显示提示消息 ?>
    <form method="post" id="update-cloudflare-ips-form">
        <?php settings_fields('cloudflare_ip_settings'); // 添加 nonce 等字段 ?>
        <?php submit_button('更新 Cloudflare IP 缓存', 'secondary', 'update_cloudflare_ips', false); ?>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#update_cloudflare_ips').on('click', function(e) {
        e.preventDefault(); // 阻止默认表单提交

        var $button = $(this);
        $button.prop('disabled', true).val('正在更新...'); // 禁用按钮并显示加载状态

        // 获取表单数据，包括 nonce
        var data = {
            action: 'update_cloudflare_ips', // AJAX 操作名称
            nonce: $('#_wpnonce').val() // 从 settings_fields 生成的 nonce
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                // 更新成功
                $('#setting-error-cloudflare_ip_messages').remove(); // 移除旧消息
                $('.wrap h1').after('<div id="setting-error-cloudflare_ip_messages" class="notice notice-success is-dismissible"><p><strong>' + response.data.message + '</strong></p></div>');
                // 可选：刷新缓存数据显示
                $('pre').text(response.data.cache_data);
            } else {
                // 更新失败
                $('.wrap h1').after('<div id="setting-error-cloudflare_ip_messages" class="notice notice-error is-dismissible"><p><strong>' + response.data.message + '</strong></p></div>');
            }
        }).fail(function() {
            alert('请求失败，请稍后重试。');
        }).always(function() {
            $button.prop('disabled', false).val('更新 Cloudflare IP 缓存'); // 恢复按钮
        });
    });
});
</script>
