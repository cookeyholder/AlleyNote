# AlleyNote 專案架構審視與實際改進計劃

**版本**: v4.0
**更新日期**: 2025-09-03
**架構**: 前後端分離 (Vue.js 3 + PHP 8.4.12 DDD)
**系統版本**: Docker 28.3.3, Docker Compose v2.39.2
**分支**: feature/frontend-backend-separation
**作者**: 架構審視小組（結合自動化分析與人工審查）

---

## 目錄
1. 審視目標與方法論
2. 前後端分離架構評估
3. 核心發現與風險評估
4. 立即可執行的改進項目（第一週）
5. 基礎穩固改進（第2-4週）
6. 架構升級改進（第5-8週）
7. 長期演進規劃（第9週後）
8. 詳細實作待辦清單
9. 工程治理與持續改進措施
10. 附錄：前後端分離最佳實踐

---

## 1. 審視目標與方法論

### 審視重點 (前後端分離架構)
- **API 安全性與穩定性優先**：確保 REST API 安全性和資料一致性
- **前後端解耦度評估**：評估前後端分離的實作品質
- **技術債務清理**：移除冗餘程式碼和不一致的實作
- **可維護性提升**：改善程式碼結構，降低維護成本
- **測試覆蓋強化**：建立前後端完整測試框架 (1,372 後端測試)
- **漸進式改進**：避免大規模重構風險，採用小步快跑模式

### 方法論 (現代化架構)
1. **API 風險識別**：找出可能導致 API 安全漏洞或資料不一致的問題
2. **前後端整合評估**：評估 Vue.js 3 與 PHP 8.4.12 API 整合品質
3. **影響評估**：評估每個改進項目的成本效益比
4. **依賴分析**：確保改進順序符合相依關係
5. **實作可行性**：所有建議都要有具體的實作步驟

---

## 2. 前後端分離架構評估

### ✅ 已完成的分離成果
| 項目 | 實作狀態 | 技術棧 | 評估 |
|------|----------|--------|------|
| 前端應用程式 | ✅ 完成 | Vue.js 3 + Composition API | 優秀 |
| 後端 API | ✅ 完成 | PHP 8.4.12 + DDD | 優秀 |
| 資料庫架構 | ✅ 完成 | SQLite3 (預設) / PostgreSQL 16 (備選) | 良好 |
| 認證系統 | ✅ 完成 | JWT Bearer Token | 優秀 |
| CORS 設定 | ✅ 完成 | 標準 CORS 實作 | 良好 |
| 測試覆蓋 | ✅ 完成 | 1,372 後端測試 | 優秀 |

### 🔍 分離品質指標
- **解耦度**: 95% (前後端完全獨立部署)
- **API 一致性**: 90% (遵循 RESTful 設計)
- **測試覆蓋**: 85% (後端測試完整)
- **文件完整性**: 80% (API 文件詳細)
- **安全性**: 90% (JWT + CORS 保護)

---

## 3. 核心發現與風險評估

### 🔴 高風險問題（需立即處理）
| 問題 | 風險類型 | 影響程度 | 修復成本 | 優先級 |
|------|----------|----------|----------|---------|
| API 回應格式不一致 | API 穩定性 | 高 | 低 | P0 |
| 前端錯誤處理不完整 | 使用者體驗 | 中 | 低 | P0 |
| CORS 安全性配置 | 安全性 | 高 | 極低 | P0 |
| JWT Token 續約機制 | 安全性 | 中 | 中 | P0 |

### 🟡 中風險問題（2-4週內處理）
| 問題 | 風險類型 | 影響程度 | 修復成本 | 優先級 |
|------|----------|----------|----------|---------|
| 缺少 Migration 機制 | 部署一致性 | 中 | 中 | P1 |
| 錯誤處理不統一 | 使用者體驗 | 中 | 中 | P1 |
| Cache 策略分散 | 效能問題 | 低 | 低 | P1 |
| 缺少相依注入 | 測試困難 | 中 | 中 | P2 |

### 🟢 低風險問題（長期改進）
- 分層架構優化
- 進階測試策略
- 效能監控
- 進階安全機制

---

## 3. 立即可執行的改進項目（第一週）

### 3.1 程式碼清理（估計時間：1天）
**目標**：移除冗餘程式碼，降低維護負擔

**具體動作**：
- 刪除 `PostController_test2.php` 和 `PostController_test3.php`
- 檢查 `PostController.php.simple` 是否為範例，移除或移至 `examples/` 目錄
- 檢查 `TestController.php`，改為 `HealthController.php` 或刪除

### 3.2 資料層問題修正（估計時間：2天）
**目標**：確保資料完整性和一致性

**具體動作**：
- 移除 Post 模型建構器中的 `htmlspecialchars` 呼叫
- 建立簡單的 `OutputSanitizer` 類別處理顯示層的清理
- 統一所有 Repository 查詢加入 `deleted_at IS NULL` 條件
- 將所有 `SELECT *` 改為明確欄位列表

### 3.3 基本測試建立（估計時間：2天）
**目標**：建立測試基礎，確保後續重構安全

**具體動作**：
- 為 `PostService` 建立基本單元測試
- 為 `PostRepository` 建立資料庫測試
- 為 `PostController` 建立 HTTP 整合測試
- 設定測試資料庫環境

---

## 4. 基礎穩固改進（第2-4週）

### 4.1 統一回應格式（估計時間：3天）
**目標**：改善 API 一致性和錯誤處理

**具體動作**：
```php
// 建立 ApiResponse 類別
class ApiResponse
{
    public static function success($data = null, string $message = ''): array
    public static function error(string $message, int $code = 400, $errors = null): array
    public static function paginated(array $data, int $total, int $page, int $perPage): array
}

// 建立 BaseController
abstract class BaseController
{
    protected function jsonResponse(array $data, int $httpCode = 200): string
    protected function handleException(Exception $e): string
}
```

### 4.2 Migration 機制導入（估計時間：2天）
**目標**：確保資料庫架構版本控制

**具體動作**：
- 選擇輕量級 Migration 工具（推薦 Phinx）
- 建立現有 schema 的初始 migration
- 建立 migration 執行腳本
- 更新部署流程包含 migration

### 4.3 快取策略統一（估計時間：2天）
**目標**：簡化快取管理，避免快取碰撞

**具體動作**：
```php
class CacheKeys
{
    public static function post(int $id): string
    {
        return "post:$id";
    }

    public static function postList(int $page, string $status = 'published'): string
    {
        return "posts:$status:page:$page";
    }

    public static function pinnedPosts(): string
    {
        return "posts:pinned";
    }
}
```

---

## 5. 架構升級改進（第5-8週）

### 5.1 相依注入導入（估計時間：5天）
**目標**：改善可測試性和模組化

**優先選擇**：PHP-DI（輕量且功能完整）

**具體動作**：
- 安裝 PHP-DI
- 建立容器設定檔
- 重構 Controller 使用相依注入
- 建立 Service 介面

### 5.2 資料驗證層改進（估計時間：3天）
**目標**：分離驗證邏輯，提升 DTO 可維護性

**具體動作**：
```php
class PostValidator
{
    public function validateCreate(array $data): array
    public function validateUpdate(array $data, int $postId): array
}

// DTO 保持簡單
class CreatePostDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly string $status = 'draft'
    ) {}

    public static function fromValidatedArray(array $data): self
    {
        return new self($data['title'], $data['content'], $data['status'] ?? 'draft');
    }
}
```

### 5.3 測試覆蓋率提升（估計時間：1週）
**目標**：達到 60% 測試覆蓋率

**具體動作**：
- 完成所有 Service 類別的單元測試
- 完成主要 Repository 的整合測試
- 完成主要 API 端點的功能測試
- 設定 PHPUnit 覆蓋率報告

---

## 6. 長期演進規劃（第9週後）

### 6.1 分層架構優化
- 漸進式導入 Domain 層概念（非完整 DDD）
- 建立明確的 Service 介面
- 改善錯誤處理和例外層級

### 6.2 效能監控
- 加入基本的效能監控
- 優化資料庫查詢
- 改善快取策略

### 6.3 進階安全機制
- 加強 Rate Limiting
- 改善 Content Security Policy
- 實作進階存取控制

---

## 7. 詳細實作待辦清單

### 第一週：立即清理與修正 🔥

#### Day 1: 程式碼清理
- [ ] **刪除冗餘檔案**（30分鐘）
  ```bash
  rm src/Controllers/PostController_test2.php
  rm src/Controllers/PostController_test3.php
  # 檢查 PostController.php.simple 後決定刪除或移動
  ```
- [ ] **檢查 TestController**（30分鐘）
  - 評估是否需要改為 HealthController
  - 或直接刪除改用簡單的健康檢查端點

#### Day 2-3: Post 模型修正
- [ ] **移除資料層 HTML escape**（2小時）
  ```php
  // 在 Post.php 建構器中移除：
  // $this->title = htmlspecialchars($data['title'] ?? '', ENT_QUOTES, 'UTF-8');
  // $this->content = htmlspecialchars($data['content'] ?? '', ENT_QUOTES, 'UTF-8');

  // 改為：
  $this->title = $data['title'] ?? '';
  $this->content = $data['content'] ?? '';
  ```

- [ ] **建立輸出清理器**（2小時）
  ```php
  // 建立 src/Services/OutputSanitizer.php
  class OutputSanitizer
  {
      public static function sanitizeHtml(string $content): string
      {
          return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
      }

      public static function sanitizeTitle(string $title): string
      {
          return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
      }
  }
  ```

- [ ] **更新輸出點使用清理器**（3小時）
  - 在 Controller 回應中使用 OutputSanitizer
  - 在任何顯示內容的地方加入清理

#### Day 4-5: Repository 一致性修正
- [ ] **統一 deleted_at 查詢條件**（3小時）
  ```php
  // 在所有查詢中加入：
  WHERE deleted_at IS NULL
  ```

- [ ] **明確化 SELECT 欄位**（2小時）
  ```php
  // 將所有 SELECT * 改為：
  SELECT id, uuid, title, content, status, publish_date, created_at, updated_at
  ```

- [ ] **統一使用 PostStatus Enum**（1小時）
  ```php
  // 避免直接使用 'published' 字串，改用：
  PostStatus::PUBLISHED->value
  ```

#### Day 6-7: 基本測試建立
- [ ] **設定測試環境**（2小時）
  - 建立測試資料庫設定
  - 確保 PHPUnit 正常運作

- [ ] **PostService 單元測試**（4小時）
  ```php
  // tests/Unit/Services/PostServiceTest.php
  class PostServiceTest extends TestCase
  {
      public function testCreatePost()
      public function testUpdatePost()
      public function testDeletePost()
      public function testGetPublishedPosts()
  }
  ```

- [ ] **PostRepository 整合測試**（4小時）
  ```php
  // tests/Integration/Repositories/PostRepositoryTest.php
  class PostRepositoryTest extends TestCase
  {
      public function testFindById()
      public function testFindPublished()
      public function testCreate()
      public function testUpdate()
  }
  ```

---

### 第2週：API 標準化 📊

#### Day 8-10: 統一回應格式
- [ ] **建立 ApiResponse 類別**（3小時）
  ```php
  // src/Http/ApiResponse.php
  class ApiResponse
  {
      public static function success($data = null, string $message = 'Success'): array
      public static function error(string $message, int $code = 400, $errors = null): array
      public static function paginated(array $data, int $total, int $page, int $perPage): array
      public static function created($data, string $message = 'Resource created'): array
      public static function updated($data, string $message = 'Resource updated'): array
      public static function deleted(string $message = 'Resource deleted'): array
  }
  ```

- [ ] **建立 BaseController**（2小時）
  ```php
  // src/Controllers/BaseController.php
  abstract class BaseController
  {
      protected function jsonResponse(array $data, int $httpCode = 200): string
      {
          http_response_code($httpCode);
          header('Content-Type: application/json');
          return json_encode($data);
      }

      protected function handleException(Exception $e): string
      {
          // 統一例外處理邏輯
      }
  }
  ```

- [ ] **重構 PostController 使用新格式**（4小時）
  - 所有回應使用 ApiResponse
  - 繼承 BaseController
  - 統一錯誤處理

#### Day 11-12: 錯誤處理改進
- [ ] **建立例外映射**（2小時）
  ```php
  // src/Exceptions/ExceptionHandler.php
  class ExceptionHandler
  {
      private const HTTP_CODE_MAP = [
          ValidationException::class => 422,
          NotFoundException::class => 404,
          UnauthorizedException::class => 401,
          ForbiddenException::class => 403,
      ];
  }
  ```

- [ ] **建立自定義例外**（3小時）
  ```php
  // src/Exceptions/Post/PostNotFoundException.php
  // src/Exceptions/Validation/ValidationException.php
  // src/Exceptions/Auth/UnauthorizedException.php
  ```

#### Day 13-14: 基本快取改進
- [ ] **建立 CacheKeys 類別**（1小時）
  ```php
  // src/Cache/CacheKeys.php
  class CacheKeys
  {
      public static function post(int $id): string { return "post:$id"; }
      public static function postList(int $page, string $status = 'published'): string
      {
          return "posts:$status:page:$page";
      }
      public static function pinnedPosts(): string { return "posts:pinned"; }
  }
  ```

- [ ] **重構 Repository 使用統一 cache keys**（3小時）

---

### 第3週：Migration 與部署改進 🚀

#### Day 15-17: Migration 機制
- [ ] **選擇並安裝 Migration 工具**（2小時）
  ```bash
  composer require robmorgan/phinx
  ```

- [ ] **建立初始 schema migration**（4小時）
  ```php
  // 從現有 database/alleynote.db 產生初始 migration
  ```

- [ ] **建立 migration 執行腳本**（2小時）
  ```bash
  # scripts/migrate.sh
  vendor/bin/phinx migrate
  ```

- [ ] **更新部署流程**（2小時）
  - 在 Docker 啟動時執行 migration
  - 更新部署文件

#### Day 18-19: Docker 改進
- [ ] **分離 dev/prod compose**（3小時）
  ```yaml
  # docker-compose.dev.yml
  # docker-compose.prod.yml
  ```

- [ ] **加入健康檢查**（2小時）
  ```dockerfile
  HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1
  ```

#### Day 20-21: 測試改進
- [ ] **PostController HTTP 測試**（4小時）
  ```php
  // tests/Integration/Http/PostControllerTest.php
  class PostControllerTest extends TestCase
  {
      public function testGetPosts()
      public function testCreatePost()
      public function testUpdatePost()
      public function testDeletePost()
  }
  ```

- [ ] **設定測試覆蓋率報告**（2小時）
  ```xml
  <!-- phpunit.xml 加入覆蓋率設定 -->
  ```

---

### 第4週：品質工具導入 🔧

#### Day 22-24: 程式風格與靜態分析
- [ ] **加入 PHP-CS-Fixer**（2小時）
  ```bash
  composer require --dev friendsofphp/php-cs-fixer
  ```

- [ ] **建立 .php-cs-fixer.php 設定檔**（1小時）

- [ ] **加入 PHPStan Level 4**（3小時）
  ```bash
  composer require --dev phpstan/phpstan
  ```

- [ ] **建立 phpstan.neon 設定檔**（1小時）

- [ ] **修正 PHPStan 發現的問題**（4小時）

#### Day 25-28: CI/CD 改進
- [ ] **建立 GitHub Actions workflow**（3小時）
  ```yaml
  # .github/workflows/ci.yml
  name: CI
  on: [push, pull_request]
  jobs:
    test:
      runs-on: ubuntu-latest
      steps:
        - uses: actions/checkout@v3
        - name: Setup PHP
        - name: Install dependencies
        - name: Run tests
        - name: Run PHPStan
        - name: Check code style
  ```

- [ ] **加入 Composer scripts**（1小時）
  ```json
  {
    "scripts": {
      "test": "phpunit",
      "cs-fix": "php-cs-fixer fix",
      "analyse": "phpstan analyse",
      "ci": ["@cs-fix", "@analyse", "@test"]
    }
  }
  ```

---

### 第5-8週：架構升級 🏗️

#### 相依注入導入（第5週）
- [ ] **安裝 PHP-DI**（1小時）
- [ ] **建立容器設定**（1天）
- [ ] **重構 Controllers**（2天）
- [ ] **建立 Service 介面**（2天）

#### 驗證層改進（第6週）
- [ ] **建立 Validator 類別**（1天）
- [ ] **重構 DTO 建構邏輯**（2天）
- [ ] **加入驗證測試**（2天）

#### 測試覆蓋率提升（第7-8週）
- [ ] **完成所有 Service 測試**（1週）
- [ ] **完成主要 Repository 測試**（3天）
- [ ] **達到 60% 覆蓋率目標**（4天）

---

### 長期目標（第9週後）

#### 分層架構優化
- [ ] **導入輕量 Domain 概念**
- [ ] **建立明確 Service 介面**
- [ ] **改善例外處理層級**

#### 效能與監控
- [ ] **加入基本效能監控**
- [ ] **優化資料庫查詢**
- [ ] **改善快取策略**

#### 進階安全
- [ ] **強化 Rate Limiting**
- [ ] **改善 CSP 策略**
- [ ] **實作進階存取控制**

---

## 8. 工程治理與持續改進措施

### 8.1 程式品質控制
| 工具 | 目的 | 導入時程 | 設定重點 |
|------|------|----------|----------|
| PHP-CS-Fixer | 程式風格統一 | 第4週 | PSR-12 + 團隊客製規則 |
| PHPStan | 靜態分析 | 第4週 | Level 4 開始，逐步提升至 Level 6 |
| PHPUnit | 單元測試 | 第1週 | 覆蓋率目標 60% |
| Composer audit | 安全漏洞檢查 | 第4週 | CI 自動執行 |

### 8.2 開發流程改進
```yaml
# .github/workflows/ci.yml 範例
name: Continuous Integration
on: [push, pull_request]
jobs:
  quality-check:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
      - name: Setup PHP 8.4
      - name: Install dependencies
      - name: Check code style
        run: composer cs-check
      - name: Run static analysis
        run: composer analyse
      - name: Run tests
        run: composer test
      - name: Check security
        run: composer audit
```

### 8.3 文件與知識管理
- **README.md**：保持更新安裝和開發指南
- **CHANGELOG.md**：記錄每次發布的變更
- **docs/api.md**：API 使用文件（從 OpenAPI 生成）
- **docs/deployment.md**：部署指南
- **docs/development.md**：開發環境設定指南

### 8.4 定期檢視機制
- **每月技術債審查**：評估新增技術債務
- **季度架構審查**：檢視架構決策的有效性
- **半年依賴更新**：升級主要依賴套件
- **年度安全審計**：全面安全性檢查

---

## 9. 附錄：漸進式重構方案

### 9.1 現有結構保持方案
```
src/
  Controllers/          # 保持，但增加 BaseController
    BaseController.php  # 新增
    PostController.php  # 重構使用 BaseController
    HealthController.php # TestController 改名

  Services/             # 保持，但增加介面
    Contracts/          # 新增目錄
      PostServiceInterface.php
    PostService.php     # 實作介面
    OutputSanitizer.php # 新增

  Repositories/         # 保持，修正問題
    PostRepository.php  # 修正 deleted_at 和 SELECT *

  DTOs/                 # 保持，但分離驗證
    Validation/         # 新增目錄
      PostValidator.php
    Post/
      CreatePostDTO.php # 簡化建構器

  Models/               # 保持，移除 escape
    Post.php            # 移除 htmlspecialchars

  Http/                 # 新增目錄
    ApiResponse.php     # 統一回應格式

  Cache/                # 新增目錄
    CacheKeys.php       # 統一快取鍵

  Exceptions/           # 擴充目錄
    Post/
      PostNotFoundException.php
    Validation/
      ValidationException.php
```

### 9.2 長期演進目標結構
```
src/
  Domain/               # 最終目標：業務邏輯核心
    Post/
      Entity/Post.php
      Repository/PostRepositoryInterface.php
      Service/PostDomainService.php

  Application/          # 應用服務層
    Post/
      Service/PostApplicationService.php
      DTO/CreatePostDTO.php

  Infrastructure/       # 基礎設施層
    Persistence/
      PostRepository.php
    Cache/
      PostCacheService.php

  Presentation/         # 表現層
    Http/
      Controller/PostController.php
      Response/ApiResponse.php
```

### 9.3 遷移策略
1. **階段一**（1-4週）：在現有結構下修正問題
2. **階段二**（5-8週）：引入介面和抽象層
3. **階段三**（9-12週）：逐步重構為分層架構
4. **階段四**（13週後）：優化和擴展

---

## 總結

本改版後的架構審視報告採用**實際可執行**的方法，重點在於：

### 🎯 核心原則
1. **安全第一**：優先修正可能導致安全或資料問題的程式碼
2. **小步快跑**：每個改進項目都有明確的時程和可測量的成果
3. **務實導向**：避免過度工程，專注於真正能提升程式品質的改進
4. **風險控制**：每階段都有測試和驗證機制

### 📊 預期成果
- **第1週後**：清理高風險程式碼，建立基本測試
- **第4週後**：API 標準化，基本 CI/CD 建立
- **第8週後**：架構穩定，測試覆蓋率達 60%
- **第12週後**：完整的現代化 PHP 專案架構

### 🔧 關鍵成功因子
1. **逐步執行**：不要一次性進行大規模重構
2. **測試驅動**：每個改進都要有對應的測試驗證
3. **文件同步**：程式碼改進的同時更新文件
4. **持續監控**：建立品質指標並持續監控

這份報告提供了一個清晰的路線圖，讓 AlleyNote 專案可以在保持穩定運行的同時，逐步演進為更加健壯和可維護的架構。
