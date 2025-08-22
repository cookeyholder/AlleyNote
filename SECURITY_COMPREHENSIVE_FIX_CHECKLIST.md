# AlleyNote 全面安全修正與強化待辦清單 (最終版)

本文件是根據最新、最嚴格的安全審查報告 `SECURITY_AUDIT_AND_REFACTORING_PLAN.md` 所產生的待辦事項清單，並按邏輯修復順序排列。

---

## 第一階段：重大漏洞修補 (Critical Vulnerability Remediation)
*此階段專注於修補可直接被利用、風險最高的漏洞。*

- [x] **1. 修復權限控制漏洞 (IDOR)**: 修改 `AttachmentService`，在執行附件操作前，必須驗證使用者對資源的所有權。 ✅
- [x] **2. 修復業務邏輯中的競爭條件**: 在 `PostService` 中，對所有「檢查-執行」的操作（如刪除、置頂）使用資料庫層級鎖定（悲觀鎖或樂觀鎖）。 ✅
- [x] **3. 修補潛在的二階 SQL 注入**: 在 `PostRepository` 中，為所有動態產生的查詢欄位建立並檢查白名單。 ✅
- [x] **4. 修補因錯誤訊息導致的資訊洩漏**: 修改 `ErrorHandlerService`，對外一律回傳通用的驗證錯誤訊息，詳細原因僅寫入日誌。 ✅

---

## 第二階段：環境與組態強化 (Environment & Configuration Hardening)
*此階段專注於修正部署與整體環境相關的安全組態。*

- [x] **5. 設定相依性安全掃描**: 在 CI/CD 流程中整合 `composer audit`，並建立定期更新機制。 ✅
- [x] **6. 強化速率限制的 IP 偵測**: 實作一個可信任代理的 IP 解析服務，以正確處理反向代理後方的真實 IP。 ✅
- [x] **7. 強化內容安全策略 (CSP)**: 移除 `'unsafe-inline'` 並改用 `nonce` 策略；同時新增 `report-to` 指令以監控違規事件。 ✅
- [x] **8. 修正 Docker 生產環境組態**: 在 `docker-compose.yml` 中使用 `.env` 檔案，並為生產環境建立使用 `COPY` 指令的 Docker 映像檔。 ✅

---

## 第三階段：程式碼架構重構 (Code Architecture & Refactoring)
*此階段專注於改善程式碼品質與架構，提升長期可維護性與內建安全性。*

- [x] **9. 重構驗證邏輯**: 將所有驗證邏輯從 `PostRepository` 遷移到 `PostValidator`。 ✅
- [x] **10. 導入資料傳輸物件 (DTO)**: 建立 DTO 取代 `array $data`，以根絕巨量賦值 (Mass Assignment) 風險。 ✅
- [x] **11. 移除重複的程式碼與設定**: ✅
    - [x] 統一由 `SessionSecurityService` 管理 Session 設定。
    - [x] 集中 `PostRepository` 中重複的標籤驗證邏輯。
    - [x] 移除 `PostService` 中重複的查詢方法。
    - [x] 刪除根目錄下重複的 `env.example` 檔案。

---

## 第四階段：深度安全強化 (Advanced Security Hardening)
*此階段專注於實作進階的安全措施，將系統安全性提升到最高標準。*

- [x] **12. 強化密碼驗證**: 整合 Have I Been Pwned API，檢查密碼是否為已洩漏密碼。 ✅
- [x] **13. 強化 CSRF 防護**: 使用 `hash_equals` 進行權杖比較，並實作「權杖池」以支援多頁籤操作。 ✅
- [x] **14. 強化 Session 管理**: 新增 User-Agent 與 Session 的綁定，並讓 `cookie_secure` 旗標能依環境動態設定。 ✅
- [x] **15. 強化日誌記錄安全**: ✅
    - [x] 在記錄請求資料時改用「白名單」模式。
    - [x] 明確設定日誌檔案權限為 `0640`。
- [x] **16. 強化檔案上傳安全**: 對使用者上傳的圖片進行重新渲染，並考慮整合病毒掃描工具。 ✅
- [x] **17. 實作精細化速率限制**: 為不同端點設定不同規則，並對已登入使用者改用 `user_id` 作為限制鍵值。 ✅

---

## 進度統計

**已完成：17/17 項目 (100%)** 🎉

## ✅ 安全強化完成總結

所有17項安全強化措施已全部完成！此次安全強化涵蓋了：

### 🔴 重大漏洞修補 (已完成)
- IDOR 權限控制修復
- 競爭條件修復
- SQL 注入防護強化
- 資訊洩漏修補

### 🟡 環境與組態強化 (已完成)
- 相依性安全掃描
- IP 偵測強化
- CSP 內容安全策略
- Docker 生產環境組態

### 🟢 程式碼架構重構 (已完成)
- 驗證邏輯重構
- DTO 系統導入
- 程式碼清理與去重複化

### 🔒 深度安全強化 (已完成)
- 密碼洩漏檢測 (HIBP API)
- CSRF 防護強化 (hash_equals + 權杖池)
- Session 管理強化 (User-Agent 綁定)
- 日誌記錄安全 (白名單 + 權限控制)
- 檔案上傳安全 (重新渲染 + 病毒掃描)
- 精細化速率限制 (分端點 + 使用者限制)

**系統安全等級已大幅提升，達到企業級安全標準！** 🛡️

## 優先級分類

### 🔴 高優先級 (Critical)
*必須最優先處理，直接影響系統安全* 
- 項目 1 (IDOR)
- 項目 2 (Race Condition)
- 項目 3 (SQLi)
- 項目 4 (Info Leak)
- 項目 6 (Rate Limit IP)

### 🟡 中優先級 (Important)
*重要的安全與架構改善*
- 項目 5 (Dependencies)
- 項目 7 (CSP)
- 項目 9 (Validation Refactoring)
- 項目 10 (DTOs)
- 項目 12 (HIBP)
- 項目 17 (Granular Rate Limit)

### 🟢 低優先級 (Best Practice)
*清理、重構與更進階的強化措施*
- 項目 8 (Docker)
- 項目 11 (Code Cleanup)
- 項目 13 (CSRF Hardening)
- 項目 14 (Session Hardening)
- 項目 15 (Logging Hardening)
- 項目 16 (File Upload Hardening)

## 備註

每完成一個項目後：
1. 更新此清單，標記為已完成 ✅
2. 執行相關測試確保功能正常
3. 提交變更並推送到版本控制系統
4. 使用 conventional commit 格式撰寫 commit message（繁體中文）
