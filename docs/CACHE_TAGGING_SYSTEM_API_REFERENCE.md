# 快取標籤系統 API 參考

## 概述

本文件提供 AlleyNote 快取標籤系統的完整 API 參考，包括所有介面、類別和方法的詳細說明。

## 核心介面

### TaggedCacheInterface

標籤化快取的核心介面，提供標籤相關的快取操作功能。

```php
namespace App\Shared\Cache\Contracts;

interface TaggedCacheInterface
```

#### 基本快取操作

##### `get(string $key, mixed $default = null): mixed`

取得快取資料。

**參數：**
- `$key` - 快取鍵
- `$default` - 預設值（當快取不存在時返回）

**返回：**
- 快取值或預設值

**範例：**
```php
$value = $cache->get('user:123', []);
```

##### `put(string $key, mixed $value, int $ttl = 3600): bool`

設定快取資料。

**參數：**
- `$key` - 快取鍵
- `$value` - 快取值
- `$ttl` - 存活時間（秒）

**返回：**
- 成功返回 `true`，失敗返回 `false`

**範例：**
```php
$success = $cache->put('user:123', $userData, 3600);
```

##### `has(string $key): bool`

檢查快取是否存在。

**參數：**
- `$key` - 快取鍵

**返回：**
- 存在返回 `true`，不存在返回 `false`

##### `forget(string $key): bool`

刪除快取。

**參數：**
- `$key` - 快取鍵

**返回：**
- 成功返回 `true`，失敗返回 `false`

##### `flush(): bool`

清空所有標籤化快取。

**返回：**
- 成功返回 `true`，失敗返回 `false`

##### `remember(string $key, callable $callback, int $ttl = 3600): mixed`

記憶化取得：如果快取不存在則執行回調函式並快取結果。

**參數：**
- `$key` - 快取鍵
- `$callback` - 回調函式
- `$ttl` - 存活時間（秒）

**返回：**
- 快取值或回調函式返回值

**範例：**
```php
$userData = $cache->remember('user:123', function() {
    return $this->fetchUserFromDatabase(123);
}, 3600);
```

#### 標籤管理操作

##### `addTags(string|array $tags): TaggedCacheInterface`

為當前快取實例增加標籤。

**參數：**
- `$tags` - 標籤或標籤陣列

**返回：**
- 標籤化快取實例

**範例：**
```php
$taggedCache = $cache->addTags(['user:123', 'profile']);
```

##### `getTags(): array`

取得當前快取實例的所有標籤。

**返回：**
- 標籤陣列

##### `tags(string|array $tags): TaggedCacheInterface`

建立新的標籤化快取實例。

**參數：**
- `$tags` - 標籤或標籤陣列

**返回：**
- 新的標籤化快取實例

**範例：**
```php
$userCache = $cache->tags(['user:123', 'profile']);
```

##### `flushByTags(string|array $tags): int`

按標籤清空快取。

**參數：**
- `$tags` - 要清空的標籤或標籤陣列

**返回：**
- 清空的項目數量

**範例：**
```php
$clearedCount = $cache->flushByTags(['user:123']);
```

##### `getTaggedKeys(): array`

取得當前標籤化快取實例的所有快取鍵。

**返回：**
- 快取鍵陣列

#### 進階標籤功能

##### `putWithTags(string $key, mixed $value, array $tags, int $ttl = 3600): bool`

使用指定標籤存放快取項目。

**參數：**
- `$key` - 快取鍵
- `$value` - 快取值
- `$tags` - 標籤陣列
- `$ttl` - 存活時間（秒）

**返回：**
- 成功返回 `true`，失敗返回 `false`

**範例：**
```php
$success = $cache->putWithTags('user_data', $data, ['user:123', 'profile'], 3600);
```

##### `getKeysByTag(string $tag): array`

取得指定標籤的所有快取鍵。

**參數：**
- `$tag` - 標籤名稱

**返回：**
- 快取鍵陣列

##### `getTagsByKey(string $key): array`

取得快取項目的所有標籤。

**參數：**
- `$key` - 快取鍵

**返回：**
- 標籤陣列

##### `addTagsToKey(string $key, string|array $tags): bool`

為現有快取項目添加標籤。

**參數：**
- `$key` - 快取鍵
- `$tags` - 標籤或標籤陣列

**返回：**
- 成功返回 `true`，失敗返回 `false`

##### `removeTagsFromKey(string $key, string|array $tags): bool`

從快取項目移除標籤。

**參數：**
- `$key` - 快取鍵
- `$tags` - 標籤或標籤陣列

**返回：**
- 成功返回 `true`，失敗返回 `false`

##### `hasTag(string $key, string $tag): bool`

檢查快取項目是否包含指定標籤。

**參數：**
- `$key` - 快取鍵
- `$tag` - 標籤名稱

**返回：**
- 包含返回 `true`，不包含返回 `false`

#### 系統功能

##### `getAllTags(): array`

取得所有系統標籤。

**返回：**
- 所有標籤陣列

##### `cleanupUnusedTags(): int`

清除未使用的標籤。

**返回：**
- 清除的標籤數量

##### `getTagStatistics(): array`

取得標籤統計資訊。

**返回：**
- 標籤統計資訊陣列，格式：`['tag_name' => item_count, ...]`

---

## 快取分組管理

### CacheGroupManager

快取分組管理器，提供高階的快取分組和依賴管理功能。

```php
namespace App\Shared\Cache\Services;

class CacheGroupManager
```

#### 建構函式

```php
public function __construct(
    TaggedCacheInterface $taggedCache,
    LoggerInterface $logger
)
```

**參數：**
- `$taggedCache` - 標籤化快取實例
- `$logger` - 日誌記錄器

#### 分組建立和管理

##### `group(string $groupName, array $tags = []): TaggedCacheInterface`

建立或取得快取分組。

**參數：**
- `$groupName` - 分組名稱
- `$tags` - 額外標籤陣列

**返回：**
- 標籤化快取實例

**範例：**
```php
$userGroup = $groupManager->group('user_123', ['profile', 'settings']);
```

##### `userGroup(int $userId, array $additionalTags = []): TaggedCacheInterface`

建立使用者相關的快取分組。

**參數：**
- `$userId` - 使用者 ID
- `$additionalTags` - 額外標籤陣列

**返回：**
- 使用者快取分組

**範例：**
```php
$userGroup = $groupManager->userGroup(123, ['premium']);
```

##### `moduleGroup(string $moduleName, array $additionalTags = []): TaggedCacheInterface`

建立模組相關的快取分組。

**參數：**
- `$moduleName` - 模組名稱
- `$additionalTags` - 額外標籤陣列

**返回：**
- 模組快取分組

##### `temporalGroup(string $period, array $additionalTags = []): TaggedCacheInterface`

建立時間相關的快取分組。

**參數：**
- `$period` - 時間週期（daily, weekly, monthly）
- `$additionalTags` - 額外標籤陣列

**返回：**
- 時間快取分組

#### 分組操作

##### `hasGroup(string $groupName): bool`

檢查分組是否存在。

**參數：**
- `$groupName` - 分組名稱

**返回：**
- 存在返回 `true`，不存在返回 `false`

##### `getGroup(string $groupName): ?TaggedCacheInterface`

取得快取分組。

**參數：**
- `$groupName` - 分組名稱

**返回：**
- 分組快取實例或 `null`

##### `getAllGroups(): array`

取得所有分組名稱。

**返回：**
- 分組名稱陣列

##### `flushGroup(string $groupName, bool $cascade = true): int`

清空快取分組。

**參數：**
- `$groupName` - 分組名稱
- `$cascade` - 是否級聯清空依賴分組

**返回：**
- 清空的項目數量

##### `flushGroups(array $groupNames, bool $cascade = true): int`

批量清空多個分組。

**參數：**
- `$groupNames` - 分組名稱陣列
- `$cascade` - 是否級聯清空

**返回：**
- 清空的項目總數

##### `flushByPattern(string $pattern, bool $cascade = true): int`

按模式清空分組。

**參數：**
- `$pattern` - 分組名稱模式（支援 `*` 萬用字元）
- `$cascade` - 是否級聯清空

**返回：**
- 清空的項目數量

**範例：**
```php
// 清空所有使用者分組
$clearedCount = $groupManager->flushByPattern('user_*');
```

#### 依賴關係管理

##### `setDependencies(string $parentGroup, array|string $childGroups): void`

設定分組依賴關係。

**參數：**
- `$parentGroup` - 父分組名稱
- `$childGroups` - 子分組名稱或名稱陣列

**範例：**
```php
$groupManager->setDependencies('user_123', ['user_posts_123', 'user_comments_123']);
```

##### `getDependencies(string $groupName): array`

取得分組的依賴關係。

**參數：**
- `$groupName` - 分組名稱

**返回：**
- 依賴分組名稱陣列

##### `removeDependencies(string $parentGroup, array|string $childGroups): void`

移除分組依賴關係。

**參數：**
- `$parentGroup` - 父分組名稱
- `$childGroups` - 子分組名稱或名稱陣列

#### 失效規則管理

##### `setInvalidationRules(string $groupName, array $rules): void`

設定分組失效規則。

**參數：**
- `$groupName` - 分組名稱
- `$rules` - 失效規則陣列

**規則格式：**
```php
[
    'max_age' => 3600,                    // 最大存活時間（秒）
    'invalidate_on' => ['user_update']    // 觸發失效的事件
]
```

##### `getInvalidationRules(string $groupName): array`

取得分組失效規則。

**參數：**
- `$groupName` - 分組名稱

**返回：**
- 失效規則陣列

##### `triggerInvalidationRules(array $events): array`

觸發失效規則。

**參數：**
- `$events` - 事件陣列

**返回：**
- 失效分組的統計結果

**範例：**
```php
$result = $groupManager->triggerInvalidationRules(['user_update']);
```

#### 統計和維護

##### `getGroupStatistics(): array`

取得分組統計資訊。

**返回：**
- 統計資訊陣列

**格式：**
```php
[
    'total_groups' => 10,
    'groups' => [
        'user_123' => [
            'cache_count' => 5,
            'tags' => ['user:123', 'profile'],
            'dependencies' => ['user_posts_123']
        ]
    ],
    'dependencies' => [...],
    'invalidation_rules_count' => 3
]
```

##### `cleanupExpiredGroups(): int`

清理過期的分組。

**返回：**
- 清理的分組數量

---

## 值物件

### CacheTag

快取標籤值物件，提供標籤的建立、驗證和正規化功能。

```php
namespace App\Shared\Cache\ValueObjects;

class CacheTag
```

#### 建構函式

```php
public function __construct(string $name)
```

**參數：**
- `$name` - 標籤名稱

**例外：**
- 如果標籤名稱無效，拋出 `InvalidArgumentException`

#### 基本方法

##### `getName(): string`

取得標籤名稱。

**返回：**
- 標籤名稱

##### `__toString(): string`

轉換為字串。

**返回：**
- 標籤名稱

##### `equals(CacheTag $other): bool`

比較兩個標籤是否相等。

**參數：**
- `$other` - 另一個標籤物件

**返回：**
- 相等返回 `true`，不相等返回 `false`

#### 靜態工廠方法

##### `static group(string $group): self`

建立標籤群組標籤。

**參數：**
- `$group` - 群組名稱

**返回：**
- 群組標籤實例

**範例：**
```php
$groupTag = CacheTag::group('admins'); // "group:admins"
```

##### `static user(int $userId): self`

建立使用者相關標籤。

**參數：**
- `$userId` - 使用者 ID

**返回：**
- 使用者標籤實例

##### `static module(string $module): self`

建立模組相關標籤。

**參數：**
- `$module` - 模組名稱

**返回：**
- 模組標籤實例

##### `static temporal(string $period): self`

建立時間相關標籤。

**參數：**
- `$period` - 時間週期

**返回：**
- 時間標籤實例

#### 工具方法

##### `static fromArray(array $names): array`

從字串陣列建立標籤陣列。

**參數：**
- `$names` - 標籤名稱陣列

**返回：**
- 標籤物件陣列

##### `static toArray(array $tags): array`

將標籤陣列轉換為字串陣列。

**參數：**
- `$tags` - 標籤物件陣列

**返回：**
- 字串陣列

##### `static isValidName(string $name): bool`

檢查標籤名稱是否有效。

**參數：**
- `$name` - 標籤名稱

**返回：**
- 有效返回 `true`，無效返回 `false`

#### 類型檢查方法

##### `isGroupTag(): bool`

檢查是否為群組標籤。

**返回：**
- 是群組標籤返回 `true`

##### `isUserTag(): bool`

檢查是否為使用者標籤。

**返回：**
- 是使用者標籤返回 `true`

##### `isModuleTag(): bool`

檢查是否為模組標籤。

**返回：**
- 是模組標籤返回 `true`

##### `isTemporalTag(): bool`

檢查是否為時間標籤。

**返回：**
- 是時間標籤返回 `true`

---

## 控制器

### TagManagementController

標籤管理控制器，提供標籤管理的 HTTP API。

```php
namespace App\Application\Controllers\Admin;

class TagManagementController
```

#### API 端點

##### `GET /admin/cache/tags`

取得所有標籤統計資訊。

**回應格式：**
```json
{
    "success": true,
    "data": {
        "total_tags": 25,
        "tags": {
            "user:123": 5,
            "posts": 12,
            "module:comments": 8
        }
    }
}
```

##### `DELETE /admin/cache/tags`

清空指定標籤的快取。

**請求格式：**
```json
{
    "tags": ["user:123", "posts"]
}
```

**回應格式：**
```json
{
    "success": true,
    "data": {
        "cleared_count": 17,
        "cleared_tags": ["user:123", "posts"]
    }
}
```

##### `POST /admin/cache/tags/cleanup`

清理未使用的標籤。

**回應格式：**
```json
{
    "success": true,
    "data": {
        "cleaned_tags": 5
    }
}
```

##### `GET /admin/cache/groups`

取得所有分組統計資訊。

**回應格式：**
```json
{
    "success": true,
    "data": {
        "total_groups": 10,
        "groups": {
            "user_123": {
                "cache_count": 5,
                "tags": ["user:123", "profile"]
            }
        }
    }
}
```

##### `DELETE /admin/cache/groups/{groupName}`

清空指定分組的快取。

**路徑參數：**
- `groupName` - 分組名稱

**查詢參數：**
- `cascade` - 是否級聯清空（預設：true）

**回應格式：**
```json
{
    "success": true,
    "data": {
        "cleared_count": 5,
        "group_name": "user_123"
    }
}
```

---

## 例外類別

### CacheException

快取相關的基礎例外類別。

```php
namespace App\Shared\Cache\Exceptions;

class CacheException extends \Exception
```

### TagValidationException

標籤驗證相關的例外類別。

```php
namespace App\Shared\Cache\Exceptions;

class TagValidationException extends CacheException
```

### GroupNotFoundException

分組不存在的例外類別。

```php
namespace App\Shared\Cache\Exceptions;

class GroupNotFoundException extends CacheException
```

---

## 事件

### 快取事件

系統會在特定操作時觸發事件，可用於日誌記錄和監控。

#### CacheTagFlushed

標籤清空事件。

**屬性：**
- `tags` - 被清空的標籤陣列
- `clearedCount` - 清空的項目數量
- `timestamp` - 事件時間

#### CacheGroupCreated

分組建立事件。

**屬性：**
- `groupName` - 分組名稱
- `tags` - 分組標籤陣列
- `timestamp` - 事件時間

#### CacheGroupFlushed

分組清空事件。

**屬性：**
- `groupName` - 分組名稱
- `clearedCount` - 清空的項目數量
- `cascade` - 是否級聯清空
- `timestamp` - 事件時間

---

## 配置選項

### 標籤化快取配置

```php
// config/cache.php
return [
    'tagged' => [
        'driver' => 'redis',           // 快取驅動
        'prefix' => 'app:tagged:',     // 鍵前綴
        'ttl' => 3600,                 // 預設 TTL
        'serialize' => true,           // 是否序列化
        'compression' => false,        // 是否壓縮
    ]
];
```

### 分組管理器配置

```php
// config/cache.php
return [
    'group_manager' => [
        'max_groups' => 1000,          // 最大分組數量
        'default_ttl' => 3600,         // 預設 TTL
        'cleanup_interval' => 3600,    // 清理間隔
        'auto_cascade' => true,        // 自動級聯清空
        'log_operations' => true,      // 記錄操作日誌
    ]
];
```

---

## 錯誤代碼

| 代碼 | 說明 |
|------|------|
| `CACHE_001` | 標籤名稱無效 |
| `CACHE_002` | 分組不存在 |
| `CACHE_003` | 操作失敗 |
| `CACHE_004` | 配置錯誤 |
| `CACHE_005` | 連線失敗 |

---

*此 API 參考文件涵蓋了快取標籤系統的所有公開介面和方法。如需更多實現細節，請參考原始程式碼。*
