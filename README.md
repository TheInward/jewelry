# En Route WordPress 本地复刻工程

当前 SiteGround 预览包含 757 件商品、53 个分类/系列、独立首页、搜索、注册登录、账号收藏、购物车及 WooCommerce 结账。2026-11-02 已补充首页商品推荐、公共导航/页脚、13 个信息页面、商品图库及详情；723 件商品取得详情，其中 630 件配置为可变商品，共 3,554 个商品变体。正式收款尚未接通，全站 1:1 视觉验收仍未完成。详见 `reference/对照完善验证-2026-11-02.md`。

## 运行

```sh
npm install
npm start
```

本地地址：http://127.0.0.1:9400

SiteGround HTTPS 预览：https://mrnobodys1.sg-host.com/ ，顾客账户：https://mrnobodys1.sg-host.com/my-account/ 。迁移记录见 `deploy/SITEGROUND.md`。

阿里云开发预览：http://39.106.108.70:8080/jewelry/ （已放行 8080，按用户要求取消预览密码，直接显示商品）。顾客账户：http://39.106.108.70:8080/jewelry/my-account/ 。服务器环境与目录见 `deploy/DEPLOYED.md`。

WordPress Playground 在 Node 中提供 PHP 和 SQLite，无需先安装 PHP/MySQL。主题位于 `theme/`，可迁移到正式 WordPress 的 `wp-content/themes/enroute-study`。本地运行时使用临时数据库，重启会按 blueprint 重新导入；订单和账户目前仅用于开发，不应作为正式数据存储。

## 已实现与边界

- 线上：757 件商品和 53 个分类；723 件商品取得详情，剩余 34 件因参考站 HTTP 429 限流保留原数据，清单见 `reference/product-details-missing.json`。
- 630 件商品使用 WooCommerce 原生变体，选项、价格、库存和结账由服务端校验；图库支持缩略图与放大。没有导入客户评价。
- 首页有响应式横幅、分类、新品/趋势商品、会员展示、品牌及媒体展示；使用参考素材，尚不代表所有布局与状态逐像素一致。
- 分类有九种排序及折扣/亮点筛选；销量排序使用本店实际销量，测试店无销量时不会复制参考商家的真实销售排名。
- 登录后收藏保存在账号，游客收藏保存在浏览器；购物车支持移入收藏。搜索支持独立结果页。
- 关于、FAQ、配送退货、护理、订单追踪等 13 个信息页面可访问。正式品牌故事、客服信息、法律政策和会员运营仍需配置；页面目前明确为预览内容，并非参考站长文原样复制。
- 线上配置 14 种语言、55 种币种。新增核心导航/购买文案补充了翻译；完整商品描述、信息页和所有错误状态尚未完成多语言覆盖。
- 九折订阅码限制邮箱、有效期及使用次数。邮件实际送达和营销平台同步尚未验证。
- 未接收款账户，未验证真实扣款、钱包支付、退款、税费和各国完整配送规则。
- 继续禁止搜索引擎索引；上线前替换品牌和素材。

## 本地与线上数据

Playground 的 `setup/import.php` 仍提供最小 175 件项链演示数据，不能代表线上完整商品库。完整部署需要先导入 `reference/catalog.json`，再导入详情；应先备份数据库。

```sh
wp eval-file deploy/import-collections.php
wp eval-file deploy/import-product-details.php /absolute/path/reference/product-details.json
wp eval-file deploy/create-information-pages.php
```

`import-product-details.php` 只更新源 ID 对应的商品，按 `REFV-源变体ID` 重用已有变体。该脚本会更新源描述、变体价格/库存及选项，正式营业后应审核再执行。信息页面脚本会更新同名预览页，修改过正式文案后不要直接重跑。

## 尚待完成

参考站剩余商品详情；全页桌面/手机视觉对照；所有语言的内容与错误状态；会员积分、补货通知等运营功能；正式业务信息及支付、邮件、退款全流程验收。
