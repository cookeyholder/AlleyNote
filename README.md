# AlleyNote 公布欄網站

本專案是一個使用 PHP 8.2 開發的現代化公布欄系統，採用三層式架構設計，並以 SQLite 作為資料庫。

## 技術棧

- PHP 8.2+
- SQLite 3
- Nginx
- Docker & Docker Compose

## 目錄結構

```
alleynote/
├── app/                    # 應用程式核心程式碼
│   ├── Controllers/       # 控制器類別
│   ├── Services/         # 業務邏輯服務
│   ├── Repositories/     # 資料存取層
│   └── Models/           # 資料模型
├── config/               # 設定檔案
├── database/            # 資料庫相關
│   ├── migrations/      # 資料庫遷移檔案
│   └── seeders/        # 資料填充類別
├── docker/              # Docker 設定檔
│   ├── nginx/          # Nginx 設定
│   └── php/            # PHP 設定
├── public/              # 公開存取目錄
│   ├── css/            # 編譯後的 CSS
│   ├── js/             # 編譯後的 JavaScript
│   ├── images/         # 圖片資源
│   └── assets/         # 其他靜態資源
├── resources/           # 前端資源
│   ├── css/            # CSS 源碼
│   ├── js/             # JavaScript 源碼
│   ├── views/          # 視圖模板
│   └── lang/           # 多語系檔案
├── routes/             # 路由設定
├── storage/            # 儲存空間
│   ├── app/           # 應用程式檔案
│   │   ├── public/   # 公開檔案
│   │   └── private/  # 私密檔案
│   ├── framework/     # 框架暫存檔案
│   │   ├── cache/    # 快取檔案
│   │   ├── sessions/ # 會話檔案
│   │   └── views/    # 視圖快取
│   └── logs/         # 日誌檔案
├── tests/              # 測試程式碼
│   ├── Unit/          # 單元測試
│   ├── Feature/       # 功能測試
│   └── Integration/   # 整合測試
├── .gitignore         # Git 忽略清單
├── composer.json      # Composer 設定
├── docker-compose.yml # Docker Compose 設定
└── README.md          # 專案說明文件
```

## 安裝說明

1. 複製專案
```bash
git clone [repository_url] alleynote
cd alleynote
```

2. 安裝相依套件
```bash
composer install
```

3. 啟動 Docker 環境
```bash
docker-compose up -d
```

4. 初始化資料庫
```bash
php artisan migrate
php artisan db:seed
```

## 開發指令

```bash
# 執行測試
composer test

# 執行程式碼分析
composer analyse

# 檢查程式碼風格
composer check-style

# 自動修正程式碼風格
composer fix-style
```

## feature/directory-structure 分支實作說明

### 實作目標
按照專案規格書建立完整的目錄結構，為後續開發做好準備。

### 實作內容
1. 建立完整的目錄樹狀結構
2. 新增各目錄的用途說明
3. 更新 README.md 加入完整的專案說明
4. 確保目錄結構符合三層式架構設計

### 目錄說明
- `app/`: 存放核心應用程式程式碼，採用三層式架構
- `config/`: 集中管理所有設定檔案
- `database/`: 資料庫相關檔案，包含遷移和填充程式
- `public/`: 對外公開的檔案，為網站根目錄
- `resources/`: 前端資源檔案
- `storage/`: 儲存空間，用於檔案上傳和快取
- `tests/`: 測試程式碼，包含單元、功能和整合測試

## 分支實作說明

### feat/user-repository 分支

#### 實作重點
1. **測試驅動開發流程**
   - 先撰寫測試案例，再實作功能
   - 使用 PHPUnit 測試框架
   - 使用 SQLite 記憶體資料庫進行測試

2. **資料庫設計考量**
   - 使用雙主鍵設計（id + uuid）
   - 確保 username 和 email 唯一性
   - 密碼使用 Argon2id 演算法進行雜湊處理
   - 使用 PDO 預處理陳述式防止 SQL 注入

3. **測試案例設計**
   - 測試使用者建立功能
   - 驗證唯一性約束（username, email）
   - 測試查詢功能
   - 確保適當的錯誤處理

4. **程式碼品質確保**
   - 使用型別提示增加程式碼可靠性
   - 遵循 PSR-4 自動載入規範
   - 確保測試覆蓋率
   - 實作清晰的錯誤處理機制

#### 技術細節
- 使用 PHP 8.4.5
- SQLite 用於測試環境
- PDO 用於資料庫操作
- PHPUnit 10.5.x 測試框架

### feat/auth-service 分支

#### 實作重點
1. **認證服務設計**
   - 實作使用者註冊功能
   - 實作使用者登入功能
   - 實作密碼驗證功能
   - 實作帳號狀態檢查

2. **資料驗證機制**
   - 使用者名稱格式驗證
   - 電子郵件格式驗證
   - 密碼強度要求檢查
   - 提供明確的錯誤訊息

3. **安全性考量**
   - 密碼使用 Argon2id 演算法雜湊
   - 移除回應中的敏感資訊
   - 實作登入失敗處理
   - 記錄最後登入時間

4. **測試驅動開發**
   - 使用 Mockery 模擬相依物件
   - 完整的測試案例涵蓋
   - 驗證所有錯誤情況
   - 確保程式碼品質

#### 技術細節
- 使用依賴注入實現鬆散耦合
- 遵循 SOLID 原則
- 實作完整的錯誤處理
- 使用型別提示確保型別安全

### feat/auth-controller 分支

#### 實作重點
1. **API 端點設計**
   - 實作 RESTful API 端點
   - 遵循 PSR-7 介面規範
   - 標準化的 JSON 回應格式
   - HTTP 狀態碼正確使用

2. **整合測試設計**
   - 模擬 HTTP 請求與回應
   - 使用測試替身隔離相依性
   - 驗證完整的認證流程
   - 確保錯誤處理機制

3. **錯誤處理機制**
   - 一致的錯誤回應格式
   - HTTP 狀態碼對應
   - 適當的錯誤訊息
   - 安全性考量的錯誤處理

4. **程式碼品質確保**
   - 遵循 SOLID 原則
   - 依賴注入設計
   - 清晰的程式碼結構
   - 完整的測試覆蓋

#### 技術細節
- 使用 PSR-7 請求回應介面
- 採用依賴注入設計模式
- JSON 格式的 API 回應
- 整合測試驗證