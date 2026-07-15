## 上下文 (Context)

針對 AlleyNote 的 OWASP Top 10 安全審查暴露出以下安全性與邏輯缺失：
1. **中斷的存取控制**：管理頁面 `/admin/cache/tags` 未受任何驗證/授權管制，因為全域認證中間件僅匹配以 `/api/` 起始的路徑。同時，原先的標籤管理與快取監控路由使用 Slim 框架特有的 Closure 格式，並未整合進專案自訂的 array 路由器中。
2. **路徑遍歷**：`web.php` 中定義的 `/assets/{path}` 路由，直接以使用者輸入的 `$path` 字串進行檔案路徑串接，未在讀取與驗證存在性之前對其解析後的規範路徑 (Canonical Path) 進行檢查。
3. **錯誤處理不當**：`FileSecurityService` 允許上傳 Word (.doc, .docx) 與 Excel (.xls, .xlsx) 文件，但簽章特徵對照表卻缺少這幾種 MIME 類型的對應，造成此類合法文件的格式驗證必定拋出異常並失敗。
4. **安全配置錯誤**：雖然定義了 `SecurityHeaderService`，但該服務並未於 DI 容器中妥善設定，也未註冊至全域中間件管道中，導致應用程式所有 HTTP 回應均缺乏基本的安全性標頭保護。

## 目標與非目標 (Goals / Non-Goals)

**目標：**
- 對所有 `/admin/` 開頭的請求路徑（包含網頁與 API）強制執行身分驗證與管理員權限驗證。
- 將孤立的 Closure 路由定義（標籤管理與快取監控）重構為 array 格式，並將其註冊至系統路由。
- 透過 canonical 路徑驗證 (`realpath`) 與目錄分隔符號邊界檢查，防堵靜態資源路由的路徑遍歷漏洞。
- 修復上傳合法 Office 文件的簽章驗證邏輯缺陷，補上真實二進位特徵簽章以防止偽裝繞過。
- 新增並全域啟用 `SecurityHeadersMiddleware`，將 `SecurityHeaderService` 的輸出附加到所有 HTTP 回應中，並處理管理網頁的 CSP Nonce 注入以防止 JavaScript 執行被阻擋。
- 為所有管理快取之狀態變更 API 啟用 CSRF 防護。

**非目標：**
- 開發新的使用者管理管理介面。
- 重新設計整個檔案儲存或上傳系統。

## 技術決策 (Decisions)

### 決策 1：重構快取管理路由並保護 `/admin/` 路徑
- **做法**：
  - 將 `tag-management.php` 和 `cache-monitor.php` 改寫為返回標準 array 路由定義。
  - 修改 `JwtAuthenticationMiddleware::shouldProcess()` 與 `JwtAuthorizationMiddleware::shouldProcess()`，將路徑匹配範圍擴大，納入以 `/admin/` 開頭的路徑。
  - 為所有管理路由套用 `'middleware' => ['auth', 'admin']` 控制器屬性。
  - 在變更快取/標籤/分組狀態的 `POST` 與 `DELETE` 路由中，強制加入 `csrf` 中間件：`'middleware' => ['auth', 'admin', 'csrf']`。
- **替代方案考慮**：
  - *修改自訂 Router 使其支援 Closure 路由*：這將大幅增加核心路由機制的複雜度與潛在問題。改用 array 格式定義更加簡潔、統一。

### 決策 2：防範資源載入器的路徑遍歷（帶有目錄分隔符號邊界驗證）
- **做法**：使用 PHP 的 `realpath()` 解析最終目標路徑，並驗證該絕對路徑是否仍處於公用資源目錄的基礎路徑下。為了防止「字串前綴匹配繞過」（例如 `/var/www/assets` 匹配到 `/var/www/assets_secret`），基礎路徑必須強制加上目錄分隔符號。
- **實作邏輯**：
  ```php
  $basePath = realpath(__DIR__ . '/../../public/assets') . DIRECTORY_SEPARATOR;
  $filePath = realpath($basePath . $path);
  if ($filePath === false || !str_starts_with($filePath, $basePath) || !is_file($filePath)) {
      // 傳回 404 檔案不存在
  }
  ```

### 決策 3：修復 Office 文件簽章驗證與 Magic Number 檢驗
- **做法**：在 `FileSecurityService.php` 中，將允許的 Office 文件 MIME 類型在 `$signatures` 對照表中加入真實的二進位特徵簽章，避免直接用空值繞過造成任意惡意檔案上傳漏洞。
- **實作邏輯**：
  - **OLE2 格式 (舊版 `.doc` / `.xls`)**：使用簽章 `\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1`
  - **OpenXML 格式 (新版 `.docx` / `.xlsx`)**：使用簽章 `\x50\x4B\x03\x04` (ZIP 壓縮格式)
  ```php
  'application/msword' => ["\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"],
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ["\x50\x4B\x03\x04"],
  'application/vnd.ms-excel' => ["\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"],
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ["\x50\x4B\x03\x04"],
  ```

### 決策 4：全域安全性標頭與管理網頁 CSP Nonce 注入
- **做法**：
  - 於 `App\Application\Middleware` 命名空間下建立 `SecurityHeadersMiddleware`。利用 `SecurityHeaderService` 生成安全性標頭並附加到回應中，同時移除 `Server` 與 `X-Powered-By` 等標頭。
  - 在 `tag-management.php` 管理頁面的路由處理器中，從 DI 容器中解析 `SecurityHeaderService` 並生成當前請求的 CSP Nonce，將其作為變數注入 HTML 樣板中，並在所有 `<script>` 標籤加上 `nonce` 屬性（即 `<script nonce="<?= $nonce ?>">`），確保頁面的 JavaScript 在嚴格 CSP 規則下仍能正常執行。

## 風險與權衡 (Risks / Trade-offs)

- **[風險]** 新增路由後，若快取檔案未更新可能造成匹配衝突 → **[對策]** 確保在部署與修改路由後，清理或重新產生快取檔案。
- **[風險]** 啟用 CSP 可能干擾既有前端腳本運作 → **[對策]** 使用 nonce 機制或在 CSP 配置中明確允許可信來源。
