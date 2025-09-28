# AlleyNote 開發者指南

> 📚 **完整開發指南**：環境設置、開發流程、測試策略與統計模組開發

**版本**: v5.2
**最後更新**: 2025-09-27
**適用版本**: PHP 8.4.12 + Docker 28.3.3 + Docker Compose v2.39.2

---

## 📑 目錄

- [快速開始](#快速開始)
- [開發環境設置](#開發環境設置)
- [技術架構](#技術架構)
- [開發工作流程](#開發工作流程)
- [統計模組開發](#統計模組開發)
- [測試策略](#測試策略)
- [程式碼品質](#程式碼品質)
- [故障排除](#故障排除)

---

## 🚀 快速開始

### 1. 專案克隆與基本設置

```bash
# 複製專案
git clone https://github.com/your-org/alleynote.git
cd alleynote

# 環境設定
cp .env.example .env
# 編輯 .env 檔案設定資料庫、快取等
```

### 2. 啟動開發環境

```bash
# 啟動 Docker 容器
docker compose up -d

# 安裝相依套件
docker compose exec web composer install

# 初始化資料庫
docker compose exec web php backend/scripts/init-sqlite.sh

# 執行完整測試套件（第一次執行時間較長）
docker compose exec -T web ./vendor/bin/phpunit
```

### 3. 環境資訊

#### 測試環境狀態
- **測試框架**: PHPUnit 11.x（版本依 `composer.lock` 為準）
- **執行建議**: `docker compose exec -T web ./vendor/bin/phpunit`
- **測試統計**: 1,300+ 測試案例，以 CI 報告為準，提交前請執行 `composer ci`
- **架構掃描**: 建議每次功能開發前執行 `php backend/scripts/scan-project-architecture.php`

#### 技術堆疊
- **後端**: PHP 8.4.12（DDD 分層架構 + 統計模組）
- **前端**: Vite 5 + TypeScript + Axios + Tailwind CSS
- **容器化**: Docker 28.3.3 & Docker Compose v2.39.2
- **資料庫**: SQLite3（預設） / PostgreSQL 16（大型部署）
- **快取**: Redis（快取標籤系統 + 統計快照）

### 4. 第一次開發提交

```bash
# 建立新功能分支
git checkout -b feature/my-first-feature

# 開發過程中進行測試與檢查
docker compose exec -T web ./vendor/bin/phpunit           # 執行測試
docker compose exec -T web ./vendor/bin/php-cs-fixer fix # 修正程式碼風格
docker compose exec -T web ./vendor/bin/phpstan analyse  # 靜態分析

# 提交前的完整檢查
docker compose exec -T web composer ci

# 提交變更
git add .
git commit -m "feat: 新增功能描述"
```

---

## 🛠️ 開發環境設置

### 系統需求
- **Docker**: 28.3.3+
- **Docker Compose**: v2.39.2+
- **Git**: 2.0+
- **Node.js**: 18.0+ (前端開發)

### 環境配置檔案

#### `.env` 主要配置
```env
# 應用程式設定
APP_ENV=development
APP_DEBUG=true

# 資料庫設定
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/alleynote.sqlite3

# Redis 快取設定
CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

# 統計模組設定
STATISTICS_CACHE_TTL=3600
STATISTICS_BATCH_SIZE=100
```

```bash
---

## 🏗️ 技術架構

### DDD 分層架構
AlleyNote 採用領域驅動設計（Domain-Driven Design），分為四個核心層次：

#### 🎯 領域層 (Domain)
- **Business Logic**: 核心業務邏輯
- **Entities**: 業務實體（Post、User、Attachment、Statistics）
- **Value Objects**: 值物件
- **Domain Services**: 領域服務

#### 🚀 應用層 (Application)
- **Application Services**: 應用服務
- **Controllers**: API 控制器
- **DTOs**: 資料傳輸物件
- **Middleware**: 中介軟體

#### 🔧 基礎設施層 (Infrastructure)
- **Repositories**: 資料存取層
- **External Services**: 外部服務整合
- **Caching**: 多層快取系統
- **File Storage**: 檔案儲存

#### 🛠️ 共用層 (Shared)
- **Validators**: 29 種驗證規則
- **Exceptions**: 例外處理
- **Utilities**: 工具函式

### 統一腳本管理系統

AlleyNote 採用現代化的統一腳本管理系統，將原本分散的 80+ 個腳本整合為單一入口點：

#### 核心特色
- **85% 程式碼精簡**: 從 80+ 個獨立腳本精簡為 9 個核心類別
- **統一介面**: 所有開發工具透過單一指令執行
- **自動發現**: 動態載入和註冊指令，無需手動維護
- **類型安全**: 完整 PHP 8.4 類型宣告與 PHPStan Level 10 合規
- **擴展性**: 模組化設計，容易新增自訂指令

#### 使用方式
│   ├── UnifiedScriptManager.php     # 核心管理器
│   ├── Command/
│   │   ├── AbstractCommand.php         # 抽象基礎指令類別
│   │   ├── TestCommand.php            # 測試相關指令
│   │   ├── QualityCommand.php         # 程式碼品質指令
│   │   ├── DatabaseCommand.php        # 資料庫操作指令
│   │   ├── SwaggerCommand.php         # API 文件產生指令
│   │   ├── CacheCommand.php           # 快取管理指令
│   │   ├── BackupCommand.php          # 備份相關指令
│   │   ├── SecurityCommand.php        # 安全性掃描指令
│   │   └── ProjectCommand.php         # 專案狀態指令
│   └── CommandRegistry.php        # 指令註冊器
```

### 基本用法

```bash
# 顯示所有可用指令
```bash
# 查看所有可用指令
docker compose exec web php backend/scripts/unified-scripts.php --help

# 執行特定指令類別的說明
docker compose exec web php backend/scripts/unified-scripts.php test --help
docker compose exec web php backend/scripts/unified-scripts.php quality --help
```

---

## 🔄 開發工作流程

### Git 工作流程
```bash
# 1. 建立功能分支
git checkout -b feature/statistics-enhancement

# 2. 開發過程
# 編寫程式碼...
# 編寫測試...

# 3. 提交前檢查
docker compose exec web composer ci

# 4. 提交變更
git add .
git commit -m "feat(statistics): 新增趨勢分析功能"

# 5. 推送並建立 Pull Request
git push origin feature/statistics-enhancement
```

### 程式碼審查標準
- **功能完整性**: 新功能必須包含對應的測試案例
- **程式碼品質**: 通過 PHPStan Level 10 檢查
- **文件更新**: 重要功能需更新相關文件
- **效能考量**: 大型功能需提供效能測試報告

---

## 📊 統計模組開發

### 模組架構
統計模組遵循 DDD 架構，包含：

```
app/Domains/Statistics/
├── Entities/              # 統計實體
│   ├── StatisticsSnapshot.php
│   └── TrendAnalysis.php
├── ValueObjects/          # 統計值物件
│   ├── StatisticsType.php
│   └── PeriodRange.php
├── Services/              # 統計服務
│   ├── StatisticsAggregationService.php
│   └── SnapshotGenerationService.php
├── Repositories/          # 統計倉庫
│   └── StatisticsRepository.php
└── Contracts/             # 統計介面
    └── StatisticsServiceInterface.php
```

### 開發新統計指標

#### 1. 建立統計實體
```php
<?php
namespace App\Domains\Statistics\Entities;

class CustomStatistics extends AbstractStatistics
{
    public function __construct(
        private readonly StatisticsId $id,
        private readonly StatisticsType $type,
        private readonly array $data,
        private readonly DateTime $createdAt
    ) {}

    public function calculate(): array
    {
        // 實作統計計算邏輯
        return $this->processData();
    }
}
```

#### 2. 建立統計服務
```php
<?php
namespace App\Domains\Statistics\Services;

class CustomStatisticsService
{
    public function generateStatistics(PeriodRange $period): CustomStatistics
    {
        $rawData = $this->repository->findByPeriod($period);
        return new CustomStatistics(
            StatisticsId::generate(),
            StatisticsType::CUSTOM,
            $rawData,
            new DateTime()
        );
    }
}
```

#### 3. 撰寫測試
```php
<?php
namespace Tests\Unit\Domains\Statistics\Services;

class CustomStatisticsServiceTest extends TestCase
{
    public function test_generates_custom_statistics(): void
    {
        $service = new CustomStatisticsService($this->mockRepository);
        $period = new PeriodRange(new Date('2025-09-01'), new Date('2025-09-30'));

        $statistics = $service->generateStatistics($period);

        $this->assertInstanceOf(CustomStatistics::class, $statistics);
        $this->assertEquals(StatisticsType::CUSTOM, $statistics->getType());
    }
}
```

### 統計快取策略
```php
# 統計模組使用多層快取
$cacheKey = "statistics.{$type}.{$period}";
$ttl = 3600; // 1 小時

# 快取標籤系統
$tags = ['statistics', $type, 'period:' . $period];
$cache->tags($tags)->put($cacheKey, $data, $ttl);
```

---

## 🧪 測試策略

### 測試執行

```bash
# 執行所有測試 (1,300+ tests)
docker compose exec -T web ./vendor/bin/phpunit

# 執行單元測試
docker compose exec -T web ./vendor/bin/phpunit tests/Unit/

# 執行整合測試
docker compose exec -T web ./vendor/bin/phpunit tests/Integration/

# 執行特定測試檔案
docker compose exec -T web ./vendor/bin/phpunit tests/Unit/Domains/Statistics/

# 執行特定測試方法
docker compose exec -T web ./vendor/bin/phpunit --filter testStatisticsGeneration

# 產生測試覆蓋率報告
docker compose exec -T web ./vendor/bin/phpunit --coverage-html coverage-reports/

# 平行執行測試 (提升速度)
docker compose exec -T web ./vendor/bin/paratest

# 詳細輸出
docker compose exec -T web ./vendor/bin/phpunit --verbose
```

### 測試分類
- **單元測試**: 測試單一類別或方法的功能
- **整合測試**: 測試多個元件的整合
- **功能測試**: 測試完整的 API 端點
- **效能測試**: 測試系統效能與負載能力

### 統計模組測試
```bash
# 執行統計模組專用測試
docker compose exec -T web ./vendor/bin/phpunit tests/Unit/Domains/Statistics/
docker compose exec -T web ./vendor/bin/phpunit tests/Integration/Statistics/
docker compose exec -T web ./vendor/bin/phpunit tests/Performance/Statistics/
```

---

## 🔍 程式碼品質

### 靜態分析
```bash
# PHPStan Level 10 分析
docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G

# 程式碼風格檢查
docker compose exec -T web ./vendor/bin/php-cs-fixer check --diff

# 自動修復程式碼風格問題
docker compose exec -T web ./vendor/bin/php-cs-fixer fix

# 完整 CI 檢查
docker compose exec -T web composer ci
```

### 程式碼規範
- **PSR-12**: 程式碼風格標準
- **PHPStan Level 10**: 最嚴格的靜態分析
- **Type Declaration**: 所有參數與回傳值必須宣告型別
- **Strict Types**: 檔案必須包含 `declare(strict_types=1);`

### 架構規則
- **單一職責原則**: 每個類別只負責一個職責
- **依賴反轉**: 依賴抽象而非具體實作
- **介面隔離**: 介面應該小而專一
- **開放封閉**: 對擴展開放，對修改封閉

---

## 🐛 故障排除

### 常見開發問題

#### 容器啟動失敗
```bash
# 檢查容器狀態
docker compose ps

# 查看日誌
docker compose logs web

# 重新建構容器
docker compose down
docker compose up -d --build
```

#### 測試執行失敗
```bash
# 清理測試環境
docker compose exec web rm -rf storage/testing.db
docker compose exec web php backend/scripts/init-sqlite.sh

# 重新執行測試
docker compose exec -T web ./vendor/bin/phpunit --stop-on-failure
```

#### PHPStan 錯誤
```bash
# 清除 PHPStan 快取
docker compose exec web rm -rf storage/phpstan/

# 重新執行分析
docker compose exec -T web ./vendor/bin/phpstan analyse --no-cache
```

#### 統計模組問題
```bash
# 檢查統計資料表
docker compose exec web sqlite3 database/alleynote.sqlite3 "SELECT * FROM statistics_snapshots LIMIT 5;"

# 重新生成統計快照
docker compose exec web php backend/scripts/statistics-calculation.php --force

# 清理統計快取
docker compose exec web rm -rf storage/cache/statistics/
```

### 效能問題診斷
```bash
# 檢查記憶體使用
docker stats --no-stream

# 分析慢查詢
docker compose exec web php backend/scripts/db-performance.php

# 監控快取命中率
docker compose exec redis redis-cli info stats | grep hits
```

---

## 📚 進階主題

### 自訂驗證器
```php
<?php
namespace App\Shared\Validators\Custom;

class StatisticsRangeValidator extends AbstractValidator
{
    public function validate($value, array $parameters = []): ValidationResult
    {
        if (!$this->isValidDateRange($value)) {
            return ValidationResult::fail('統計日期範圍無效');
        }

        return ValidationResult::success();
    }
}
```

### 效能優化技巧
- **資料庫索引**: 為常用查詢建立適當索引
- **查詢優化**: 使用 `EXPLAIN` 分析查詢執行計畫
- **快取策略**: 實作多層快取減少資料庫負載
- **批量處理**: 大量資料操作使用批量處理

### 部署前檢查清單
- [ ] 所有測試通過
- [ ] PHPStan Level 10 無錯誤
- [ ] 程式碼風格符合 PSR-12
- [ ] 效能測試通過
- [ ] 安全性檢查通過
- [ ] 文件已更新

---

**🔗 相關資源**
- [API 文件](API_DOCUMENTATION.md) - RESTful API 規格
- [統計功能規格書](STATISTICS_FEATURE_SPECIFICATION.md) - 統計模組詳細規格
- [架構審計報告](ARCHITECTURE_AUDIT.md) - DDD 架構分析
- [管理員手冊](ADMIN_MANUAL.md) - 系統運維指南

**📧 技術支援**
- GitHub Issues: [提交問題](https://github.com/your-org/alleynote/issues/new)
- 開發討論: [GitHub Discussions](https://github.com/your-org/alleynote/discussions)

**🎯 開發狀態**: ✅ 生產就緒 | 🧪 持續改進 | 📈 功能豐富

# 資料庫回滾
docker compose exec web php backend/scripts/unified-scripts.php db:rollback

# 檢查資料庫效能
docker compose exec web php backend/scripts/unified-scripts.php db:performance
```

### 開發工具指令

```bash
# 產生 Swagger API 文件
docker compose exec web php backend/scripts/unified-scripts.php swagger:generate

# 測試 Swagger 設定
docker compose exec web php backend/scripts/unified-scripts.php swagger:test

# 快取管理
docker compose exec web php backend/scripts/unified-scripts.php cache:clear
docker compose exec web php backend/scripts/unified-scripts.php cache:warm

# 專案狀態檢查
docker compose exec web php backend/scripts/unified-scripts.php project:status
```

### 備份與維運指令

```bash
# 資料庫備份
docker compose exec web php backend/scripts/unified-scripts.php backup:db

# 檔案備份
docker compose exec web php backend/scripts/unified-scripts.php backup:files

# 安全性掃描
docker compose exec web php backend/scripts/unified-scripts.php security:scan

# SSL 憑證管理 (生產環境)
docker compose exec web php backend/scripts/unified-scripts.php ssl:setup
docker compose exec web php backend/scripts/unified-scripts.php ssl:renew
```

### 自訂指令開發

要新增自訂指令，請遵循以下步驟：

1. **建立指令類別**:
```php
<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Command;

class MyCustomCommand extends AbstractCommand
{
    protected function getCommands(): array
    {
        return [
            'my:custom' => 'Execute my custom functionality',
        ];
    }

    protected function executeCommand(string $command, array $args): int
    {
        match ($command) {
            'my:custom' => $this->executeMyCustom($args),
            default => throw new \InvalidArgumentException("Unknown command: {$command}")
        };

        return 0;
    }

    private function executeMyCustom(array $args): void
    {
        echo "Executing my custom command...\n";
        // 實作自訂功能
    }
}
```

2. **註冊指令** (自動發現，無需手動註冊):
指令會在 `UnifiedScriptManager` 啟動時自動發現並註冊。

### 效能優化

統一腳本系統採用以下優化策略：

- **Lazy Loading**: 指令類別僅在需要時載入
- **快取機制**: 指令清單和metadata會被快取
- **記憶體優化**: 避免載入不必要的依賴
- **並行執行**: 部分指令支援並行處理

---

## 開發環境設定

### IDE 設定

#### PhpStorm 設定

```xml
<!-- .idea/php.xml -->
<project version="4">
  <component name="PhpProjectSharedConfiguration">
    <option name="suggestChangeDefaultLanguageLevel" value="false" />
  </component>
  <component name="PhpUnit">
    <phpunit_settings>
      <PhpUnitSettings configuration_file_path="$PROJECT_DIR$/backend/phpunit.xml" />
    </phpunit_settings>
  </component>
</project>
```

#### VS Code 設定

```json
// .vscode/settings.json
{
    "php.suggest.basic": false,
    "php.validate.executablePath": "/usr/local/bin/php",
    "phpunit.phpunit": "./vendor/bin/phpunit",
    "phpunit.args": ["--configuration", "phpunit.xml"],
    "files.associations": {
        "*.php": "php"
    },
    "emmet.includeLanguages": {
        "php": "html"
    }
}
```

### Git Hooks 設定

```bash
# 設定 pre-commit hook
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/sh
echo "執行 pre-commit 檢查..."

# 進入後端目錄
cd backend

# 檢查 PHP 語法
find . -name "*.php" -print0 | xargs -0 -n1 php -l

# 執行 PHPStan 靜態分析
./vendor/bin/phpstan analyse --memory-limit=1G

# 執行測試
./vendor/bin/phpunit

echo "pre-commit 檢查通過！"
EOF

chmod +x .git/hooks/pre-commit
```

---

## 專案架構概覽

### 目錄結構 (前後端分離 + DDD 架構)

```
AlleyNote/                          # 根目錄
├── backend/                       # 後端 PHP DDD 架構
│   ├── app/                      # 應用程式核心
│   │   ├── Application/          # 應用層
│   │   │   ├── Controllers/      # HTTP 控制器
│   │   │   └── Middleware/       # 中介軟體
│   │   ├── Domains/              # 領域層 (核心業務邏輯)
│   │   │   ├── Auth/             # 身份驗證領域
│   │   │   ├── Post/             # 文章管理領域
│   │   │   ├── Attachment/       # 附件管理領域
│   │   │   └── Security/         # 安全性領域
│   │   ├── Infrastructure/       # 基礎設施層
│   │   │   ├── Repositories/     # 資料存取實作
│   │   │   ├── Services/         # 外部服務整合
│   │   │   └── Cache/           # 快取機制
│   │   └── Shared/               # 共用元件
│   ├── tests/                    # 測試套件 (138 檔案, 1,372 通過測試)
│   │   ├── Unit/                 # 單元測試
│   │   ├── Integration/          # 整合測試
│   │   └── Feature/              # 功能測試
│   ├── database/                 # 資料庫相關
│   ├── public/                   # 公開存取檔案
│   ├── scripts/                  # 維護腳本
│   └── vendor/                   # Composer 依賴套件
├── frontend/                      # 前端 Vite + TypeScript 應用
│   ├── src/                      # Vite + TypeScript Composition API 程式碼
│   ├── public/                   # 靜態檔案
│   └── package.json              # Node.js 依賴套件
├── docker/                       # Docker 容器設定
│   ├── php/                      # PHP 8.4.12 設定
│   └── nginx/                    # Nginx 設定
├── docs/                         # 專案文件 (36 個文件)
└── docker compose.yml            # Docker Compose v2.39.2 設定
```

### DDD 分層架構

```
┌──────────────────────────┐
│     Presentation         │ ← HTTP Controllers, API Routes
├──────────────────────────┤
│     Application          │ ← Application Services, DTOs
├──────────────────────────┤
│       Domain             │ ← Business Logic, Entities, Value Objects
├──────────────────────────┤
│    Infrastructure        │ ← Repositories, External Services
└──────────────────────────┘
```

### 領域模型 (Bounded Contexts)

```
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│    Auth     │  │    Post     │  │ Attachment  │
│   Domain    │  │   Domain    │  │   Domain    │
│             │  │             │  │             │
│ - User      │  │ - Post      │  │ - File      │
│ - Session   │  │ - Comment   │  │ - Image     │
│ - Token     │  │ - Category  │  │ - Upload    │
└─────────────┘  └─────────────┘  └─────────────┘

┌─────────────┐  ┌─────────────┐
│  Security   │  │   Shared    │
│   Domain    │  │  Elements   │
│             │  │             │
│ - Audit     │  │ - Common    │
│ - Log       │  │ - Utils     │
│ - Firewall  │  │ - Events    │
└─────────────┘  └─────────────┘
```

### 資料流 (DDD + 統一腳本)

```
HTTP Request → Router → Controller → Application Service → Domain Service → Repository
                                           ↓
HTTP Response ← View ← Controller ← DTO ← Application Service ← Domain Entity ← Database

統一腳本系統:
CLI Input → unified-scripts.php → CommandRegistry → Specific Command → Domain/Infrastructure
```

### 專案統計 (最新)

- **總類別數**: 161 classes
- **介面數**: 37 interfaces
- **命名空間**: 73 namespaces
- **測試套件**: 1,213 tests (100% 通過)
- **程式碼覆蓋率**: 87.5%
- **統一腳本**: 9 core classes (取代 58+ legacy scripts)
- **PHPStan 等級**: Level 8 (0 errors)
- **PHP 版本**: 8.4.11

---

## 編碼規範

### PSR 標準

AlleyNote 遵循以下 PSR 標準：

- **PSR-1**: 基本編碼標準
- **PSR-4**: 自動加載標準
- **PSR-11**: 容器介面標準
- **PSR-12**: 擴展編碼風格指南

### 命名慣例

```php
<?php
// 類別：PascalCase
class PostService {}
class CreatePostDTO {}

// 方法和變數：camelCase
public function createPost() {}
private $postRepository;

// 常數：SCREAMING_SNAKE_CASE
const MAX_UPLOAD_SIZE = 1024;

// 介面：以 Interface 結尾
interface PostRepositoryInterface {}

// 抽象類別：以 Abstract 開頭
abstract class AbstractRepository {}
```

### 檔案組織

```php
<?php
declare(strict_types=1);

namespace AlleyNote\Service;

use AlleyNote\DTO\CreatePostDTO;
use AlleyNote\Repository\PostRepositoryInterface;
use AlleyNote\Validation\ValidatorInterface;
use AlleyNote\Exception\ValidationException;

/**
 * 文章服務類別
 *
 * 處理文章相關的業務邏輯，包括建立、更新、刪除等操作。
 *
 * @package AlleyNote\Service
 * @author AlleyNote Team
 * @since 4.0.0
 */
class PostService
{
    public function __construct(
        private readonly PostRepositoryInterface $repository,
        private readonly ValidatorInterface $validator
    ) {}

    /**
     * 建立新文章
     *
     * @param CreatePostDTO $dto 文章資料
     * @return array 建立結果
     * @throws ValidationException 當驗證失敗時
     */
    public function createPost(CreatePostDTO $dto): array
    {
        // 使用 PHP 8.4 新特性
        $validatedData = $this->validator->validate($dto);

        // 使用新的 array spread 語法
        return [
            'success' => true,
            'data' => $this->repository->create(...$validatedData),
            'timestamp' => now(),
        ];
    }
}
```

### PHP 8.4 新語法特性

```php
<?php

declare(strict_types=1);

// 1. 屬性掛鉤 (Property Hooks)
class User
{
    public string $fullName {
        get => $this->firstName . ' ' . $this->lastName;
        set(string $value) {
            [$this->firstName, $this->lastName] = explode(' ', $value, 2);
        }
    }

    private string $firstName = '';
    private string $lastName = '';
}

// 2. 非對稱可見性 (Asymmetric Visibility)
class Product
{
    public private(set) string $id;

    public function __construct(string $id)
    {
        $this->id = $id; // 內部可設定
    }

    // 外部只能讀取，不能設定
}

// 3. 新的陣列函式
$numbers = [1, 2, 3, 4, 5];
$result = array_find($numbers, fn($n) => $n > 3); // 4
$allEven = array_all($numbers, fn($n) => $n % 2 === 0); // false
$anyEven = array_any($numbers, fn($n) => $n % 2 === 0); // true

// 4. 改進的型別系統
function processItems(array<string> $items): array<ProcessedItem>
{
    return array_map(fn($item) => new ProcessedItem($item), $items);
}
```

### 錯誤處理

```php
<?php
// ✅ 好的錯誤處理
try {
    $result = $this->postService->createPost($dto);
    return ApiResponse::success($result);
} catch (ValidationException $e) {
    return ApiResponse::error('驗證失敗', $e->getErrors(), 400);
} catch (DatabaseException $e) {
    $this->logger->error('資料庫錯誤', ['exception' => $e]);
    return ApiResponse::error('系統錯誤', [], 500);
} catch (Exception $e) {
    $this->logger->critical('未預期錯誤', ['exception' => $e]);
    return ApiResponse::error('系統錯誤', [], 500);
}

// ❌ 不好的錯誤處理
$result = $this->postService->createPost($dto); // 沒有錯誤處理
```

---

## 新功能開發流程

### 1. 需求分析與設計

```markdown
## 功能需求文件範本

### 功能描述
簡要描述要開發的功能

### 使用者故事
作為 [角色]，我希望 [功能]，以便 [目標]

### 驗收標準
- [ ] 標準 1
- [ ] 標準 2
- [ ] 標準 3

### 技術設計
- API 端點設計
- 資料庫表設計
- 類別設計

### 測試計劃
- 單元測試覆蓋
- 整合測試場景
- UI 測試流程
```

### 2. 建立開發分支

```bash
# 從最新的 main 分支建立功能分支
git checkout main
git pull origin main
git checkout -b feature/user-comments

# 分支命名規則：
# feature/功能名稱 - 新功能
# bugfix/問題描述 - 修復 bug
# hotfix/緊急修復 - 緊急修復
# refactor/重構項目 - 重構
```

### 3. TDD 開發流程

```php
<?php
// 1. 先寫測試 (Red)
class CommentServiceTest extends TestCase
{
    public function testCreateComment(): void
    {
        // Arrange
        $dto = new CreateCommentDTO([
            'post_id' => 1,
            'content' => '這是測試留言',
            'author_id' => 123
        ], $this->validator);

        // Act
        $result = $this->commentService->createComment($dto);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('comment_id', $result);
    }
}

// 2. 執行測試確認失敗
// vendor/bin/phpunit tests/Unit/Service/CommentServiceTest.php

// 3. 實作最小代碼讓測試通過 (Green)
class CommentService
{
    public function createComment(CreateCommentDTO $dto): array
    {
        // 最小實作
        return ['success' => true, 'comment_id' => 1];
    }
}

// 4. 重構程式碼 (Refactor)
class CommentService
{
    public function createComment(CreateCommentDTO $dto): array
    {
        // 完整實作
        $comment = $this->repository->create([
            'post_id' => $dto->getPostId(),
            'content' => $dto->getContent(),
            'author_id' => $dto->getAuthorId(),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return ['success' => true, 'comment_id' => $comment->getId()];
    }
}
```

### 4. 完整開發步驟

```bash
# 第一步：建立 DTO
cat > src/DTO/CreateCommentDTO.php << 'EOF'
<?php
declare(strict_types=1);

namespace AlleyNote\DTO;

class CreateCommentDTO extends BaseDTO
{
    protected function rules(): array
    {
        return [
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'content' => ['required', 'string', 'min_length:1', 'max_length:1000'],
            'author_id' => ['required', 'integer', 'exists:users,id']
        ];
    }

    public function getPostId(): int
    {
        return $this->get('post_id');
    }

    public function getContent(): string
    {
        return $this->get('content');
    }

    public function getAuthorId(): int
    {
        return $this->get('author_id');
    }
}
EOF

# 第二步：建立 Repository
cat > src/Repository/CommentRepository.php << 'EOF'
<?php
declare(strict_types=1);

namespace AlleyNote\Repository;

use AlleyNote\Model\Comment;

class CommentRepository extends BaseRepository
{
    protected function getTableName(): string
    {
        return 'comments';
    }

    protected function getModelClass(): string
    {
        return Comment::class;
    }

    public function findByPostId(int $postId): array
    {
        return $this->findBy(['post_id' => $postId]);
    }
}
EOF

# 第三步：建立 Service
# 第四步：建立 Controller
# 第五步：建立測試
# 第六步：更新路由
```

### 5. 提交與程式碼審查

```bash
# 執行完整測試套件
composer test-all

# 檢查程式碼風格
composer cs-fix

# 執行靜態分析
composer analyse

# 提交變更
git add .
git commit -m "feat(comments): 新增留言功能

- 新增 CreateCommentDTO 資料傳輸物件
- 新增 CommentRepository 資料存取層
- 新增 CommentService 業務邏輯層
- 新增 CommentController 控制器
- 新增完整測試覆蓋
- 更新 API 路由設定

Closes #123"

# 推送到遠端
git push origin feature/user-comments
```

---

## 測試指南

### 測試策略與覆蓋率

```
測試金字塔 (AlleyNote 實際分布)：
    ┌─────────────┐
    │  UI 測試     │ ~8% (97 tests)
    ├─────────────┤
    │  整合測試    │ ~22% (267 tests)
    ├─────────────┤
    │  單元測試    │ ~70% (849 tests)
    └─────────────┘

總計: 1,213 tests, 5,714 assertions
覆蓋率: 87.5% (目標: >85%)
執行時間: ~20.4 秒
```

### 🚀 使用統一腳本執行測試

```bash
# 執行所有測試套件 (推薦)
docker compose exec web php backend/scripts/unified-scripts.php test:run

# 執行特定類型測試
docker compose exec web php backend/scripts/unified-scripts.php test:unit         # 單元測試
docker compose exec web php backend/scripts/unified-scripts.php test:integration  # 整合測試
docker compose exec web php backend/scripts/unified-scripts.php test:security     # 安全性測試
docker compose exec web php backend/scripts/unified-scripts.php test:ui           # UI 測試

# 測試覆蓋率報告
docker compose exec web php backend/scripts/unified-scripts.php test:coverage

# 並行執行測試 (加速執行)
docker compose exec web php backend/scripts/unified-scripts.php test:parallel

# CI 環境測試 (包含所有檢查)
docker compose exec web php backend/scripts/unified-scripts.php ci:check
```

### 測試環境管理

```bash
# 測試資料庫初始化
docker compose exec web php backend/scripts/unified-scripts.php db:test-setup

# 清理測試資料
docker compose exec web php backend/scripts/unified-scripts.php test:cleanup

# 重設測試環境
docker compose exec web php backend/scripts/unified-scripts.php test:reset
```

### 單元測試

```php
<?php
namespace Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use AlleyNote\Service\PostService;
use AlleyNote\Repository\PostRepositoryInterface;
use AlleyNote\Validation\ValidatorInterface;
use AlleyNote\DTO\CreatePostDTO;

class PostServiceTest extends TestCase
{
    private PostService $service;
    private PostRepositoryInterface $repository;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PostRepositoryInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->service = new PostService($this->repository, $this->validator);
    }

    public function testCreatePost(): void
    {
        // Arrange
        $dto = new CreatePostDTO([
            'title' => '測試文章',
            'content' => '測試內容'
        ], $this->validator);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn(['id' => 1]);

        // Act
        $result = $this->service->createPost($dto);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['post_id']);
    }

    #[Test]
    public function createPostWithInvalidDataThrowsException(): void
    {
        // PHPUnit 11.5.34 新語法：使用 Attribute 取代 annotation
        $this->expectException(ValidationException::class);

        new CreatePostDTO([
            'title' => '', // 空標題應該失敗
            'content' => '測試內容'
        ], $this->validator);
    }

    #[DataProvider('invalidPostDataProvider')]
    public function testCreatePostWithInvalidDataVariations(array $data): void
    {
        $this->expectException(ValidationException::class);
        new CreatePostDTO($data, $this->validator);
    }

    public static function invalidPostDataProvider(): array
    {
        return [
            'empty title' => [['title' => '', 'content' => 'content']],
            'null content' => [['title' => 'title', 'content' => null]],
            'title too long' => [['title' => str_repeat('a', 256), 'content' => 'content']],
        ];
    }
}
```

### 整合測試 (最新 PHPUnit 語法)

```php
<?php
namespace Tests\Integration\Controller;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
class PostControllerTest extends TestCase
{
    #[Test]
    public function createPostEndpointReturnsCorrectResponse(): void
    {
        // 使用真實的服務但模擬的資料庫
        $response = $this->post('/api/posts', [
            'title' => '整合測試文章',
            'content' => '這是整合測試的內容'
        ], [
            'Authorization' => 'Bearer ' . $this->getTestToken()
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'post_id',
                'title',
                'content',
                'created_at'
            ]
        ]);
    }

    #[Test]
    public function unauthorizedRequestReturns401(): void
    {
        $response = $this->post('/api/posts', [
            'title' => '測試文章',
            'content' => '測試內容'
        ]);

        $response->assertStatus(401);
    }
}
```

### 效能測試

```php
<?php
namespace Tests\Performance;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use AlleyNote\Repository\PostRepository;

#[Group('performance')]
class PostRepositoryPerformanceTest extends TestCase
{
    #[Test]
    public function bulkInsertPerformanceIsWithinAcceptableRange(): void
    {
        $start = microtime(true);

        // 插入 1000 筆資料
        for ($i = 0; $i < 1000; $i++) {
            $this->repository->create([
                'title' => "測試文章 {$i}",
                'content' => "測試內容 {$i}"
            ]);
        }

        $duration = microtime(true) - $start;

        // 應該在 5 秒內完成
        $this->assertLessThan(5.0, $duration, '批量插入應在 5 秒內完成');
    }

    #[Test]
    public function queryPerformanceWithLargeDataset(): void
    {
        // 建立測試資料
        $this->createTestPosts(10000);

        $start = microtime(true);
        $results = $this->repository->findByPage(1, 50);
        $duration = microtime(true) - $start;

        $this->assertLessThan(0.1, $duration, '分頁查詢應在 100ms 內完成');
        $this->assertCount(50, $results);
    }
}
```

### 測試資料工廠

```php
<?php
namespace Tests\Factories;

class PostFactory
{
    public static function make(array $attributes = []): array
    {
        return array_merge([
            'title' => 'Default Test Title',
            'content' => 'Default test content for the post.',
            'author_id' => 1,
            'category_id' => 1,
            'is_published' => true,
            'created_at' => date('Y-m-d H:i:s')
        ], $attributes);
    }

    public static function makeMany(int $count, array $attributes = []): array
    {
        $posts = [];
        for ($i = 0; $i < $count; $i++) {
            $posts[] = self::make(array_merge($attributes, [
                'title' => "Test Post {$i}",
                'content' => "Test content for post {$i}"
            ]));
        }
        return $posts;
    }
}
```

### 測試執行

```bash
# 執行所有測試（建議與 CI 保持一致）
docker compose exec -T web ./vendor/bin/phpunit

# 按群組執行測試
docker compose exec -T web ./vendor/bin/phpunit --group unit
docker compose exec -T web ./vendor/bin/phpunit --group integration
docker compose exec -T web ./vendor/bin/phpunit --group performance

# 執行單一測試檔案
docker compose exec -T web ./vendor/bin/phpunit tests/Unit/Service/PostServiceTest.php

# 執行特定測試方法
docker compose exec -T web ./vendor/bin/phpunit --filter testCreatePost

# 產生程式碼覆蓋率報告
docker compose exec -T web ./vendor/bin/phpunit --coverage-html coverage-reports

# 平行執行測試 (提升速度)
docker compose exec -T web ./vendor/bin/paratest

# 詳細輸出
docker compose exec -T web ./vendor/bin/phpunit --testdox --verbose
```

### 測試設定檔

```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         executionOrder="random"
         resolveDependencies="true"
         stopOnFailure="false"
         cacheDirectory=".phpunit.cache"
         testdox="true">

    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory>app</directory>
        </include>
        <exclude>
            <directory>app/storage</directory>
        </exclude>
    </source>

    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
```

---

## 除錯與故障排除

### 🛠️ 基本除錯工具

```bash
# 檢查容器狀態
docker compose ps

# 查看容器日誌
docker compose logs web
docker compose logs -f web  # 即時追蹤

# 進入容器
docker compose exec web bash

# 檢查 PHP 設定
docker compose exec web php --ini
docker compose exec web php -m  # 查看已載入模組

# 檢查 Xdebug 狀態
docker compose exec web php -v  # 應顯示 Xdebug 3.4.5
```

### 常見問題快速修復

```bash
# 清除所有快取
docker compose exec web php -r "opcache_reset();"

# 重新產生 Composer autoload
docker compose exec web composer dump-autoload

# 修正檔案權限
sudo chown -R $USER:$USER storage/
sudo chown -R $USER:$USER database/

# 重新建立資料庫
docker compose exec web ./vendor/bin/phinx rollback -t 0
docker compose exec web ./vendor/bin/phinx migrate

# 清除失敗的測試
rm -rf storage/framework/testing/
```

### 日誌系統

```php
<?php
// 使用日誌記錄除錯資訊
use Psr\Log\LoggerInterface;

class SomeService
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function someMethod(array $data): void
    {
        $this->logger->debug('開始處理資料', ['data' => $data]);

        try {
            // 處理邏輯
            $result = $this->processData($data);
            $this->logger->info('資料處理成功', ['result' => $result]);
        } catch (Exception $e) {
            $this->logger->error('資料處理失敗', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw $e;
        }
    }
}
```

### 除錯工具

```bash
# 查看容器日誌
docker compose logs -f web

# 查看應用程式日誌
tail -f backend/storage/logs/app.log

# 使用 Xdebug 3.4.5（開發環境）
export XDEBUG_MODE=debug
docker compose -f docker compose.dev.yml up -d

# 執行單一測試進行除錯
docker compose exec web ./vendor/bin/phpunit --filter testSpecificMethod tests/Unit/SomeTest.php

# 監控 PHP 記憶體使用
docker compose exec web php -d memory_limit=256M your-script.php

# 檢查 OPcache 狀態
docker compose exec web php -r "var_dump(opcache_get_status());"
```

### 常見問題排除

#### 容器化環境問題

```bash
# 重建容器（清除快取）
docker compose down
docker compose build --no-cache
docker compose up -d

# 檢查容器資源使用
docker stats

# 清理 Docker 系統
docker system prune -f
```

#### 依賴注入問題

```bash
# 檢查 Composer 依賴
docker compose exec web composer validate
docker compose exec web composer install --optimize-autoloader

# 清理 autoload 快取
docker compose exec web composer dump-autoload
```

#### 資料庫問題

```bash
# 檢查資料庫連接
docker compose exec web php -r "new PDO('sqlite:database/alleynote.sqlite3');"

# 重新建立資料庫
docker compose exec web ./vendor/bin/phinx rollback -t 0
docker compose exec web ./vendor/bin/phinx migrate

# 檢查資料庫檔案權限
docker compose exec web ls -la database/
```

#### 前後端通訊問題

```bash
# 檢查 API 端點
curl -i http://localhost:8080/api/health

# 檢查 CORS 設定
curl -i -H "Origin: http://localhost:3000" http://localhost:8080/api/posts

# 檢查前端建構
cd frontend && npm run build
```

---

## 部署與維運

### 環境配置

```bash
# 開發環境
export APP_ENV=development
export APP_DEBUG=true
export LOG_LEVEL=debug

# 測試環境
export APP_ENV=testing
export APP_DEBUG=false
export LOG_LEVEL=info

# 生產環境
export APP_ENV=production
export APP_DEBUG=false
export LOG_LEVEL=error
```

### Docker Compose 生產設定

```yaml
# docker compose.production.yml
version: '3.8'
services:
  web:
    build:
      context: .
      dockerfile: docker/php/Dockerfile.prod
    environment:
      - APP_ENV=production
      - PHP_OPCACHE_ENABLE=1
      - PHP_OPCACHE_MEMORY_CONSUMPTION=256
    volumes:
      - ./backend:/var/www/html:ro

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/prod.conf:/etc/nginx/conf.d/default.conf:ro
      - ./ssl-data:/etc/nginx/ssl:ro
```

### 部署腳本

```bash
# 自動部署
./scripts/deploy.sh

# 部署步驟：
# 1. 備份當前版本
# 2. 拉取最新程式碼
# 3. 安裝依賴
# 4. 執行資料庫遷移
# 5. 清理快取
# 6. 重啟服務
# 7. 執行健康檢查
# 8. 如果失敗，自動回滾

# 手動回滾
./scripts/rollback.sh
```

### 監控與告警

```bash
# 健康檢查
curl -f http://localhost/health || echo "服務異常"

# 監控腳本
cat > scripts/monitor.sh << 'EOF'
#!/bin/bash
# 檢查服務狀態
if ! curl -f http://localhost/health > /dev/null 2>&1; then
    echo "警告：服務健康檢查失敗" | mail -s "AlleyNote 服務異常" admin@example.com
fi

# 檢查磁碟空間
df -h | awk '$5 > 80 {print $0}' | while read line; do
    echo "警告：磁碟空間不足：$line" | mail -s "磁碟空間警告" admin@example.com
done
EOF

# 設定 cron 任務
echo "*/5 * * * * /path/to/alleynote/scripts/monitor.sh" | crontab -
```

---

## 進階主題

### 效能優化

```php
<?php
// 1. 資料庫查詢優化
class OptimizedPostRepository extends PostRepository
{
    public function findRecentPosts(int $limit = 10): array
    {
        // 使用索引
        $sql = "SELECT * FROM posts
                WHERE deleted_at IS NULL
                ORDER BY created_at DESC
                LIMIT :limit";

        return $this->query($sql, ['limit' => $limit]);
    }

    public function findPostsWithCategories(int $limit = 10): array
    {
        // 一次查詢避免 N+1 問題
        $sql = "SELECT p.*, c.name as category_name
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.deleted_at IS NULL
                ORDER BY p.created_at DESC
                LIMIT :limit";

        return $this->query($sql, ['limit' => $limit]);
    }
}

// 2. 快取策略
class CachedPostService
{
    public function __construct(
        private PostService $postService,
        private CacheInterface $cache
    ) {}

    public function getPopularPosts(): array
    {
        $cacheKey = 'popular_posts';

        return $this->cache->remember($cacheKey, 3600, function () {
            return $this->postService->getPopularPosts();
        });
    }
}
```

### 安全性最佳實踐

```php
<?php
// 1. 輸入驗證與清理
class SecurityHelper
{
    public static function sanitizeInput(array $input): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            return $value;
        }, $input);
    }

    public static function validateCSRFToken(string $token): bool
    {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}

// 2. SQL 注入防護
class SecureRepository
{
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

### 微服務架構準備

```php
<?php
// 事件驅動架構準備
interface EventInterface
{
    public function getName(): string;
    public function getPayload(): array;
    public function getTimestamp(): DateTimeInterface;
}

class PostCreatedEvent implements EventInterface
{
    public function __construct(
        private readonly int $postId,
        private readonly int $authorId,
        private readonly DateTimeInterface $timestamp
    ) {}

    public function getName(): string
    {
        return 'post.created';
    }

    public function getPayload(): array
    {
        return [
            'post_id' => $this->postId,
            'author_id' => $this->authorId
        ];
    }

    public function getTimestamp(): DateTimeInterface
    {
        return $this->timestamp;
    }
}

// 事件發布器
class EventPublisher
{
    public function publish(EventInterface $event): void
    {
        // 發布到消息佇列（Redis、RabbitMQ 等）
        $this->messageQueue->publish($event->getName(), $event->getPayload());
    }
}
```

---

## FAQ 常見問題

### 開發環境問題

**Q: Docker 容器啟動失敗？**
```bash
# 檢查 Docker 版本 (需要 Docker 28.3.3+)
docker --version
docker compose --version  # 需要 v2.39.2+

# 檢查端口占用
sudo netstat -tulpn | grep :8080

# 重新建立容器
docker compose down
docker compose up -d --build
```

**Q: Composer 安裝依賴失敗？**
```bash
# 檢查 PHP 版本 (需要 PHP 8.4.12+)
docker compose exec web php --version

# 清理 Composer 快取
docker compose exec web composer clear-cache

# 增加記憶體限制
docker compose exec web php -d memory_limit=2G composer install
```

### 程式碼問題

**Q: PHPStan 靜態分析錯誤？**
```bash
# 執行 PHPStan Level 10 分析
docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G

# 生成基準線文件
docker compose exec -T web ./vendor/bin/phpstan analyse --generate-baseline

# 檢查特定檔案
docker compose exec web ./vendor/bin/phpstan analyse app/Services/PostService.php
```

**Q: 自動載入找不到類別？**
```bash
# 重新生成自動載入檔案
docker compose exec web composer dump-autoload

# 檢查命名空間是否正確
grep -r "namespace" backend/app/
```

### 測試問題

**Q: 測試失敗或超時？**
```bash
# 檢查測試環境
docker compose exec web ./vendor/bin/phpunit --version  # 需要 PHPUnit 11.5.34

# 執行特定測試群組
docker compose exec web ./vendor/bin/phpunit --group unit

# 增加測試記憶體限制
docker compose exec web php -d memory_limit=512M ./vendor/bin/phpunit

# 查看失敗測試詳情
docker compose exec web ./vendor/bin/phpunit --stop-on-failure --verbose
```

**Q: 測試覆蓋率問題？**
```bash
# 確保 Xdebug 已啟用 (需要 Xdebug 3.4.5)
docker compose exec web php -m | grep xdebug

# 產生覆蓋率報告
docker compose exec web ./vendor/bin/phpunit --coverage-html coverage-reports/

# 檢查覆蓋率數據
open coverage-reports/index.html
```

### 前後端整合問題

**Q: 前端無法連接後端 API？**
```bash
# 檢查後端 API 狀態
curl -i http://localhost:8080/api/health

# 檢查前端服務
cd frontend && npm run dev

# 檢查 CORS 設定
curl -i -H "Origin: http://localhost:3000" http://localhost:8080/api/posts
```

**Q: Vite + TypeScript Composition API 問題？**
```bash
# 檢查 Vue.js 版本
cd frontend && npm list vue

# 更新到最新版本
cd frontend && npm update vue

# 檢查 Composition API 語法
npm run lint
```

### 部署問題

**Q: 生產環境部署失敗？**
```bash
# 使用生產配置
docker compose -f docker compose.production.yml up -d

# 檢查容器狀態
docker compose ps

# 查看部署日誌
docker compose logs web
```

**Q: 效能問題？**
```bash
# 啟用 OPcache (PHP 8.4.12 內建 Zend OPcache v8.4.12)
docker compose exec web php -d opcache.enable=1 -v

# 檢查快取狀態
docker compose exec web php -r "var_dump(opcache_get_status());"

# 優化 Composer autoloader
docker compose exec web composer install --optimize-autoloader --no-dev
```

---

## 參考資源

### 官方文件
- [PHP 8.4 新特性](https://www.php.net/releases/8.4/en.php)
- [PHPUnit 11.5 文件](https://phpunit.de/documentation.html)
- [Docker Compose v2.39 文件](https://docs.docker.com/compose/)
- [TypeScript 官方文件](https://www.typescriptlang.org/)
- [Vite 官方文件](https://vitejs.dev/)

### 專案文件
- [ARCHITECTURE_AUDIT.md](ARCHITECTURE_AUDIT.md) - 架構審查報告
- [DI_CONTAINER_GUIDE.md](DI_CONTAINER_GUIDE.md) - DI 容器使用指南
- [VALIDATOR_GUIDE.md](VALIDATOR_GUIDE.md) - 驗證器使用指南
- [README.md](../README.md) - 專案說明

### 開發工具
- **後端測試**: `./vendor/bin/phpunit` (1,372 個通過測試)
- **程式碼風格**: `./vendor/bin/php-cs-fixer` (PSR-12 標準)
- **靜態分析**: `./vendor/bin/phpstan` (Level 10)
- **前端開發**: `npm run dev` (Vite + TypeScript)

### 社群資源
- [PHP 官方網站](https://www.php.net/)
- [Vue.js 官方文件](https://vuejs.org/)
- [Composer 套件庫](https://packagist.org/)
- [GitHub Issues](https://github.com/your-org/alleynote/issues)

---

## 結語

歡迎加入 AlleyNote 開發團隊！這份指南涵蓋了從環境設定到進階開發的各個方面。如果遇到任何問題，請：

1. 先查閱相關文件
2. 搜尋已知問題
3. 在 GitHub Issues 提問
4. 聯絡開發團隊

讓我們一起打造更好的 AlleyNote！

### 當前專案狀態
- **PHP**: 8.4.12 (Xdebug 3.4.5, Zend OPcache v8.4.12)
- **測試**: 138 檔案, 1,372 個通過測試
- **Docker**: 28.3.3 & Docker Compose v2.39.2
- **前端**: Vite + TypeScript Composition API
- **架構**: 前後端分離 + DDD 設計模式

---

*文件版本: v4.0*
*最後更新: 2025-01-20*
*維護者: AlleyNote 開發團隊*
