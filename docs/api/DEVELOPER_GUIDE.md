# AlleyNote API 開發者指南

**版本**: 1.0.0  
**最後更新**: 2025-10-11

---

## 📖 目錄

1. [簡介](#簡介)
2. [開發環境設置](#開發環境設置)
3. [添加新的 API 端點](#添加新的-api-端點)
4. [OpenAPI 註解規範](#openapi-註解規範)
5. [測試指南](#測試指南)
6. [程式碼品質標準](#程式碼品質標準)
7. [常見問題](#常見問題)

---

## 簡介

本指南旨在幫助開發者在 AlleyNote 專案中添加和維護 API 端點，遵循 DDD（Domain-Driven Design）原則和 OpenAPI 規範。

### 技術棧

- **PHP**: 8.4+
- **框架**: 自建輕量級框架（DDD 架構）
- **API 規範**: OpenAPI 3.0.0
- **文件工具**: Swagger UI
- **程式碼風格**: PSR-12 + 專案自訂規範
- **靜態分析**: PHPStan Level 10

---

## 開發環境設置

### 1. 啟動 Docker 容器

```bash
cd /path/to/AlleyNote
docker compose up -d
```

### 2. 驗證環境

```bash
# 檢查容器狀態
docker compose ps

# 測試 API
curl http://localhost:8080/api/health
```

### 3. 開發工具

推薦使用以下工具：
- **IDE**: PhpStorm 或 VS Code
- **API 測試**: Postman 或 Insomnia
- **Git 客戶端**: SourceTree 或命令列

---

## 添加新的 API 端點

### 步驟概覽

1. 設計 API 端點
2. 定義路由
3. 建立 Controller
4. 添加 OpenAPI 註解
5. 實作業務邏輯
6. 撰寫測試
7. 執行品質檢查

### 詳細步驟

#### 步驟 1: 設計 API 端點

在開始編寫程式碼前，先設計 API 端點：

- 確定 HTTP 方法（GET, POST, PUT, DELETE）
- 定義 URL 路徑
- 設計請求參數和主體
- 定義回應格式
- 確定錯誤處理方式

**範例**:
```
端點: GET /api/products
功能: 取得產品列表
參數: page, per_page, category
回應: 產品陣列 + 分頁資訊
```

#### 步驟 2: 定義路由

在 `backend/config/routes/api.php` 或相應的路由檔案中添加路由：

```php
return [
    // 產品管理
    'products.index' => [
        'methods' => ['GET'],
        'path' => '/api/products',
        'handler' => [ProductController::class, 'index'],
        'middleware' => ['auth'],
        'name' => 'products.index'
    ],
    
    'products.store' => [
        'methods' => ['POST'],
        'path' => '/api/products',
        'handler' => [ProductController::class, 'store'],
        'middleware' => ['auth'],
        'name' => 'products.store'
    ],
    
    // ... 其他路由
];
```

#### 步驟 3: 建立 Controller

在 `backend/app/Application/Controllers/Api/V1/` 目錄下建立 Controller：

```php
<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Product\Services\ProductService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 產品管理 Controller.
 */
#[OA\Tag(
    name: 'Products',
    description: 'Product management endpoints',
)]
class ProductController
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    /**
     * 取得產品列表.
     */
    #[OA\Get(
        path: '/api/products',
        operationId: 'listProducts',
        summary: '取得產品列表',
        description: '取得系統中所有產品的分頁列表',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: '頁碼',
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: '每頁筆數',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 10),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得產品列表',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'price', type: 'number'),
                                ],
                            ),
                        ),
                        new OA\Property(
                            property: 'pagination',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer'),
                                new OA\Property(property: 'page', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                            ],
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 10)));

        $result = $this->productService->listProducts($page, $perPage);

        $responseData = json_encode([
            'success' => true,
            'data' => $result['items'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
            ],
        ]);

        $response->getBody()->write($responseData ?: '');

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
```

#### 步驟 4: OpenAPI 註解規範

請參考 [OpenAPI 註解規範](#openapi-註解規範) 章節。

#### 步驟 5: 實作業務邏輯

在 Domain 層實作業務邏輯（遵循 DDD 原則）。

---

## OpenAPI 註解規範

### 基本結構

每個 Controller 類別應該有：

1. **Tag 註解**：標記 API 分類
2. **方法註解**：標記每個端點的詳細資訊

### Tag 註解

```php
#[OA\Tag(
    name: 'TagName',
    description: 'Tag description in English',
)]
class YourController
{
    // ...
}
```

### 方法註解結構

```php
#[OA\{HttpMethod}(
    path: '/api/path',
    operationId: 'uniqueOperationId',
    summary: '簡短摘要',
    description: '詳細描述',
    tags: ['TagName'],
    parameters: [...],      // GET 參數
    requestBody: ...,       // POST/PUT 請求主體
    responses: [...]        // 回應定義
)]
public function methodName(Request $request, Response $response): Response
{
    // ...
}
```

### HTTP 方法註解

- `OA\Get`: GET 請求
- `OA\Post`: POST 請求
- `OA\Put`: PUT 請求
- `OA\Delete`: DELETE 請求
- `OA\Patch`: PATCH 請求

### 參數定義（Parameters）

#### 查詢參數（Query）

```php
parameters: [
    new OA\Parameter(
        name: 'page',
        in: 'query',
        description: '頁碼',
        required: false,
        schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
    ),
]
```

#### 路徑參數（Path）

```php
parameters: [
    new OA\Parameter(
        name: 'id',
        in: 'path',
        description: '資源 ID',
        required: true,
        schema: new OA\Schema(type: 'integer'),
    ),
]
```

### 請求主體（RequestBody）

```php
requestBody: new OA\RequestBody(
    description: '請求資料',
    required: true,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'name',
                type: 'string',
                description: '名稱',
                example: 'John Doe',
            ),
            new OA\Property(
                property: 'email',
                type: 'string',
                format: 'email',
                description: '電子郵件',
                example: 'john@example.com',
            ),
        ],
        required: ['name', 'email'],
    ),
)
```

### 回應定義（Responses）

```php
responses: [
    new OA\Response(
        response: 200,
        description: '成功回應',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: '操作成功'),
                new OA\Property(property: 'data', type: 'object'),
            ],
        ),
    ),
    new OA\Response(
        response: 404,
        description: '資源不存在',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: '資源不存在'),
            ],
        ),
    ),
]
```

### 陣列回應

```php
new OA\Property(
    property: 'data',
    type: 'array',
    items: new OA\Items(
        properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
        ],
    ),
)
```

### 物件陣列（Key-Value）

```php
new OA\Property(
    property: 'data',
    type: 'object',
    additionalProperties: new OA\AdditionalProperties(
        type: 'array',
        items: new OA\Items(type: 'string'),
    ),
    example: [
        'category1' => ['item1', 'item2'],
        'category2' => ['item3', 'item4'],
    ],
)
```

---

## 測試指南

### 單元測試

使用 PHPUnit 撰寫單元測試：

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;

class ProductControllerTest extends TestCase
{
    public function testIndexReturnsProductList(): void
    {
        // Arrange
        // Act
        // Assert
    }
}
```

### 整合測試

測試 API 端點的完整流程：

```bash
# 執行所有測試
docker compose exec -T web ./vendor/bin/phpunit

# 執行特定測試檔案
docker compose exec -T web ./vendor/bin/phpunit tests/Integration/Api/ProductControllerTest.php
```

### API 測試

使用 curl 或 Postman 測試 API：

```bash
# 測試 GET 端點
curl -X GET "http://localhost:8080/api/products?page=1&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN"

# 測試 POST 端點
curl -X POST http://localhost:8080/api/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Product Name", "price": 99.99}'
```

---

## 程式碼品質標準

### 執行品質檢查

在提交程式碼前，**務必**執行以下檢查：

#### 1. 程式碼風格檢查（PHP CS Fixer）

```bash
# 檢查風格問題
docker compose exec -T web ./vendor/bin/php-cs-fixer check --diff

# 自動修復風格問題
docker compose exec -T web ./vendor/bin/php-cs-fixer fix
```

#### 2. 靜態分析（PHPStan Level 10）

```bash
docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G
```

#### 3. 執行測試

```bash
docker compose exec -T web ./vendor/bin/phpunit
```

#### 4. 完整 CI 檢查

```bash
docker compose exec -T web composer ci
```

### 品質標準

所有程式碼必須符合以下標準：

- ✅ **PHP 版本**: 使用 PHP 8.4+ 語法特性
- ✅ **Strict Types**: 每個檔案開頭必須有 `declare(strict_types=1);`
- ✅ **型別宣告**: 所有參數和回傳值都要有型別宣告
- ✅ **命名規範**:
  - 類別名稱：`UpperCamelCase`
  - 方法名稱：`lowerCamelCase`
  - 變數名稱：`lowerCamelCase`
- ✅ **SOLID 原則**: 遵循物件導向設計原則
- ✅ **DDD 原則**: 遵循領域驅動設計原則
- ✅ **PSR 標準**: 遵循 PSR-7、PSR-15、PSR-17 標準
- ✅ **PHPStan Level 10**: 通過最嚴格的靜態分析

---

## 常見問題

### Q1: 如何查看 OpenAPI 文件？

**A**: 訪問 http://localhost:8080/api/docs/ui 查看 Swagger UI。

### Q2: 為什麼我的端點沒有出現在 Swagger UI 中？

**A**: 確認以下幾點：
1. 路由是否正確註冊
2. Controller 是否有 OpenAPI 註解
3. OpenAPI 註解語法是否正確
4. 清除快取後重新載入

### Q3: 如何測試需要認證的端點？

**A**: 
1. 先呼叫 `/api/auth/login` 取得 Token
2. 在後續請求中加入 `Authorization: Bearer YOUR_TOKEN` header

### Q4: OpenAPI 註解中的繁體中文會不會有問題？

**A**: 不會，OpenAPI 規範支援 UTF-8 編碼，繁體中文可以正常顯示。

### Q5: 如何處理檔案上傳？

**A**: 使用 `multipart/form-data` 格式：

```php
requestBody: new OA\RequestBody(
    content: new OA\MediaType(
        mediaType: 'multipart/form-data',
        schema: new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'file',
                    type: 'string',
                    format: 'binary',
                ),
            ],
        ),
    ),
)
```

### Q6: 如何添加認證說明到 OpenAPI？

**A**: 在主要的 OpenAPI 配置中添加 Security Schemes。

### Q7: PHPStan 報錯怎麼辦？

**A**: 
1. 仔細閱讀錯誤訊息
2. 確保所有變數都有正確的型別宣告
3. 使用 PHPStan 的註解協助型別推斷（如必要）
4. 參考 `phpstan.neon` 配置

---

## 參考資源

### 內部文件
- [OpenAPI 驗證報告](./OPENAPI_VERIFICATION_REPORT.md)
- [API 使用指南](./API_USAGE_GUIDE.md)
- [DDD 架構設計](../domains/shared/DDD_ARCHITECTURE_DESIGN.md)
- [路由系統架構](../domains/shared/ROUTING_SYSTEM_ARCHITECTURE.md)

### 外部資源
- [OpenAPI 3.0 規範](https://swagger.io/specification/)
- [PHP PSR 標準](https://www.php-fig.org/psr/)
- [PHPStan 文件](https://phpstan.org/user-guide/getting-started)
- [Swagger UI 文件](https://swagger.io/tools/swagger-ui/)

---

## 貢獻指南

### 提交流程

1. 建立功能分支
2. 實作功能並添加測試
3. 執行品質檢查
4. 提交 Pull Request
5. 等待 Code Review

### Commit Message 規範

使用 Conventional Commit 格式（繁體中文）：

```
feat: 新增產品管理 API 端點

- 新增 GET /api/products 端點
- 新增 POST /api/products 端點
- 完整的 OpenAPI 註解
- 單元測試與整合測試

通過所有品質檢查
```

類型：
- `feat`: 新功能
- `fix`: 錯誤修復
- `docs`: 文件更新
- `style`: 程式碼格式調整
- `refactor`: 重構
- `test`: 測試相關
- `chore`: 雜項

---

**Happy Coding! 🚀**
