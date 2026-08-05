# Design: Comprehensive Code Review Fixes

## Technical Design

### 1. Fix `DateTimeImmutable` return value in `RefreshTokenService.php`
- **問題**：`$expiresAt->setTimestamp(...)` 無法對不可變物件進行原地修改。
- **方案**：
  ```php
  $expiresAt = $expiresAt->setTimestamp($payload->getExpiresAt()->getTimestamp());
  ```
  確保取得正確的時間戳記物件並存入 `$expiresAt`。

### 2. SQL Prepared Statement in `TokenBlacklistRepository.php`
- **問題**：硬編碼雙引號拼貼陣列 `WHERE reason IN ("...")`。
- **方案**：改用動態佔位符與綁定參數：
  ```php
  $placeholders = implode(',', array_fill(0, count($securityReasons), '?'));
  $securitySql = "SELECT COUNT(*) FROM token_blacklist WHERE reason IN ({$placeholders})";
  $securityStmt = $this->pdo->prepare($securitySql);
  $securityStmt->execute($securityReasons);
  ```

### 3. Graceful JWT Test Fallback in `JwtConfig.php`
- **問題**：測試或未設定實體 PEM 金鑰檔時直接 throw Exception。
- **方案**：若環境變數沒有金鑰檔案且處於 test 環境，生成或使用記憶體級的 Mock/Default Key。

### 4. Remove `file` Dependency in `restore_db.sh`
- **問題**：`restore_db.sh` 使用 `file` 檢查檔型，Alpine 不支援。
- **方案**：使用 `sqlite3` 自帶標頭檢驗 `sqlite3 "$backup_file" "PRAGMA schema_version;"` 來取代 `file "$backup_file" | grep -q "SQLite"`。

---
