=== Corrected commenter IP for Cloudflare CDN ===
Contributors: GuGuan123
Donate link: https://qr.alipay.com/fkx17591v9cegbc196ly4b3
Tags: Cloudflare, Security, CDN, comments, real ip
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 0.1.3
Requires PHP: 7.4
License: MIT
License URI: https://opensource.org/licenses/MIT

一个用于修正使用 Cloudflare CDN 的 WordPress 网站的评论者 IP 的插件。

== Description ==

此 WordPress 插件旨在纠正使用 Cloudflare CDN 的网站上，评论者 IP 地址显示不准确的问题。通过处理来自 Cloudflare 的代理 IP，本插件确保能够获取并记录真实的评论者 IP 信息。这对于评论管理、垃圾评论识别和网站安全分析至关重要。

请注意：本插件由独立开发者提供，并非 Cloudflare 官方产品。

== Installation ==

1.  上传 `cfcdn-comment-ip-fix` 文件夹到 `/wp-content/plugins/` 目录。
2.  通过 WordPress 后台的“插件”菜单激活插件。

== Usage ==

1.  确保您的 WordPress 网站已正确配置并通过 Cloudflare CDN 进行代理访问。
2.  激活本插件后，它将自动修正以下功能中的 IP 地址，使其显示真实的访问者 IP：
    * **用户评论：** 评论提交时记录的 IP 地址。
    * **找回密码邮件：** 发送给用户的找回密码邮件中包含的 IP 地址。

本插件无需额外配置，激活即可生效。

== Frequently Asked Questions ==

= 为什么我需要这个插件？ =
当您的网站使用 Cloudflare CDN 时，所有访客的真实 IP 地址可能会被 Cloudflare 的代理 IP 地址所替代。这会导致您在评论管理、用户行为分析或安全审计时无法获取到准确的访客来源信息。本插件旨在修正这些 IP 地址，确保您能够获取到真实的 IP 信息，从而更好地管理您的网站。

= 插件支持 IPv6 吗？ =
是的，本插件支持 IPv6 地址的获取和修正。
但是，对于**找回密码邮件**中的 IP 修正，目前主要针对 IPv4 地址进行优化。如果您的网站主要使用 IPv6 回源，邮件中的 IP 修正功能可能无法完全覆盖 IPv6 地址。我正在持续改进此功能，以提供更完善的 IPv6 支持。

= 插件会自动更新 Cloudflare 的 IP 列表吗？ =
是的，插件每天会自动更新 Cloudflare 的 IP 列表，以确保其始终是最新且准确的。

= 插件会影响我的网站性能吗？ =
它主要在用户评论提交和找回密码邮件发送等非高频场景下进行 IP 修正，因此在正常访问量下不会对网站性能产生显著影响。

= 此插件支持其他 CDN 吗？ =
目前，本插件**仅支持 Cloudflare CDN**。但是可以手动添加其它CDN的IP地址，它通过 X-Forwarded-For 头信息来获取真实 IP。

== External Services ==

本插件连接到 Cloudflare API 以获取最新的 Cloudflare CDN IP 地址列表。这是确保插件能够准确处理 IP 相关功能（例如在使用 Cloudflare CDN 时修正评论 IP 检测）所必需的。

=== What the service is and what it is used for ===
本插件使用 Cloudflare API（具体是 `https://api.cloudflare.com/client/v4/ips` 端点）来获取当前 Cloudflare CDN IP 地址的列表。

=== What data is sent and when ===
插件**不会发送任何用户特定数据**到 Cloudflare API，它仅向 API 端点发出一个简单的 GET 请求来检索 IP 列表。此请求发生在插件需要刷新 IP 列表时

=== Service provider ===
获取 Cloudflare IP 列表的服务由 Cloudflare, Inc. 提供。

=== Links to terms of service and privacy policy ===
  - 服务条款: https://www.cloudflare.com/terms/
  - 隐私政策: https://www.cloudflare.com/privacypolicy/

此信息（Cloudflare IP 列表）的获取是为了确保插件能与 Cloudflare 的 CDN 服务正确协作。在此过程中，不会传输任何个人或敏感的用户数据。

== Libraries Used ==

-   **[IPLib](https://github.com/mlocati/ip-lib)**: 用于处理 IP 地址的解析与匹配。

== Changelog ==

= 0.1.3 =
* 改进：对 `readme.txt` 文件进行了全面的格式和内容优化，以符合 WordPress.org 标准。
* 修复：当X-Forwarded-For请求头有多个IP时正确识别

= 0.1.2 =
* 新增：邮件中找回密码的 IP 地址修正。
* 改进：IPv6 支持。

= 0.1.1 =
* 初始版本。
