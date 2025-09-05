# 統計功能生產環境部署檢查清單

## 📋 部署前檢查清單

### 系統要求確認
- [ ] **PHP 版本**: PHP 8.4 或更高版本
- [ ] **記憶體限制**: 建議至少 512MB
- [ ] **資料庫**: MySQL 8.0 或 MariaDB 10.5 以上
- [ ] **快取系統**: Redis 7.0 或更高版本
- [ ] **磁碟空間**: 至少 2GB 可用空間

### 相依套件檢查
- [ ] 執行 `composer install --no-dev --optimize-autoloader`
- [ ] 確認所有 PHP 擴展已安裝 (PDO, Redis, JSON, OpenSSL)
- [ ] 檢查 Composer 套件版本無衝突

### 環境配置檢查
- [ ] **環境變數設定**:
  ```bash
  # 基本設定
  APP_ENV=production
  APP_DEBUG=false

  # 資料庫設定
  DB_HOST=<production_host>
  DB_DATABASE=<production_database>
  DB_USERNAME=<production_user>
  DB_PASSWORD=<secure_password>

  # 快取設定
  CACHE_DRIVER=redis
  REDIS_HOST=<redis_host>
  REDIS_PORT=6379

  # JWT 設定
  JWT_PRIVATE_KEY=<base64_encoded_private_key>
  JWT_PUBLIC_KEY=<base64_encoded_public_key>
  JWT_TTL=3600
  ```

### 資料庫部署檢查
- [ ] **備份確認**:
  - [ ] 完整資料庫備份已建立
  - [ ] 備份檔案已驗證完整性
  - [ ] 回滾腳本已準備
- [ ] **Migration 執行**:
  - [ ] 執行 `php bin/console migrate`
  - [ ] 確認 `statistics_snapshots` 表已建立
  - [ ] 確認 `posts` 表已新增來源欄位
  - [ ] 驗證索引已正確建立

### 檔案權限檢查
- [ ] **目錄權限**:
  ```bash
  # 設定目錄權限
  chmod -R 755 backend/storage/
  chmod -R 755 backend/storage/logs/
  chmod -R 755 backend/storage/cache/

  # 設定擁有者
  chown -R www-data:www-data backend/storage/
  ```

### 快取配置檢查
- [ ] **快取服務狀態**:
  - [ ] Redis 服務正常運行
  - [ ] 快取連線測試通過
  - [ ] 快取清除功能正常
- [ ] **快取預熱**:
  - [ ] 執行統計快取預熱
  - [ ] 驗證快取鍵格式正確

## 🚀 部署步驟

### 1. 程式碼部署
```bash
# 1. 停止應用服務
sudo systemctl stop nginx
sudo systemctl stop php-fpm

# 2. 備份現有程式碼
cp -r /var/www/html /var/www/html.backup.$(date +%Y%m%d_%H%M%S)

# 3. 部署新程式碼
git pull origin main
composer install --no-dev --optimize-autoloader

# 4. 設定檔案權限
chmod -R 755 backend/storage/
chown -R www-data:www-data backend/storage/
```

### 2. 資料庫遷移
```bash
# 1. 備份資料庫
mysqldump -u [username] -p[password] [database] > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. 執行遷移
php bin/console migrate

# 3. 驗證遷移結果
php bin/console migrate:status
```

### 3. 快取設定
```bash
# 1. 清除舊快取
redis-cli FLUSHALL

# 2. 重啟快取服務
sudo systemctl restart redis

# 3. 預熱快取
php bin/console statistics:cache:warmup
```

### 4. 服務重啟
```bash
# 1. 重啟 PHP-FPM
sudo systemctl start php-fpm
sudo systemctl status php-fpm

# 2. 重啟 Nginx
sudo systemctl start nginx
sudo systemctl status nginx

# 3. 檢查服務狀態
curl -I http://localhost/api/health
```

## ✅ 部署後驗證

### 功能測試
- [ ] **統計 API 測試**:
  ```bash
  # 測試統計概覽
  curl -X GET "http://localhost/api/statistics/overview?period=monthly" \
    -H "Authorization: Bearer <token>"

  # 測試文章統計
  curl -X GET "http://localhost/api/statistics/posts?period=daily" \
    -H "Authorization: Bearer <token>"

  # 測試熱門內容
  curl -X GET "http://localhost/api/statistics/popular?limit=10" \
    -H "Authorization: Bearer <token>"
  ```

### 效能驗證
- [ ] **回應時間檢查**:
  - [ ] API 平均回應時間 < 2 秒
  - [ ] 快取命中率 > 80%
  - [ ] 資料庫查詢時間 < 500ms

### 監控設定
- [ ] **日誌監控**:
  - [ ] 統計 API 請求日誌正常
  - [ ] 錯誤日誌監控設定
  - [ ] 效能指標記錄正常

### 安全檢查
- [ ] **認證授權**:
  - [ ] JWT Token 驗證正常
  - [ ] 權限控制正確實作
  - [ ] 敏感資料不洩露

## 🔄 定時任務設定

### Cron 任務配置
```bash
# 編輯 crontab
sudo crontab -e

# 新增統計計算任務
# 每日統計計算 (每天 02:00)
0 2 * * * cd /var/www/html/backend && php scripts/statistics-console.php daily

# 週統計計算 (每週一 03:00)
0 3 * * 1 cd /var/www/html/backend && php scripts/statistics-console.php weekly

# 月統計計算 (每月 1 號 04:00)
0 4 1 * * cd /var/www/html/backend && php scripts/statistics-console.php monthly

# 快取清理 (每天 01:00)
0 1 * * * cd /var/www/html/backend && php scripts/statistics-console.php cleanup
```

### 任務執行驗證
- [ ] **手動執行測試**:
  ```bash
  # 測試統計計算
  php scripts/statistics-console.php daily --force

  # 檢查執行結果
  tail -f backend/storage/logs/statistics.log
  ```

## ⚠️ 回滾計劃

### 緊急回滾步驟
```bash
# 1. 停止服務
sudo systemctl stop nginx php-fpm

# 2. 回滾程式碼
rm -rf /var/www/html
mv /var/www/html.backup.[timestamp] /var/www/html

# 3. 回滾資料庫
mysql -u [username] -p[password] [database] < backup_[timestamp].sql

# 4. 清除快取
redis-cli FLUSHALL

# 5. 重啟服務
sudo systemctl start php-fpm nginx
```

### 回滾驗證
- [ ] 應用服務正常運行
- [ ] 核心功能可用
- [ ] 資料完整性確認

## 📊 監控與維護

### 日常監控項目
- [ ] **系統資源**:
  - [ ] CPU 使用率 < 80%
  - [ ] 記憶體使用率 < 85%
  - [ ] 磁碟使用率 < 90%

- [ ] **應用效能**:
  - [ ] API 回應時間
  - [ ] 快取命中率
  - [ ] 資料庫連線池狀態

- [ ] **業務指標**:
  - [ ] 統計計算成功率
  - [ ] 快取更新頻率
  - [ ] 錯誤率監控

### 定期維護任務
- [ ] **每週檢查**:
  - [ ] 統計資料準確性驗證
  - [ ] 效能指標回顧
  - [ ] 日誌檔案清理

- [ ] **每月檢查**:
  - [ ] 資料庫效能優化
  - [ ] 快取策略調整
  - [ ] 容量規劃評估

## 🔍 故障排除

### 常見問題
1. **統計計算失敗**:
   - 檢查資料庫連線
   - 確認統計表結構
   - 檢查權限設定

2. **快取失效**:
   - 重啟 Redis 服務
   - 檢查快取配置
   - 驗證網路連線

3. **API 回應慢**:
   - 檢查資料庫索引
   - 優化查詢語句
   - 增加快取 TTL

### 聯絡資訊
- **技術負責人**: [聯絡資訊]
- **運維團隊**: [聯絡資訊]
- **緊急聯絡**: [24小時聯絡方式]

---

**完成日期**: 2024-12-19
**版本**: 1.0
**負責人**: 開發團隊
