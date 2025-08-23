# Database 目錄

本目錄包含 AlleyNote 專案的資料庫相關檔案。

## 目錄結構

```
database/
├── migrations/           # 資料庫遷移檔案
├── backups/             # 資料庫備份檔案（.gitignore 忽略）
├── alleynote.db         # SQLite 主要資料庫檔案（.gitignore 忽略）
└── README.md           # 本說明檔案
```

## SQLite 資料庫

本專案使用 SQLite3 作為資料庫引擎，具有以下特點：

### 優勢
- ✅ 零設定，無需額外的資料庫伺服器
- ✅ 檔案型資料庫，易於備份和遷移
- ✅ 支援 ACID 交易和外鍵約束
- ✅ 適合中小型應用程式
- ✅ 跨平台相容性好

### 檔案說明

- **`alleynote.db`**: 主要的 SQLite 資料庫檔案
- **`migrations/`**: 包含資料庫結構定義和遷移腳本
- **`backups/`**: 自動備份檔案儲存位置

## 管理指令

### 初始化資料庫
```bash
# 建立並初始化 SQLite 資料庫
./scripts/init-sqlite.sh
```

### 備份資料庫
```bash
# 手動備份
./scripts/backup_sqlite.sh

# 設定定期備份（建議每日執行）
0 2 * * * /var/www/html/scripts/backup_sqlite.sh
```

### 還原資料庫
```bash
# 從備份檔案還原
./scripts/restore_sqlite.sh /path/to/backup.db.gz

# 列出可用的備份檔案
ls -la backups/alleynote_backup_*.db.gz
```

### 直接操作資料庫
```bash
# 進入 SQLite 命令列介面
sqlite3 alleynote.db

# 常用 SQLite 指令
.tables                 # 列出所有表格
.schema table_name     # 顯示表格結構
.dump                  # 匯出整個資料庫
.quit                  # 退出
```

## 安全注意事項

1. **權限設定**: 確保資料庫檔案權限設為 `664`，擁有者為 `www-data`
2. **備份策略**: 定期備份資料庫，建議每日執行
3. **檔案保護**: 資料庫檔案已加入 `.gitignore`，不會被版本控制
4. **災難恢復**: 保留多個備份檔案，並定期測試還原程序

## 疑難排解

### 權限問題
```bash
# 修復資料庫檔案權限
sudo chown www-data:www-data alleynote.db
sudo chmod 664 alleynote.db
```

### 資料庫鎖定問題
```bash
# 檢查是否有程序正在使用資料庫
lsof alleynote.db

# 重建資料庫（如果檔案損壞）
rm alleynote.db
./scripts/init-sqlite.sh
```

### 遷移失敗
```bash
# 檢查資料庫完整性
sqlite3 alleynote.db "PRAGMA integrity_check;"

# 手動執行特定遷移
php -r "require 'migrations/001_create_posts_tables.php'; ..."
```

## 效能調整

SQLite 可透過以下方式提升效能：

```sql
-- 啟用 WAL 模式（寫前日誌）
PRAGMA journal_mode=WAL;

-- 調整同步模式
PRAGMA synchronous=NORMAL;

-- 增加快取大小
PRAGMA cache_size=10000;

-- 啟用記憶體暫存
PRAGMA temp_store=MEMORY;
```

這些設定已在應用程式的 DatabaseConnection 類別中自動套用。
