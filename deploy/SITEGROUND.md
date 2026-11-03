# SiteGround 当前部署

- 正式站点：https://seleneith.com/
- SSH：ssh.seleneith.com，端口18765；专用密钥保存在本机SSH目录，用户名沿用既有连接配置。
- WordPress：/home/customer/www/seleneith.com/public_html
- 主题：wp-content/themes/seleneith/
- 商品/页面图片：wp-content/uploads/seleneith-media/，完整性清单见 reference/media-manifest.json。
- 私有迁移文件及备份：/home/customer/jewelry-migration/，不在web根目录，不加入Git。

更新前备份数据库和主题。只同步明确变更的主题文件；不要覆盖 wp-config.php、订单、用户、上传目录或重跑首次安装脚本。数据库密码通过受控输入传入，不能加入仓库或日志。

图片二进制单独部署，不放入Git；首次安装须从本站资源地址或服务器私有资源归档恢复，并核验清单。含品牌标识的图片不进入发布包。

更新后运行 wp cache flush，再通过 deploy/purge-siteground-cache.php 清理本站 SiteGround 动态缓存。仅运行前者不足以清理托管代理缓存。

部署和数据完整性验证：verify-domain.mjs、verify-brand.mjs、verify-mobile.mjs、verify-shipping-checkout.mjs、verify-editorials.mjs。指定站点可设置 SELENEITH_PREVIEW_URL。账户和邮件测试会产生数据或发送邮件，应使用经授权的测试地址并清理。

目前配送、售后与事务邮件已配置并验收；实际支付、正式库存和完整翻译仍待完善，保留开发提示和 noindex。管理员登录名为 seleneith_admin，密码保留原值，不存入Git。
