# 文件分类清单 — cpu-benchmark-leaderboard（Phase 1 产出，待确认）

审查对象：私有仓库 `AmigaMeow/17nas.com`（PHP 8.1 原生 + MariaDB，SSR）。
分类标准：能否直接进入公开仓库 / 需要剥离改造 / 绝对不得进入公开仓库。
**本清单确认前不进行任何代码剥离。**

## 一、可开源（直接或极小改动即可入库）

### 核心业务逻辑（本仓库范围内）
| 文件 | 说明 |
|---|---|
| `includes/cpu-whitelist.php` | CPU 白名单/架构映射，0 依赖 |
| `includes/cpu-compare-pairs.php` | 手工精选对比组合 + 人工结论 |
| `includes/ssr-benchmark-rows.php` | 天梯榜 SSR 行渲染 |
| `includes/ssr-benchmark-cards.php` | 天梯榜 SSR 卡片渲染 |
| `includes/head-meta.php` | `<head>` meta 输出 |
| `includes/breadcrumb.php` | 面包屑 |
| `includes/language-switcher.php` | 语言切换 UI |
| `includes/version.php` | `ASSET_VERSION` 常量 |
| `includes/scripts.php` | 脚本引入聚合 |

### 前端资源
| 文件 | 说明 |
|---|---|
| `assets/js/echarts.min.js` | Apache-2.0，**已确认版权头保留在文件内**；需在 README/NOTICE 注明 |
| `assets/js/html2canvas.min.js` | MIT；源站走 CDN，开源版改为本地引用即可 |
| `assets/css/layout.css` / `style.css` / `combined.css` / `mobile-sidebar.css` / `swup-transitions.css` / `submit-page.css` | 页面样式（`combined.css` 是打包产物，建议开源时拆分或注明来源） |
| `assets/images/` 中榜单相关图片（logo、favicon、默认头像、og 兜底图） | 需逐个目检不含品牌敏感物 |

### 基础设施
| 文件 | 说明 |
|---|---|
| `admin/utils/Database.php` | 纯 PDO 封装，无业务无密钥（连同公开版的 `config/database.example.php` 一起给） |
| `admin/utils/Response.php` | JSON 响应封装 |
| `admin/utils/Validator.php` | 输入校验（仅当后续开源提交接口时需要） |
| `config.php.example` | DB 配置模板（站点已有 example 惯例） |

## 二、需改造（剥离后可入库）

| 文件 | 需要剥掉什么 |
|---|---|
| `index.php`（3,743 行巨石） | 登录/注册模态入口（`show_login` 参数、`showLoginModal`）、VIP/赞助导航入口、用户头像区、提交入口中涉及隐私字段的部分、对 `admin/config/database.php` 的引用（改为公共 DB 配置）、`html2canvas` CDN 引用改为本地。保留：天梯榜渲染、`filterArch()` 架构筛选、详情弹层逻辑 |
| `cpu-detail.php` | 同上：去 `admin/utils/Database.php` 依赖、去个人化（提交者头像/昵称展示字段需评估是否保留 — 见「待确认」） |
| `cpu-compare.php` | 去 `admin/utils/Database.php` 依赖 |
| `benchmark-methodology.php` | 去 chrome 依赖，其余基本干净 |
| `includes/header.php` | 剥 `userAuthArea`/`user_token` 登录区（L406+）、VIP/赞助入口、对 `article_categories`/`is_vip_only` 的查询；保留导航骨架 |
| `includes/footer.php` | 剥 `admin/config/database.php` 直连查询（L561+）、商业链接（nas-products/带货）、Adsense 输出；保留页脚导航与统计 |
| `includes/footer-stats.php` | 去 `admin/utils/Database.php` 依赖 |
| `includes/mobile-nav.php` / `mobile-sidebar.php` | 剥登录/注册/个人中心 token 逻辑（L119-128 等）、VIP 赞助入口 |
| `includes/I18n.php` | 强依赖 `admin/utils/Database.php` + `translations`/`seo_translations` 表 → 需给开源版提供独立模式（DB i18n 或语言文件 fallback），并随附建表/种子 |
| `includes/settings.php` | 同上，依赖 `site_settings` 表 → 需独立配置 fallback |
| `includes/cpu-i18n.php` | 内含 `nas_products` 推荐文案与字段访问（`runtime.nas_*`）→ 去掉带货段落，保留跑分/详情文案 |
| `assets/js/main.js` | 删 `fetch('/api/public/cpu-nas-products.php')`（L1010，带货）调用；`benchmark-detail.php` 调用需改为开源版端点；检查用户态逻辑 |
| `api/public/cpu-ranking.php` / `cpu-benchmark-detail.php` / `benchmark-detail.php` | 改 `admin/utils` 路径为公开版 includes；`benchmark-detail.php` 返回的 `submitted_by`/`submitter_avatar` 字段需按「待确认」决定 |
| `assets/css/combined.min.css` / `*.min.css` | 产物文件二选一：开源源文件并给构建说明，或直接给 min（不推荐） |
| 建表 SQL（新增 `sql/schema.sql`） | 从 `docs/database/schema.sql` 提取：`cpus` 全字段可直接用；`benchmark_submissions` **必须去掉** `submitter_email`/`submitter_ip`/`user_id`/`submitter_name`/`screenshot_url`/`screenshot_sha256`/`reviewed_by`/`reject_reason`/`notes` |
| 示例数据（新增 `sql/seed.sql`） | 新造 10-20 条 CPU 示例行，不从生产库导出 |

## 三、禁止开源（绝对不得进入公开仓库）

| 文件/目录 | 原因 |
|---|---|
| 整个 `admin/`（除 `utils/Database.php`、`utils/Response.php`、`utils/Validator.php` 三个无状态工具类外） | 后台管理、审核、商业、用户体系 |
| `admin/config/database.php` / `jwt.php`（真实文件） | 已在私有仓库 .gitignore，不存在；防范重新引入 |
| `submit.php`、`api/submit_benchmark.php`、`api/frontend/submit-benchmark.php` | 跑分提交链路，写入隐私字段（email/IP/截图）。开源范围 v1 不含提交入口；如需提供需重新设计（无个人字段的匿名提交） |
| `api/public/user-submissions.php`、`profile.php`、`change-password.php`、`update-profile.php`、`upload-avatar.php`、`sponsor-records.php`、`vip-*.php` | 用户隐私/会员接口 |
| `api/public/cpu-nas-products.php`、`api/get_nas_products.php`、`api/get_recommended_products.php` | 带货/商业逻辑 |
| `includes/Membership.php`、`auth-modal.php`、`adsense-block.php`、`ApiCommerce.php`、`StripePayment.php`、`PaymentFulfillment.php`、`IndexNow.php`、`AiClient.php`、`BaiduOcr.php`、`ClientIP.php`、`ip-ban-*.php`、`Parsedown.php`（若被用户体系依赖） | 用户体系 / 商业 / 第三方密钥调用 |
| `includes/NetworkIntelligence*.php`、`NetworkProbe*.php`、`NetworkMapAccess.php` | 网络情报系统 |
| `admin/utils/JWT.php`、`AuditLog.php`、`EpayCore.php`、`JdUnion*`、`JdHttp.php`、`JdProductHelper.php`、`PicHubClient.php`、`PushPlus.php`、`CpuReviewHelper.php`、`AppReleaseHelper.php`、`NetDiagHelper.php` | 鉴权/支付/返利/内部工具 |
| `config/vip_plans.php` | 定价策略 |
| `config/analytics.php` | GA4/Bing ID 本属公开信息，但开源版一律换占位符 |
| `config/oauth.example.php`/`stripe.example.php`/`wechat.example.php` | 与本功能无关，不带走（避免误导使用者） |
| `config/network-intelligence.php`、`ip_api.php` | 与本功能无关 + 情报系统 |
| `sql/` 全部现有迁移、`optimize_database.sql` | 用户/支付/情报表结构，不带走 |
| `coremark/`、`cli-tool/`、`cloudflare/`、`scripts/`（备份/运维/采集）、`docs/`（内部 SEO/设计文档）、根目录 `*REPORT*.md`/`*_FIX*.md` | 内部工具与内部文档 |
| `.hoplite/` | 本地预览工具 |
| 其他工具页（`nat-test.php`、`ip-*.php`、`xiaoya.php`、`wechat-chat.php`、`payslip.php` 等 100+ 页面） | 不在开源范围 |

## 四、待用户确认

1. **`benchmark-detail` 返回的 `submitted_by` / `submitter_avatar`**：详情弹层会显示提交者昵称与头像。这属于用户身份信息的公开展示 — 开源版建议直接移除该展示（或只保留匿名），是否同意？
2. **跑分提交入口**：v1 不提供提交功能（表单+接口均含隐私字段）。后续若要「匿名提交」需重新���计表结构 — 是否同意 v1 不含提交？
3. **i18n 模式**：开源版是把 i18n 简化成语言文件，还是保留 DB 方案 + 附 translations 建表和种子？前者实现快，后者与源站结构一致。
