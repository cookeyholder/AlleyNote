# AlleyNote 公布欄網站

## feature/autoload-setup 分支實作說明

### 實作目標
設定專案的自動載入規則，建立符合 PSR-4 標準的基礎目錄結構。

### 實作內容
1. 建立基礎目錄結構
   - src/ 目錄：存放專案的主要程式碼
   - tests/ 目錄：存放測試程式碼

2. Composer 設定
   - 設定 PSR-4 自動載入規則
   - App\\ 命名空間對應到 src/ 目錄
   - Tests\\ 命名空間對應到 tests/ 目錄

3. 相依套件設定
   - PHP 8.2 以上版本要求
   - PDO 與 PDO_SQLite 擴充套件需求
   - 開發環境相依套件：
     - PHPUnit: 單元測試框架
     - Mockery: 測試替身框架
     - PHPStan: 靜態程式碼分析工具
     - PHP_CodeSniffer: 程式碼風格檢查工具

### 相關指令
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