=== Corrected commenter IP for Cloudflare CDN ===
Contributors: GuGuan123
Donate link: https://qr.alipay.com/fkx17591v9cegbc196ly4b3
Tags: Cloudflare, Security, CDN, comments, real ip
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 0.1.4
Requires PHP: 7.4
License: MIT
License URI: https://opensource.org/licenses/MIT

A plugin to correct commenter IP addresses for WordPress websites using Cloudflare CDN.

== Description ==

This WordPress plugin is designed to correct the inaccurate display of commenter IP addresses on websites utilizing Cloudflare CDN. By processing proxy IPs from Cloudflare, this plugin ensures that the real commenter IP information is retrieved and recorded. This is crucial for comment moderation, spam identification, and website security analysis.

Please note: This plugin is provided by an independent developer and is not an official Cloudflare product.

== Installation ==

1.  Upload the `cfcdn-comment-ip-fix` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in your WordPress dashboard.

== Usage ==

1.  Ensure your WordPress website is correctly configured and proxied through Cloudflare CDN.
2.  Once activated, the plugin will automatically correct IP addresses in the following functionalities to display the real visitor IP:
    * **User Comments:** The IP address recorded upon comment submission.
    * **Password Reset Emails:** The IP address included in password reset emails sent to users.

This plugin requires no additional configuration; it works automatically upon activation.

== Frequently Asked Questions ==

= Why do I need this plugin? =
When your website uses Cloudflare CDN, all visitor IP addresses may be replaced by Cloudflare's proxy IP addresses. This prevents you from obtaining accurate visitor source information for comment management, user behavior analysis, or security auditing. This plugin aims to correct these IP addresses, ensuring you get the real IP information to better manage your website.

= Does the plugin support IPv6? =
Yes, this plugin supports the retrieval and correction of IPv6 addresses.
However, for IP correction in **password reset emails**, the current optimization primarily targets IPv4 addresses. If your website predominantly uses IPv6 for its origin server, the IP correction functionality in emails might not fully cover IPv6 addresses. I am continuously improving this feature to provide more comprehensive IPv6 support.

= Does the plugin automatically update Cloudflare's IP list? =
Yes, the plugin automatically updates Cloudflare's IP list daily to ensure it is always current and accurate.

= Will the plugin affect my website's performance? =
This plugin is designed to operate without significantly impacting core website performance. It primarily corrects IP addresses in low-frequency scenarios such as user comment submissions and password reset email dispatches, thus not causing a noticeable performance impact under normal traffic.

= Does this plugin support other CDNs? =
Currently, this plugin **only supports Cloudflare CDN**. However, you can manually add IP addresses from other CDNs. It retrieves the real IP via the X-Forwarded-For header.

== External Services ==

This plugin connects to the Cloudflare API to retrieve the latest list of Cloudflare CDN IP addresses. This is necessary to ensure the plugin can accurately handle IP-related functionalities, such as fixing comment IP detection when Cloudflare's CDN is in use.

=== What the service is and what it is used for ===
The plugin uses the Cloudflare API (specifically the `https://api.cloudflare.com/client/v4/ips` endpoint) to fetch the current list of Cloudflare CDN IP addresses.

=== What data is sent and when ===
The plugin **does not send any user-specific data** to the Cloudflare API. It only makes a simple GET request to the API endpoint to retrieve the IP list. This request occurs when the plugin needs to refresh the IP list.

=== Service provider ===
The service for retrieving Cloudflare IP lists is provided by Cloudflare, Inc..

=== Links to terms of service and privacy policy ===
  - Terms of Service: https://www.cloudflare.com/terms/
  - Privacy Policy: https://www.cloudflare.com/privacypolicy/

The retrieval of this information (Cloudflare IP list) ensures the plugin functions correctly with Cloudflare's CDN services. No personal or sensitive user data is transmitted during this process.

== Libraries Used ==

-   **[IPLib](https://github.com/mlocati/ip-lib)**: Used for parsing and matching IP addresses.

== Changelog ==

= 0.1.4 =
改进了国际化支持，更符合WordPress的要求

= 0.1.3 =
* Improved: Comprehensive formatting and content optimization of the `readme.txt` file to comply with WordPress.org standards.
* Fixed: Correctly identify the real IP when the X-Forwarded-For request header contains multiple IPs.

= 0.1.2 =
* Added: IP address correction for password reset emails.
* Improved: IPv6 support.

= 0.1.1 =
* Initial release.
