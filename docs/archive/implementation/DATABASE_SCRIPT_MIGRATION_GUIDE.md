# 資料庫腳本遷移指南

## 📋 概述

本指南說明 AlleyNote 專案中資料庫相關 shell script 的現代化升級與使用方式。

## 🔄 腳本變更歷程

### 已移除的舊版腳本

| 舊版腳本 | 狀態 | 替代方案 |
|---------|------|----------|
| `backup_db.sh` | ❌ 已移除 | `backup_sqlite.sh` |
| `restore_db.sh` | ❌ 已移除 | `restore_sqlite.sh` |
| `modern-init-sqlite.sh` | ❌ 已移除 | 功能已整合到 `init-sqlite.sh` |

### 現代化腳本列表

| 腳本名稱 | 功能 | 狀態 |
|---------|------|------|
| `init-sqlite.sh` | SQLite 資料庫初始化 | ✅ 現代化完成 |
| `backup_sqlite.sh` | SQLite 資料庫備份 | ✅ 現代化完成 |
| `restore_sqlite.sh` | SQLite 資料庫還原 | ✅ 現代化完成 |

## 🚀 現代化功能特色

### 統一特色
- ✅ 嚴謹錯誤處理 (`set -euo pipefail`)
- ✅ Docker 環境自動檢測
- ✅ 彩色日誌輸出與檔案記錄
- ✅ 詳細的參數解析與 `--help` 說明
- ✅ 環境變數支援
- ✅ 完整性驗證與統計資訊顯示

### 特殊功能
- **壓縮支援**: gzip、bzip2、xz 多種壓縮格式
- **自動備份**: 操作前可自動備份現有資料
- **互動模式**: 支援選單式操作與強制模式
- **效能最佳化**: SQLite WAL 模式、PRAGMA 最佳化設定
- **清理機制**: 自動清理舊備份檔案

## 📝 使用範例

### 資料庫初始化
```bash
# 基本初始化
./scripts/init-sqlite.sh

# 指定環境初始化
./scripts/init-sqlite.sh -e testing -f -v

# 生產環境初始化（包含備份與驗證）
./scripts/init-sqlite.sh -e production -b -v
```

### 資料庫備份
```bash
# 預設備份
./scripts/backup_sqlite.sh

# 壓縮備份並驗證
./scripts/backup_sqlite.sh -v -c gzip

# 列出現有備份
./scripts/backup_sqlite.sh --list

# 清理舊備份
./scripts/backup_sqlite.sh --cleanup
```

### 資料庫還原
```bash
# 互動式選擇備份還原
./scripts/restore_sqlite.sh

# 自動選擇最新備份還原
./scripts/restore_sqlite.sh --auto

# 指定備份檔案還原（包含預先備份與驗證）
./scripts/restore_sqlite.sh -b -v backup.sqlite3.gz
```

## 🔧 遷移指引

### CI/CD 腳本更新
如果您的 CI/CD 管道使用了舊版腳本，請更新如下：

**舊版**:
```bash
./scripts/backup_db.sh /path/to/db.sqlite3 /path/to/backup.sqlite3
./scripts/restore_db.sh /path/to/backup.sqlite3 /path/to/db.sqlite3
```

**新版**:
```bash
./scripts/backup_sqlite.sh -c gzip -v /path/to/db.sqlite3 /path/to/backup.sqlite3
./scripts/restore_sqlite.sh -b -v /path/to/backup.sqlite3.gz /path/to/db.sqlite3
```

### Docker Compose 整合
```yaml
# docker-compose.yml
services:
  web:
    # ... 其他設定
    volumes:
      - ./scripts:/app/scripts
    
  # 資料庫初始化服務
  db-init:
    extends: web
    command: /app/scripts/init-sqlite.sh -e production -b -v
    profiles: ["init"]
```

## 📚 相關文件

- [現代化資料庫初始化指南](MODERN_DATABASE_INITIALIZATION_GUIDE.md)
- [資料庫腳本現代化分析報告](DATABASE_SCRIPT_MODERNIZATION_ANALYSIS.md)
- [部署指南](DEPLOYMENT.md)
- [管理員手冊](ADMIN_MANUAL.md)

## 🔍 故障排除

### 常見問題

**問題**: 腳本執行權限錯誤
```bash
# 解決方案
chmod +x scripts/*.sh
```

**問題**: 找不到 Phinx 指令
```bash
# 解決方案
composer install
```

**問題**: SQLite 檔案權限問題
```bash
# 解決方案
sudo chown -R www-data:www-data database/
chmod 664 database/*.sqlite3
```

### 日誌檢查
所有腳本都會將詳細日誌記錄到 `storage/logs/` 目錄：
- 初始化日誌: `storage/logs/database-init-YYYYMMDD.log`
- 備份日誌: `storage/logs/backup-YYYYMMDD.log`
- 還原日誌: `storage/logs/restore-YYYYMMDD.log`

## 🏆 最佳實踐

1. **定期備份**: 設定 cron job 定期執行備份
2. **測試還原**: 定期測試備份檔案的還原功能
3. **監控日誌**: 定期檢查操作日誌確保無異常
4. **環境隔離**: 不同環境使用不同的配置參數
5. **版本控制**: 重要操作前建立安全備份點

---

*最後更新: 2025-08-26*
*版本: 1.0.0*