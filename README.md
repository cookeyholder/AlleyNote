# AlleyNote

> 公告／佈告欄平台 · DDD 架構 · PHP 8.4 + SQLite + ES6 SPA

![PHP](https://img.shields.io/badge/PHP-8.4-%23777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)
[![CI](https://github.com/cookeyholder/AlleyNote/actions/workflows/ci.yml/badge.svg)](https://github.com/cookeyholder/AlleyNote/actions/workflows/ci.yml)

---

## 你是誰？

| 角色 | 從這裡開始 |
|------|-----------|
| 🖥️ 後端開發者 | [`docs/architecture/01-統計分析器模式.md`](docs/architecture/01-統計分析器模式.md) |
| 🎨 前端開發者 | [`docs/frontend/01-架構總覽.md`](docs/frontend/01-架構總覽.md) |
| 📝 內容管理者 | [`docs/guides/content-creators/01-管理後台使用手冊.md`](docs/guides/content-creators/01-管理後台使用手冊.md) |
| ⚙️ 系統管理員 | [`docs/guides/admin/03-系統需求.md`](docs/guides/admin/03-系統需求.md) |
| 🔌 API 整合者 | [`docs/api/README.md`](docs/api/README.md) |
| 👋 新進成員 | [`docs/INDEX.md`](docs/INDEX.md) |

---

## 快速開始

```bash
git clone https://github.com/cookeyholder/AlleyNote.git
cd AlleyNote
cp backend/.env.example backend/.env
# 編輯 .env 填入 JWT_PRIVATE_KEY 與 JWT_PUBLIC_KEY
docker compose up -d
```

完整流程 → [`docs/runbooks/01-開發環境.md`](docs/runbooks/01-開發環境.md)

---

## 開發指令

| 位置 | 指令 | 用途 |
|------|------|------|
| `backend/` | `composer test` | PHPUnit 測試（2220+ tests） |
| `backend/` | `composer analyse` | PHPStan Level 10 靜態分析 |
| `backend/` | `composer cs-fix` | PHP-CS-Fixer 自動修正 |
| `backend/` | `composer check-all` | 分析 + 風格 + 測試 |
| `frontend/` | `npm run lint` | Prettier 前端格式檢查 |
| `frontend/` | `npm run dev` | 開發伺服器（port 3000） |
| `tests/e2e/` | `npm test` | Playwright E2E 測試 |

---

## 技術棧

| 層級 | 技術 |
|------|------|
| 後端 | PHP 8.4, DDD 架構, PHP-DI 7.x, FastRoute, PSR-7/15 |
| 資料庫 | SQLite 3 + PDO（無 ORM） |
| 快取 | Redis（Predis）+ SQLite 降級方案 |
| 認證 | JWT (RS256), firebase/php-jwt |
| 前端 | ES6 模組（無建構工具）, Tailwind CSS CDN, CKEditor 5 |
| 圖表 | Chart.js 4.x |
| 容器 | Docker Compose（PHP 8.4 + Nginx + Redis） |
| CI/CD | GitHub Actions（PHPStan, PHPUnit, Playwright, CodeQL） |

---

## 專案結構

```
AlleyNote/
├── backend/
│   ├── app/
│   │   ├── Application/     # 控制器、中介層、資源、服務
│   │   ├── Domains/         # 7 個 Bounded Context
│   │   │   ├── Post/        # 公告核心領域
│   │   │   ├── Auth/        # 認證授權
│   │   │   ├── Statistics/  # 統計分析
│   │   │   ├── Security/    # 安全防護
│   │   │   ├── Attachment/  # 附件管理
│   │   │   ├── Setting/     # 系統設定
│   │   │   └── Shared/      # 共享值物件
│   │   ├── Infrastructure/  # 基礎設施實作
│   │   └── Shared/          # 共享核心
│   ├── config/              # DI 容器、路由定義
│   ├── database/            # Phinx migrations
│   ├── tests/               # 單元、整合、功能、安全測試
│   └── public/              # 進入點 index.php
├── frontend/
│   ├── index.html
│   ├── js/                  # ES6 模組
│   └── css/                 # Tailwind 樣式
├── docker/                  # PHP、Nginx、Redis 設定
├── tests/e2e/               # Playwright E2E 測試
└── docs/                    # 文件（5 種讀者分流）
    ├── decisions/           # ADR（架構決策記錄）
    ├── domains/             # 領域概述
    ├── architecture/        # 設計文件
    ├── frontend/            # 前端架構
    └── guides/              # 操作手冊
```

---

## 相關連結

- [📖 文件總索引](docs/INDEX.md)
- [📋 變更記錄](CHANGELOG.md)
- [🤝 貢獻指南](CONTRIBUTING.md)
- [🛡️ 文件治理規範](docs/DOCUMENTATION_GOVERNANCE.md)
- [🐛 回報問題](https://github.com/cookeyholder/AlleyNote/issues)

---

## 授權

MIT
