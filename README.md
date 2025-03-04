# WordPress-CfCDN-Comment-IP-Fix

**WordPress 插件** 用于修正评论者的 IP 地址，专为使用 Cloudflare CDN 的网站设计。  
它能够处理来自 Cloudflare 的代理 IP，确保获取真实的评论者 IP 信息，支持 IPv6。

## 特性

- **修正评论者 IP 地址**：确保正确记录通过 Cloudflare 代理的评论者真实 IP 地址。
- **支持 IPv6**：完整支持 IPv6 地址。
- **自动更新 Cloudflare IP 列表**：插件每天会自动更新 Cloudflare 的 IP 列表。
- **完全干净的卸载**：插件卸载时会彻底清理所有相关数据，没有任何残留。

## 安装

1. 下载并安装插件。
2. 启用插件。
3. 插件会自动开始更新 Cloudflare 的 IP 地址列表。

## 使用方法

1. 确保你的 WordPress 网站通过 Cloudflare 代理进行访问。
2. 插件会自动修正评论中的 IP 地址，使其显示真实的评论者 IP。

## 更新日志

- **v0.1.1**：初始发布。

## 兼容性

- **WordPress 版本**：适用于 WordPress 6.0 及以上版本。
- **PHP 版本**：适用于 PHP 8.0 及以上版本。

## 使用的库

- [IPLib](https://github.com/mlocati/ip-lib)：用于处理 IP 地址的解析与匹配。

## 为什么我需要这个插件？

当您的网站使用 Cloudflare 时，所有的访客 IP 地址都会被 Cloudflare 的代理 IP 地址所替代。此插件会修正评论中的 IP 地址，确保您能够获取到真实的评论者 IP。

## 支持

如果您遇到任何问题或有建议，请随时联系插件开发者或者通过 GitHub 提交 issue。

## 许可

本插件使用 MIT 许可，详细内容请参见 [LICENSE](LICENSE) 文件。
