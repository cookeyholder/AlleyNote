# AlleyNote 公布欄系統

## 專案架構

本專案採用三層式架構，遵循 SOLID 原則與領域驅動設計（DDD）概念進行開發。

### 文章系統開發思路

1. **資料存取層**
   - PostRepository：負責文章的 CRUD 操作
   - TagRepository：負責標籤的管理
   - AttachmentRepository：負責附件的管理
   - ViewRepository：負責觀看記錄的管理

2. **業務邏輯層**
   - PostService：實作文章相關業務邏輯
     - 文章新增/編輯/刪除
     - 文章置頂功能
     - 觀看次數統計
   - TagService：實作標籤相關業務邏輯
     - 標籤樹狀結構管理
     - 標籤關聯管理
   - AttachmentService：實作附件相關業務邏輯
     - 檔案上傳與儲存
     - 檔案類型驗證
     - 儲存空間管理

3. **表現層**
   - PostController：處理文章相關請求
   - TagController：處理標籤相關請求
   - AttachmentController：處理附件相關請求

### 開發步驟

1. **基礎設施準備**
   - [x] 建立輔助函式
   - [ ] 設定資料庫遷移
   - [ ] 建立測試資料工廠

2. **資料存取層開發**
   - [ ] 實作 Repository 介面
   - [ ] 實作資料表 CRUD 操作
   - [ ] 撰寫單元測試

3. **業務邏輯層開發**
   - [ ] 實作業務邏輯服務
   - [ ] 實作驗證規則
   - [ ] 實作檔案處理邏輯
   - [ ] 撰寫單元測試

4. **表現層開發**
   - [ ] 實作 API 端點
   - [ ] 實作請求驗證
   - [ ] 實作回應格式
   - [ ] 撰寫整合測試

### 編碼規範

1. **命名規範**
   - 類別：Pascal Case (如：PostService)
   - 方法：Camel Case (如：createPost)
   - 變數：Camel Case (如：postCount)
   - 常數：全大寫底線分隔 (如：MAX_FILE_SIZE)

2. **文件規範**
   - 所有公開方法必須有 PHPDoc 註解
   - 註解必須包含參數型別、回傳型別與說明
   - 複雜的業務邏輯必須加上說明註解

3. **測試規範**
   - 單元測試覆蓋率要求：
     - Repository：90%
     - Service：85%
     - Controller：80%
   - 每個測試都要有明確的測試目的說明
   - 使用 arrange-act-assert 模式組織測試程式碼

## 開始開發

請依照以下順序進行開發：

1. 建立資料表遷移檔案
2. 實作資料存取層
3. 實作業務邏輯層
4. 實作表現層
5. 撰寫自動化測試
6. 進行程式碼審查

## 文章系統資料庫交易功能實作

### 功能說明
實作文章系統的資料庫交易（Transaction）功能，確保文章和標籤的關聯操作具有原子性（Atomicity）。

### 實作重點
1. 交易控制
   - 使用 PDO 的 beginTransaction()、commit() 和 rollBack() 方法
   - 在 try-catch 區塊中處理交易，確保錯誤時能正確回溯

2. 測試案例
   - testShouldRollbackOnTagAssignmentError：驗證標籤指派失敗時的交易回溯
   - testShouldCommitOnTagAssignmentSuccess：驗證標籤指派成功時的交易提交

3. 測試方法
   - 使用計數器追蹤資料表行數變化
   - 透過無效的標籤 ID 模擬失敗情境
   - 使用有效的標籤 ID 測試成功情境

4. 程式碼優化
   - 將交易邏輯封裝在 Repository 層
   - 使用輔助方法簡化測試程式碼
   - 保持程式碼的可讀性和可維護性

### 注意事項
1. 在交易中避免執行耗時操作
2. 確保所有資料表支援交易（InnoDB 引擎）
3. 維持適當的錯誤處理和記錄機制
4. 注意交易範圍的控制，避免過大的交易範圍

# AlleyNote 公布欄網站

## 專案說明
現代化的公布欄網站系統，採用 PHP 8.4.5 與 SQLite3 資料庫，透過 Docker 容器化技術在 Debian Linux 12 上執行，並使用 NGINX 作為網頁伺服器。

## 技術堆疊
- 後端框架：PHP 8.4.5
- 資料庫：SQLite3
- 前端技術：HTML5, CSS3, JavaScript, Tailwind CSS
- 開發工具：Docker, Visual Studio Code
- 測試框架：PHPUnit

## 分支開發紀錄

### feature/post-repository
實作文章系統的資料存取層，主要完成：

1. PostRepository 實作
   - 採用 PDO 實作資料庫操作
   - 實作完整的 CRUD 操作
   - 支援文章置頂功能
   - 實作觀看次數追蹤
   - 支援標籤系統

2. 單元測試重點：
   - 使用 SQLite 記憶體資料庫進行測試
   - 測試覆蓋率達到 90% 以上
   - 測試案例包含：
     - 基本 CRUD 操作
     - 文章置頂功能
     - 觀看次數追蹤
     - 標籤關聯操作
     - 分頁功能
     - 錯誤處理

3. 資料庫交易測試：
   - 確保資料一致性
   - 測試交易復原機制
   - 驗證並發處理

### feature/post-crud-implementation 分支
實作文章系統的資料存取層，完成了完整的 CRUD 操作。主要實作內容包含：

1. 實作重點：
   - 使用 PDO 預處理陳述式確保資料安全
   - 實作完整的資料驗證機制
   - 加入關鍵欄位保護功能
   - 實作自動流水號與時間戳記處理
   - 支援文章置頂功能
   - 完整的交易管理
   - 標籤關聯管理功能

2. 安全性考量：
   - 使用預處理陳述式防止 SQL 注入
   - 所有輸入資料進行檢查與驗證
   - 實作欄位存取控制，防止關鍵欄位被非法修改
   - 自動產生 UUID 確保唯一性

3. 效能優化：
   - 最佳化 SQL 查詢語句
   - 實作分頁機制減少資料載入量
   - 使用適當的索引提升查詢效能
   - 批次處理標籤關聯操作

4. 程式碼品質：
   - 完整的方法文件註解
   - 清晰的變數與方法命名
   - 單一職責原則的實作
   - 良好的錯誤處理機制

5. 測試覆蓋：
   - 完整的單元測試
   - 異常情況測試
   - 邊界條件測試
   - 資料完整性測試

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