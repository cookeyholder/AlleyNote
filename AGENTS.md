# AlleyNote 專案指南 for AI Agents

## 專案概述

AlleyNote 是一個公告/佈告欄平台，採用**領域驅動設計（DDD）** 架構，包含角色權限管理、安全控管與統計分析功能。後端為自製 PHP 框架，前端為無建構工具的純 ES6 模組化 SPA。

## 技術棧

### 後端 (PHP 8.4+)
- **架構**: 自製 DDD（無框架），PSR-7/PSR-15/PSR-11 相容
- **DI**: PHP-DI 7.x
- **資料庫**: SQLite3 + PDO（無 ORM）
- **Migration**: Phinx
- **路由**: nikic/fast-route
- **認證**: firebase/php-jwt（RS256）
- **快取**: Predis (Redis)
- **驗證**: 自製 `ValidatorInterface`
- **日誌**: Monolog

### 前端 (無建構工具)
- 純 ES6 模組，無框架（由 Vue 遷移而來）
- Tailwind CSS（CDN 載入）
- CKEditor 5（純文字編輯器）
- Chart.js（統計圖表）
- 自製 SPA router + global store

### 測試
- **單元/整合測試**: PHPUnit 11.x + Mockery
- **E2E 測試**: Playwright（位於 `tests/e2e/`）

### 靜態分析與程式碼風格
- **PHPStan**: Level 10（strictest）
- **PHP-CS-Fixer**: @PER-CS2.0 + @PHP84Migration
- **Prettier**: 前端 JS/HTML 格式化
- **無 ESLint**

## 專案結構

```
backend/
├── app/
│   ├── Application/     # 應用層（Controllers, Middleware, Resources, Services）
│   ├── Domains/         # 領域層（7 個 Bounded Context）
│   │   ├── Attachment/
│   │   ├── Auth/
│   │   ├── Post/
│   │   ├── Security/
│   │   ├── Setting/
│   │   ├── Shared/
│   │   └── Statistics/
│   ├── Http/
│   ├── Infrastructure/  # 基礎設施層
│   └── Shared/          # 共享核心
├── config/
│   ├── container.php    # PHP-DI 定義
│   └── routes/          # 路由檔案
├── database/            # Phinx migrations + seeds
├── public/              # 進入點 index.php
├── tests/               # 測試（Unit, Integration, Functional, E2E, Security, UI, Performance, Database）
│   ├── Support/         # 測試基礎類別與 traits
│   └── Factory/         # 測試工廠
└── vendor/

frontend/
├── index.html
├── css/
├── js/
│   ├── api/             # API 客戶端
│   ├── components/      # UI 元件
│   ├── layouts/         # 佈局元件
│   ├── pages/           # 頁面模組（public/, admin/）
│   ├── store/           # 全域狀態
│   └── utils/           # 工具（router, notification, validator）
└── favicon.ico

docker/                   # Docker 設定（php, nginx, redis）
tests/e2e/                # Playwright E2E 測試（獨立 package.json）
```

## 領域層 (7 個 Bounded Context)

| Context | 職責 |
|---------|------|
| Attachment | 附件上傳與管理 |
| Auth | 認證與授權（JWT, OAuth） |
| Post | 公告/文章 CRUD |
| Security | 安全審計、速率限制 |
| Setting | 系統設定 |
| Shared | 共享值物件與契約 |
| Statistics | 統計分析 |

## DDD 分層約定

```
Controller → Service → Repository → Model (POJO)
                ↕                    ↑
            DTO / ValueObject     Database (PDO)
```

- **Controllers** (`Application/Controllers/`): 處理 HTTP，委派給 Service，儘量無邏輯
- **Services** (`Domains/*/Services/`): 商業邏輯
- **Repositories** (`Domains/*/Repositories/`): 資料存取 + 快取
- **Models** (`Domains/*/Models/`): 純 PHP 物件（不是 ORM），實作 `JsonSerializable`
- **DTOs** (`Domains/*/DTOs/`): 資料傳輸 + 驗證
- **ValueObjects** (`Domains/*/ValueObjects/`): 不可變值型別
- **Enums** (`Domains/*/Enums/`): PHP 8.1+ backed enums
- **Contracts** (`Domains/*/Contracts/`): 介面定義
- **Resources** (`Application/Resources/`): API 回傳轉換

## 程式碼規範

### PHP
- `declare(strict_types=1);` 每個檔案必加
- 所有參數與回傳值必須有型別標示
- 建構子屬性提升（constructor property promotion）優先使用
- readonly property 用於 injected dependencies
- PHPDoc 使用繁體中文描述
- `@param`, `@return`, `@throws` 一致標註
- Controller 方法結構：驗證 → 呼叫 Service → 日誌 → 回傳 Response
- 命名：
  - 類別: PascalCase
  - 方法/屬性: camelCase
  - 常數: UPPER_SNAKE_CASE
  - 介面: `*Interface` 後綴
  - DTO: `*DTO` 後綴
  - Exception: `*Exception` 後綴

### JavaScript
- ES6 模組（`import`/`export`）
- camelCase 變數與函式
- PascalCase 元件類別
- async/await 處理 API 呼叫
- JSDoc 使用繁體中文

## 常用指令

### 後端 (在 `backend/` 目錄執行)
```bash
composer test              # PHPUnit（無 coverage）
composer test-coverage     # PHPUnit（有 coverage）
composer analyse           # PHPStan Level 10
composer cs-check          # PHP-CS-Fixer 檢查
composer cs-fix            # PHP-CS-Fixer 自動修正
composer check-all         # analyse + cs-check + test
```

### 前端
```bash
npm run dev            # 啟動開發伺服器（port 3000，proxy /api → :8081）
npm run lint           # Prettier 檢查
npm run lint:fix       # Prettier 自動修正
```

### E2E (在 `tests/e2e/` 目錄執行)
```bash
npm test               # Playwright headless
npm run test:headed    # 有頭模式
npm run test:ui        # UI 模式
npm run test:debug     # 除錯模式
```

### Docker
```bash
docker compose up -d   # 啟動所有服務
```

## 可用工具

以下 CLI 工具可提升程式碼搜尋與分析效率，若環境中缺少這些工具，AI agent 可直接安裝：

### 必備（已安裝或可自動安裝）
- **codegraph**: 程式碼知識圖譜，用於查詢符號呼叫者/被呼叫者，精準定位相依關係（不需語法樹猜測）
- **ripgrep (rg)**: 超高速內容搜尋，支援 `--type php` / `--type js` 等語言過濾，比 grep 快數十倍
- **fd-find (fd)**: 快速檔案搜尋，取代 `find` 指令，支援正規表示法與智慧大小寫
- **fzf**: 模糊搜尋過濾器，可 pipe 串接 `rg`、`fd` 等輸出進行互動式過濾

### 推薦安裝
- **bat**: `cat` 的強化版，具備語法高亮、行號顯示、Git 變更標記。查看 PHP/JS 檔案時能自動上色，對讀取大型檔案特別有用
- **jq**: 輕量級 JSON 處理器。用於解析 `composer audit --format=json`、`phpstan analyse --error-format=json`、或 API 回應
- **delta**: Git diff 的語法高亮分頁工具，讓 `git diff` 輸出更容易閱讀
- **tokei**: 專案程式碼統計工具，快速了解各語言的檔案數、行數與註解比例
- **tree**: 以樹狀結構顯示目錄內容
- **xh** 或 **httpie**: 指令列 HTTP 客戶端，用於快速測試 AlleyNote API 端點

### 工具使用時機參考

| 情境 | 建議工具 |
|------|----------|
| 找某個類別/方法的所有呼叫端 | `codegraph callers "FQN"` |
| 搜尋特定字串（如 deprecated 標記） | `rg "deprecated" --type php` |
| 找檔名包含特定關鍵字的檔案 | `fd "Repository"` |
| 閱讀 PHP/JS 檔案內容 | `bat`（自動語法高亮） |
| 解析 JSON 格式輸出 | `jq '.scripts' composer.json` |
| 檢視 git 變更 | `git diff | delta` |
| 查詢專案程式碼量統計 | `tokei` |
| 測試 API | `xh GET /api/posts` |

## 重要規則

1. **不要修改 `vendor/`、`node_modules/`** 內的檔案
2. **檔案編碼**: UTF-8，行尾 LF
3. **資料庫 Migration**: 使用 Phinx，位於 `backend/database/migrations/`
4. **設定檔合併**: php-cs-fixer 的 `.dist.php` 為正式設定；兩份設定檔待合併
5. **測試資料庫**: SQLite `:memory:`，每次測試重新建立
6. **所有文件與 PHPDoc 使用繁體中文（zh-TW）**
7. **Git commit 訊息、程式碼註解、PR message 都要使用繁體中文**
8. **程式碼中避免 emoji**
9. **雙 php-cs-fixer 設定檔**（`.php-cs-fixer.php` 與 `.php-cs-fixer.dist.php`），目標是合併為一份
10. **CI/CD** — GitHub Actions 工作流程位於 `.github/workflows/ci.yml`，在 PR 與 push 至 main/develop 時觸發

## 安全注意事項

- JWT 使用 RS256 演算法
- 速率限制（Rate Limit）套用於認證與公告端點
- 所有使用者輸入經 HTML Purifier 消毒
- SQL 使用 PDO prepared statements + 欄位白名單
- 檔案上傳有 MIME type 驗證
- XSS 防護經 DOMPurify（前端）+ HTMLPurifier（後端）
