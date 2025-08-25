以資深程式設計師的角度出發，在開發程式時，請做到以下幾點：

-   充分理解需求，提出適當問題以釐清需求，並且將討論過程簡要地紀錄下來。
-   先設計好程式架構和資料庫結構，以高效和安全為最優先考量。
-   在動手開發前，先列出詳細的開發計劃和任務清單，最好是能切分任務到每項都能獨立開發的。
-   清單地排列以合乎邏輯的順序進行開發，若是有依賴關係的任務，務必先完成基礎任務。
-   開發功能前嘗試使用 Context7 MCP 查詢最新的 API 文件和範例程式碼，以加速開發流程。
-   每次開發前都先建立一個新的分支，並且在完成任務後進行大量的單元測試和整合測試，確保功能正常。
-   **提交前必須在本地端進行程式碼品質檢查**，確保符合專案標準後再提交。
-   完成一項任務後，先更新任務清單，再以繁體中文撰寫符合 conventional commit 規範的 commit message。
-   在開發過程中，隨時記錄遇到的問題和解決方案，以便日後參考。
-   開發完成後，撰寫詳細的文件說明，包括程式碼註解、使用說明和維護指南。

這是一個 PHP 專案，請依照上述原則進行開發，並且留意以下事項：

## 程式碼品質與風格規範

-   變數的命名必須具描述性，避免使用單字母或不明確的名稱，以 lower camel case 方式命名
-   按照字母順序重新排列 import 語句
-   符合 PHP CS Fixer 程式碼風格規範
-   所有程式碼必須通過 PHPStan 靜態分析檢查

## 本地端程式碼品質檢查

**在每次提交前，務必執行以下檢查指令以確保程式碼品質：**

### 使用 Docker Compose（推薦）

```bash
# 程式碼風格檢查
docker-compose exec -T web ./vendor/bin/php-cs-fixer check --diff

# 自動修復程式碼風格問題
docker-compose exec -T web ./vendor/bin/php-cs-fixer fix

# 靜態分析檢查
docker-compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G

# 執行所有測試
docker-compose exec -T web ./vendor/bin/phpunit

# 完整的 CI 檢查（建議在提交前執行）
docker-compose exec -T web composer ci
```

### 使用 Composer 腳本

專案已定義了以下 Composer 腳本，可直接使用：

```bash
# 在 Docker 容器內執行
docker-compose exec web composer cs-check     # 檢查程式碼風格
docker-compose exec web composer cs-fix       # 修復程式碼風格
docker-compose exec web composer analyse      # 靜態分析
docker-compose exec web composer test         # 執行測試
docker-compose exec web composer ci           # 完整 CI 檢查
```

### 建議的開發工作流程

1. **開發完成後**：

    ```bash
    # 自動修復程式碼風格
    docker-compose exec -T web ./vendor/bin/php-cs-fixer fix
    ```

2. **提交前檢查**：

    ```bash
    # 執行完整的品質檢查
    docker-compose exec web composer ci
    ```

3. **如果有錯誤**：

    - 修復 PHP CS Fixer 報告的風格問題
    - 解決 PHPStan 發現的靜態分析錯誤
    - 確保所有測試通過

4. **全部通過後才進行 git commit**

### 注意事項

-   使用 `php-cs-fixer fix` 可自動修復大部分風格問題
-   PHPStan 錯誤需要手動修復，通常涉及類型宣告、未使用的變數等
-   建議在 IDE 中整合這些工具，以便即時檢查
-   團隊成員應定期同步 `.php-cs-fixer.php` 和 `phpstan.neon` 配置檔案
