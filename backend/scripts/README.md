# AlleyNote Scripts Directory

> **📌 經過重新整理的腳本目錄，採用 PSR-4 命名空間和現代 PHP 架構**

## 📁 目錄結構

### Analysis/ - 分析工具
- `analyze-code-quality.php` - 程式碼品質分析腳本
- `scan-project-architecture.php` - 專案架構掃描工具

### CI/ - 持續整合腳本
- `ci-generate-docs.sh` - 自動生成文件
- `ci-test.sh` - CI 測試腳本
- `create-activity-log-test.sh` - 活動日誌測試建立
- `docker-entrypoint.sh` - Docker 容器進入點

### Core/ - 核心腳本工具
- `ConsolidatedAnalyzer.php` - 整合分析器
- `ConsolidatedDeployer.php` - 整合部署器
- `ConsolidatedErrorFixer.php` - 整合錯誤修復器
- `ConsolidatedMaintainer.php` - 整合維護工具
- `ConsolidatedTestManager.php` - 整合測試管理器
- `DefaultScriptAnalyzer.php` - 預設腳本分析器
- `DefaultScriptConfiguration.php` - 預設腳本配置
- `DefaultScriptExecutor.php` - 預設腳本執行器
- `ScriptManager.php` - 腳本管理器
- `generate-swagger-docs.php` - Swagger 文件生成器
- `statistics-calculation.php` - 統計計算腳本
- `unified-scripts.php` - 統一腳本管理工具

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
- `ArchitectureScanner.php` - 架構掃描器
- `CodeQualityAnalyzer.php` - 程式碼品質分析器
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
- `phpstan-fixer.php` - 統一的 PHPStan 修復工具
- `unified-syntax-fixer.php` - 統一的語法修復工具

### 根目錄檔案
- `ScriptBootstrap.php` - 腳本統一載入器
- `README.md` - 本說明文件

## 🚀 使用方法

### 基本原則

所有 PHP 腳本現在都使用 PSR-4 命名空間：
- `AlleyNote\Scripts\Analysis\*` - 分析工具
- `AlleyNote\Scripts\Core\*` - 核心工具
- `AlleyNote\Scripts\Quality\*` - 品質工具
- `AlleyNote\Scripts\Maintenance\*` - 維護工具
- `AlleyNote\Scripts\Lib\*` - 共用函式庫

### 執行方式

使用 Docker 容器執行（推薦）：
```bash
# 程式碼品質分析
docker-compose exec web php scripts/Analysis/analyze-code-quality.php

# 專案架構掃描
docker-compose exec web php scripts/Analysis/scan-project-architecture.php

# PHPStan 錯誤修復
docker-compose exec web php scripts/Quality/phpstan-fixer.php --list
docker-compose exec web php scripts/Quality/phpstan-fixer.php type-hints

# 統一語法修復
docker-compose exec web php scripts/Quality/unified-syntax-fixer.php --list
docker-compose exec web php scripts/Quality/unified-syntax-fixer.php basic-syntax
```

### 共用載入器使用

所有腳本都可以使用 `ScriptBootstrap` 進行統一初始化：

```php
<?php
use function AlleyNote\Scripts\bootstrap;
use function AlleyNote\Scripts\script_output;

// 初始化腳本環境
$bootstrap = bootstrap();

// 輸出格式化訊息
script_output('開始執行腳本...', 'info');
script_output('執行成功！', 'success');
script_output('發生警告', 'warning');
script_output('執行失敗', 'error');
```

## 📊 重新整理成果

### 文件數量變化
- **整理前**: 94 個檔案 (74 PHP + 20 Shell)
- **整理後**: 40 個檔案 (22 PHP + 18 Shell)
- **減少率**: 57%

### 已刪除的冗餘檔案 (59 個)

#### PHPStan 修復工具 (15 個) → 整合為 `Quality/phpstan-fixer.php`
- fix-phpstan-attributes.php
- fix-phpstan-callable-errors.php
- fix-phpstan-core-fixes.php
- fix-phpstan-generics.php
- fix-phpstan-iterables.php
- fix-phpstan-method-calls.php
- fix-phpstan-mixed-types.php
- fix-phpstan-null-check.php
- fix-phpstan-return-types.php
- fix-phpstan-type-hints.php
- fix-phpstan-undefined-variables.php
- fix-phpstan-union-types.php
- fix-phpstan-unused-variables.php
- phpstan-auto-fixer.php
- phpstan-final-fixes.php

#### 語法修復工具 (10 個) → 整合為 `Quality/unified-syntax-fixer.php`
- fix-basic-syntax.php
- fix-constructor-promotion.php
- fix-generics.php
- fix-match-expressions.php
- fix-mixed-types.php
- fix-modern-php.php
- fix-nullsafe-operators.php
- fix-string-interpolation.php
- fix-syntax-errors.php
- fix-union-types.php

#### 其他冗餘工具 (34 個)
[包含各種重複的分析、配置、維護工具等]

### 新增的統一工具

#### `Quality/phpstan-fixer.php` - 統一 PHPStan 修復工具
支援的修復類型：
- `type-hints` - 修復型別提示問題
- `generics` - 修復泛型語法問題
- `null-checks` - 修復 null 檢查問題
- `iterables` - 修復 iterable 型別問題
- `mixed-types` - 修復 mixed 型別問題
- `undefined-variables` - 修復未定義變數問題

#### `Quality/unified-syntax-fixer.php` - 統一語法修復工具
支援的修復類型：
- `basic-syntax` - 基本語法修復
- `generics` - 泛型語法修復
- `string-interpolation` - 字串插值修復
- `match-expressions` - Match 表達式修復
- `constructor-promotion` - 建構子屬性提升
- `nullsafe-operators` - 空安全運算子修復

## 🔧 開發指南

### 添加新腳本

1. 根據功能放在適當的目錄中
2. 使用適當的 PSR-4 命名空間
3. 在檔案開頭添加 `declare(strict_types=1);`
4. 使用 `ScriptBootstrap` 進行初始化
5. 更新此 README.md

### 修改現有腳本

1. 確保 autoload 路徑正確：`require_once __DIR__ . '/../../vendor/autoload.php';`
2. 遵循現代 PHP 最佳實踐
3. 執行本地程式碼品質檢查

## 📝 最佳實踐

- 所有腳本都應該使用 `docker-compose exec web` 執行
- 重要的腳本都應該有錯誤處理和日誌記錄
- 使用統一的訊息輸出格式
- 遵循 DDD 和 PSR-4 原則
- 定期執行程式碼品質分析

---

**本次重新整理完成於**: 2025-09-28
**維護者**: GitHub Copilot
**遵循指南**: [copilot-instructions.md](../.github/copilot-instructions.md)
