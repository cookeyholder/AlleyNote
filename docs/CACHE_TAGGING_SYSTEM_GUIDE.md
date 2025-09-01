# 快取標籤系統使用指南

## 概述

AlleyNote 快取標籤系統提供了強大且靈活的快取管理功能，支援按標籤組織快取項目、批次操作、分組管理，以及智能失效策略。

## 核心概念

### 1. 標籤化快取 (Tagged Cache)

標籤化快取允許您為每個快取項目分配一個或多個標籤，以便後續按標籤進行批次操作：

```php
// 獲取標籤化快取實例
$cache = app(TaggedCacheInterface::class);

// 為快取項目設定標籤
$taggedCache = $cache->tags(['user:123', 'posts']);
$taggedCache->put('user_posts_123', $posts, 3600);

// 按標籤清空快取
$cache->flushByTags(['user:123']); // 清空所有用戶相關快取
$cache->flushByTags(['posts']); // 清空所有文章相關快取
```

### 2. 快取分組 (Cache Groups)

快取分組提供更高層次的快取組織和管理功能，支援依賴關係和失效規則：

```php
use App\Shared\Cache\Services\CacheGroupManager;

$groupManager = app(CacheGroupManager::class);

// 建立快取分組
$userGroup = $groupManager->group('user_123', ['user:123', 'profile']);
$userGroup->put('user_info', $userInfo, 3600);
$userGroup->put('user_preferences', $preferences, 7200);

// 清空整個分組
$groupManager->flushGroup('user_123');
```

### 3. 標籤值物件 (CacheTag)

標籤值物件提供標籤的正規化和驗證功能：

```php
use App\Shared\Cache\ValueObjects\CacheTag;

// 建立不同類型的標籤
$userTag = CacheTag::user(123);        // "user:123"
$groupTag = CacheTag::group('admins');  // "group:admins"
$moduleTag = CacheTag::module('posts'); // "module:posts"
$timeTag = CacheTag::temporal('daily'); // "time:daily"

// 檢查標籤類型
$isUserTag = $userTag->isUserTag(); // true
$isGroupTag = $userTag->isGroupTag(); // false
```

## 功能特性

### 1. 基本標籤操作

#### 設定帶標籤的快取

```php
// 方式一：使用 tags() 方法
$cache = app(TaggedCacheInterface::class);
$userCache = $cache->tags(['user:123', 'profile']);
$userCache->put('user_data', $userData, 3600);

// 方式二：使用 putWithTags() 方法
$cache->putWithTags('user_data', $userData, ['user:123', 'profile'], 3600);
```

#### 取得標籤下的所有鍵

```php
$keys = $cache->getKeysByTag('user:123');
// 結果：['user_data', 'user_posts', 'user_preferences']
```

#### 清空指定標籤的快取

```php
$clearedCount = $cache->flushByTags(['user:123']);
echo "清空了 {$clearedCount} 個快取項目";
```

### 2. 進階標籤管理

#### 為現有快取項目添加/移除標籤

```php
// 為現有快取添加標籤
$cache->addTagsToKey('user_data', ['vip', 'premium']);

// 移除標籤
$cache->removeTagsFromKey('user_data', ['premium']);

// 檢查是否包含標籤
$hasVipTag = $cache->hasTag('user_data', 'vip'); // true
```

#### 取得快取項目的所有標籤

```php
$tags = $cache->getTagsByKey('user_data');
// 結果：['user:123', 'profile', 'vip']
```

### 3. 快取分組功能

#### 建立和使用分組

```php
$groupManager = app(CacheGroupManager::class);

// 建立使用者相關分組
$userGroup = $groupManager->userGroup(123, ['profile', 'settings']);
$userGroup->put('basic_info', $basicInfo, 3600);
$userGroup->put('extended_info', $extendedInfo, 1800);

// 建立模組相關分組
$postsGroup = $groupManager->moduleGroup('posts', ['content']);
$postsGroup->put('recent_posts', $recentPosts, 900);
```

#### 分組依賴關係

```php
// 設定分組依賴：當 parent_group 失效時，child_groups 也會失效
$groupManager->setDependencies('user_123', ['user_posts_123', 'user_comments_123']);

// 清空父分組時，子分組會自動清空
$groupManager->flushGroup('user_123', true); // cascade = true
```

#### 分組失效規則

```php
// 設定自動失效規則
$groupManager->setInvalidationRules('user_123', [
    'max_age' => 3600,
    'invalidate_on' => ['user_update', 'password_change']
]);

// 觸發失效規則
$groupManager->triggerInvalidationRules(['user_update']); // 自動清空相關分組
```

#### 按模式清空分組

```php
// 清空所有用戶相關分組
$clearedCount = $groupManager->flushByPattern('user_*');

// 清空特定模組的所有分組
$clearedCount = $groupManager->flushByPattern('module_posts_*');
```

### 4. 統計和監控

#### 快取統計

```php
// 取得標籤統計
$stats = $cache->getTagStatistics();
/* 結果：
[
    'user:123' => 5,    // 5 個快取項目
    'posts' => 12,      // 12 個快取項目
    'profile' => 3      // 3 個快取項目
]
*/

// 取得分組統計
$groupStats = $groupManager->getGroupStatistics();
/* 結果：
[
    'total_groups' => 10,
    'groups' => [
        'user_123' => [
            'cache_count' => 5,
            'created_at' => '2024-01-15 10:30:00',
            'last_accessed' => '2024-01-15 11:45:00'
        ]
    ]
]
*/
```

#### 清理和維護

```php
// 清理未使用的標籤
$cleanedTags = $cache->cleanupUnusedTags();

// 清理過期的分組
$cleanedGroups = $groupManager->cleanupExpiredGroups();
```

## 使用範例

### 範例 1：用戶資料快取

```php
use App\Shared\Cache\Services\CacheGroupManager;
use App\Shared\Cache\ValueObjects\CacheTag;

class UserService
{
    private CacheGroupManager $cacheManager;

    public function __construct(CacheGroupManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    public function getUserData(int $userId): array
    {
        // 建立用戶快取分組
        $userGroup = $this->cacheManager->userGroup($userId, ['profile']);

        return $userGroup->remember("user_data_{$userId}", function() use ($userId) {
            return $this->fetchUserFromDatabase($userId);
        }, 3600);
    }

    public function updateUser(int $userId, array $data): void
    {
        // 更新數據庫
        $this->updateUserInDatabase($userId, $data);

        // 清空用戶相關快取
        $this->cacheManager->flushGroup("user_{$userId}");

        // 也清空相關的快取
        $this->cacheManager->triggerInvalidationRules(['user_update']);
    }
}
```

### 範例 2：文章快取管理

```php
class PostService
{
    private CacheGroupManager $cacheManager;

    public function getRecentPosts(int $limit = 10): array
    {
        $postsGroup = $this->cacheManager->moduleGroup('posts', ['recent']);

        return $postsGroup->remember("recent_posts_{$limit}", function() use ($limit) {
            return $this->fetchRecentPostsFromDatabase($limit);
        }, 900); // 15 分鐘快取
    }

    public function getUserPosts(int $userId): array
    {
        // 組合標籤：用戶標籤 + 文章標籤
        $cache = app(TaggedCacheInterface::class);
        $userPostsCache = $cache->tags([
            CacheTag::user($userId)->getName(),
            CacheTag::module('posts')->getName()
        ]);

        return $userPostsCache->remember("user_posts_{$userId}", function() use ($userId) {
            return $this->fetchUserPostsFromDatabase($userId);
        }, 1800); // 30 分鐘快取
    }

    public function createPost(int $userId, array $postData): int
    {
        $postId = $this->createPostInDatabase($userId, $postData);

        // 清空相關快取
        $cache = app(TaggedCacheInterface::class);
        $cache->flushByTags([
            CacheTag::user($userId)->getName(),
            CacheTag::module('posts')->getName()
        ]);

        return $postId;
    }
}
```

### 範例 3：分層快取策略

```php
class HierarchicalCacheExample
{
    private CacheGroupManager $cacheManager;

    public function setupHierarchicalCache(): void
    {
        // 建立分層分組結構
        $this->cacheManager->group('system', ['global']);
        $this->cacheManager->group('users', ['user_data']);
        $this->cacheManager->group('user_123', ['user:123']);

        // 設定依賴關係：system -> users -> user_123
        $this->cacheManager->setDependencies('system', ['users']);
        $this->cacheManager->setDependencies('users', ['user_123']);

        // 設定失效規則
        $this->cacheManager->setInvalidationRules('user_123', [
            'max_age' => 3600,
            'invalidate_on' => ['user_update']
        ]);
    }

    public function clearSystemCache(): void
    {
        // 清空系統快取會級聯清空所有用戶快取
        $this->cacheManager->flushGroup('system', true);
    }
}
```

## 最佳實務

### 1. 標籤命名規則

- 使用清晰的前綴：`user:123`, `post:456`, `module:posts`
- 避免過長的標籤名稱（建議 50 字符以內）
- 使用一致的命名格式
- 避免使用系統保留字：`all`, `none`, `cache`, `system`

### 2. 快取分組策略

```php
// 好的做法：按功能模組劃分分組
$userProfileGroup = $groupManager->group('user_profile_123', ['user:123', 'profile']);
$userPostsGroup = $groupManager->group('user_posts_123', ['user:123', 'posts']);

// 避免：過度細分或過度聚合
$tooSpecificGroup = $groupManager->group('user_avatar_thumbnail_123', ['user:123']); // 過度細分
$tooGeneralGroup = $groupManager->group('all_user_data', ['users']); // 過度聚合
```

### 3. 記憶體管理

```php
// 定期清理未使用的標籤和過期分組
$this->scheduledTask(function() {
    $cache = app(TaggedCacheInterface::class);
    $groupManager = app(CacheGroupManager::class);

    $cleanedTags = $cache->cleanupUnusedTags();
    $cleanedGroups = $groupManager->cleanupExpiredGroups();

    Log::info("快取清理完成", [
        'cleaned_tags' => $cleanedTags,
        'cleaned_groups' => $cleanedGroups
    ]);
});
```

### 4. 效能優化

- 避免在單一請求中創建過多分組
- 合理設定快取時間，避免過度頻繁的失效
- 使用批次操作代替單個操作
- 定期監控快取統計，調整策略

```php
// 批次操作示例
$keysToFlush = ['user_posts_123', 'user_comments_123', 'user_likes_123'];
$groupManager->flushGroups($keysToFlush); // 批次清空
```

### 5. 錯誤處理

```php
try {
    $cache = app(TaggedCacheInterface::class);
    $result = $cache->tags(['user:123'])->remember('user_data', $callback);
} catch (CacheException $e) {
    Log::error('快取操作失敗', ['error' => $e->getMessage()]);
    // 降級到直接查詢數據庫
    $result = $this->fetchFromDatabase();
}
```

## 設定和配置

### 快取驅動配置

```php
// config/cache.php
return [
    'tagged_cache' => [
        'driver' => 'redis',
        'connection' => 'default',
        'prefix' => 'alleynote:tagged:',
        'ttl' => 3600,
    ],

    'group_manager' => [
        'max_groups' => 1000,
        'default_ttl' => 3600,
        'cleanup_interval' => 3600,
    ]
];
```

### 容器綁定

```php
// config/container.php
use App\Shared\Cache\Services\CacheGroupManager;
use App\Shared\Cache\Contracts\TaggedCacheInterface;

$container->singleton(TaggedCacheInterface::class, function() {
    return new RedisTaggedCache(/* 配置 */);
});

$container->singleton(CacheGroupManager::class, function($container) {
    return new CacheGroupManager(
        $container->get(TaggedCacheInterface::class),
        $container->get(LoggerInterface::class)
    );
});
```

## 故障排除

### 常見問題

1. **標籤名稱無效**
   ```
   錯誤：標籤名稱只能包含英文字母、數字、底線、連字號和點號
   解決：使用 CacheTag::isValidName() 驗證標籤名稱
   ```

2. **分組不存在**
   ```
   錯誤：嘗試操作不存在的分組
   解決：先使用 hasGroup() 檢查分組是否存在
   ```

3. **快取未失效**
   ```
   問題：修改數據後快取沒有正確更新
   解決：確認失效規則設定正確，檢查標籤是否匹配
   ```

### 除錯工具

```php
// 除錯快取狀態
$cache = app(TaggedCacheInterface::class);
$groupManager = app(CacheGroupManager::class);

// 查看標籤統計
dump($cache->getTagStatistics());

// 查看分組統計
dump($groupManager->getGroupStatistics());

// 查看特定標籤的鍵
dump($cache->getKeysByTag('user:123'));
```

## API 參考

### TaggedCacheInterface

- `tags(string|array $tags): TaggedCacheInterface` - 設定標籤
- `flushByTags(string|array $tags): int` - 按標籤清空
- `getKeysByTag(string $tag): array` - 取得標籤下的鍵
- `putWithTags(string $key, mixed $value, array $tags, int $ttl): bool` - 帶標籤存儲
- `addTagsToKey(string $key, string|array $tags): bool` - 添加標籤到鍵
- `getTagsByKey(string $key): array` - 取得鍵的標籤

### CacheGroupManager

- `group(string $name, array $tags): TaggedCacheInterface` - 建立分組
- `flushGroup(string $name, bool $cascade): int` - 清空分組
- `flushByPattern(string $pattern, bool $cascade): int` - 按模式清空
- `setDependencies(string $parent, array|string $children): void` - 設定依賴
- `setInvalidationRules(string $group, array $rules): void` - 設定失效規則
- `getGroupStatistics(): array` - 取得統計資訊

---

*這份文件涵蓋了 AlleyNote 快取標籤系統的核心功能和使用方法。如需更多技術細節，請參考原始程式碼和單元測試。*
