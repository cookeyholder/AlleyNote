# 已驗證的指令參考

**驗證日期**: 2025-10-02  
**容器工作目錄**: `/var/www/html`（對應專案的 `backend/` 目錄）

---

## ✅ 日常開發（推薦）

### 1. 完整 CI 檢查
```bash
docker compose exec web composer ci
```
**說明**: 執行程式碼風格檢查、靜態分析和測試（最推薦的品質檢查方式）

### 2. 程式碼風格自動修復
```bash
docker compose exec web composer cs-fix
```
**說明**: 自動修復程式碼風格問題

### 3. 程式碼風格檢查（不修復）
```bash
docker compose exec web composer cs-check
```
**說明**: 只檢查不修復，顯示差異

### 4. PHPStan 靜態分析
```bash
docker compose exec web composer analyse
```
**說明**: 執行 PHPStan Level 10 檢查

### 5. 執行測試
```bash
docker compose exec web composer test
```
**說明**: 執行所有測試

### 6. 測試覆蓋率
```bash
docker compose exec web composer test:coverage
```
**說明**: 執行測試並生成覆蓋率報告

---

## 🔍 分析工具

### 1. 程式碼品質完整分析（標準參考）
```bash
docker compose exec -T web php scripts/Analysis/analyze-code-quality.php
```
**說明**: 
- 分析 PSR-4 合規性、現代 PHP 特性、DDD 架構
- 生成詳細的品質報告
- 建議每週執行一次
- **這是唯一的官方程式碼品質分析工具**

**輸出位置**: `backend/storage/code-quality-analysis.md`

### 2. 掃描缺少回傳型別的函式
```bash
docker compose exec -T web php scripts/Analysis/scan-missing-return-types.php
```
**說明**: 掃描所有缺少回傳型別宣告的函式

**輸出位置**: `backend/storage/missing-return-types.md`

---

## 📊 統計工具

### 1. 統計計算定時任務
```bash
# 計算每日統計
docker compose exec web php scripts/Core/statistics-calculation.php --periods=daily

# 計算每日和每週統計
docker compose exec web php scripts/Core/statistics-calculation.php --periods=daily,weekly

# 顯示幫助訊息
docker compose exec web php scripts/Core/statistics-calculation.php --help
```
**說明**: 用於定時任務的統計計算（Cron）

### 2. 統計資料回填
```bash
# 回填 overview 統計（2024 年 1 月）
php scripts/statistics-recalculation.php overview 2024-01-01 2024-01-31 --force

# 回填 posts 統計
php scripts/statistics-recalculation.php posts 2024-01-01 2024-01-31

# 回填 users 統計
php scripts/statistics-recalculation.php users 2024-01-01 2024-01-31
```
**說明**: 用於手動回填歷史統計資料（從主機執行，不在容器內）

---

## 🛠️ 維護工具

### 1. 快取預熱
```bash
docker compose exec web php scripts/Maintenance/warm-cache.php
```

### 2. 配置驗證
```bash
docker compose exec web php scripts/Maintenance/validate-config.php
```

### 3. 文章來源資訊更新
```bash
docker compose exec web php scripts/Maintenance/update-posts-source-info.php
```

---

## 🗄️ 資料庫工具

### SQLite 初始化
```bash
docker compose exec web bash scripts/Database/init-sqlite.sh
```

### 資料庫遷移
```bash
docker compose exec web bash scripts/Database/migrate.sh
```

### 資料庫備份
```bash
docker compose exec web bash scripts/Database/backup_sqlite.sh
```

### 資料庫還原
```bash
docker compose exec web bash scripts/Database/restore_sqlite.sh
```

---

## 🚀 部署工具

### SSL 設定
```bash
docker compose exec web bash scripts/Deployment/ssl-setup.sh
```

### SSL 憑證更新
```bash
docker compose exec web bash scripts/Deployment/ssl-renew.sh
```

---

## ⚠️ 常見錯誤與解決方案

### 錯誤 1: Could not open input file
```bash
# ❌ 錯誤的指令（多了 backend/）
docker compose exec -T web php backend/scripts/Analysis/analyze-code-quality.php

# ✅ 正確的指令
docker compose exec -T web php scripts/Analysis/analyze-code-quality.php
```
**原因**: 容器的工作目錄已經是 `/var/www/html`（對應 `backend/`），不需要再加 `backend/` 前綴

### 錯誤 2: 記憶體不足
```bash
# 如果遇到記憶體不足，可以增加記憶體限制
docker compose exec -T web php -d memory_limit=512M scripts/Analysis/analyze-code-quality.php
```
**說明**: 通常不需要額外設定，預設記憶體已足夠

---

## 📋 路徑對應關係

| 主機路徑 | 容器路徑 | 容器內使用 |
|---------|---------|-----------|
| `backend/` | `/var/www/html/` | （工作目錄） |
| `backend/scripts/` | `/var/www/html/scripts/` | `scripts/` |
| `backend/app/` | `/var/www/html/app/` | `app/` |
| `backend/vendor/` | `/var/www/html/vendor/` | `vendor/` |
| `backend/storage/` | `/var/www/html/storage/` | `storage/` |

---

## 🎯 快速參考

| 任務 | 指令 |
|------|------|
| 日常品質檢查 | `docker compose exec web composer ci` |
| 自動修復風格 | `docker compose exec web composer cs-fix` |
| 詳細品質分析 | `docker compose exec -T web php scripts/Analysis/analyze-code-quality.php` |
| 執行測試 | `docker compose exec web composer test` |
| 統計計算 | `docker compose exec web php scripts/Core/statistics-calculation.php` |

---

## ✅ 驗證記錄

所有指令已於 2025-10-02 驗證通過：

- ✅ Composer CI 管道
- ✅ 程式碼品質分析工具
- ✅ 回傳型別掃描工具
- ✅ 統計計算工具
- ✅ 所有維護腳本

**測試環境**:
- Docker Compose 版本: v2.39.2
- PHP 版本: 8.4.12
- Composer 版本: 2.8.11

---

**建議**: 將此文件加入書籤，作為日常開發的指令參考。
