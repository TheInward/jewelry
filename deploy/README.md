# 阿里云开发站部署说明

这是独立 WordPress 站点的部署包，不能覆盖服务器上的既有站点或共用数据库。

## 包内文件

- `theme/`：安装到目标 WordPress 的 `wp-content/themes/seleneith/`。
- `reference/necklaces.json`：安装到目标 WordPress 的 `wp-content/reference/necklaces.json`。
- `setup/import.php`：通过目标 WordPress 的 WP-CLI `eval-file` 手动执行一次。

导入脚本会设置站点标题、美国/USD、固定链接、购物车与结账页面、175 条商品和开发用运费。仅对新建的隔离开发站执行。商品按 slug 跳过已存在记录，脚本不会更新同名商品。脚本不会配置真实支付。

## 连接服务器后确认

1. 检查系统版本、Web 服务、PHP/MySQL、Docker、磁盘、端口与已有网站；选择与现有环境兼容的安装方式。
2. 新建独立目录与数据库，使用持久化存储；不把本地 Playground 临时数据库作为服务器数据库。
3. 当前按用户要求使用独立 8080 端口，直接展示商品，不加全站访问密码，WordPress 保留禁止索引。绑定测试域名后配置 HTTPS。
4. 安装 WordPress 和 WooCommerce，复制主题与数据，激活主题，执行导入脚本。
5. 检查首页、分类页、商品页、购物车会话、结账、后台登录、文件权限及重启后的数据保留。

域名 DNS、安全组端口、HTTPS 证书与服务器现有服务需结合实际环境处理。密码和数据库凭据只在目标服务器保存，不写入仓库或部署包。

## 验证边界

这是现有首版的远端开发部署，保留未完成项：全站视觉还原、其他品类、完整变体、多语言、多币种与真实支付。部署成功不等于这些功能已完成。

## 首次部署与日常更新

`provision-server.sh` 只用于空白的 `/srv/参考站点-preview/jewelry` 安装，使用 PHP 8.3、MariaDB、独立 Nginx 8080，安装后启用顾客账户。依赖安装和 WordPress/WooCommerce 发行包需先按脚本列明的路径准备。脚本不会修改已有 80 端口站点。

已有服务器更新时，只同步 `theme/` 至 `wp-content/themes/seleneith/`，先备份主题；不要重新运行首次安装或 `bootstrap-server.php`，不要覆盖 `wp-config.php`、数据库和上传目录。`migrate-port8080.php`、`fix-config.php`、预览验证脚本是历史迁移工具，正常更新不需要执行。
