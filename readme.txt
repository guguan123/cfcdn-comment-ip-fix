=== Corrected commenter IP for Cloudflare CDN ===
Contributors: GuGuan123
Donate link: https://s1.imagehub.cc/images/2025/03/04/33128a3f3455b55b5c7321ee4c05527c.jpg
Tags: Cloudflare, IP, Security, CDN
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 0.1.1
Requires PHP: 7.4
License: MIT
License URI: https://opensource.org/licenses/MIT

一个用于修复使用 Cloudflare CDN 的 WordPress 网站的评论者 IP 的插件。

== Description ==

此 WordPress 插件更正了使用 Cloudflare CDN 的网站的评论者 IP 地址。它能够处理来自 Cloudflare 的代理 IP，确保获取真实的评论者 IP 信息（🚨本插件不属于 Cloudflare 官方！）。

== Usage ==

1. 确保你的 WordPress 网站通过 Cloudflare 代理进行访问。
2. 插件会自动修正评论中的 IP 地址，使其显示真实的评论者 IP。

== Frequently Asked Questions ==

= 为什么我需要这个插件？ =
当您的网站使用 Cloudflare CDN 时，所有的访客 IP 地址都会被 Cloudflare 的代理 IP 地址所替代。此插件会修正评论中的 IP 地址，确保您能够获取到真实的评论者 IP。

= 插件支持 IPv6 吗？ =
是的，本插件完全支持 IPv6 地址。

= 插件会自动更新 Cloudflare 的 IP 列表吗？ =
是的，插件每天会自动更新 Cloudflare 的 IP 列表。

= 插件会影响我的网站性能吗？ =
此插件在低访问量的情况下不会对网站性能产生显著影响。

= 此插件支持其他 CDN 吗？ =
仅支持 Cloudflare CDN

== External Services ==

This plugin connects to the Cloudflare API to retrieve a list of Cloudflare CDN IP addresses. This is necessary to ensure accurate handling of IP-related functionality, such as fixing comment IP detection when Cloudflare's CDN is in use.

= What the service is and what it is used for =
The plugin uses the Cloudflare API (specifically the endpoint `https://api.cloudflare.com/client/v4/ips`) to fetch the current list of Cloudflare CDN IP addresses.

= What data is sent and when =
No user-specific data is sent to the Cloudflare API. The plugin makes a simple GET request to the API endpoint to retrieve the IP list. This request occurs whenever the plugin needs to refresh or verify the IP list (e.g., during initialization or periodic updates, depending on your plugin's logic).

= Service provider =
This service is provided by Cloudflare, Inc.

= Links to terms of service and privacy policy =
  - Terms of Service: https://www.cloudflare.com/terms/
  - Privacy Policy: https://www.cloudflare.com/privacypolicy/

This information is fetched to ensure the plugin functions correctly with Cloudflare’s CDN services. No personal or sensitive user data is transmitted in this process.

== Compatibility ==

Tested up to: WordPress 6.7
Requires at least: WordPress 6.0
Requires PHP: 7.4

== Install ==

1. 下载ZIP格式的代码文件并安装插件。
2. 启用插件。
3. 插件会自动开始更新 Cloudflare 的 IP 地址列表。

== Libraries Used ==

- [IPLib](https://github.com/mlocati/ip-lib): 用于处理 IP 地址的解析与匹配。

== Support ==

如果您遇到任何问题或有建议，请随时联系插件开发者或者通过 GitHub 提交 issue。

== License ==

本插件使用 MIT License，详细内容请参见 LICENSE 文件。
