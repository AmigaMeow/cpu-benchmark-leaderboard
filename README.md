# CPU Benchmark Leaderboard

> 自托管的 CPU 性能天梯榜 —— PHP 8.1 + MariaDB，服务端渲染，零框架依赖。

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)](https://www.php.net/)
[![MariaDB 10.6+](https://img.shields.io/badge/MariaDB-10.6%2B-003545.svg)](https://mariadb.org/)

按架构浏览、筛选、对比 CPU 跑分：`x86_64` / `ARM64` / `ARMv7` / `Other`。

> 由 [17nas.com](https://17nas.com/) 开源 —— 一个 NAS 与网络工具站。

---

## Status

🚧 **Early development** — 代码正在从上游单体应用中剥离，**当前尚不可运行**。
本仓库先建立骨架与规范；具体实施由 [Issues](../../issues) 跟踪。

## Planned Features

- CPU 天梯榜主视图，支持按架构筛选（全部 / x86_64 / ARM64 / ARMv7 / Other）
- CPU 详情页：规格 + 多维度跑分
- CPU 对比页：两两对比
- ECharts 图表，本地打包，不依赖 CDN
- 服务端渲染，对搜索引擎友好

## Architecture

```
public/      Web 根，页面入口
includes/    页面片段与查询逻辑
config/      配置模板（只放 *.example.php）
sql/         建表语句（脱敏）
data/        示例数据
docs/        规范与架构说明
```

| 层 | 技术 |
|---|---|
| 后端 | PHP 8.1（无框架） |
| 数据库 | MariaDB 10.6 |
| 前端 | 原生 JS + ECharts（本地打包） |
| 渲染 | PHP 服务端渲染为主 |

## What this project does NOT include

上游应用同时承载了用户体系与商业功能。以下内容**有意不包含**在本仓库：

- 注册 / 登录 / 会话 / 验证码
- 会员与定价逻辑
- 支付与订单
- 后台管理端
- **用户提交的原始跑分记录及任何可关联到个人的信息**
- 任何真实密钥或凭据
- 部署与运维脚本、Web 服务器配置

本仓库只包含**排行与展示**这一层。详见 [docs/SPEC.md](docs/SPEC.md)。

## Getting Started

> 尚不可运行 —— 待 `sql/` 与 `public/` 落地后补全安装步骤。

## Contributing

欢迎 issue 与 PR。提交前请先读 [docs/SPEC.md](docs/SPEC.md) 的边界说明。

## License

[MIT](LICENSE) © 2026 17nas.com
