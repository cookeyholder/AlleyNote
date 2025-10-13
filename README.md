# AlleyNote 公布欄系統

[![持續整合](https://github.com/cookeyholder/AlleyNote/workflows/Continuous%20Integration/badge.svg)](https://github.com/cookeyholder/AlleyNote/actions)
[![安全審計](https://github.com/cookeyholder/AlleyNote/workflows/Security%20Audit/badge.svg)](https://github.com/cookeyholder/AlleyNote/actions)
[![PHP Version](https://img.shields.io/badge/PHP-8.4.13-blue.svg)](https://www.php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![測試覆蓋率](https://img.shields.io/badge/Tests-2225%20Passed-brightgreen.svg)](#測試與品質保證)
[![架構](https://img.shields.io/badge/Architecture-DDD-blue.svg)](#技術架構)
[![統計分析](https://img.shields.io/badge/Statistics-Enabled-orange.svg)](docs/STATISTICS_API_SPEC.md)
[![密碼安全](https://img.shields.io/badge/Security-Enhanced-green.svg)](docs/SECURITY_HEADERS.md)

> **✨ 現代化公告管理系統**
>
> AlleyNote 是一個基於 **領域驅動設計（DDD）** 架構的企業級公告系統，專為學校、社區、企業等組織設計。系統採用前後端分離架構，提供完整的內容管理、權限控制、統計分析與安全防護功能。
>
> **核心特色**：
> - 📝 **內容管理**：完整的公告發布、編輯、分類與標籤系統
> - 👥 **權限控制**：細緻的角色權限管理（超級管理員、管理員、編輯者）
> - 🔒 **安全防護**：企業級安全機制（JWT、CSRF、XSS、SQL 注入防護）
> - 📊 **統計分析**：多維度數據分析與可視化儀表板
> - 🔑 **密碼安全**：強制密碼強度驗證與即時檢查
> - ⚡ **高效能**：快取機制與資料庫索引優化

---

## 目錄

- [專案簡介](#專案簡介)
- [核心功能](#核心功能)
- [技術架構](#技術架構)
- [系統需求](#系統需求)
- [快速開始](#快速開始)
- [測試與品質保證](#測試與品質保證)
- [API 文件](#api-文件)
- [文件資源](#文件資源)
- [授權](#授權)

---

## 專案簡介

AlleyNote 是一個功能完整的公告欄管理系統，適用於需要集中化資訊發布的各類組織。

### 主要特點

**1. 領域驅動設計（DDD）架構**
- 清晰的領域邊界：Auth（認證）、Post（文章）、Statistics（統計）
- 高內聚低耦合的模組設計
- 易於維護和擴展

**2. 前後端分離**
- 前端：純 HTML/CSS/JavaScript（無需構建工具）
- 後端：PHP 8.4 + Slim Framework
- RESTful API 設計

**3. 企業級安全**
- JWT Token 認證機制
- CSRF、XSS、SQL 注入防護
- 強制密碼強度驗證（10,000+ 黑名單密碼）
- IP 黑白名單管理

**4. 完整的統計分析**
- 7 個統計 API 端點
- 多維度數據分析（文章、使用者、來源）
- 即時統計儀表板
- 資料快照與趨勢分析

### 適用場景

| 使用場景 | 功能應用 |
|---------|---------|
| 🏫 **學校** | 課程公告、考試通知、活動資訊、校規更新 |
| 🏘️ **社區** | 住戶通知、活動公告、設施維護、繳費提醒 |
| 🏢 **企業** | 內部公告、政策更新、活動報名、員工福利 |
| 🏛️ **政府** | 政策宣導、活動訊息、表單下載、服務資訊 |

---

## 核心功能

### 📝 內容管理系統

**文章管理**
- 建立、編輯、刪除公告文章
- 支援草稿、已發布、下架等狀態
- 文章置頂功能
- 富文本編輯器（CKEditor 5）
- 檔案附件上傳（圖片、文件、PDF）
- 標籤分類系統

**權限控制**
- 三種角色：超級管理員、管理員、編輯者
- 細緻的權限管理（文章讀寫、使用者管理、統計查詢）
- 基於 JWT 的身份驗證
- 角色繼承機制

### 🔒 安全防護

**認證與授權**
- JWT Token 機制（Access Token + Refresh Token）
- 密碼強度驗證（8+字元、大小寫、數字、特殊符號）
- 黑名單密碼檢查（10,000+ 常見弱密碼）
- 密碼即時強度指示器

**安全防護**
- CSRF Token 防護
- XSS（跨站腳本）過濾
- SQL 注入防護（PDO Prepared Statements）
- HTTP Security Headers
- IP 黑白名單管理
- 登入失敗記錄與統計

### 📊 統計分析系統

**統計 API**（7 個端點）
1. `GET /api/v1/statistics/overview` - 統計概覽
2. `GET /api/v1/statistics/posts` - 文章統計
3. `GET /api/v1/statistics/sources` - 來源分布
4. `GET /api/v1/statistics/users` - 使用者統計
5. `GET /api/v1/statistics/popular` - 熱門內容
6. `GET /api/v1/statistics/charts/views/timeseries` - 流量時間序列
7. `GET /api/v1/activity-logs/login-failures` - 登入失敗統計

**統計功能**
- 多維度數據分析（文章、使用者、來源）
- 即時統計儀表板
- 資料快照與趨勢分析
- 熱門文章排行
- 使用者活動追蹤
- 圖表視覺化（Chart.js）

### ⚡ 效能優化

**資料庫優化**
- 41 個精心設計的索引
- 平均查詢時間 < 1ms
- 複合索引優化複雜查詢

**快取機制**
- 應用層快取（統計資料 TTL 5分鐘）
- 快取標籤系統
- 預期命中率 70-80%

---

## 技術架構

### 後端技術棧

| 技術 | 版本 | 用途 |
|------|------|------|
| PHP | 8.4.13 | 主要開發語言 |
| Slim Framework | 4.x | HTTP 路由與中介層 |
| SQLite | 3.x | 資料庫 |
| JWT | - | 身份驗證 |
| PHPUnit | 11.x | 單元測試 |
| PHPStan | Level 10 | 靜態分析 |
| PHP CS Fixer | 3.x | 程式碼風格 |

### 前端技術棧

| 技術 | 版本 | 用途 |
|------|------|------|
| HTML5 | - | 頁面結構 |
| CSS3 | - | 樣式設計 |
| JavaScript | ES6+ | 互動邏輯 |
| CKEditor 5 | 41.4.2 | 富文本編輯器 |
| Chart.js | 4.x | 圖表視覺化 |
| Fetch API | - | HTTP 請求 |

### 領域模型

```
AlleyNote/
├── Auth Domain（認證領域）
│   ├── User Management（使用者管理）
│   ├── Role & Permissions（角色權限）
│   ├── Activity Logging（活動記錄）
│   └── Password Security（密碼安全）
│
├── Post Domain（文章領域）
│   ├── Article Management（文章管理）
│   ├── Tag System（標籤系統）
│   ├── Attachment Handling（附件處理）
│   └── Status Control（狀態控制）
│
└── Statistics Domain（統計領域）
    ├── Data Collection（資料收集）
    ├── Snapshot Management（快照管理）
    ├── Query API（查詢 API）
    └── Dashboard（儀表板）
```

---

## 系統需求

### 伺服器需求

- Docker 20.10+
- Docker Compose 2.0+
- 至少 2GB RAM
- 至少 5GB 磁碟空間

### 開發需求

- Git
- Docker & Docker Compose
- 基本的命令列操作知識

### 網路需求

- 端口 3000（前端）
- 端口 8080（API）

---

## 快速開始

### 1. Clone 專案

```bash
git clone https://github.com/cookeyholder/AlleyNote.git
cd AlleyNote
```

### 2. 環境設定

```bash
# 複製環境變數範本
cp backend/.env.example backend/.env

# 編輯 .env 檔案，設定 JWT 金鑰
# JWT_SECRET=your-secret-key-here
```

### 3. 啟動服務

```bash
# 啟動所有容器
docker compose up -d

# 檢查容器狀態
docker compose ps
```

### 4. 初始化資料庫

```bash
# 執行資料庫遷移
docker compose exec web php vendor/bin/phinx migrate

# 載入測試資料
docker compose exec web php vendor/bin/phinx seed:run
```

### 5. 訪問應用

- **前端**：http://localhost:3000
- **API 文件**：http://localhost:8080/api/docs
- **健康檢查**：http://localhost:8080/api/health

### 6. 預設帳號

| 角色 | 帳號 | 密碼 |
|------|------|------|
| 超級管理員 | admin@example.com | Admin@123456 |
| 管理員 | manager@example.com | Manager@123 |
| 編輯者 | editor@example.com | Editor@123 |

詳細的安裝與設定說明請參考 [QUICK_START.md](QUICK_START.md)。

---

## 測試與品質保證

### 測試統計

```
✅ 2,225 個測試全部通過
✅ 9,255 個斷言驗證
✅ PHPStan Level 10：0 錯誤
✅ PHP CS Fixer：555 檔案格式化
```

### 執行測試

```bash
# 執行所有測試
docker compose exec web composer test

# 執行單元測試
docker compose exec web ./vendor/bin/phpunit --testsuite Unit

# 執行整合測試
docker compose exec web ./vendor/bin/phpunit --testsuite Integration

# 靜態分析
docker compose exec web composer analyse

# 程式碼風格檢查
docker compose exec web composer cs-check

# 完整 CI 檢查
docker compose exec web composer ci
```

### 測試覆蓋

| 類型 | 數量 | 狀態 |
|------|------|------|
| 單元測試 | 1,800+ | ✅ 通過 |
| 整合測試 | 300+ | ✅ 通過 |
| E2E 測試 | 100+ | ✅ 通過 |
| 安全測試 | 25+ | ✅ 通過 |

---

## API 文件

### 認證端點

```
POST   /api/v1/auth/login         # 使用者登入
POST   /api/v1/auth/refresh        # 刷新 Token
POST   /api/v1/auth/logout         # 使用者登出
POST   /api/auth/validate-password # 密碼驗證
```

### 文章端點

```
GET    /api/v1/posts              # 取得文章列表
GET    /api/v1/posts/:id          # 取得單一文章
POST   /api/v1/posts              # 建立文章
PUT    /api/v1/posts/:id          # 更新文章
DELETE /api/v1/posts/:id          # 刪除文章
```

### 統計端點

```
GET    /api/v1/statistics/overview           # 統計概覽
GET    /api/v1/statistics/posts              # 文章統計
GET    /api/v1/statistics/sources            # 來源分布
GET    /api/v1/statistics/users              # 使用者統計
GET    /api/v1/statistics/popular            # 熱門內容
GET    /api/v1/statistics/charts/views/timeseries  # 流量時間序列
GET    /api/v1/activity-logs/login-failures  # 登入失敗統計
```

完整的 API 文件請參考：
- [STATISTICS_API_SPEC.md](docs/STATISTICS_API_SPEC.md) - 統計 API 規格
- [API Documentation](docs/api/) - 完整 API 文件

---

## 文件資源

### 核心文件

| 文件 | 說明 |
|------|------|
| [QUICK_START.md](QUICK_START.md) | 快速開始指南（5 分鐘啟動） |
| [CHANGELOG.md](CHANGELOG.md) | 版本更新日誌 |
| [docs/README.md](docs/README.md) | 文件總覽 |

### 功能文件

| 文件 | 說明 |
|------|------|
| [STATISTICS_API_SPEC.md](docs/STATISTICS_API_SPEC.md) | 統計 API 完整規格 |
| [STATISTICS_PAGE_README.md](docs/STATISTICS_PAGE_README.md) | 統計頁面使用說明 |
| [SECURITY_HEADERS.md](docs/SECURITY_HEADERS.md) | 安全標頭設定 |
| [FRONTEND_USER_GUIDE.md](docs/FRONTEND_USER_GUIDE.md) | 前端使用指南 |

### 開發文件

所有開發文件位於 `docs/` 目錄：
- `docs/api/` - API 文件
- `docs/guides/` - 開發指南
- `docs/domains/` - 領域模型文件
- `docs/archive/` - 歷史文件與完成報告

---

## 貢獻指南

我們歡迎各種形式的貢獻！

1. Fork 專案並建立分支
2. 遵循 DDD 架構原則開發
3. 撰寫/更新測試
4. 確保程式碼品質檢查通過（PHPStan Level 10, PHP CS Fixer）
5. 提交 Pull Request，說明變更內容

---

## 授權

本專案採用 MIT 授權，詳見 [LICENSE](LICENSE)。

---

## 聯絡方式

- **Issues**: [GitHub Issues](https://github.com/cookeyholder/alleynote/issues)
- **Wiki**: [專案 Wiki](https://github.com/cookeyholder/alleynote/wiki)

---

**🎉 感謝您的關注！如有任何問題，歡迎開啟 Issue 或 Pull Request。**
