# AlleyNote 開發者指南

**版本**: v3.0  
**日期**: 2025-08-28  
**適用範圍**: AlleyNote 專案新手與進階開發者  
**更新**: 包含統一腳本管理系統指南

---

## 📋 目錄

1. [快速開始](#快速開始)
2. [統一腳本管理系統](#統一腳本管理系統)
3. [開發環境設定](#開發環境設定)
4. [專案架構概覽](#專案架構概覽)
5. [編碼規範](#編碼規範)
6. [新功能開發流程](#新功能開發流程)
7. [測試指南](#測試指南)
8. [除錯與故障排除](#除錯與故障排除)
9. [部署與維運](#部署與維運)
10. [進階主題](#進階主題)
11. [FAQ 常見問題](#faq-常見問題)

---

## 快速開始

### 1. 環境準備

```bash
# 系統需求
- PHP 8.4.11+
- Docker & Docker Compose
- Git
- Composer

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

# 安裝依賴套件
docker compose exec web composer install

# 初始化資料庫
docker compose exec web php scripts/unified-scripts.php db:init

# 執行完整測試套件 (1,213 tests, 87.5% coverage)
docker compose exec web php scripts/unified-scripts.php test:run
```

### 3. 🚀 統一腳本管理系統

AlleyNote 採用現代化的統一腳本管理系統，取代傳統的 58+ 個獨立腳本，實現 85% 程式碼精簡：

```bash
# 統一腳本入口點
docker compose exec web php scripts/unified-scripts.php

# 查看所有可用指令和說明
docker compose exec web php scripts/unified-scripts.php --help

# 核心開發工具
docker compose exec web php scripts/unified-scripts.php test:run           # 執行測試套件
docker compose exec web php scripts/unified-scripts.php quality:check      # 程式碼品質檢查
docker compose exec web php scripts/unified-scripts.php db:migrate         # 資料庫遷移
docker compose exec web php scripts/unified-scripts.php swagger:generate   # API 文件產生
docker compose exec web php scripts/unified-scripts.php cache:warm         # 快取預熱

# 維運工具
docker compose exec web php scripts/unified-scripts.php backup:db          # 資料庫備份
docker compose exec web php scripts/unified-scripts.php security:scan      # 安全性掃描
docker compose exec web php scripts/unified-scripts.php project:status     # 專案狀態檢查
```

### 4. 第一次開發提交

### 4. 第一次開發提交

```bash
# 建立新功能分支
git checkout -b feature/my-first-feature

# 開發過程中，使用統一腳本進行測試與檢查
docker compose exec web php scripts/unified-scripts.php test:unit         # 單元測試
docker compose exec web php scripts/unified-scripts.php quality:fix       # 自動修正程式碼風格

# 提交前的完整檢查
docker compose exec web php scripts/unified-scripts.php ci:check          # CI 檢查

# 提交變更 (遵循 Conventional Commit 規範)
git add .
git commit -m "feat: 新增我的第一個功能"
git push origin feature/my-first-feature
```

### 5. 專案狀態概覽

當前專案統計資訊 (最新更新)：
- **測試套件**: 1,213 tests, 5,714 assertions (100% 通過率)
- **程式碼覆蓋率**: 87.5%
- **靜態分析**: 0 errors (PHPStan Level 8)
- **類別架構**: 161 classes, 37 interfaces
- **統一腳本**: 9 core classes (取代 58+ legacy scripts)

---

## 統一腳本管理系統

### 系統概述

AlleyNote 採用現代化的統一腳本管理系統，將原本分散的 58+ 個腳本整合為單一入口點，實現：

- **85% 程式碼精簡**: 從 58+ 個獨立腳本精簡為 9 個核心類別
- **統一介面**: 所有開發工具透過單一指令執行
- **自動發現**: 動態載入和註冊指令，無需手動維護
- **類型安全**: 完整 PHP 8.4 類型宣告與 PHPStan Level 8 合規
- **擴展性**: 模組化設計，容易新增自訂指令

### 核心架構

```
scripts/
├── unified-scripts.php          # 主要入口點
├── lib/
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
docker compose exec web php scripts/unified-scripts.php --help

# 執行特定指令類別的說明
docker compose exec web php scripts/unified-scripts.php test --help
docker compose exec web php scripts/unified-scripts.php quality --help
```

### 測試相關指令

```bash
# 執行所有測試 (1,213 tests)
docker compose exec web php scripts/unified-scripts.php test:run

# 執行單元測試
docker compose exec web php scripts/unified-scripts.php test:unit

# 執行整合測試
docker compose exec web php scripts/unified-scripts.php test:integration

# 產生測試覆蓋率報告 (87.5% coverage)
docker compose exec web php scripts/unified-scripts.php test:coverage

# 執行安全性測試
docker compose exec web php scripts/unified-scripts.php test:security
```

### 程式碼品質指令

```bash
# 執行完整程式碼品質檢查
docker compose exec web php scripts/unified-scripts.php quality:check

# 自動修正程式碼風格問題
docker compose exec web php scripts/unified-scripts.php quality:fix

# 執行 PHPStan 靜態分析 (Level 8)
docker compose exec web php scripts/unified-scripts.php quality:analyse

# CI 環境的完整檢查
docker compose exec web php scripts/unified-scripts.php ci:check
```

### 資料庫管理指令

```bash
# 初始化資料庫
docker compose exec web php scripts/unified-scripts.php db:init

# 執行資料庫遷移
docker compose exec web php scripts/unified-scripts.php db:migrate

# 資料庫回滾
docker compose exec web php scripts/unified-scripts.php db:rollback

# 檢查資料庫效能
docker compose exec web php scripts/unified-scripts.php db:performance
```

### 開發工具指令

```bash
# 產生 Swagger API 文件
docker compose exec web php scripts/unified-scripts.php swagger:generate

# 測試 Swagger 設定
docker compose exec web php scripts/unified-scripts.php swagger:test

# 快取管理
docker compose exec web php scripts/unified-scripts.php cache:clear
docker compose exec web php scripts/unified-scripts.php cache:warm

# 專案狀態檢查
docker compose exec web php scripts/unified-scripts.php project:status
```

### 備份與維運指令

```bash
# 資料庫備份
docker compose exec web php scripts/unified-scripts.php backup:db

# 檔案備份
docker compose exec web php scripts/unified-scripts.php backup:files

# 安全性掃描
docker compose exec web php scripts/unified-scripts.php security:scan

# SSL 憑證管理 (生產環境)
docker compose exec web php scripts/unified-scripts.php ssl:setup
docker compose exec web php scripts/unified-scripts.php ssl:renew
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
      <PhpUnitSettings configuration_file_path="$PROJECT_DIR$/phpunit.xml" />
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

# 檢查 PHP 語法
php -l $(git diff --cached --name-only --diff-filter=ACM | grep '\.php$')

# 執行 PHPStan
composer analyse

# 執行測試
composer test

echo "pre-commit 檢查通過！"
EOF

chmod +x .git/hooks/pre-commit
```

---

## 專案架構概覽

### 目錄結構 (DDD 架構)

```
AlleyNote/                          # 根目錄
├── app/                           # 應用程式核心 (DDD 架構)
│   ├── Application/               # 應用層
│   │   ├── Controllers/          # HTTP 控制器
│   │   └── Middleware/           # 中介軟體
│   ├── Domains/                  # 領域層 (核心業務邏輯)
│   │   ├── Auth/                 # 身份驗證領域
│   │   ├── Post/                 # 文章管理領域
│   │   ├── Attachment/           # 附件管理領域
│   │   └── Security/             # 安全性領域
│   ├── Infrastructure/           # 基礎設施層
│   │   ├── Repositories/         # 資料存取實作
│   │   ├── Services/             # 外部服務整合
│   │   └── Cache/               # 快取機制
│   ├── Services/                 # 應用服務
│   └── Shared/                   # 共用元件
├── tests/                        # 測試套件 (1,213 tests)
│   ├── Unit/                     # 單元測試
│   ├── Integration/              # 整合測試
│   ├── Security/                 # 安全性測試
│   └── UI/                      # 使用者介面測試
├── scripts/                      # 統一腳本管理系統
│   ├── unified-scripts.php       # 主入口點
│   └── lib/                     # 腳本核心類別庫 (9 classes)
├── database/                     # 資料庫相關
│   ├── alleynote.sqlite3         # SQLite 資料庫
│   └── migrations/               # 資料庫遷移
├── public/                       # 公開存取檔案
│   ├── index.php                 # 應用程式入口
│   ├── api-docs.json            # Swagger API 文件
│   └── api-docs.yaml            # Swagger YAML 格式
├── docker/                       # Docker 容器設定
│   ├── php/                     # PHP-FPM 設定
│   └── nginx/                   # Nginx 設定
├── docs/                        # 專案文件 (37 documents)
└── coverage_report/             # 測試覆蓋率報告 (87.5%)
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
 * @since 2.0.0
 */
class PostService
{
    public function __construct(
        private PostRepositoryInterface $repository,
        private ValidatorInterface $validator
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
        // 實作...
    }
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
docker compose exec web php scripts/unified-scripts.php test:run

# 執行特定類型測試
docker compose exec web php scripts/unified-scripts.php test:unit         # 單元測試
docker compose exec web php scripts/unified-scripts.php test:integration  # 整合測試
docker compose exec web php scripts/unified-scripts.php test:security     # 安全性測試
docker compose exec web php scripts/unified-scripts.php test:ui           # UI 測試

# 測試覆蓋率報告
docker compose exec web php scripts/unified-scripts.php test:coverage

# 並行執行測試 (加速執行)
docker compose exec web php scripts/unified-scripts.php test:parallel

# CI 環境測試 (包含所有檢查)
docker compose exec web php scripts/unified-scripts.php ci:check
```

### 測試環境管理

```bash
# 測試資料庫初始化
docker compose exec web php scripts/unified-scripts.php db:test-setup

# 清理測試資料
docker compose exec web php scripts/unified-scripts.php test:cleanup

# 重設測試環境
docker compose exec web php scripts/unified-scripts.php test:reset
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
    
    public function testCreatePostWithInvalidData(): void
    {
        $this->expectException(ValidationException::class);
        
        new CreatePostDTO([
            'title' => '', // 空標題應該失敗
            'content' => '測試內容'
        ], $this->validator);
    }
}
```

### 整合測試

```php
<?php
namespace Tests\Integration\Controller;

use Tests\TestCase;
use AlleyNote\Service\PostService;

class PostControllerTest extends TestCase
{
    public function testCreatePostEndpoint(): void
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
}
```

### 效能測試

```php
<?php
namespace Tests\Performance;

use PHPUnit\Framework\TestCase;
use AlleyNote\Repository\PostRepository;

class PostRepositoryPerformanceTest extends TestCase
{
    public function testBulkInsertPerformance(): void
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
        $this->assertLessThan(5.0, $duration);
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

---

## 除錯與故障排除

### 🛠️ 統一腳本除錯工具

```bash
# 專案整體狀態檢查
docker compose exec web php scripts/unified-scripts.php project:status

# 系統健康檢查
docker compose exec web php scripts/unified-scripts.php system:health

# 快取狀態診斷
docker compose exec web php scripts/unified-scripts.php cache:status

# 資料庫連線檢查
docker compose exec web php scripts/unified-scripts.php db:test-connection

# 權限問題診斷
docker compose exec web php scripts/unified-scripts.php system:permissions

# 效能分析
docker compose exec web php scripts/unified-scripts.php performance:analyze
```

### 常見問題快速修復

```bash
# 快取問題
docker compose exec web php scripts/unified-scripts.php cache:clear
docker compose exec web php scripts/unified-scripts.php cache:warm

# 權限問題  
docker compose exec web php scripts/unified-scripts.php fix:permissions

# 測試失敗清理
docker compose exec web php scripts/unified-scripts.php test:cleanup
docker compose exec web php scripts/unified-scripts.php test:reset

# 資料庫問題
docker compose exec web php scripts/unified-scripts.php db:repair
docker compose exec web php scripts/unified-scripts.php db:optimize
```

### 日誌系統

```php
<?php
// 使用日誌記錄除錯資訊
use Psr\Log\LoggerInterface;

class SomeService
{
    public function __construct(private LoggerInterface $logger) {}
    
    public function someMethod($data): void
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
# 查看應用程式日誌
docker compose logs -f php

# 查看資料庫查詢日誌
tail -f logs/database.log

# 使用 Xdebug（開發環境）
export XDEBUG_MODE=debug
docker compose -f docker-compose.dev.yml up -d

# 執行單一測試進行除錯
vendor/bin/phpunit --filter testSpecificMethod tests/Unit/SomeTest.php
```

### 常見問題排除

#### 依賴注入問題

```bash
# 檢查服務是否正確註冊
php scripts/container-debug.php list-services

# 清理 DI 快取
rm -rf storage/di-cache/*
php scripts/warm-cache.php
```

#### 資料庫問題

```bash
# 檢查資料庫連接
php scripts/db-health-check.php

# 重新初始化資料庫
php scripts/init-sqlite.sh

# 檢查資料庫效能
php scripts/db-performance.php
```

#### 快取問題

```bash
# 清理所有快取
php scripts/cache-monitor.php clear all

# 檢查快取狀態
php scripts/cache-monitor.php stats

# 監控快取效能
php scripts/cache-monitor.php monitor
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
        private int $postId,
        private int $authorId,
        private DateTimeInterface $timestamp
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
# 檢查 Docker 服務
sudo systemctl status docker

# 檢查端口占用
sudo netstat -tulpn | grep :80

# 重新建立容器
docker compose down
docker compose up -d --build
```

**Q: Composer 安裝依賴失敗？**
```bash
# 清理 Composer 快取
composer clear-cache

# 增加記憶體限制
php -d memory_limit=2G composer install

# 使用國內鏡像
composer config repo.packagist composer https://packagist.org
```

### 程式碼問題

**Q: 自動載入找不到類別？**
```bash
# 重新生成自動載入檔案
composer dump-autoload

# 檢查命名空間是否正確
grep -r "namespace" src/
```

**Q: 依賴注入失敗？**
```bash
# 檢查服務是否註冊
php scripts/container-debug.php

# 清理 DI 快取
rm -rf storage/di-cache/*
```

### 測試問題

**Q: 測試資料庫衝突？**
```bash
# 使用獨立的測試資料庫
export TEST_DB_PATH="database/test.sqlite"

# 每次測試前清理資料庫
php scripts/reset-test-db.php
```

**Q: 測試覆蓋率不夠？**
```bash
# 產生詳細覆蓋率報告
vendor/bin/phpunit --coverage-html coverage-reports/

# 檢查未覆蓋的程式碼
open coverage-reports/index.html
```

### 部署問題

**Q: 生產環境部署失敗？**
```bash
# 檢查部署日誌
tail -f logs/deploy.log

# 檢查服務狀態
systemctl status alleynote

# 手動回滾
./scripts/rollback.sh
```

---

## 參考資源

### 官方文件
- [PSR 標準](https://www.php-fig.org/psr/)
- [PHPUnit 文件](https://phpunit.de/documentation.html)
- [Docker 文件](https://docs.docker.com/)

### 專案文件
- [ARCHITECTURE_IMPROVEMENT_COMPLETION.md](ARCHITECTURE_IMPROVEMENT_COMPLETION.md) - 架構改進報告
- [DI_CONTAINER_GUIDE.md](DI_CONTAINER_GUIDE.md) - DI 容器使用指南
- [VALIDATOR_GUIDE.md](VALIDATOR_GUIDE.md) - 驗證器使用指南
- [README.md](README.md) - 專案說明

### 工具與腳本
- `scripts/warm-cache.php` - 快取預熱
- `scripts/cache-monitor.php` - 快取監控
- `scripts/db-performance.php` - 資料庫效能分析
- `scripts/deploy.sh` - 自動部署腳本

### 社群資源
- [PHP 官方網站](https://www.php.net/)
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

---

*文件版本: v2.0*  
*維護者: AlleyNote 開發團隊*