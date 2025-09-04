# 使用者活動記錄測試資料 Seeder

## 概述

`UserActivityLogsSeeder` 是為 AlleyNote 專案建立的測試資料播種器，用於產生各種類型的使用者活動記錄，供開發和測試使用。

## 功能特性

### 🎯 資料類型覆蓋
- **認證相關活動** - 登入成功/失敗、密碼變更、登出
- **文章管理活動** - 文章建立、瀏覽、權限被拒
- **附件管理活動** - 檔案上傳、下載、病毒檢測
- **安全事件** - 可疑登入嘗試、IP 封鎖

### 📊 狀態類型
- `success` - 操作成功
- `failed` - 操作失敗
- `error` - 系統錯誤
- `blocked` - 被系統阻擋

### 🏗️ 資料結構
每筆記錄包含完整的上下文資訊：
- UUID 唯一識別碼
- 使用者ID和會話ID
- 行為類型和分類
- IP位址和User Agent
- 請求方法和路徑
- 時間戳記和元數據

## 使用方法

### 執行 Seeder

```bash
# 在 Docker 容器內執行
docker compose exec web ./vendor/bin/phinx seed:run -s UserActivityLogsSeeder

# 或直接在容器內
./vendor/bin/phinx seed:run -s UserActivityLogsSeeder
```

### 驗證結果

```bash
# 檢查總記錄數
docker compose exec web sqlite3 database/alleynote.sqlite3 "SELECT COUNT(*) FROM user_activity_logs;"

# 檢查記錄類型分布
docker compose exec web sqlite3 database/alleynote.sqlite3 "
SELECT action_type, status, COUNT(*) as count
FROM user_activity_logs
GROUP BY action_type, status
ORDER BY action_type;
"
```

## 資料樣本

### 成功登入記錄
```json
{
    "uuid": "12345678-1234-4567-8901-123456789012",
    "user_id": 1,
    "action_type": "auth.login.success",
    "action_category": "authentication",
    "status": "success",
    "description": "使用者成功登入系統",
    "metadata": {
        "login_method": "password",
        "remember_me": true
    },
    "ip_address": "192.168.1.100"
}
```

### 安全事件記錄
```json
{
    "uuid": "87654321-4321-7654-1098-210987654321",
    "action_type": "security.suspicious_login_attempt",
    "action_category": "security",
    "status": "blocked",
    "description": "可疑登入嘗試被阻擋",
    "metadata": {
        "reason": "multiple_failed_attempts",
        "attempt_count": 5,
        "blocked_duration": 1800
    },
    "ip_address": "203.0.113.1"
}
```

## 測試驗證

Seeder 包含完整的測試套件，位於 `tests/Database/Seeds/UserActivityLogsSeederTest.php`：

```bash
# 執行測試
docker compose exec web ./vendor/bin/phpunit tests/Database/Seeds/UserActivityLogsSeederTest.php
```

### 測試覆蓋範圍
- ✅ 資料建立功能驗證
- ✅ 行為類型多樣性檢查
- ✅ 狀態類型完整性檢查
- ✅ 資料格式正確性驗證
- ✅ 安全事件記錄檢查
- ✅ 清空功能驗證

## 程式碼品質

- ✅ **PHP CS Fixer** - 符合專案程式碼風格規範
- ✅ **PHPStan Level 8** - 通過最嚴格的靜態分析檢查
- ✅ **單元測試** - 6個測試案例，27個斷言，100%通過率
- ✅ **Type Safety** - 使用 strict_types，所有方法都有型別宣告

## 注意事項

### ⚠️ 重要提醒
1. **資料清空** - Seeder 執行時會清空現有的 `user_activity_logs` 資料表
2. **生產環境** - 切勿在生產環境執行此 Seeder
3. **資料庫鎖定** - 在某些情況下可能遇到 SQLite 資料庫鎖定問題

### 🔧 故障排除

**資料庫鎖定錯誤**
```
SQLSTATE[HY000]: General error: 5 database is locked
```
解決方案：
- 確保沒有其他程序正在使用資料庫
- 等待幾秒後重試
- 檢查是否有未關閉的資料庫連線

**權限問題**
```
Permission denied
```
解決方案：
```bash
# 確保資料庫檔案權限正確
docker compose exec web chmod 664 database/alleynote.sqlite3
```

## 開發指南

### 新增資料類型
要新增新的活動記錄類型：

1. 在對應的產生方法中新增資料
2. 確保包含所有必要欄位
3. 更新測試案例
4. 執行品質檢查

### 自訂元數據
每種活動類型都可以包含特定的元數據：

```php
'metadata' => json_encode([
    'custom_field_1' => 'value1',
    'custom_field_2' => 'value2',
    // 其他自訂欄位
])
```

## 相關文件

- [使用者行為紀錄功能開發規格書](../docs/USER_ACTIVITY_LOGGING_SPEC.md)
- [使用者行為紀錄功能開發待辦清單](../docs/USER_ACTIVITY_LOGGING_TODO.md)
- [資料庫遷移檔案](migrations/)
- [活動記錄相關測試](../../tests/Domains/Security/ActivityLog/)

---

**最後更新**: 2025-08-30
**版本**: 1.0.0
**狀態**: ✅ 完成並通過所有測試
