# 測試基底類別重構說明

## 概述

為了改善測試程式碼的可維護性和重複使用性，我們重構了測試基底類別架構。新架構採用組合式設計，讓測試類別能夠按需選擇所需的功能。

## 新架構

### 基底類別

#### `Tests\Support\BaseTestCase`
- 所有測試類別的共同祖先
- 提供基本的測試環境設定和輔助方法
- 包含隨機資料產生器等通用功能

#### `Tests\Support\UnitTestCase`
- 適用於純單元測試
- 不包含資料庫和外部依賴
- 只提供 Mockery 清理功能

#### `Tests\Support\DatabaseTestCase`
- 適用於需要資料庫操作的測試
- 自動設定記憶體 SQLite 資料庫
- 提供測試資料建立輔助方法

#### `Tests\Support\IntegrationTestCase`
- 適用於完整的整合測試
- 包含資料庫、快取、HTTP 回應等完整功能
- 組合多個 Trait 提供豐富的測試環境

### Trait 功能模組

#### `Tests\Support\Traits\DatabaseTestTrait`
- 提供資料庫相關的測試功能
- 自動建立測試用資料表和索引
- 提供測試資料插入輔助方法
- 支援自訂資料表結構

#### `Tests\Support\Traits\CacheTestTrait`
- 提供模擬快取服務功能
- 記憶體儲存快取資料
- 提供快取斷言輔助方法
- 支援所有常見的快取操作

#### `Tests\Support\Traits\HttpResponseTestTrait`
- 提供 HTTP 回應相關的模擬物件
- 支援各種回應類型（JSON、狀態碼等）
- 提供回應內容斷言方法
- 簡化 HTTP 測試的設定流程

## 使用方式

### 純單元測試

```php
use Tests\Support\UnitTestCase;

class MyServiceTest extends UnitTestCase
{
    // 不需要資料庫或快取的純單元測試
}
```

### 需要資料庫的測試

```php
use Tests\Support\DatabaseTestCase;

class MyRepositoryTest extends DatabaseTestCase
{
    public function testSomeMethod()
    {
        // 可以直接使用 $this->db
        $postId = $this->insertTestPost(['title' => 'Test']);
        // ...
    }
}
```

### 整合測試

```php
use Tests\Support\IntegrationTestCase;

class MyControllerTest extends IntegrationTestCase
{
    public function testAction()
    {
        // 可以使用資料庫、快取、HTTP 回應等完整功能
        $this->setCacheValue('test_key', 'test_value');
        $response = $this->createJsonResponseMock(['status' => 'ok']);
        // ...
    }
}
```

### 組合式使用

如果需要特定的功能組合，可以建立自訂的測試基底類別：

```php
use Tests\Support\BaseTestCase;
use Tests\Support\Traits\CacheTestTrait;

abstract class CacheOnlyTestCase extends BaseTestCase
{
    use CacheTestTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCache();
    }
    
    protected function tearDown(): void
    {
        $this->tearDownCache();
        parent::tearDown();
    }
}
```

## 遷移指南

### 現有測試的相容性

現有的測試類別可以繼續使用 `Tests\TestCase`，該類別現在繼承自 `IntegrationTestCase` 並保持完全的向後相容性。

### 建議的遷移步驟

1. **新測試類別**：使用適當的新基底類別
2. **單元測試**：考慮遷移到 `UnitTestCase`
3. **資料庫測試**：考慮遷移到 `DatabaseTestCase`
4. **逐步重構**：根據需要逐步重構現有測試

## 優勢

### 性能改善
- 單元測試不再需要設定資料庫，執行更快
- 按需載入功能，減少記憶體使用

### 程式碼維護性
- 關注點分離，每個 Trait 負責特定功能
- 減少重複程式碼，提高可重複使用性
- 更清晰的測試意圖表達

### 擴充性
- 容易新增新的測試功能模組
- 靈活的組合方式
- 支援客製化測試環境

## 測試輔助方法

### 資料產生器
- `generateRandomString(int $length)`: 產生隨機字串
- `generateTestUuid()`: 產生測試用 UUID
- `generateTestEmail()`: 產生測試用電子郵件

### 資料庫輔助方法
- `insertTestPost(array $data)`: 插入測試貼文
- `insertTestUser(array $data)`: 插入測試使用者
- 可以自行擴充更多資料建立方法

### 快取斷言方法
- `assertCacheHasKey(string $key)`: 檢查快取鍵存在
- `assertCacheValue(string $key, $value)`: 檢查快取值
- `assertCacheIsEmpty()`: 檢查快取為空

### HTTP 回應斷言方法
- `assertResponseStatus(ResponseInterface $response, int $status)`: 檢查回應狀態
- `assertJsonResponseHasKey(ResponseInterface $response, string $key)`: 檢查 JSON 回應鍵
- `assertResponseContains(ResponseInterface $response, string $text)`: 檢查回應內容

## 注意事項

1. **命名空間**：新的類別都位於 `Tests\Support` 命名空間下
2. **相容性**：舊的 `Tests\TestCase` 仍然可用，但標記為 deprecated
3. **效能**：建議根據測試需要選擇最輕量的基底類別
4. **擴充**：需要新功能時，優先考慮建立新的 Trait

這個新架構提供了更好的靈活性和可維護性，讓測試程式碼更加清晰和高效。