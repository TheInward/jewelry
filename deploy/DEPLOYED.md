# 阿里云预览部署记录

部署日期：2026-11-01（北京时间）

- 入口：http://39.106.108.70:8080/jewelry/ （8080 根路径会跳转至此）。
- WordPress 后台：http://39.106.108.70:8080/jewelry/wp-admin/
- 当前为 HTTP 开发预览，已按用户要求取消全站访问密码；绑定自己的域名并配置 HTTPS 后再接支付渠道。
- 前置访问账户及 WordPress 后台账户：仅保存在服务器 `/root/enroute-preview-access.json`，不入库到本地项目。
- 服务器程序目录：`/srv/enroute-preview/jewelry`。
- 数据库：`enroute_preview`，独立数据库用户；MariaDB 只监听 `127.0.0.1:3306`。
- PHP：8.3.36，独立 `enroute-php-fpm.service`，监听 `127.0.0.1:9073`。
- Nginx 后端：只监听 `127.0.0.1:9401`；公网入口独立监听 8080。已移除端口 80 的预览路由，80 归原有 Clipflow 使用。
- WordPress：7.1.4；WooCommerce：11.2.1；175 条商品。
- 新增软件源：`/etc/yum.repos.d/enroute-remi.repo`，阿里云 Remi 镜像，保留 GPG 校验。
- 原 Nginx 配置备份：`/srv/enroute-preview/clipflow.before-preview.conf`。
- 初始数据库备份：`/srv/enroute-preview/backups/initial-install.sql.gz`，仅 root 可读。
- 定时任务：`/etc/cron.d/enroute-preview`，每 5 分钟运行 WordPress cron。

HTTP 与购物车验证：预览首页、商品页、结账页、登录页均返回 200；175 条商品；加购及跨请求购物车会话正常；原 Clipflow 首页保持 200。

预览访问验证已取消：Nginx 不再调用预览验证脚本，旧登录入口跳转至商品页。顾客账户独立使用 WooCommerce 邮箱/密码认证。原验证文件仅保留作历史备份，不参与当前访问流程。

这次部署不代表全站复刻已经完成。尚未完成的功能仍以 `reference/STATUS.md` 为准。未配置真实收款，订单邮件也尚未配置。

8080 迁移后的公网访问现已正常，首页/账户/商品/购物车请求已通过公网验证。注册、登录、退出、错误密码拒绝、订单/地址/资料页面已验证。未配置外部邮件送达及社交登录应用。
