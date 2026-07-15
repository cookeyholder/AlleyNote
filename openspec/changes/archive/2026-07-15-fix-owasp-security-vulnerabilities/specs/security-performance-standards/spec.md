## ADDED Requirements

### Requirement: Page Authentication (網頁存取認證)
所有管理網頁端點與 API（例如以 `/admin` 開頭的頁面或快取管理 API）都必須 (MUST) 經過身分認證與管理員權限驗證。所有變更狀態（`POST`、`PUT`、`DELETE`）的快取管理 API 還必須 (MUST) 包含 CSRF 防護驗證。

#### Scenario: Unauthorized access to admin cache tag management
- **WHEN** 未經認證的使用者嘗試存取 `/admin/cache/tags` 頁面
- **THEN** 系統必須拒絕存取或重新導向至登入頁面

#### Scenario: Verify CSRF protection on cache tag flush
- **WHEN** 已認證的使用者發送 `DELETE /api/admin/cache/tags` 請求且未提供有效的 CSRF token
- **THEN** 系統必須拒絕該請求並回傳 403 錯誤

### Requirement: Path Traversal Prevention (防止路徑遍歷)
靜態資源載入器必須 (MUST) 驗證所有解析後的檔案路徑，確保其被嚴格限制在公用資源目錄的基礎路徑下，且該路徑檢查必須 (MUST) 包含目錄邊界字元（`DIRECTORY_SEPARATOR`）以防範字串前綴匹配繞過。

#### Scenario: Attempted path traversal via assets route
- **WHEN** 使用者使用遍歷路徑（如 `/assets/../../composer.json`）請求檔案
- **THEN** 系統必須拒絕該請求並回傳 404 或 400 錯誤

#### Scenario: Prefix matching traversal bypass test
- **WHEN** 使用者請求一個會解析為外部但前綴相似目錄（如 `/assets/../assets_secret/file.txt`）的路徑
- **THEN** 系統必須拒絕該請求並回傳 404 或 400 錯誤

### Requirement: Safe File Upload Signatures (安全的上傳檔案類型簽名驗證)
所有允許的檔案上傳 MIME 類型（包括 Microsoft Office 文件如 `.doc`、`.docx`、`.xls`、`.xlsx`）都必須 (MUST) 有定義明確且合法的二進位特徵碼簽章，並在驗證時正確通過簽章比對，禁止使用空對照規則進行繞過。

#### Scenario: Uploading allowed Office document
- **WHEN** 使用者上傳一個合法的 `.docx` 文件（其以 `PK\x03\x04` 二進位特徵開頭）
- **THEN** 檔案驗證必須通過並成功完成上傳

#### Scenario: Uploading spoofed Office document
- **WHEN** 使用者上傳一個偽裝成 `application/msword` 格式的惡意腳本檔案（其二進位特徵不符 OLE2 簽章 `\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1`）
- **THEN** 檔案驗證必須失敗並拒絕該上傳

### Requirement: Global Security Headers (全域安全性 HTTP 標頭)
應用程式必須 (MUST) 全域為所有處理的請求傳送安全的 HTTP 回應標頭（包含 CSP、HSTS、X-Frame-Options、X-Content-Type-Options）。對於注入 HTML 內聯腳本的管理網頁，必須 (MUST) 透過與回應標頭相符的 CSP Nonce 進行注入，以防 JavaScript 執行被瀏覽器安全政策阻擋。

#### Scenario: Verify security headers in response
- **WHEN** 客戶端請求任何頁面
- **THEN** 回應標頭必須包含 Content-Security-Policy、Strict-Transport-Security、X-Frame-Options 與 X-Content-Type-Options

#### Scenario: Verify CSP Nonce injection on tag management page
- **WHEN** 客戶端存取 `/admin/cache/tags` 頁面
- **THEN** 該頁面的內聯 `<script>` 標籤中必須包含一個隨機產生的 `nonce` 屬性，且此屬性值必須與回應標頭中的 `Content-Security-Policy` 的 `nonce-*` 設定相符
