# AlleyNote 登入測試報告

## 📋 測試執行時間
- 日期：2025-01-05
- 測試工具：Playwright MCP + curl
- 測試環境：Docker Compose (localhost:8080)

## 🧪 測試步驟與結果

### 1. Playwright 瀏覽器測試

#### 步驟 1：訪問登入頁面
```
URL: http://localhost:8080/login
結果：✅ 頁面成功載入
```

**頁面截圖**：`login-page.png`
- 顯示登入表單
- 包含測試帳號提示：admin@example.com / password

#### 步驟 2：填寫登入資訊
```
Email: admin@example.com
Password: password
結果：✅ 表單填寫成功
```

**頁面截圖**：`login-filled.png`

#### 步驟 3：點擊登入按鈕
```
結果：❌ 登入失敗
錯誤訊息：「伺服器錯誤，請稍後再試」
HTTP 狀態碼：500 Internal Server Error
```

**錯誤截圖**：`login-error.png`

### 2. 網絡請求分析

```http
POST /api/auth/login HTTP/1.1
Host: localhost:8080
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

**回應**：
```http
HTTP/1.1 500 Internal Server Error
Content-Type: application/json

{
  "success": false,
  "error": "系統發生錯誤"
}
```

### 3. curl 命令測試

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

**結果**：
```json
{
  "success": false,
  "error": "系統發生錯誤"
}
```
HTTP 狀態碼：500

## 🔍 診斷結果

### ✅ 已確認正常的部分

1. **資料庫連線**
   - ✅ SQLite 資料庫檔案存在
   - ✅ 可以成功連線
   - ✅ users 表結構正確

2. **測試帳號**
   - ✅ 使用者存在（ID: 1, admin@example.com）
   - ✅ 密碼 hash 正確儲存
   - ✅ password_verify() 驗證成功

3. **前端介面**
   - ✅ 登入頁面正常顯示
   - ✅ 表單可以正常填寫
   - ✅ AJAX 請求正確發送

4. **JWT 環境變數**
   - ✅ JWT_ALGORITHM=RS256
   - ✅ JWT_PRIVATE_KEY 已設定
   - ✅ JWT_PUBLIC_KEY 已設定
   - ✅ JWT_ACCESS_TOKEN_TTL=3600
   - ✅ JWT_REFRESH_TOKEN_TTL=2592000

5. **PHP 類別**
   - ✅ Application 類別存在
   - ✅ AuthService 類別存在
   - ✅ AuthController 類別存在

### ❌ 問題分析

**問題**：後端 API 回傳 500 錯誤

**可能原因**：

1. **Exception 被 catch 但未記錄**
   - AuthController.php 第 464 行捕獲所有 Exception
   - 只回傳「系統發生錯誤」，沒有記錄實際錯誤訊息
   - 導致無法診斷真正的問題

2. **DI 容器或服務配置問題**
   - AuthenticationServiceInterface 實作可能有問題
   - JwtTokenServiceInterface 實作可能有問題
   - ActivityLoggingServiceInterface 實作可能有問題

3. **JWT Token 生成邏輯**
   - RSA 金鑰讀取可能失敗
   - Token 簽署過程可能出錯
   - 金鑰格式或路徑配置錯誤

4. **資料庫架構不匹配**
   - ORM/Entity 映射可能與實際表結構不符
   - 欄位名稱不一致（password vs password_hash）
   - 缺少必要的欄位（如 role 欄位）

## 📊 測試數據

### 資料庫內容
```sql
SELECT id, username, email FROM users WHERE email = 'admin@example.com';
```

| id | username | email              |
|----|----------|--------------------|
| 1  | admin    | admin@example.com  |

### 網絡請求統計
- 總請求數：16
- 成功請求：15
- 失敗請求：1 (登入 API)
- 404 錯誤：4 (圖示檔案)
- 500 錯誤：1 (登入 API)

## 🛠️ 建議解決方案

### 短期解決（除錯）

1. **修改 AuthController 記錄錯誤**
   ```php
   // 在 catch (Exception $e) 區塊中
   error_log('Login error: ' . $e->getMessage());
   error_log('Stack trace: ' . $e->getTraceAsString());
   ```

2. **啟用 PHP 錯誤日誌**
   ```bash
   # 在 docker-compose.yml 中設定
   PHP_ERROR_LOG=/var/www/html/storage/logs/php-errors.log
   ```

3. **檢查服務容器配置**
   ```bash
   # 檢查 DI 容器設定檔
   cat app/Application.php | grep -A 10 "AuthenticationService"
   ```

### 長期解決（修復）

1. **改善錯誤處理**
   - 在開發環境顯示詳細錯誤
   - 增加結構化日誌記錄
   - 使用 Monolog 或類似工具

2. **完整的單元測試**
   - 為 AuthenticationService 編寫測試
   - 為 JwtTokenService 編寫測試
   - 確保所有服務正常運作

3. **資料庫遷移**
   - 執行完整的 Phinx 遷移
   - 確保所有欄位都存在
   - 新增缺少的索引和約束

## 📝 結論

**測試結果**：❌ 登入功能**無法使用**

**主要問題**：
- 後端 API 回傳 500 錯誤
- 錯誤訊息被隱藏，無法診斷根本原因
- 需要查看應用程式日誌或修改程式碼以顯示詳細錯誤

**已完成工作**：
- ✅ 前端完整部署並正常運作
- ✅ 資料庫初始化並建立測試帳號
- ✅ 完整的測試和診斷流程
- ✅ 詳細的文件和問題分析

**下一步行動**：
1. 修改 AuthController 以記錄詳細錯誤
2. 檢查應用程式日誌檔案
3. 驗證 DI 容器配置
4. 測試 JWT Token 生成邏輯

---

**測試者**：GitHub Copilot CLI  
**報告生成時間**：2025-01-05 20:10  
**狀態**：需要後端開發人員介入修復
