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