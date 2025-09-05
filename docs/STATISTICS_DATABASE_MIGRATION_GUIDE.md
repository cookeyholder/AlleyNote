# AlleyNote 統計功能資料庫遷移指南

## 📋 概覽

本指南說明如何安全地將 AlleyNote 統計功能的資料庫變更部署到現有系統。

## 🗄️ 資料庫變更摘要

### 新增資料表

1. **statistics_snapshots** - 統計快照資料表
2. **statistics_cache** - 統計快取資料表
3. **user_activity_logs** - 使用者活動記錄表

### 現有資料表修改

- **posts** - 新增統計相關欄位
- **users** - 新增活動追蹤欄位
- **post_views** - 優化索引結構

## 🚀 遷移步驟

### 步驟 1: 環境準備

**1.1 檢查系統需求**
```bash
# 檢查 MySQL 版本 (需要 8.0+)
mysql --version

# 檢查可用磁碟空間 (建議至少 1GB)
df -h

# 檢查 MySQL 連線
mysql -u root -p -e "SELECT VERSION();"
```

**1.2 確認當前架構**
```bash
# 檢查現有資料表
mysql -u root -p alleynote -e "SHOW TABLES;"

# 檢查資料量
mysql -u root -p alleynote -e "
  SELECT
    table_name,
    table_rows,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size_MB'
  FROM information_schema.tables
  WHERE table_schema = 'alleynote';"
```

### 步驟 2: 資料備份

**2.1 完整資料庫備份**
```bash
# 建立備份目錄
mkdir -p backup/$(date +%Y%m%d)

# 完整資料庫備份
mysqldump -u root -p alleynote > backup/$(date +%Y%m%d)/alleynote_full_backup.sql

# 壓縮備份檔案
gzip backup/$(date +%Y%m%d)/alleynote_full_backup.sql

# 檢查備份檔案
ls -lh backup/$(date +%Y%m%d)/
```

**2.2 關鍵資料表備份**
```bash
# 備份關鍵資料表
mysqldump -u root -p alleynote posts users post_views > backup/$(date +%Y%m%d)/critical_tables_backup.sql

# 驗證備份完整性
mysql -u root -p < backup/$(date +%Y%m%d)/critical_tables_backup.sql
```

### 步驟 3: 執行遷移

**3.1 進入維護模式**
```bash
# 停止應用服務 (視部署方式而定)
docker-compose stop web
# 或
systemctl stop apache2
```

**3.2 執行資料庫遷移**
```bash
# 進入專案目錄
cd /path/to/alleynote

# 執行 Phinx 遷移
./vendor/bin/phinx migrate

# 或手動執行遷移檔案
mysql -u root -p alleynote < database/migrations/20241219000001_create_statistics_tables.php.sql
mysql -u root -p alleynote < database/migrations/20241219000002_add_statistics_indexes.php.sql
```

**3.3 驗證遷移結果**
```bash
# 檢查新資料表
mysql -u root -p alleynote -e "SHOW TABLES LIKE 'statistics_%';"

# 檢查資料表結構
mysql -u root -p alleynote -e "DESCRIBE statistics_snapshots;"
mysql -u root -p alleynote -e "DESCRIBE statistics_cache;"
mysql -u root -p alleynote -e "DESCRIBE user_activity_logs;"

# 檢查索引
mysql -u root -p alleynote -e "SHOW INDEX FROM posts;"
mysql -u root -p alleynote -e "SHOW INDEX FROM users;"
mysql -u root -p alleynote -e "SHOW INDEX FROM post_views;"
```

### 步驟 4: 資料初始化

**4.1 產生初始統計快照**
```bash
# 重新啟動應用服務
docker-compose start web

# 執行統計資料初始化
docker-compose exec web php scripts/initialize-statistics.php

# 或使用 Artisan 指令 (如果有)
php artisan statistics:initialize
```

**4.2 驗證統計功能**
```bash
# 測試統計 API
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "http://localhost/api/statistics/overview"

# 檢查快取功能
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "http://localhost/api/statistics/posts"
```

## 📊 效能影響評估

### 預期影響

| 操作 | 預期時間 | 對系統的影響 |
|------|---------|-------------|
| 資料表建立 | 30-60 秒 | 低 - 只影響新功能 |
| 索引建立 | 2-10 分鐘 | 中 - 短暫鎖定現有資料表 |
| 資料初始化 | 5-30 分鐘 | 低 - 背景執行 |
| 總維護時間 | 10-45 分鐘 | 中 |

### 風險評估

**高風險**：
- 大型資料表 (>100萬筆記錄) 的索引建立可能造成長時間鎖定

**中風險**：
- 磁碟空間不足導致遷移失敗
- 遷移過程中斷電或網路中斷

**低風險**：
- 新功能與現有功能隔離，不影響核心業務邏輯

## 🔄 回滾程序

### 情況 1: 遷移失敗

```bash
# 停止應用服務
docker-compose stop web

# 恢復資料庫備份
mysql -u root -p alleynote < backup/$(date +%Y%m%d)/alleynote_full_backup.sql

# 重新啟動服務
docker-compose start web

# 驗證回滾結果
curl "http://localhost/api/health"
```

### 情況 2: 部分遷移成功

```bash
# 只回滾統計相關資料表
mysql -u root -p alleynote -e "
  DROP TABLE IF EXISTS statistics_snapshots;
  DROP TABLE IF EXISTS statistics_cache;
  DROP TABLE IF EXISTS user_activity_logs;
"

# 移除新增的索引
mysql -u root -p alleynote -e "
  ALTER TABLE posts DROP INDEX idx_posts_statistics;
  ALTER TABLE users DROP INDEX idx_users_activity;
  ALTER TABLE post_views DROP INDEX idx_post_views_optimized;
"
```

### 情況 3: 功能異常

```bash
# 暫時停用統計功能
# 修改環境變數或設定檔
echo "STATISTICS_ENABLED=false" >> .env

# 重新啟動服務
docker-compose restart web
```

## 🧪 測試驗證

### 功能測試

**1. 基本 API 測試**
```bash
# 測試腳本
cat > test_statistics_api.sh << 'EOF'
#!/bin/bash

TOKEN="YOUR_JWT_TOKEN"
BASE_URL="http://localhost/api/statistics"

echo "測試統計概覽 API..."
curl -s -H "Authorization: Bearer $TOKEN" "$BASE_URL/overview" | jq .success

echo "測試文章統計 API..."
curl -s -H "Authorization: Bearer $TOKEN" "$BASE_URL/posts" | jq .success

echo "測試熱門內容 API..."
curl -s -H "Authorization: Bearer $TOKEN" "$BASE_URL/popular" | jq .success

echo "所有測試完成"
EOF

chmod +x test_statistics_api.sh
./test_statistics_api.sh
```

**2. 效能測試**
```bash
# 併發測試
ab -n 100 -c 10 -H "Authorization: Bearer YOUR_TOKEN" \
   "http://localhost/api/statistics/overview"

# 回應時間測試
curl -w "@curl-format.txt" -s -o /dev/null \
     -H "Authorization: Bearer YOUR_TOKEN" \
     "http://localhost/api/statistics/overview"
```

**3. 資料完整性測試**
```sql
-- 檢查統計資料一致性
SELECT
  (SELECT COUNT(*) FROM posts) as total_posts,
  (SELECT COUNT(*) FROM statistics_snapshots WHERE metric_name = 'total_posts') as snapshot_records;

-- 檢查索引效果
EXPLAIN SELECT * FROM posts WHERE created_at >= '2024-01-01' ORDER BY view_count DESC LIMIT 10;
```

## 📋 部署檢查清單

### 遷移前檢查

- [ ] 確認 MySQL 版本 ≥ 8.0
- [ ] 檢查磁碟空間 (至少 1GB 可用)
- [ ] 備份現有資料庫
- [ ] 確認備份檔案完整性
- [ ] 準備回滾計劃
- [ ] 通知相關團隊維護時間

### 遷移過程檢查

- [ ] 應用服務已停止
- [ ] 資料庫連線正常
- [ ] 遷移腳本執行成功
- [ ] 新資料表建立完成
- [ ] 索引建立完成
- [ ] 無錯誤訊息或警告

### 遷移後檢查

- [ ] 應用服務正常啟動
- [ ] 統計 API 回應正常
- [ ] 快取功能運作正常
- [ ] 效能指標符合預期
- [ ] 錯誤日誌檢查
- [ ] 使用者功能測試

## ⚠️ 注意事項

### 重要提醒

1. **備份是必須的**：在任何遷移操作前都必須完整備份資料庫
2. **維護時間規劃**：建議在低峰時段 (如凌晨) 執行遷移
3. **分階段部署**：大型系統建議先在測試環境完整測試
4. **監控準備**：遷移後密切監控系統效能和錯誤日誌
5. **文件更新**：遷移完成後更新相關技術文件

### 效能最佳化建議

```sql
-- 遷移後執行統計更新
ANALYZE TABLE statistics_snapshots;
ANALYZE TABLE statistics_cache;
ANALYZE TABLE user_activity_logs;

-- 優化查詢快取
SET GLOBAL query_cache_size = 268435456; -- 256MB
SET GLOBAL query_cache_type = ON;
```

### 故障排除

**常見問題**：

1. **索引建立超時**
   ```sql
   -- 增加鎖定等待時間
   SET SESSION lock_wait_timeout = 3600;
   SET SESSION innodb_lock_wait_timeout = 3600;
   ```

2. **磁碟空間不足**
   ```bash
   # 清理暫存檔案
   docker system prune -f

   # 清理舊的日誌檔案
   find /var/log -name "*.log" -mtime +7 -delete
   ```

3. **記憶體不足**
   ```sql
   -- 調整 MySQL 記憶體設定
   SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB
   ```

## 📞 支援聯絡

如果在遷移過程中遇到問題：

1. 查看錯誤日誌：`tail -f /var/log/mysql/error.log`
2. 檢查應用日誌：`docker-compose logs web`
3. 聯繫技術支援團隊
4. 參考 [故障排除文件](./TROUBLESHOOTING_GUIDE.md)

---

*最後更新：2024-12-19*
*版本：1.0.0*
