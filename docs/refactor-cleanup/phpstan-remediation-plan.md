# PHPStan 錯誤修復行動計畫

- **啟動掃描工具**：先執行架構掃描腳本並產生最新 snapshot，掌握 `backend/app/Application/**` 與 `backend/app/Domains/Auth/**` 的依賴關係與資料流，釐清 `mixed` 型別來源。
- **第一階段 — API 入口層**：針對 `backend/app/Application/Controllers/Api/V1` 補齊輸入檢核、導入 DTO／Value Object，調整私有函式簽章，確保所有參數與回傳值型別明確。
- **第二階段 — 核心領域模型**：集中修整 `backend/app/Domains/Auth` 與 `backend/app/Domains/Post` 的 DTO、Value Object、Service、Repository，將建構子型別化、限制 Repository 回傳型別並補上輸入驗證。
- **第三階段 — Middleware**：重構 `backend/app/Application/Middleware/**`，統一 request attribute 解析流程，移除冗餘的 `??` 判斷，並以強型別資料在 Middleware 之間傳遞。
- **第四階段 — 測試套件**：調整 `backend/tests/**` 的 Mock、測試資料與斷言，讓回傳值符合介面宣告，移除恒真斷言並補上 PDO 失敗路徑的檢查。
- **持續驗證**：每完成一階段就執行 `docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G`，確認錯誤量逐步下降；必要時更新 baseline 僅保留暫時無法排除的警告。
