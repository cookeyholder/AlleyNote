# JWT 認證系統開發待辦清單

**專案**: AlleyNote JWT 認證系統  
**建立日期**: 2025-08-26  
**更新日期**: 2025-08-26  

## 📋 任務概覽

總任務數: **24 項**  
狀態統計:
- ⏳ 未開始: 16 項
- 🔄 進行中: 0 項  
- ✅ 已完成: 8 項

---

## 🏗️ Phase 1: 基礎建設 (6 項任務)

### 1.1 安裝和配置 JWT 函式庫
- **優先級**: 高
- **預估時間**: 30分鐘
- **狀態**: ✅ 已完成
- **描述**: 
  - 安裝 `firebase/php-jwt` 套件
  - 更新 composer.json 和 composer.lock
  - 在 Docker 容器中測試套件載入
- **驗收標準**:
  - ✅ firebase/php-jwt 套件成功安裝
  - ✅ 可以在程式碼中成功 import JWT 相關類別
  - ✅ Docker 容器內可以正常使用 JWT 功能
- **相依任務**: 無
- **完成日期**: 2025-08-26
- **檔案異動**: `composer.json`, `composer.lock`

### 1.2 建立 JWT 相關資料庫遷移
- **優先級**: 高
- **預估時間**: 45分鐘
- **狀態**: ✅ 已完成
- **描述**:
  - 建立 `refresh_tokens` 表的遷移檔案
  - 建立 `token_blacklist` 表的遷移檔案
  - 建立必要的索引
  - 執行遷移並驗證
- **驗收標準**:
  - ✅ refresh_tokens 表結構正確建立
  - ✅ token_blacklist 表結構正確建立
  - ✅ 所有必要索引都已建立
  - ✅ 外鍵約束正確設定
  - ✅ 遷移可以正常 rollback
- **相依任務**: 無
- **檔案異動**: `database/migrations/xxx_create_refresh_tokens_table.php`, `database/migrations/xxx_create_token_blacklist_table.php`
- **完成日期**: 2025-08-26

### 1.3 建立 JWT 配置管理
- **優先級**: 高
- **預估時間**: 30分鐘
- **狀態**: ✅ 已完成
- **描述**:
  - 在 .env 中加入 JWT 相關配置 (RS256 演算法)
  - 建立 JwtConfig 類別管理配置
  - 實作配置驗證和預設值
  - 處理 RSA 金鑰對的載入和驗證
- **驗收標準**:
  - ✅ .env 檔案包含所有 JWT 必要配置 (RS256 金鑰對)
  - ✅ JwtConfig 類別可以正確載入所有配置
  - ✅ 缺少必要配置時會拋出適當例外
  - ✅ RSA 金鑰對格式驗證正確
- **相依任務**: 無
- **檔案異動**: `.env`, `app/Shared/Config/JwtConfig.php`
- **完成日期**: 2025-08-26

### 1.4 建立 JWT Value Objects
- **優先級**: 高
- **預估時間**: 60分鐘
- **狀態**: ✅ 已完成
- **描述**:
  - 建立 `JwtPayload` Value Object
  - 建立 `TokenPair` Value Object
  - 建立 `DeviceInfo` Value Object
  - 建立 `TokenBlacklistEntry` Value Object
  - 加入適當的驗證和方法
- **驗收標準**:
  - ✅ 所有 Value Object 都是 immutable
  - ✅ 包含適當的驗證邏輯
  - ✅ 實作 JsonSerializable 介面
  - ✅ 包含 equals 和 toString 方法
  - ✅ 有完整的 PHPDoc 註解
- **相依任務**: 無
- **檔案異動**: `app/Domains/Auth/ValueObjects/JwtPayload.php`, `app/Domains/Auth/ValueObjects/TokenPair.php`, `app/Domains/Auth/ValueObjects/DeviceInfo.php`, `app/Domains/Auth/ValueObjects/TokenBlacklistEntry.php`
- **完成日期**: 2025-08-26
- **測試覆蓋率**: 129 個測試，420 個斷言，100% 通過

### 1.5 建立 JWT 領域例外類別
- **優先級**: 中
- **預估時間**: 30分鐘
- **狀態**: ✅ 已完成
- **描述**:
  - 建立 JwtException 基礎類別
  - 建立特定 JWT 例外類別 (TokenExpiredException, InvalidTokenException, 等)
  - 實作錯誤碼和訊息管理
- **驗收標準**:
  - ✅ 所有例外類別都繼承自適當的基礎類別
  - ✅ 包含清楚的錯誤訊息和錯誤碼
  - ✅ 支援多語言錯誤訊息
  - ✅ 例外類別命名符合專案規範
- **相依任務**: 無
- **檔案異動**: `app/Domains/Auth/Exceptions/JwtException.php`, `app/Domains/Auth/Exceptions/TokenExpiredException.php`, `app/Domains/Auth/Exceptions/InvalidTokenException.php`, 等
- **完成日期**: 2025-01-25
- **測試覆蓋率**: 128 個測試，604 個斷言，100% 通過

### 1.6 建立 JWT 領域介面
- **優先級**: 中
- **預估時間**: 60分鐘
- **狀態**: ✅ 已完成
- **描述**:
  - 定義 JwtTokenServiceInterface 
  - 定義 RefreshTokenRepositoryInterface
  - 定義 TokenBlacklistRepositoryInterface
  - 撰寫介面的單元測試
- **驗收標準**:
  - ✅ 介面方法簽名清楚且完整
  - ✅ 包含詳細的 PHPDoc 註解
  - ✅ 方法參數和回傳類型正確定義
  - ✅ 介面設計符合 SOLID 原則
  - ✅ 介面單元測試完整覆蓋（74測試，552斷言）
- **相依任務**: 1.4 (需要 Value Objects)
- **完成日期**: 2025-08-26
- **檔案異動**: `app/Domains/Auth/Contracts/JwtTokenServiceInterface.php`, `app/Domains/Auth/Contracts/RefreshTokenRepositoryInterface.php`, `app/Domains/Auth/Contracts/TokenBlacklistRepositoryInterface.php`, `tests/Unit/Domains/Auth/Contracts/*`

---

## 🔧 Phase 2: 核心實作 (8 項任務)

### 2.1 實作 Firebase JWT Provider
- **優先級**: 高
- **預估時間**: 90分鐘
- **狀態**: ✅ 已完成
- **描述**:
  - 建立 FirebaseJwtProvider 包裝類別
  - 實作 RS256 token 產生、驗證、解析功能
  - 處理所有 JWT 相關例外
  - 加入詳細的日誌記錄
- **驗收標準**:
  - ✅ 可以正確產生 RS256 JWT access token
  - ✅ 可以正確產生 RS256 JWT refresh token
  - ✅ 可以正確驗證和解析 token
  - ✅ 正確處理所有例外情況
  - ✅ 包含完整的單元測試
- **相依任務**: 1.1, 1.3, 1.5
- **完成日期**: 2025-08-26
- **檔案異動**: `app/Infrastructure/Auth/Jwt/FirebaseJwtProvider.php`, `app/Domains/Auth/Exceptions/JwtConfigurationException.php`, `app/Domains/Auth/Exceptions/TokenValidationException.php`, `app/Domains/Auth/Exceptions/TokenParsingException.php`, `tests/Unit/Infrastructure/Auth/Jwt/FirebaseJwtProviderTest.php`
- **測試覆蓋率**: 26 個測試，53 個斷言，100% 通過

### 2.2 實作 JwtTokenService
- **優先級**: 高
- **預估時間**: 120分鐘
- **狀態**: ✅ 已完成
- **描述**:
  - 實作 JwtTokenService 類別，實現 JwtTokenServiceInterface
  - 整合 FirebaseJwtProvider 和 Repository 層
  - 實作 token 產生、驗證、撤銷功能
  - 實作安全性檢查（IP、裝置、使用者歸屬）
  - 建立完整的單元測試
- **驗收標準**:
  - ✅ generateTokenPair() 可以產生有效的 JWT token pair
  - ✅ validateAccessToken() 可以正確驗證 token
  - ✅ refreshTokens() 可以使用 refresh token 產生新 token
  - ✅ revokeToken() 和 revokeAllUserTokens() 可以正確撤銷 token
  - ✅ extractPayload() 可以提取 token payload（不驗證）
  - ✅ isTokenOwnedBy() 和 isTokenFromDevice() 安全性檢查正確
  - ✅ getTokenRemainingTime() 和 isTokenNearExpiry() 時效檢查正確
  - ✅ 所有工具方法（getAlgorithm, getTTL 等）正確實作
  - ✅ 單元測試涵蓋率 >= 95%
  - ✅ 所有例外情境都有適當處理
  - ✅ 與 FirebaseJwtProvider 整合正確
- **相依任務**: 1.4, 1.6, 2.1
- **檔案異動**: `app/Domains/Auth/Services/JwtTokenService.php`, `tests/Unit/Domains/Auth/Services/JwtTokenServiceTest.php`
- **完成日期**: 2025-08-26

### 2.3 實作 RefreshToken Entity
- **優先級**: 高
- **預估時間**: 60分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 建立 RefreshToken Entity 類別
  - 實作業務邏輯方法
  - 加入驗證規則
  - 實作 JSON 序列化
- **驗收標準**:
  - ✅ Entity 包含所有必要屬性
  - ✅ 實作適當的業務邏輯方法
  - ✅ 包含資料驗證規則
  - ✅ 正確實作 JsonSerializable
  - ✅ 包含完整的單元測試
- **相依任務**: 1.4
- **檔案異動**: `app/Domains/Auth/Entities/RefreshToken.php`

### 2.4 實作 RefreshTokenRepository
- **優先級**: 高
- **預估時間**: 90分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 實作 RefreshTokenRepositoryInterface
  - 建立 CRUD 操作方法
  - 實作查詢和篩選功能
  - 加入快取機制 (可選)
- **驗收標準**:
  - ✅ 所有 CRUD 操作正常運作
  - ✅ 可以根據 JTI 查詢 refresh token
  - ✅ 可以根據使用者 ID 查詢所有 token
  - ✅ 可以清理過期的 token
  - ✅ 包含完整的單元測試
- **相依任務**: 1.2, 1.6, 2.3
- **檔案異動**: `app/Infrastructure/Auth/Repositories/RefreshTokenRepository.php`

### 2.5 實作 TokenBlacklistRepository
- **優先級**: 高
- **預估時間**: 75分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 實作 TokenBlacklistRepositoryInterface
  - 建立黑名單 CRUD 操作
  - 實作高效的查詢機制
  - 加入自動清理功能
- **驗收標準**:
  - ✅ 可以新增 token 到黑名單
  - ✅ 可以快速檢查 token 是否在黑名單
  - ✅ 可以批次處理黑名單操作
  - ✅ 自動清理過期的黑名單項目
  - ✅ 包含完整的單元測試
- **相依任務**: 1.2, 1.4, 1.6
- **檔案異動**: `app/Infrastructure/Auth/Repositories/TokenBlacklistRepository.php`

### 2.6 實作 RefreshTokenService
- **優先級**: 高
- **預估時間**: 90分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 建立 RefreshTokenService 領域服務
  - 實作 refresh token 的業務邏輯
  - 處理 token 輪轉和清理
  - 實作多裝置管理
- **驗收標準**:
  - ✅ 可以建立和儲存 refresh token
  - ✅ 可以使用 refresh token 產生新的 access token
  - ✅ 可以撤銷單個或所有裝置的 token
  - ✅ 自動清理過期和無效的 token
  - ✅ 包含完整的單元測試
- **相依任務**: 2.2, 2.3, 2.4
- **檔案異動**: `app/Domains/Auth/Services/RefreshTokenService.php`

### 2.7 實作 TokenBlacklistService
- **優先級**: 中
- **預估時間**: 60分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 建立 TokenBlacklistService 領域服務
  - 實作黑名單管理邏輯
  - 提供便利的操作方法
  - 最佳化查詢效能
- **驗收標準**:
  - ✅ 可以將 token 加入黑名單
  - ✅ 可以檢查 token 是否在黑名單
  - ✅ 可以批次處理黑名單操作
  - ✅ 提供統計和監控功能
  - ✅ 包含完整的單元測試
- **相依任務**: 2.5
- **檔案異動**: `app/Domains/Auth/Services/TokenBlacklistService.php`

### 2.8 更新現有 AuthService
- **優先級**: 高
- **預估時間**: 120分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 修改 AuthService 整合 JWT 認證
  - 保持向後相容性
  - 實作新的登入、登出流程
  - 加入設定開關控制認證方式
- **驗收標準**:
  - ✅ 登入成功時回傳 JWT token pair
  - ✅ 保持現有介面不變
  - ✅ 支援新舊認證方式共存
  - ✅ 正確處理所有錯誤情況
  - ✅ 包含完整的單元測試
- **相依任務**: 2.2, 2.6, 2.7
- **檔案異動**: `app/Domains/Auth/Services/AuthService.php`

---

## 🚪 Phase 3: Middleware 和控制器 (4 項任務)

### 3.1 實作 JwtAuthenticationMiddleware
- **優先級**: 高
- **預估時間**: 120分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 建立 JWT 認證中介軟體
  - 實作 token 提取和驗證
  - 處理認證失敗情況
  - 加入請求上下文資訊
- **驗收標準**:
  - ✅ 可以從 Header 或 Query 參數提取 token
  - ✅ 正確驗證 token 有效性
  - ✅ 檢查 token 是否在黑名單
  - ✅ 將使用者資訊注入到請求中
  - ✅ 包含完整的單元測試
- **相依任務**: 2.2, 2.7
- **檔案異動**: `app/Application/Middleware/JwtAuthenticationMiddleware.php`

### 3.2 實作 JwtAuthorizationMiddleware
- **優先級**: 中
- **預估時間**: 90分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 建立 JWT 授權中介軟體
  - 實作角色和權限檢查
  - 支援動態權限驗證
  - 提供靈活的配置選項
- **驗收標準**:
  - ✅ 可以驗證使用者角色
  - ✅ 可以驗證使用者權限
  - ✅ 支援複雜的權限組合
  - ✅ 提供清楚的錯誤訊息
  - ✅ 包含完整的單元測試
- **相依任務**: 3.1
- **檔案異動**: `app/Application/Middleware/JwtAuthorizationMiddleware.php`

### 3.3 更新 AuthController JWT 端點
- **優先級**: 高
- **預估時間**: 150分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 更新 login 端點使用 JWT
  - 實作 refresh token 端點
  - 更新 logout 端點支援黑名單
  - 更新 me 端點使用 JWT 驗證
- **驗收標準**:
  - ✅ login 端點回傳 JWT token pair
  - ✅ refresh 端點可以刷新 access token
  - ✅ logout 端點將 token 加入黑名單
  - ✅ me 端點從 JWT 取得使用者資訊
  - ✅ 包含完整的整合測試
- **相依任務**: 2.8, 3.1
- **檔案異動**: `app/Application/Controllers/Api/V1/AuthController.php`

### 3.4 更新路由配置
- **優先級**: 高
- **預估時間**: 60分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 在需要認證的路由加入 JWT middleware
  - 配置不同權限等級的路由組
  - 更新路由文件和註解
  - 測試所有路由配置
- **驗收標準**:
  - ✅ 所有需要認證的 API 都有 JWT middleware
  - ✅ 不同角色的路由正確配置授權
  - ✅ 公開路由不受 middleware 影響
  - ✅ 路由配置文件清楚易懂
- **相依任務**: 3.1, 3.2
- **檔案異動**: `config/routes.php`, 相關路由檔案

---

## 🧪 Phase 4: 測試和文件 (6 項任務)

### 4.1 撰寫 JWT 核心功能單元測試
- **優先級**: 高
- **預估時間**: 180分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 為所有 JWT 相關服務撰寫單元測試
  - 測試正常流程和錯誤情況
  - 實作測試輔助工具和假物件
  - 確保測試覆蓋率 >= 95%
- **驗收標準**:
  - ✅ JwtTokenService 單元測試完整
  - ✅ RefreshTokenService 單元測試完整
  - ✅ TokenBlacklistService 單元測試完整
  - ✅ 所有錯誤情況都有測試
  - ✅ 測試覆蓋率達標
- **相依任務**: Phase 2 所有任務
- **檔案異動**: `tests/Unit/Domains/Auth/Services/`

### 4.2 撰寫 Repository 單元測試
- **優先級**: 高
- **預估時間**: 120分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 為所有 Repository 撰寫單元測試
  - 使用記憶體資料庫進行測試
  - 測試所有 CRUD 操作
  - 測試查詢和篩選功能
- **驗收標準**:
  - ✅ RefreshTokenRepository 測試完整
  - ✅ TokenBlacklistRepository 測試完整
  - ✅ 所有資料庫操作都有測試
  - ✅ 測試資料隔離正確
  - ✅ 測試可以獨立執行
- **相依任務**: 2.4, 2.5
- **檔案異動**: `tests/Unit/Infrastructure/Auth/Repositories/`

### 4.3 撰寫 Middleware 單元測試
- **優先級**: 高
- **預估時間**: 150分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 為 JWT middleware 撰寫單元測試
  - 測試不同 token 格式和狀態
  - 測試認證和授權流程
  - 模擬各種錯誤情況
- **驗收標準**:
  - ✅ JwtAuthenticationMiddleware 測試完整
  - ✅ JwtAuthorizationMiddleware 測試完整
  - ✅ 所有認證情況都有測試
  - ✅ 所有授權情況都有測試
  - ✅ 錯誤處理測試完整
- **相依任務**: 3.1, 3.2
- **檔案異動**: `tests/Unit/Application/Middleware/`

### 4.4 撰寫 API 整合測試
- **優先級**: 高
- **預估時間**: 180分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 為所有 Auth API 撰寫整合測試
  - 測試完整的認證流程
  - 測試 API 回應格式
  - 測試錯誤處理和狀態碼
- **驗收標準**:
  - ✅ 登入 API 整合測試完整
  - ✅ 刷新 token API 整合測試完整
  - ✅ 登出 API 整合測試完整
  - ✅ 使用者資訊 API 整合測試完整
  - ✅ 所有錯誤情況都有測試
- **相依任務**: 3.3
- **檔案異動**: `tests/Integration/Api/V1/AuthControllerTest.php`

### 4.5 效能和安全性測試
- **優先級**: 中
- **預估時間**: 120分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 測試 JWT token 產生和驗證效能
  - 測試大量並發請求處理
  - 驗證安全性機制
  - 測試記憶體使用情況
- **驗收標準**:
  - ✅ Token 產生效能符合要求 (< 10ms)
  - ✅ Token 驗證效能符合要求 (< 5ms)
  - ✅ 並發處理測試通過
  - ✅ 記憶體使用在合理範圍
  - ✅ 安全性測試全部通過
- **相依任務**: Phase 2, Phase 3
- **檔案異動**: `tests/Performance/`, `tests/Security/`

### 4.6 更新文件和 Swagger 規格
- **優先級**: 中
- **預估時間**: 90分鐘
- **狀態**: ⏳ 未開始
- **描述**:
  - 更新 API 文件中的認證部分
  - 更新 Swagger/OpenAPI 規格
  - 撰寫 JWT 使用指南
  - 更新部署和配置文件
- **驗收標準**:
  - ✅ API 文件反映最新的 JWT 認證流程
  - ✅ Swagger 規格包含所有 JWT 相關端點
  - ✅ 使用指南清楚易懂
  - ✅ 部署文件包含 JWT 配置說明
- **相依任務**: 3.3
- **檔案異動**: `docs/API_DOCUMENTATION.md`, OpenAPI 規格檔案, `README.md`

---

## 📊 進度追蹤

### 依賴關係圖
```
Phase 1 (基礎建設) → Phase 2 (核心實作) → Phase 3 (Middleware) → Phase 4 (測試)
     ↓                    ↓                    ↓                    ↓
   1.1-1.6            2.1-2.8              3.1-3.4              4.1-4.6
```

### 關鍵路徑
1. 1.1 → 2.1 → 2.2 → 2.8 → 3.1 → 3.3 (核心認證流程)
2. 1.2 → 2.4 → 2.6 (Refresh token 功能)
3. 1.2 → 2.5 → 2.7 (Token 黑名單功能)

### 預估總時間
- **Phase 1**: 5.5 小時
- **Phase 2**: 12 小時  
- **Phase 3**: 8.5 小時
- **Phase 4**: 14 小時
- **總計**: **40 小時**

---

## 📝 注意事項

1. **TDD 開發**: 每個功能都先寫測試再寫實作
2. **Docker 環境**: 所有開發和測試都在 Docker 容器內進行
3. **程式碼品質**: 每次提交前都要執行 `composer ci`
4. **文件同步**: 程式碼變更時同步更新相關文件
5. **安全性**: 特別注意 JWT secret 管理和 token 安全性
6. **效能**: 關注 token 驗證和資料庫查詢效能
7. **向後相容**: 確保現有功能不受影響

---

## ✅ 完成標記

當某項任務完成時，請更新此文件：
1. 將狀態從 ⏳ 改為 ✅
2. 記錄實際花費時間
3. 更新相關的檔案清單
4. 提交 commit 時引用任務編號

**範例 commit message**: 
```
feat(auth): 實作 JWT 函式庫配置和安裝

- 安裝 firebase/php-jwt 套件
- 建立 JwtConfig 類別管理配置
- 在 Docker 環境中測試 JWT 功能

完成任務: 1.1, 1.3
參考: JWT_AUTHENTICATION_SPECIFICATION.md
```

---

**最後更新**: 2025-08-26 00:52:00  
**下次檢查**: 每完成一個 Phase 後更新