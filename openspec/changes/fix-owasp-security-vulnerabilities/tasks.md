## 1. 路由重構與身分驗證 / 管理權限防護

- [x] 1.1 將 `backend/config/routes/tag-management.php` 改寫為返回 array 格式的路由配置，並在 `POST` 和 `DELETE` 路由中套用 `csrf` 中間件。
- [x] 1.2 將 `backend/config/routes/cache-monitor.php` 改寫為返回 array 格式的路由配置，並在 `POST` 和 `DELETE` 路由中套用 `csrf` 中間件。
- [x] 1.3 更新 `backend/app/Infrastructure/Routing/Providers/RoutingServiceProvider.php` 中的 `getRouteFiles()`，使其載入 `tag-management` 與 `cache-monitor` 的路由設定檔。
- [x] 1.4 更新 `backend/app/Application/Middleware/JwtAuthenticationMiddleware.php` 的 `shouldProcess()`，使其攔截以 `/admin/` 開頭的請求路徑。
- [x] 1.5 更新 `backend/app/Application/Middleware/JwtAuthorizationMiddleware.php` 的 `shouldProcess()`，使其攔截以 `/admin/` 開頭的請求路徑。
- [x] 1.6 在 `backend/config/routes/tag-management.php` 中取得 `SecurityHeaderService` 生成的 CSP Nonce，並動態注入到 HTML 頁面的 `<script>` 標籤中。

## 2. 防止資源下載路徑遍歷

- [x] 2.1 修改 `backend/config/routes/web.php` 中的 `/assets/{path}` 路由處理器，改用 `realpath()` 取得實際絕對路徑，並驗證該路徑是否安全地處於公用 assets 目錄（附加 `DIRECTORY_SEPARATOR`）之下，防止字串前綴比對繞過。

## 3. 上傳檔案特徵簽章對應修復

- [x] 3.1 於 `backend/app/Domains/Attachment/Services/FileSecurityService.php` 的 `$signatures` 對照表中，加入 Microsoft Office 文件類型的二進位特徵碼（OLE2: `\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1` 與 OpenXML: `\x50\x4B\x03\x04`），防止任意惡意檔案上傳。

## 4. 全域安全性標頭中間件實作

- [x] 4.1 建立全域中間件 `backend/app/Application/Middleware/SecurityHeadersMiddleware.php`，調用 `SecurityHeaderService` 設定標頭並移除伺服器敏感資訊。
- [x] 4.2 於 `backend/config/container.php` 中註冊 `SecurityHeadersMiddleware`。
- [x] 4.3 將 `SecurityHeadersMiddleware` 加入 `backend/config/container.php` 或 `backend/app/Application.php` 的全域中間件載入排程中。
