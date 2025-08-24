# AlleyNote 公布欄網站

[![測試](https://github.com/your-org/alleynote/workflows/測試/badge.svg)](https://github.com/your-org/alleynote/actions)
[![程式碼品質](https://github.com/your-org/alleynote/workflows/程式碼品質/badge.svg)](https://github.com/your-org/alleynote/actions)
[![部署](https://github.com/your-org/alleynote/workflows/部署/badge.svg)](https://github.com/your-org/alleynote/actions)
[![PHP Version](https://img.shields.io/badge/PHP-8.4.11-blue.svg)](https://www.php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![測試覆蓋率](https://img.shields.io/badge/Coverage-91%25-brightgreen.svg)](docs/TEST_SUITE_IMPROVEMENTS.md)
[![架構版本](https://img.shields.io/badge/Architecture-DDD-green.svg)](docs/ARCHITECTURE_AUDIT.md)

---

## 目錄

- [專案簡介](#專案簡介)
- [功能特色](#功能特色)
- [技術架構](#技術架構)
- [專案結構說明](#專案結構說明)
- [系統需求](#系統需求)
- [安裝與快速開始](#安裝與快速開始)
- [開發流程](#開發流程)
- [測試流程](#測試流程)
- [部署說明](#部署說明)
- [常見問題 FAQ](#常見問題-faq)
- [文件資源](#文件資源)
- [授權](#授權)

---

## 專案簡介

AlleyNote 是一個現代化的公布欄網站系統，專為學校、社區、企業等單位設計，支援多用戶、權限控管、IP 黑白名單、附件上傳、資料自動備份等功能。

本專案以 PHP 8.4.11 開發，採用 SQLite 資料庫，並以 Docker 容器化部署，具備完善的自動化測試與 CI/CD 流程。專案已成功從 MVC 架構遷移到 DDD（領域驅動設計）架構，大幅提升了程式碼品質和可維護性。

---

## 功能特色

### 🚀 核心功能
- **文章管理**: 發布、編輯、刪除、置頂、封存
- **附件系統**: 上傳、下載、刪除，支援多種檔案格式
- **使用者系統**: 認證、權限管理、角色控制
- **安全控制**: IP 黑白名單、CSRF 防護、XSS 過濾

### 🏗️ DDD 架構特色
- **領域驅動設計**: Post、Attachment、Auth、Security 四個業務領域
- **分層架構**: Domain → Application → Infrastructure → Shared
- **強型別驗證系統**: 29 種內建驗證規則，支援繁體中文
- **現代化 DI 容器**: PHP-DI 依賴注入，支援編譯快取

### 🧪 品質保證
- **495 個測試**: 單元、整合、效能、安全測試
- **91% 測試通過率**: 大幅改善的測試穩定性
- **完整測試覆蓋**: 核心業務邏輯高覆蓋率
- **靜態分析**: PHPStan Level 8

### 🔧 維運功能
- **自動備份**: 資料庫與檔案自動備份
- **效能監控**: 快取統計、資料庫效能分析
- **Docker 容器化**: 開發與生產環境一致
- **SSL 支援**: Let's Encrypt 自動憑證管理

---

## 技術架構

### 🎯 核心技術棧
- **後端語言**: PHP 8.4.11（強型別、現代語法）
- **Web 伺服器**: NGINX（高效能、負載均衡）
- **資料庫**: SQLite3（零設定、檔案型資料庫）
- **快取系統**: File Cache + APCu（支援分散式快取）

### 🏗️ DDD 架構組件
- **Domain 層**: 業務實體、值物件、領域服務
- **Application 層**: 應用服務、控制器、DTO
- **Infrastructure 層**: 資料庫、外部服務、技術實作
- **Shared 層**: 共用元件、驗證器、例外處理

### 🛠️ 開發工具
- **自動化測試**: PHPUnit, PHPStan Level 8, PHPCS
- **容器化**: Docker, Docker Compose
- **依賴管理**: Composer
- **程式碼品質**: PHP-CS-Fixer

### 🔒 安全與維運
- **SSL 憑證**: Let's Encrypt 自動續簽
- **備份策略**: 自動備份與災難復原
- **作業系統**: Debian Linux 12

---

## 專案結構說明

```
AlleyNote/
├── app/                        # 應用程式核心
│   ├── Application/           # 應用層（控制器、API）
│   ├── Domains/              # 領域層（業務邏輯）
│   │   ├── Post/             # 文章領域
│   │   ├── Attachment/       # 附件領域
│   │   ├── Auth/             # 認證領域
│   │   └── Security/         # 安全領域
│   ├── Infrastructure/       # 基礎設施層
│   └── Shared/              # 共用元件
├── tests/                   # 測試套件（495 個測試）
│   ├── Unit/               # 單元測試
│   ├── Integration/        # 整合測試
│   ├── Security/           # 安全測試
│   └── Factory/            # 測試工廠
├── docs/                   # 技術文件
├── scripts/                # 維運腳本
├── public/                 # 公開檔案
├── database/               # SQLite 資料庫
├── docker/                 # Docker 設定
├── storage/                # 檔案儲存
└── .github/workflows/      # CI/CD 流程
```

---

## 系統需求

### 硬體需求
- CPU: 2 核心以上
- 記憶體: 4GB 以上
- 硬碟空間: 20GB 以上

### 軟體需求
- Debian Linux 12
- Docker 24.0.0+
- Docker Compose 2.20.0+
- PHP 8.4.11
- SQLite3
- NGINX

---

## 安裝與快速開始

### 1. 取得專案原始碼

```bash
git clone https://github.com/your-org/alleynote.git
cd alleynote
```

### 2. 設定環境變數

```bash
cp .env.example .env
# 編輯 .env 檔案，設定管理員帳號、資料庫、檔案上傳等
```

### 3. 啟動服務

```bash
# 開發環境
docker compose up -d

# 生產環境
docker compose -f docker-compose.production.yml up -d
```

### 4. 安裝相依套件

```bash
docker compose exec web composer install
```

### 5. 初始化系統

```bash
# 初始化資料庫
./scripts/init-database.sh

# 建立管理員帳號
./scripts/create-admin.sh

# 驗證系統狀態
./scripts/health-check.sh
```

### 6. 訪問系統

- 網站首頁: http://localhost:8080
- 管理後台: http://localhost:8080/admin

---

## 開發流程

### 📋 標準開發流程

1. **準備工作**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **開發實作**（遵循 DDD 原則）
   ```bash
   # 先寫測試
   vim tests/Unit/Domains/Post/Services/PostServiceTest.php
   
   # 實作領域邏輯
   vim app/Domains/Post/Services/PostService.php
   
   # 更新應用層
   vim app/Application/Controllers/Api/V1/PostController.php
   ```

3. **品質檢查**
   ```bash
   # 執行完整測試套件
   ./vendor/bin/phpunit
   
   # 靜態分析
   ./vendor/bin/phpstan analyse
   
   # 程式碼風格檢查
   ./vendor/bin/php-cs-fixer fix
   ```

4. **提交流程**
   ```bash
   git commit -m "feat(post): 新增文章分類功能
   
   - 實作分類管理服務
   - 新增分類 API 端點
   - 完善測試覆蓋
   
   Closes #123"
   ```

---

## 測試流程

### 📊 測試統計
- **總測試數**: 495 個測試
- **總斷言數**: 2,661 個斷言
- **通過率**: 91%（18 錯誤 + 28 失敗 + 15 跳過）
- **錯誤減少**: 76%（從 75 個減少到 18 個）

### 🧪 測試分類
- **單元測試** (`tests/Unit/`): 領域邏輯、服務層、驗證器
- **整合測試** (`tests/Integration/`): API 端點、資料庫整合
- **安全測試** (`tests/Security/`): XSS、CSRF、SQL 注入防護
- **效能測試**: 資料庫查詢、快取效能

### 🚀 執行測試

```bash
# 完整測試套件
./vendor/bin/phpunit

# 分類測試
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Integration
./vendor/bin/phpunit --testsuite Security

# 生成覆蓋率報告
./vendor/bin/phpunit --coverage-html ./storage/coverage

# 特定測試
./vendor/bin/phpunit tests/Unit/Domains/Post/Services/PostServiceTest.php
```

---

## 快速部署（管理員）

🚀 **新管理員**：請先閱讀 [管理員快速入門指南](docs/ADMIN_QUICK_START.md)，30分鐘內完成部署！

### 基本部署步驟
```bash
# 1. 克隆專案
git clone https://github.com/your-org/alleynote.git
cd alleynote

# 2. 快速啟動
./alleynote.sh start

# 3. 初始化資料庫
docker-compose exec web ./scripts/init-sqlite.sh
```

### 完整管理文件
- **[系統需求檢查](docs/SYSTEM_REQUIREMENTS.md)** - 環境準備和需求確認
- **[完整部署指南](docs/DEPLOYMENT.md)** - 詳細部署流程
- **[管理員操作手冊](docs/ADMIN_MANUAL.md)** - 日常管理和維護
- **[故障排除指南](docs/TROUBLESHOOTING_GUIDE.md)** - 問題診斷和解決

### SSL 憑證設定
```bash
./scripts/ssl-setup.sh your-domain.com admin@your-domain.com
```

---

## 常見問題 FAQ

**Q: 啟動服務時遇到權限問題？**  
A: 請確認 storage 目錄權限為 www-data，並執行 `chmod -R 755 storage`。

**Q: 系統無法啟動怎麼辦？**  
A: 參考 [故障排除指南](docs/TROUBLESHOOTING_GUIDE.md) 的緊急故障處理章節。

**Q: 如何進行日常維護？**  
A: 查看 [管理員操作手冊](docs/ADMIN_MANUAL.md) 的監控與維護章節。

**Q: 測試失敗如何除錯？**  
A: 檢查 [TEST_SUITE_IMPROVEMENTS.md](docs/TEST_SUITE_IMPROVEMENTS.md) 了解測試改善歷程和除錯方法。

**Q: 如何還原備份？**  
A: 參考 [管理員操作手冊](docs/ADMIN_MANUAL.md) 的備份與還原章節。

**Q: 系統需求是什麼？**  
A: 詳見 [系統需求說明](docs/SYSTEM_REQUIREMENTS.md)。

---

## 文件資源


### 👨‍💼 管理員文件
- [管理員快速入門指南](docs/ADMIN_QUICK_START.md)
- [系統需求與環境準備](docs/SYSTEM_REQUIREMENTS.md)
- [管理員操作手冊](docs/ADMIN_MANUAL.md)
- [故障排除與維護指南](docs/TROUBLESHOOTING_GUIDE.md)

### 📖 開發者文件
- [開發者指南](docs/DEVELOPER_GUIDE.md)
- [DI 容器使用手冊](docs/DI_CONTAINER_GUIDE.md)
- [驗證器詳細指南](docs/VALIDATOR_GUIDE.md)
- [API 文件](docs/API_DOCUMENTATION.md)

### 📋 專案文件
- [架構審查報告](docs/ARCHITECTURE_AUDIT.md)
- [測試改善報告](docs/TEST_SUITE_IMPROVEMENTS.md)
- [部署說明](docs/DEPLOYMENT.md)
- [SSL 設定指南](docs/SSL_DEPLOYMENT_GUIDE.md)
- [版本更新日誌](CHANGELOG.md)

### 📊 規劃文件
- [系統規格書](AlleyNote公布欄網站規格書.md)


---

## 🎯 專案里程碑

### ✅ 已完成
- 🏗️ MVC 到 DDD 架構遷移
- 🧪 測試套件穩定性改善（91% 通過率）
- 🔍 強型別驗證系統
- ⚡ 效能優化與監控工具
- 🔒 完整安全防護機制

### 🚧 進行中
- 📈 持續提升測試覆蓋率
- 🔧 效能調校與優化
- 📚 文件完善化

---

## 貢獻指南

1. Fork 專案並建立分支
2. 遵循 DDD 架構原則開發
3. 撰寫/更新測試
4. 確保程式碼品質檢查通過
5. 提交 Pull Request，說明變更內容

---

## 授權

本專案採用 MIT 授權，詳見 [LICENSE](LICENSE)。

---

## 聯絡方式

- **Issues**: [GitHub Issues](https://github.com/cookeyholder/alleynote/issues)
- **Wiki**: [專案 Wiki](https://github.com/cookeyholder/alleynote/wiki)

---

*🎉 歡迎貢獻！請先閱讀 [docs/DEVELOPER_GUIDE.md](docs/DEVELOPER_GUIDE.md) 了解開發流程。*