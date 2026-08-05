# AlleyNote 全面程式碼審查與品質診斷報告 (Code Review Report)

> 報告日期：2026-08-05  
> 審查層級：全系統（後端 PHP 8.4 + 領域驅動設計 / 前端 ES6 SPA / 安全性 / 效能 / 靜態分析）

---

## 專案概述 (Project Overview)

**AlleyNote** 是一個基於**領域驅動設計 (Domain-Driven Design, DDD)** 架構開發的現代化公告與佈告欄平台。

### 核心技術特徵：
1. **後端 (Backend)**：
   - **PHP 8.4+** 嚴格強型別模式 (`declare(strict_types=1);`)。
   - **自製輕量級 DDD 框架**，相容 PSR-7 / PSR-11 / PSR-15。
   - **無 ORM 原生 PDO + SQLite 3** 資料庫驅動，並支援 Redis 快取與 SQLite 降級快取。
   - **JWT (RS256 非對稱加密)** 認證機制與 Token 黑名單防護機制。
   - 包含 **7 個 Bounded Contexts** (Post, Auth, Statistics, Security, Attachment, Setting, Shared)。
2. **前端 (Frontend)**：
   - **原生 ES6 模組**，無前端框架/無打包建構工具 (No-build SPA)。
   - **Tailwind CSS (CDN)** + **CKEditor 5** 內文編輯 + **Chart.js 4.x** 統計圖表。
3. **品質與測試 (QA & Testing)**：
   - **PHPStan Level 10** 嚴格靜態分析。
   - **PHPUnit 11** 單元與整合測試（2300+ 測試案例）。
   - **Playwright** 端對端 (E2E) 測試與 PHP-CS-Fixer 程式碼風格規範。

---

## 審查發現與問題清單 (Audit Findings)

本次審查採用最高標準進行資安、效能、 Clean Code、架構與潛在邏輯漏洞盤點，共發現以下關鍵問題：

### 1. 資安與重大邏輯錯誤 (Security & Logic Bugs)

#### 🔴 [P0_CRITICAL] `RefreshTokenService.php` 中 `DateTimeImmutable` 返回值丟棄導致黑名單過期時間無效
- **位置**：`backend/app/Domains/Auth/Services/RefreshTokenService.php` (`addToBlacklist` 方法, L480)
- **問題說明**：`DateTimeImmutable::setTimestamp()` 會回傳一個新的 `DateTimeImmutable` 物件，原物件不會被變更。程式碼中以 `$expiresAt->setTimestamp(...)` 獨立一行呼叫，未將傳回值賦予變數，導致 `$expiresAt` 永遠為 `now` (當前時間)。
- **影響範圍**：被加入黑名單的 Refresh Token 其紀錄過期時間永遠等於建立時間，造成黑名單清理邏輯可能立即判定其已過期並予以清除，引發**黑名單防護繞過 (Blacklist Bypass)** 嚴重資安隱患。
- **修復方案**：將該行改為 `$expiresAt = $expiresAt->setTimestamp($payload->getExpiresAt()->getTimestamp());`。

#### 🔴 [P0_CRITICAL] JWT 金鑰環境設定缺失引發 API 全面回傳 500 Error
- **位置**：`backend/app/Shared/Config/JwtConfig.php` 及單元/整合測試初始化
- **問題說明**：當環境變數未載入 `test_private_key.pem` 或找不到指定 PEM 檔時，`JwtConfig` 直接拋出 `InvalidArgumentException`，導致 50+ 個 Auth/Statistics 整合測試中斷並觸發 500 伺服器錯誤。
- **影響範圍**：JWT 認證失效與系統在缺少金鑰實體檔案時無容錯機制。
- **修復方案**：在測試環境下自動產生臨時動態金鑰，並於 `JwtConfig` 中提供更友善的退避 fallback 機制。

#### 🟠 [P1_HIGH] `TokenBlacklistRepository.php` 中未使用 Prepared Statement 綁定陣列參數
- **位置**：`backend/app/Infrastructure/Auth/Repositories/TokenBlacklistRepository.php` (L642, L652)
- **問題說明**：`SELECT COUNT(*) FROM token_blacklist WHERE reason IN ("' . implode('","', $securityReasons) . '")` 直接將字串陣列以雙引號拼貼進 SQL 子句中。在 ANSI SQL 標準下雙引號代表識別碼而非字串常數，且字串拼貼違反了 PDO Prepared Statement 參數化原則。
- **修復方案**：改用佔位符 `IN (?, ?, ?)` 並以 PDO bindValue 綁定參數陣列。

#### 🟠 [P1_HIGH] 備份還原腳本依賴 Alpine/Linux 缺少的 `file` CLI 指令
- **位置**：`backend/scripts/Database/restore_db.sh` (L59)
- **問題說明**：還原腳本直接執行 `file` 指令來驗證是否為 SQLite 資料庫，在容器環境缺乏 `file` 指令時會爆出 `line 59: file: command not found` 導致備份還原測試失敗。
- **修復方案**：改用 SQLite header (`sqlite3 <file> "PRAGMA schema_version;"`) 或安全的工具判斷。

---

### 2. Clean Code & 程式碼規範 (Code Style & Quality)

#### 🟡 [P2_MEDIUM] `RefreshTokenService.php` 錯誤捕捉缺少結構化日誌記錄
- **位置**：`backend/app/Domains/Auth/Services/RefreshTokenService.php` (L495)
- **問題說明**：在 `addToBlacklist` 的 `catch (Throwable $e)` 中，僅吞掉異常或缺乏明確的日誌上下文記錄。
- **修復方案**：加入 `LoggerInterface` 紀錄警告或錯誤細節。

---

### 3. 前端資安與 DOM 操作防護 (Frontend Security & DOM Sanitization)

#### 🟡 [P2_MEDIUM] 前端 DOM 設定未統一套用安全跳脫機制
- **位置**：`frontend/js/pages/admin/` 及 `frontend/js/components/` 多處 `.innerHTML` 賦值
- **問題說明**：部分模組手動調用 `escapeHtml`，但缺乏統一機制，易因後續維護疏忽遺漏跳脫。
- **修復方案**：統一宣導與推行 `security.js` 的 `safeHTML` 模板標籤或 DOMPurify 工具。

---

## 建議修復計畫 (Action Plan)

1. **第一階段 (P0/P1 優先修復)**：
   - 修正 `RefreshTokenService.php` 中的 `DateTimeImmutable` 返回值問題，確保 PHPStan Level 10 分析通過。
   - 重構 `TokenBlacklistRepository.php` 中的 SQL `IN` 查詢為標準參數綁定。
2. **第二階段 (P2 品質優化)**：
   - 補強 `RefreshTokenService` 異常處理日誌。
   - 執行完整的測試套件 (`composer check-all`) 確保 100% PASS。
3. **第三階段 (文件與 OpenSpec 歸檔)**：
   - 更新 `README.md` 與相關文件，確保修復內容納入版本紀錄。

---
