# CfCDN-Comment-IP-Fix

此 WordPress 插件用于修正评论者的 IP 地址，专为使用 Cloudflare CDN 的网站设计。  
它能够处理来自 Cloudflare 的代理 IP，确保获取真实的评论者 IP 信息。

## Usage

1. 确保你的 WordPress 网站通过 Cloudflare 代理进行访问。
2. 插件会自动修正评论中的 IP 地址，使其显示真实的评论者 IP。

## Frequently Asked Questions

**Q1: 为什么我需要这个插件？**
A1: 当您的网站使用 Cloudflare CDN 时，所有的访客 IP 地址都会被 Cloudflare 的代理 IP 地址所替代。此插件会修正评论中的 IP 地址，确保您能够获取到真实的评论者 IP。

**Q2: 插件支持 IPv6 吗？**
A2: 是的，本插件完全支持 IPv6 地址。

**Q3: 插件会自动更新 Cloudflare 的 IP 列表吗？**
A3: 是的，插件每天会自动更新 Cloudflare 的 IP 列表。

**Q4: 插件会影响我的网站性能吗？**
A4: 此插件在低访问量的情况下不会对网站性能产生显著影响。

**Q5: 插件会有卸载残留吗？**
A5: 插件卸载时会彻底清理所有相关数据，没有任何残留。

**Q6: 此插件支持其他 CDN 吗？**
A6: 仅支持 Cloudflare CDN

## Screenshots

![插件设置页面截图](https://s1.imagehub.cc/images/2025/03/04/cf8c3fb5738de0382307a223f211256b.png)

## Stable tag

- **Stable tag**: 0.1.1

## Upgrade Notice

- **v0.1.1**：初始发布。

## Compatibility

- **Tested up to**: WordPress 6.7.2
- **Requires at least**: WordPress 6.0
- **Requires PHP**: 7.4

## Install

1. 下载ZIP格式的代码文件并安装插件。
2. 启用插件。
3. 插件会自动开始更新 Cloudflare 的 IP 地址列表。

## Libraries Used

- [IPLib](https://github.com/mlocati/ip-lib)：用于处理 IP 地址的解析与匹配。

## Support

如果您遇到任何问题或有建议，请随时联系插件开发者或者通过 GitHub 提交 issue。

## Contributors

- [GuGuan123](https://guguan.us.kg)

## License

本插件使用 MIT 许可，详细内容请参见 [LICENSE](LICENSE) 文件。

## Donate?

~~如果您喜欢这个插件，您可以通过 [支付宝转账](https://s1.imagehub.cc/images/2025/03/04/33128a3f3455b55b5c7321ee4c05527c.jpg) 奖励我~~
