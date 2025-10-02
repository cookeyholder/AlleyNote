# 現代化資料庫初始化指南

## 概述

`modern-init-sqlite.sh` 是基於 Context7 MCP 查詢 Phinx 和 SQLite 最佳實踐開發的現代化資料庫初始化腳本，取代了舊的 `init-sqlite.sh`。

## 新功能特色

### 🚀 現代化改進

- **Phinx 原生支援**: 完全使用 Phinx 框架進行遷移管理，不再手動執行 PHP 檔案
- **多環境支援**: 支援 development、testing、production 環境
- **Docker 環境檢測**: 自動檢測並適配 Docker 容器環境
- **智能錯誤處理**: 嚴格模式執行，完善的錯誤處理和回滾機制
- **自動備份**: 強制重新初始化時自動備份現有資料庫
- **完整性驗證**: 初始化後自動驗證資料庫完整性
- **效能最佳化**: 自動執行 SQLite 最佳化設定

### 📊 功能對比

| 功能 | 舊腳本 (init-sqlite.sh) | 新腳本 (modern-init-sqlite.sh) |
|------|-------------------------|--------------------------------|
| 遷移管理 | 手動執行 PHP 檔案 | Phinx 框架原生支援 |
| 環境支援 | 單一環境 | 多環境 (dev/test/prod) |
| Docker 支援 | 基本支援 | 智能檢測與適配 |
| 錯誤處理 | 基本 | 完善的錯誤處理和日誌 |
| 備份機制 | 無 | 自動備份與版本管理 |
| 完整性檢查 | 無 | 自動完整性驗證 |
| 最佳化 | 無 | SQLite 效能最佳化 |
| 進度回報 | 基本 | 詳細進度與統計資訊 |

## 使用方法

### 基本語法

```bash
./scripts/modern-init-sqlite.sh [OPTIONS] [ENVIRONMENT]
```

### 參數說明

- `ENVIRONMENT`: 目標環境
  - `development` (預設)
  - `testing` 
  - `production`

### 選項

- `-h, --help`: 顯示說明
- `-f, --force`: 強制重新初始化（備份現有資料庫）
- `-s, --skip-seeds`: 跳過種子資料執行
- `-d, --debug`: 啟用除錯模式
- `-n, --dry-run`: 模擬執行（不實際修改資料庫）
- `-q, --quiet`: 靜默模式（只顯示錯誤）

### 環境變數

- `DEBUG=true`: 啟用除錯日誌
- `IS_DOCKER=true`: 強制 Docker 模式
- `COMPOSER_CMD`: 自訂 Composer 命令
- `PHINX_CMD`: 自訂 Phinx 命令

## 使用範例

### 開發環境

```bash
# 基本初始化
./scripts/modern-init-sqlite.sh

# 強制重新初始化（會備份現有資料庫）
./scripts/modern-init-sqlite.sh --force development

# 除錯模式
./scripts/modern-init-sqlite.sh --debug development
```

### 測試環境

```bash
# 初始化測試環境
./scripts/modern-init-sqlite.sh testing

# 跳過種子資料的初始化
./scripts/modern-init-sqlite.sh --skip-seeds testing
```

### 生產環境

```bash
# 生產環境初始化（自動跳過種子資料）
./scripts/modern-init-sqlite.sh production

# 靜默模式執行
./scripts/modern-init-sqlite.sh --quiet production
```

### Docker 環境

```bash
# 在 Docker 容器內執行
sudo docker compose exec web ./scripts/modern-init-sqlite.sh development

# Docker 環境的強制重新初始化
sudo docker compose exec web ./scripts/modern-init-sqlite.sh --force --debug development
```

### 模擬執行

```bash
# 檢查腳本會執行什麼操作，但不實際修改資料庫
./scripts/modern-init-sqlite.sh --dry-run development
```

## 執行流程

### 1. 依賴檢查

- PHP 版本檢查
- Composer 可用性檢查
- Phinx 設定檔驗證
- vendor 目錄檢查

### 2. 目錄設定

- 建立必要目錄結構
- 設定適當權限

### 3. 備份處理

- 檢查現有資料庫
- 自動備份（如需要）
- 備份檔案版本管理

### 4. 遷移執行

- Phinx 狀態檢查
- 執行未完成的遷移
- 顯示遷移狀態

### 5. 種子資料

- 檢查種子檔案
- 執行種子資料（非生產環境）

### 6. 最佳化

- SQLite PRAGMA 設定
- 查詢計劃器統計更新

### 7. 驗證

- 資料庫完整性檢查
- 顯示資料庫資訊和統計

## 日誌和備份

### 日誌檔案

```
database/init.log
```

### 備份目錄

```
database/backups/
```

備份檔案命名格式：
```
[原檔名].backup.[YYYYMMDD_HHMMSS]
```

### 備份檔案管理

- 自動保留最近 10 個備份檔案
- 舊備份檔案會自動清理

## 錯誤處理

### 常見錯誤和解決方案

1. **PHP 未安裝**
   ```
   [ERROR] PHP 未安裝或不在 PATH 中
   ```
   解決方案：確認 PHP 已安裝且可執行

2. **Phinx 設定檔不存在**
   ```
   [ERROR] Phinx 配置檔案不存在: /path/to/phinx.php
   ```
   解決方案：確認 phinx.php 存在於專案根目錄

3. **vendor 目錄不存在**
   ```
   [WARN] vendor 目錄不存在，嘗試執行 composer install...
   ```
   解決方案：腳本會自動執行 `composer install`

4. **資料庫完整性驗證失敗**
   ```
   [ERROR] 資料庫完整性驗證失敗: [詳細錯誤]
   ```
   解決方案：檢查資料庫檔案是否損壞，考慮從備份恢復

## 與 DDD 架構整合

### 遷移檔案結構

```
database/migrations/
├── 20250823051608_initial_schema.php       # 基礎架構
├── 20250825165731_create_refresh_tokens_table.php    # JWT 認證
├── 20250825165750_create_token_blacklist_table.php   # Token 管理
└── 20250826023305_add_token_hash_to_refresh_tokens_table.php  # 增強功能
```

### 領域模型支援

腳本確保以下領域模型的資料表正確建立：

- **Auth Domain**: users, roles, permissions, refresh_tokens, token_blacklist
- **Post Domain**: posts, tags, post_tags, post_views
- **Security Domain**: ip_lists
- **System**: phinxlog (Phinx 遷移日誌)

## 效能特色

### SQLite 最佳化設定

```sql
PRAGMA foreign_keys = ON;        -- 啟用外鍵約束
PRAGMA journal_mode = WAL;       -- 使用 WAL 模式提升併發
PRAGMA synchronous = NORMAL;     -- 平衡安全性和效能
PRAGMA cache_size = 10000;       -- 增加快取大小
PRAGMA temp_store = MEMORY;      -- 暫存資料使用記憶體
PRAGMA optimize;                 -- 查詢計劃器最佳化
```

## 安全性考量

### 生產環境保護

- 生產環境自動跳過種子資料執行
- 強制重新初始化前自動備份
- 完整性驗證防止資料損壞
- 詳細日誌記錄所有操作

### 權限管理

- 自動設定適當的檔案權限
- Docker 環境適配
- 敏感操作需要明確參數確認

## 與舊腳本的遷移

### 遷移步驟

1. **備份現有資料**
   ```bash
   cp database/alleynote.sqlite3 database/alleynote.sqlite3.manual.backup
   ```

2. **測試新腳本**
   ```bash
   ./scripts/modern-init-sqlite.sh --dry-run development
   ```

3. **執行遷移**
   ```bash
   ./scripts/modern-init-sqlite.sh --force development
   ```

### 功能映射

| 舊功能 | 新功能 |
|--------|--------|
| 建立資料庫檔案 | Phinx 自動處理 |
| 執行 PHP 遷移檔案 | `phinx migrate` |
| 執行 SQL 檔案 | 不再需要 |
| 顯示表格列表 | 詳細資料庫資訊 |
| 基本權限設定 | 完整權限和最佳化 |

## 疑難排解

### 常見問題

**Q: 如何恢復到舊的資料庫狀態？**

A: 使用備份檔案：
```bash
cp database/backups/alleynote.sqlite3.backup.[timestamp] database/alleynote.sqlite3
```

**Q: 如何清理所有資料重新開始？**

A: 使用強制重新初始化：
```bash
./scripts/modern-init-sqlite.sh --force development
```

**Q: 如何只執行遷移不執行種子資料？**

A: 使用 --skip-seeds 選項：
```bash
./scripts/modern-init-sqlite.sh --skip-seeds development
```

**Q: 如何在 CI/CD 中使用？**

A: 使用靜默模式：
```bash
./scripts/modern-init-sqlite.sh --quiet testing
```

## 結論

新的現代化資料庫初始化腳本提供了：

✅ **可靠性**: Phinx 框架保證的遷移管理  
✅ **安全性**: 自動備份和完整性驗證  
✅ **效能**: SQLite 最佳化設定  
✅ **靈活性**: 多環境和多種執行模式  
✅ **可觀測性**: 詳細日誌和進度報告  
✅ **現代化**: 基於最新最佳實踐開發  

這個腳本完全符合專案的 DDD 架構需求，並為未來的擴展提供了堅實的基礎。