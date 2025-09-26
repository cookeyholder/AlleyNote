# 統計資料回填指令使用指南

## 概述

統計資料回填指令是一個強大的 CLI 工具，用於重新計算和產生歷史統計資料快照。此工具支援多種統計類型、批次處理、試執行模式等進階功能，確保在生產環境中能夠安全且有效率地進行資料維護。

## 核心功能

### 1. 統計類型支援
- **overview**: 總覽統計資料
- **posts**: 文章相關統計
- **users**: 使用者行為統計
- **popular**: 熱門內容統計

### 2. 進階操作模式
- **批次處理**: 可配置批次大小（1-365 天）
- **試執行模式**: 安全預覽即將執行的操作
- **強制覆蓋**: 覆蓋已存在的統計快照
- **進度追蹤**: 即時顯示處理進度

## 安裝與設定

### 1. 環境需求
- PHP 8.4 或更新版本
- Composer 相依套件已安裝
- 有效的資料庫連線設定
- 足夠的系統記憶體（推薦至少 512MB）

### 2. 設定檢查
```bash
# 確認 PHP 版本
php --version

# 確認 Composer 相依套件
composer install

# 檢查環境變數設定
cat .env | grep -E "^(DB_|REDIS_|LOG_)"
```

## 使用方法

### 基本語法
```bash
php scripts/statistics-recalculation.php [type] [start_date] [end_date] [options]
```

### 參數說明

#### 必要參數
- `type`: 統計類型 (overview|posts|users|popular)
- `start_date`: 開始日期 (YYYY-MM-DD 格式)
- `end_date`: 結束日期 (YYYY-MM-DD 格式)

#### 可選參數
- `--force, -f`: 強制覆蓋已存在的快照
- `--batch-size=N, -b=N`: 批次處理天數 (預設 30，範圍 1-365)
- `--dry-run, -d`: 試執行模式
- `--help, -h`: 顯示使用說明

## 實用範例

### 1. 基本回填操作
```bash
# 回填一個月的總覽統計
php scripts/statistics-recalculation.php overview 2024-01-01 2024-01-31

# 回填一週的文章統計
php scripts/statistics-recalculation.php posts 2024-01-01 2024-01-07
```

### 2. 進階回填操作
```bash
# 強制覆蓋已存在的資料
php scripts/statistics-recalculation.php overview 2024-01-01 2024-01-31 --force

# 自訂批次大小（每批處理 7 天）
php scripts/statistics-recalculation.php posts 2024-01-01 2024-01-31 --batch-size=7

# 試執行模式（不實際執行，只顯示計劃）
php scripts/statistics-recalculation.php users 2024-01-01 2024-01-31 --dry-run
```

### 3. 大量資料回填
```bash
# 回填整年資料（使用較小的批次大小）
php scripts/statistics-recalculation.php overview 2023-01-01 2023-12-31 --batch-size=14 --force

# 回填多個統計類型
for type in overview posts users popular; do
  php scripts/statistics-recalculation.php $type 2024-01-01 2024-01-31 --force
done
```

## 最佳實務

### 1. 執行前準備
```bash
# 1. 確認系統資源充足
free -h
df -h

# 2. 建立資料庫備份
mysqldump -u user -p database > backup_$(date +%Y%m%d_%H%M%S).sql

# 3. 先使用試執行模式確認操作
php scripts/statistics-recalculation.php overview 2024-01-01 2024-01-31 --dry-run
```

### 2. 效能最佳化
```bash
# 使用適當的批次大小
# 小批次（1-7天）：較低記憶體使用，適用於資源受限環境
# 中批次（14-30天）：平衡效能與資源使用，推薦設定
# 大批次（60-90天）：最高效能，需要充足記憶體

# 範例：針對不同情境的批次大小選擇
php scripts/statistics-recalculation.php posts 2024-01-01 2024-01-31 --batch-size=7   # 資源受限
php scripts/statistics-recalculation.php posts 2024-01-01 2024-01-31 --batch-size=30  # 一般情況
php scripts/statistics-recalculation.php posts 2024-01-01 2024-01-31 --batch-size=90  # 高效能需求
```

### 3. 錯誤處理
```bash
# 啟用詳細日誌記錄
tail -f storage/logs/statistics.log &

# 執行指令並記錄輸出
php scripts/statistics-recalculation.php overview 2024-01-01 2024-01-31 2>&1 | tee recalc_$(date +%Y%m%d_%H%M%S).log
```

## 監控與除錯

### 1. 執行狀態監控
```bash
# 監控系統資源使用
htop

# 監控資料庫連線
mysql -u user -p -e "SHOW PROCESSLIST;"

# 監控日誌檔案
tail -f storage/logs/laravel.log
```

### 2. 常見問題診斷

#### 記憶體不足錯誤
```bash
# 增加 PHP 記憶體限制
php -d memory_limit=1024M scripts/statistics-recalculation.php [參數]

# 或減少批次大小
php scripts/statistics-recalculation.php overview 2024-01-01 2024-01-31 --batch-size=7
```

#### 資料庫連線逾時
```bash
# 檢查資料庫連線設定
php -r "echo 'DB_HOST: ' . getenv('DB_HOST') . PHP_EOL;"
php -r "echo 'DB_PORT: ' . getenv('DB_PORT') . PHP_EOL;"

# 測試資料庫連線
mysql -h $DB_HOST -P $DB_PORT -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE -e "SELECT 1;"
```

## 自動化腳本範例

### 1. 定期回填腳本
```bash
#!/bin/bash
# weekly-stats-backfill.sh

set -e

LOG_DIR="/var/log/statistics"
DATE=$(date +%Y%m%d_%H%M%S)
LAST_WEEK_START=$(date -d "last monday - 1 week" +%Y-%m-%d)
LAST_WEEK_END=$(date -d "last sunday - 1 week" +%Y-%m-%d)

mkdir -p "$LOG_DIR"

echo "開始統計資料回填: $LAST_WEEK_START 至 $LAST_WEEK_END"

for TYPE in overview posts users popular; do
    echo "處理 $TYPE 統計..."
    php scripts/statistics-recalculation.php "$TYPE" "$LAST_WEEK_START" "$LAST_WEEK_END" \
        --force --batch-size=7 >> "$LOG_DIR/backfill_$DATE.log" 2>&1
done

echo "統計資料回填完成"
```

### 2. Cron 任務設定
```bash
# 每週日凌晨 2 點執行回填
0 2 * * 0 /path/to/project/scripts/weekly-stats-backfill.sh

# 每月第一天回填上個月的資料
0 3 1 * * /path/to/project/scripts/monthly-stats-backfill.sh
```

## 安全性考量

### 1. 權限管理
```bash
# 確認腳本權限
chmod 750 scripts/statistics-recalculation.php
chown www-data:www-data scripts/statistics-recalculation.php
```

### 2. 資料保護
```bash
# 執行前建立備份
mysqldump statistics_snapshots > backup_before_recalc.sql

# 使用試執行模式驗證
php scripts/statistics-recalculation.php overview 2024-01-01 2024-01-31 --dry-run
```

## 效能基準

### 典型執行時間（參考值）
- **小型資料集** (< 10,000 記錄/天): 30天批次 ~5-10 分鐘
- **中型資料集** (10,000-100,000 記錄/天): 30天批次 ~15-30 分鐘
- **大型資料集** (> 100,000 記錄/天): 30天批次 ~30-60 分鐘

### 記憶體使用
- **批次大小 7天**: ~128MB
- **批次大小 30天**: ~256MB
- **批次大小 90天**: ~512MB

## 支援與維護

### 技術支援
- 查看日誌檔案: `storage/logs/statistics.log`
- 檢查錯誤報告: `storage/logs/laravel.log`
- 系統狀態監控: `/admin/system-status`

### 回報問題
請在問題回報中包含：
1. 完整的錯誤訊息
2. 執行的完整指令
3. 系統環境資訊 (`php --version`, `composer --version`)
4. 相關的日誌檔案內容
