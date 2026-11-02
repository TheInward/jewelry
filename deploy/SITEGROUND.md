# 当前访问与部署地址

站点：https://seleneith.com/ 。当前SSH主机：`ssh.seleneith.com`，端口18765，既有专用密钥和用户保持有效。WordPress目录：`/home/customer/www/seleneith.com/public_html`。以下旧临时域名内容是迁移历史记录。

# SiteGround migration

Target temporary domain: `https://mrnobodys1.sg-host.com`.

SSH host: `ssh.mrnobodys1.sg-host.com`, port `18765`. The site-specific SSH key stays outside the repository. The target directory is `/home/customer/www/mrnobodys1.sg-host.com/public_html`.

The existing Alibaba site stays available during migration. Full WordPress files and the database are copied, rather than reconstructing only products from the seed JSON. Do not run the Alibaba provisioning scripts on SiteGround.

## Deployment status

- Temporary-domain site created in Singapore and WordPress published at the HTTPS root.
- Dedicated SSH public key imported with the user's confirmation.
- Site-specific database and dedicated database user created in Site Tools with user confirmation; the user is assigned only to this database.
- Full source database imported and serialized URL values changed from the Alibaba `/jewelry` URL to the target HTTPS root.
- 175 products and the existing admin account preserved. Disposable registration test account removed after validation.
- Source archives stay outside public web roots and outside Git. The Alibaba `wp-config.php` is excluded; the new host gets independent database credentials and WordPress salts.

## Migration steps after database access is ready

1. Check both archive SHA-256 hashes against the source copies before extracting.
2. Extract the application into `/home/customer/jewelry-migration/site`, outside the public directory.
3. Run `siteground-write-config.php` over SSH. Supply database name, user and password as a JSON object on stdin, never in command-line arguments. The script checks that the new database is empty before writing its config.
4. Import the SQL backup with WP-CLI; replace the old Alibaba `/jewelry` URL with the target HTTPS root URL using serialization-aware `wp search-replace`, skipping GUIDs.
5. Preserve the blank site's default placeholder outside the public directory, then copy staged files into the existing `public_html` directory and install `siteground.htaccess`. Preserve the hosting provider's existing document-root directory rather than replacing it.
6. Confirm site/checkout/account URLs, rewrite rules, cache behavior, guest cart, login and public HTTPS access. Keep backups and credentials outside the web root.

This migration does not complete the outstanding design, product-variation, translation, social-login or payment work documented in the comparison report.

## Catalog and localization update — 2026-11-02

The SiteGround site now contains 757 unique products in 53 source collections. The original 175 product IDs and admin account are retained. Header navigation and submenus use actual collection URLs and scoped queries; `/collections/` is a collection directory.

WCML 5.6.3 is active in free standalone multicurrency mode with 55 configured currencies. WordPress core and WooCommerce language packs are installed for the 13 non-English locales defined in `theme/localization.php`. Main theme strings and 719 product names have local translations. Language and market cookies are independent, HttpOnly preferences; public HTML is intentionally uncached during this development stage.

For a new installation, install/activate WooCommerce and `woocommerce-multilingual`, deploy the theme and `reference/{collections,catalog}.json` into `wp-content/reference`, run `deploy/import-collections.php` through WP-CLI, then initialize rates with `wp eval 'if (!study_refresh_exchange_rates()) WP_CLI::error("Rate initialization failed");'`. Install the core/plugin locale packs listed in `study_languages()`. Run `deploy/configure-commerce-localization.php` to use consistent server-rendered cart/checkout pages and disable the extra product currency switcher. This newer catalog import is separate from the original 175-product Playground seed.

Use `deploy/purge-siteground-cache.php` with WP-CLI to clear this site's dynamic cache through SiteGround's official local socket protocol after a release. `wp cache flush` alone does not clear the hosting proxy cache. The purge script does not alter optimizer settings or other sites.

Pre-update theme and database backups are under `/home/customer/jewelry-migration/`, outside the web root. Current feature limits and verification are recorded in `reference/功能修复验证-2026-11-02.md`. Real payments, international delivery policies and outbound email remain unconnected/unverified.

## Verification

- Official WordPress core checksums passed before publication; both archive hashes matched the source.
- Safari displayed the published catalog over HTTPS with no preview password.
- Public catalog, product, cart persistence, checkout with an item, and login page returned successfully.
- Registration, logout, incorrect-password rejection, email/password login, orders, addresses and account details passed with a disposable test customer.
- Account and checkout responses use private/no-cache headers. Empty-cart checkout redirects to the cart as expected.
- Direct access to `wp-config.php` returns HTTP 403. Development noindex is retained.
- Plain HTTP redirects to HTTPS with HTTP 301. The live theme's 12 tracked files match the Git source hashes.
- Alibaba and the existing port-80 Clipflow site remain available.
- Reset-password email delivery and real payments are not verified or connected by this migration.

## 2026-11-02 对照完善批次

已部署首页/导航/页脚、13个信息页、独立搜索、账号收藏、图库及原生变体。导入723件详情，其中630件可变商品、3554个变体；34件因参考站限流暂缺。信息页业务内容目前为预览稿，会员积分未启用。

操作前数据库和主题备份位于服务器私有目录 `jewelry-migration/parity-20261102/`。详情源快照随仓库保存为 `reference/product-details.json`。本轮实际导入参数位于同一私有目录；未来可通过 `wp eval-file deploy/import-product-details.php /absolute/path/reference/product-details.json` 明确指定数据文件。不要在修改了正式价格/库存后未经审核重跑源数据导入。

验证和未完成项见 `reference/对照完善验证-2026-11-02.md`。测试未提交订单，测试购物车和本轮测试账号已清理；SiteGround动态缓存已清除。

## 首页桌面专题修复

金色、银色、Jewelry Sets三个专题现有桌面左图+右侧2×2商品布局。主题备份位于私有目录 `jewelry-migration/editorial-20261102/before-theme.tar.gz`。专题价格和加购优先绑定同一默认变体，修正套装显示为单件最低价的问题。验证记录见 `reference/首页桌面专题修复-2026-11-02.md`。

## 2026-11-02 正式域名样式修复

SiteGround改名后WP_HOME/WP_SITEURL和HTTPS跳转仍指向旧临时域名，导致页面引用不可达的旧资源。

- 私有备份：`jewelry-migration/production-domain-20261102/` 内的wp-config.php、.htaccess和before-domain.sql。
- 将数据库中的旧HTTPS网址替换为seleneith.com（支持序列化，跳过GUID），更新WP_HOME/WP_SITEURL及HTTPS重写。
- 删除WooCommerce Multilingual的派生模板缓存选项 `wcml_currency_switcher_template_objects` 供其重新生成；未修改币种业务配置。
- 清除WP和SiteGround动态缓存。验证脚本默认域名已更新。
- 新SSH主机的RSA/ED25519指纹与原来已信任的SiteGround主机一致，已核对后登记。
- 正式域名下验证：全部CSS返回200和text/css，页面不残留旧主机名，AJAX/checkout同源，HTTP跳转至正式HTTPS；360/390/430/767px及桌面浏览器回归通过；套装加购金额一致、测试购物车清空，未提交订单或付款。

运行 `node deploy/verify-domain.mjs`、`npm run verify:mobile`、`VERIFY_WIDTHS=1440 node deploy/verify-editorials.mjs` 可复核。
