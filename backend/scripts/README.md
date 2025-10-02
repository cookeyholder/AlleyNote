# AlleyNote Scripts Directory

> **📌 經過重新整理的腳本目錄，採用 PSR-4 命名空間和現代 PHP 架構**

## 📁 目錄結構

### Analysis/ - 分析工具
- `analyze-code-quality.php` - **主要程式碼品質分析工具**（使用 CodeQualityAnalyzer）
- `scan-missing-return-types.php` - 掃描缺少回傳型別的函式

### Archive/ - 已封存的舊工具
- `Consolidated*.php` - 舊的統一腳本系統（已由 Composer scripts 取代）
- `Default*.php` - 舊的預設腳本（已不再使用）
- `ScriptManager.php` - 舊的腳本管理器（已由 Composer scripts 取代）
- `unified-scripts.php` - 舊的統一入口點（已由 Composer scripts 取代）

### Core/ - 核心腳本工具
- `generate-swagger-docs.php` - Swagger 文件生成器
- `statistics-calculation.php` - 統計計算定時任務

### Database/ - 資料庫相關腳本
- `backup_db.sh` - 資料庫備份
- `backup_sqlite.sh` - SQLite 備份
- `init-sqlite.sh` - SQLite 初始化
- `migrate.sh` - 資料庫遷移
- `restore_db.sh` - 資料庫還原
- `restore_sqlite.sh` - SQLite 還原

### Deployment/ - 部署腳本
- `deploy.sh` - 部署腳本
- `rollback.sh` - 回滾腳本
- `ssl-renew.sh` - SSL 憑證更新
- `ssl-setup.sh` - SSL 設定

### lib/ - 共用函式庫
- `CodeQualityAnalyzer.php` - **主要程式碼品質分析器**（取代 ArchitectureScanner）
- `ConsoleOutput.php` - 控制台輸出工具

### Maintenance/ - 維護腳本
- `backup_files.sh` - 檔案備份
- `cache-cleanup.sh` - 快取清理
- `restore_files.sh` - 檔案還原
- `update-posts-source-info.php` - 文章來源資訊更新
- `validate-config.php` - 配置驗證
- `warm-cache.php` - 快取預熱

### Quality/ - 程式碼品質工具
- `check-environment.sh` - 環境檢查
- `PhpstanFixer.php` - PHPStan 修復工具
- `UnifiedSyntaxFixer.php` - 統一語法修復工具

### 根目錄檔案
- `ScriptBootstrap.php` - 腳本統一載入器
- `README.md` - 本說明文件

## 🚀 使用方法

### 主要工具鏈（推薦）

使用 Composer scripts 執行（最簡單）：
```bash
# 完整 CI 檢查（程式碼風格 + 靜態分析 + 測試）
docker compose exec web composer ci

# 程式碼風格檢查
docker compose exec web composer cs-check

# 程式碼風格自動修復
docker compose exec web composer cs-fix

# PHPStan 靜態分析
docker compose exec web composer analyse

# 執行測試
docker compose exec web composer test

# 測試覆蓋率
docker compose exec web composer test:coverage
```

### 分析工具

```bash
# 程式碼品質完整分析（標準參考）
docker compose exec -T web php scripts/Analysis/analyze-code-quality.php

# 掃描缺少回傳型別的函式
docker compose exec -T web php scripts/Analysis/scan-missing-return-types.php
```

### 統計工具

```bash
# 統計計算定時任務
docker compose exec web php scripts/Core/statistics-calculation.php --periods=daily,weekly

# 統計資料回填（位於根目錄 scripts/）
php scripts/statistics-recalculation.php overview 2024-01-01 2024-01-31 --force
```

## 📊 最近一次整理成果（2025-10-02）

### 移除的工具
- ✅ **ArchitectureScanner** - 由 CodeQualityAnalyzer 完全取代
- ✅ **舊的統一腳本系統** - 移至 Archive/（Consolidated*, Default*, ScriptManager, unified-scripts）
- ✅ 原因：功能重複，且 Composer scripts 提供更好的工具鏈

### 保留的核心工具
- ✅ **CodeQualityAnalyzer** - 唯一的程式碼品質分析工具
- ✅ **Composer scripts** - CI/CD 標準管道
- ✅ 統計相關腳本 - 業務功能必需

### 工具選擇原則
- **一件事只用一項工具** - 避免功能重複
- **優先使用標準工具** - Composer scripts, PHPStan, PHP CS Fixer
- **保留業務必需工具** - 統計、部署、維護腳本
- **封存過時工具** - 移至 Archive/ 而非刪除

## 🔧 開發指南

### 添加新腳本

1. 根據功能放在適當的目錄中
2. 使用適當的 PSR-4 命名空間
3. 在檔案開頭添加 `declare(strict_types=1);`
4. 更新此 README.md
5. 確保不與現有工具重複

### 修改現有腳本

1. 確保 autoload 路徑正確：`require_once __DIR__ . '/../../vendor/autoload.php';`
2. 遵循現代 PHP 最佳實踐
3. 執行 `composer ci` 確保品質

## 📝 最佳實踐

- 優先使用 `composer ci` 進行品質檢查
- 使用 `CodeQualityAnalyzer` 作為品質分析的標準參考
- 所有腳本都應該使用 `docker compose exec` 執行
- 遵循 DDD 和 PSR-4 原則
- 避免建立重複功能的工具

---

**最近更新**: 2025-10-02  
**維護者**: GitHub Copilot  
**遵循指南**: [copilot-instructions.md](../.github/copilot-instructions.md)
