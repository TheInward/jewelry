# Seleneith WordPress 商城

正式域名：https://seleneith.com/ 。主题源码位于 `theme/`，线上安装目录为 `wp-content/themes/seleneith/`。

## 本地运行

运行 `npm install`，再运行 `npm start`。本地 Playground 使用临时数据库；正式商品、订单和用户数据由服务器上的 WordPress/WooCommerce 保存。不要用本地初始化脚本覆盖线上数据。

## 线上更新

见 `deploy/SITEGROUND.md`。更新前备份数据库和主题；只部署明确变更的代码，保留现有数据库凭据、盐值及订单数据。

商品和页面图片托管于本站 `wp-content/uploads/seleneith-media/`，文件清单和 SHA-256 位于 `reference/media-manifest.json`。图片二进制不进入 Git，服务器私有备份保留完整资源。初始化环境可从正式站点按清单取得资源。

当前已配置多语言、多币种、顾客账户、收藏、商品规格、购物车、分区配送及售后政策。邮件已通过实收验证。实际支付尚未验收，完整翻译、正式库存与品牌自有素材仍需完善；开发提示和 noindex 保留。

## 验证

- `node deploy/verify-audit-live.mjs`：接口边界、搜索与分页；只使用临时访客购物车，不创建订单或发信
- `node deploy/verify-storefront-state.mjs`：模拟收藏失败和并发、弹层延迟响应，不写真实账户
- `node deploy/verify-domain.mjs`
- `node deploy/verify-brand.mjs`
- `npm run verify:mobile`
- `node deploy/verify-shipping-checkout.mjs`
- `node deploy/verify-catalog-localization.mjs`
- `VERIFY_WIDTHS=1440 node deploy/verify-editorials.mjs`

验证脚本默认正式站点，可通过 `SELENEITH_PREVIEW_URL` 指定测试站。注册/邮件类测试会写入或发送数据，执行前确认测试目标与清理方法。
