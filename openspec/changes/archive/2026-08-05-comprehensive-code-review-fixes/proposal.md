# Proposal: Comprehensive Code Review Fixes

## Why
在全方位程式碼審查 (Code Review Audit) 中發現了若干關鍵致命錯誤 (P0_CRITICAL)、高風險注入與環境問題 (P1_HIGH)、以及品質改善建議 (P2_MEDIUM)。這些問題涵蓋：
1. `RefreshTokenService.php` 中 `DateTimeImmutable::setTimestamp()` 傳回值丟棄導致黑名單過期時間誤設為 `now`，造成黑名單失效 (Blacklist Bypass)。
2. 測試環境中缺乏預設 RSA 私鑰檔案時觸發 `InvalidArgumentException` 導致 API 500 錯誤。
3. `TokenBlacklistRepository.php` 使用雙引號 SQL 字串拼貼。
4. `restore_db.sh` 依賴 Alpine 缺少的 `file` 命令。

## Goal
修復報告中提出的所有 P0-P3 問題，使 static analysis (`composer check-all` 包括 PHPStan Level 10, PHP-CS-Fixer, PHPUnit) 全數通過 (Green)，並提升系統安全性與穩定度。

## Scope
- `backend/app/Domains/Auth/Services/RefreshTokenService.php`
- `backend/app/Infrastructure/Auth/Repositories/TokenBlacklistRepository.php`
- `backend/app/Shared/Config/JwtConfig.php`
- `backend/scripts/Database/restore_db.sh`
- 相關單元與整合測試
