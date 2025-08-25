# copilot-instructions.md

> **📌 本專案採用 DDD（Domain-Driven Design）原則開發，請在開發過程中遵循以下準則與流程。**

以資深程式設計師的角度出發，在開發程式時，請做到以下幾點：

-   充分理解領域需求，與 PM、領域專家（Domain Expert）密切溝通，提出適當問題以釐清模糊地帶，並簡要紀錄討論結果。
-   在開始開發前，先建構清楚的子領域（Subdomains）、限界上下文（Bounded Context）及核心模型（Core Domain），並設計出清楚的模組劃分。
-   將業務邏輯封裝於領域層（Domain Layer），並避免將其寫在控制器或基礎設施層。
-   設計好系統架構和資料庫結構，以**高內聚、低耦合**、可測試性與擴充性為優先考量。
-   開始開發前，列出詳細的開發計劃與任務清單，每項任務盡可能切割為可獨立開發與測試的最小單元。
-   根據模組依賴順序進行開發，例如：Domain → Application → Infrastructure → Interface（如 Web API）。
-   嘗試先撰寫測試（TDD），在撰寫實作前，定義好 Entity、Value Object、Aggregate Root 等核心概念。
-   每次開發請使用 Context7 MCP 查詢相關 API 文件或參考範例程式碼。
-   每項開發任務請從建立分支開始，並撰寫單元測試與整合測試，確保功能正確、邏輯合理。
-   **提交前務必執行本地程式碼品質檢查**，確保程式碼品質與格式皆符合專案標準。
-   完成任務後，請更新任務清單，並以**繁體中文**撰寫符合 Conventional Commit 規範的 commit message。
-   在開發過程中，隨時記錄遇到的技術問題、建模考量與解決方式，便於後續回顧與知識分享。
-   所有完成的功能，需撰寫清楚的文件說明，包括程式碼註解、模組設計說明與維護指南。
-   先撰寫一個腳本，可以用 shell script 也可以用專案使用的程式語言。用它來掃瞄整個專案檔案之間的關係，包括引用、參考、所在位置等等的關係，然後產生自己看得懂的報告，並且在解決問題的過程中，利用經驗改寫這個腳本工具，讓它更好用！
-   多多利用這個自己寫的掃瞄專案架構的腳本工具，參考報告讓你更快理解專案架構，好進行調整。

---

## ⚙️ 程式碼品質與風格規範

-   所有命名需具描述性，禁止使用單字母、不明確名稱，並遵循 lower camel case 命名慣例
-   Import 語句須按字母順序排列
-   遵循 PSR-7、PSR-15、PSR-17 標準
-   撰寫測試時，請先查詢正確的函式參數與回傳型態
-   所有程式碼應符合 PHP CS Fixer 的風格規範
-   必須通過 PHPStan 靜態分析（建議等級 8 或團隊預設等級）

---

## 🧪 本地端程式碼品質檢查

**在每次提交前，務必執行以下指令以確保程式碼品質：**

### 使用 Docker Compose（推薦方式）

```bash
# 程式碼風格檢查
docker compose exec -T web ./vendor/bin/php-cs-fixer check --diff

# 自動修復程式碼風格問題
docker compose exec -T web ./vendor/bin/php-cs-fixer fix

# 靜態分析檢查
docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G

# 執行所有測試
docker compose exec -T web ./vendor/bin/phpunit

# 執行完整 CI 檢查（建議提交前執行）
docker compose exec -T web composer ci
```

---

## 🔄 建議的開發工作流程

1. **開發前**：

    - 先從主分支拉出新的功能分支
    - 規畫功能清單，並切割為可獨立開發與測試的最小單元
    - 建立詳細的規格書與測試案例
    - 了解相關的 API 規格與參考範例
    - 設計好系統架構與資料庫結構
    - 了解模組間的依賴關係與開發順序
    - 了解核心模型（Entity、Value Object、Aggregate Root 等）
    - 確認所有業務邏輯已被封裝在領域層中
    - 建立開發待辦清單，每個待辦事項盡可能切割為獨立的最小單元，要明確寫清楚驗收標準
    - 撰寫測試（TDD）
        - 要依照規格書仔仔細細撰寫測試案例
        - 測試案例要涵蓋所有正常與異常情況
        - 測試案例要能獨立執行，且不依賴外部狀態
        - 測試案例要能快速執行
    - 測試案例要能夠清楚地表達其意圖，並提供足夠的上下文資訊

2. **開發完成後**：

    ```bash
    # 自動修復程式碼風格
    docker-compose exec -T web ./vendor/bin/php-cs-fixer fix
    ```

3. **提交前進行檢查**：

    ```bash
    # 執行完整的品質檢查
    docker-compose exec web composer ci
    ```

4. **如有錯誤，請依序處理**：

    - 使用 `php-cs-fixer` 修正風格錯誤
    - 修復 PHPStan 報告的問題（如類型錯誤、未使用變數等）
    - 確保所有測試均通過

5. **全部通過後再進行 Git Commit，Message 請符合 Conventional Commit 規範（以繁體中文撰寫）**

---

## 📌 額外注意事項（DDD 專案特有）

-   Value Object 應該是 immutable，不得提供修改狀態的方法
-   Entity 必須有唯一識別（通常為 UUID），不可混用概念
-   不得在 Infrastructure 層直接操作 Domain 層資料結構
-   使用 Repository 模式封裝資料存取，勿將 ORM 邏輯散佈於 Domain 或 Application 層
-   每個 Bounded Context 應維持高內聚，必要時使用 Anti-Corruption Layer（ACL）進行整合
-   當涉及跨模組溝通，請考慮使用 Domain Event 或 Application Event 的方式實作

---

如對本專案有任何實作、建模或架構相關的疑問，請先記錄在 `/docs/decision-log/` 中，再開啟 issue 或發起技術討論。
