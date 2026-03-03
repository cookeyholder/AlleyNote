# AlleyNote 專案目錄結構

> 最後更新：2024-10-18

## 根目錄結構

```
AlleyNote/
├── backend/              # 後端應用程式（PHP/DDD 架構）
├── frontend/             # 前端應用程式（Vanilla JS）
├── docker/               # Docker 配置檔案
├── docs/                 # 專案文件
├── notes/                # 開發筆記
├── .github/              # GitHub 工作流程與設定
├── docker-compose.yml    # 開發環境 Docker Compose
└── README.md             # 專案說明文件
```

## Backend 目錄結構

```
backend/
├── app/
│   ├── Application/      # 應用層（Use Cases、DTOs）
│   ├── Domains/          # 領域層（Entities、Value Objects、Domain Services）
│   ├── Infrastructure/   # 基礎設施層（Repository 實作、外部服務）
│   ├── Http/             # HTTP 層（Controllers、Middleware）
│   └── Shared/           # 共用元件
├── config/               # 應用程式設定
│   └── routes/           # 路由定義
├── database/
│   ├── migrations/       # 資料庫遷移檔案
│   ├── seeds/            # 種子資料
│   └── backups/          # 資料庫備份
├── resources/
│   └── data/             # 靜態資源資料
├── scripts/              # 工具腳本
│   ├── Analysis/         # 程式碼分析工具
│   ├── Archive/          # 歷史腳本（已棄用）
│   ├── CI/               # CI/CD 相關腳本
│   ├── Core/             # 核心工具
│   ├── Database/         # 資料庫工具
│   ├── Deployment/       # 部署腳本
│   ├── Examples/         # 範例程式碼
│   ├── Maintenance/      # 維護腳本
│   ├── Quality/          # 程式碼品質工具
│   └── Testing/          # 測試工具腳本
├── storage/              # 存儲目錄
│   ├── cache/            # 快取檔案
│   ├── logs/             # 日誌檔案
│   └── phpstan/          # PHPStan 快取
├── tests/
│   ├── Unit/             # 單元測試
│   ├── Integration/      # 整合測試
│   ├── Functional/       # 功能測試
│   ├── E2E/              # 端對端測試
│   │   ├── Shared/       # E2E 測試共用元件
│   │   └── playwright/   # Playwright 測試套件
│   ├── Performance/      # 效能測試
│   ├── Security/         # 安全測試
│   └── Support/          # 測試輔助工具
├── public/               # Web 伺服器根目錄
├── vendor/               # Composer 依賴（自動生成）
└── composer.json         # PHP 依賴管理
```

## Frontend 目錄結構

```
frontend/
├── css/                  # 樣式檔案
├── js/
│   ├── api/              # API 客戶端
│   ├── components/       # 可重用元件
│   ├── layouts/          # 版面配置
│   ├── pages/            # 頁面模組
│   ├── store/            # 狀態管理
│   └── utils/            # 工具函式
├── html/
│   ├── admin/            # 管理後台頁面
│   └── public/           # 公開頁面
├── assets/
│   ├── images/           # 圖片資源
│   └── icons/            # 圖示資源
└── examples/             # 前端範例
    └── vanilla-frontend/ # Vanilla JS 範例
```

## Docs 目錄結構

```
docs/
├── architecture/         # 架構文件
│   ├── architecture-report.md        # 架構分析報告
│   ├── code-quality-analysis.md      # 程式碼品質分析
│   ├── architecture-summary.txt      # 架構摘要
│   └── DIRECTORY_STRUCTURE.md        # 本檔案
├── api/                  # API 文件
├── development/          # 開發指南
└── deployment/           # 部署文件
```

## 重要變更記錄

### 2024-10-18 - 目錄結構整理

1. **移除重複目錄**：
   - 移除根目錄的 `/app/`（已整合至 `/backend/app/`）
   - 移除根目錄的 `/database/`（已整合至 `/backend/database/`）
   - 移除根目錄的 `/storage/`（已整合至 `/backend/storage/`）

2. **整合分散的工具目錄**：
   - `/scripts/` → `/backend/scripts/Testing/`
   - `/examples/vanilla-frontend/` → `/frontend/examples/`
   - `/backend/examples/` → `/backend/scripts/Examples/`

3. **整理測試目錄**：
   - `/tests/e2e/` → `/backend/tests/E2E/playwright/`

4. **文件整理**：
   - 架構分析文件移至 `/docs/architecture/`
   - 移除生成的 HTML 報告目錄

5. **清理空目錄**：
   - 移除 `/certbot-data/`、`/ssl-data/`

## 目錄命名規範

- **Backend/PHP**：使用 UpperCamelCase（如 `Application`、`Domains`）
- **Frontend/JS**：使用 kebab-case（如 `api`、`components`）
- **Docs**：使用 kebab-case（如 `architecture`、`development`）
- **Scripts**：使用 UpperCamelCase（如 `Database`、`Analysis`）

## .gitignore 規則

重要的忽略規則：
- `vendor/` - Composer 依賴
- `node_modules/` - NPM 依賴
- `*.sqlite*`, `*.db*` - 資料庫檔案
- `coverage-reports/` - 測試覆蓋率報告
- `storage/logs/` - 日誌檔案
- `storage/cache/` - 快取檔案

詳細規則請參考專案根目錄的 `.gitignore` 檔案。
