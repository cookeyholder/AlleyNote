<?php

declare(strict_types=1);

namespace App\Application\Controllers\Admin;

use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\Contracts\TagRepositoryInterface;
use App\Shared\Cache\Services\CacheGroupManager;
use App\Shared\Cache\ValueObjects\CacheTag;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * 標籤管理控制器
 *
 * 提供快取標籤管理的 REST API 端點
 */
class TagManagementController
{
    public function __construct(
        private CacheManagerInterface $cacheManager,
        private ?TagRepositoryInterface $tagRepository = null,
        private ?CacheGroupManager $groupManager = null,
        private ?LoggerInterface $logger = null
    ) {
    }

    /**
     * 取得所有標籤列表
     *
     * GET /admin/cache/tags
     */
    public function listTags(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $queryParams = $request->getQueryParams();
            $page = max(1, (int) ($queryParams['page'] ?? 1));
            $limit = min(100, max(10, (int) ($queryParams['limit'] ?? 20)));
            $search = $queryParams['search'] ?? '';

            $tags = [];
            $totalTags = 0;

            // 從支援標籤的驅動取得標籤資訊
            foreach (['redis', 'memory'] as $driverName) {
                $driver = $this->cacheManager->getDriver($driverName);
                if ($driver && $driver instanceof TaggedCacheInterface) {
                    $driverTags = $driver->getAllTags();
                    $tagStats = $driver->getTagStatistics();
                    
                    foreach ($driverTags as $tag) {
                        if (empty($search) || stripos($tag, $search) !== false) {
                            $tags[] = [
                                'name' => $tag,
                                'driver' => $driverName,
                                'key_count' => $tagStats['tags'][$tag]['key_count'] ?? 0,
                                'sample_keys' => $tagStats['tags'][$tag]['sample_keys'] ?? [],
                                'type' => $this->getTagType($tag),
                                'created_at' => $this->getTagCreatedAt($tag),
                            ];
                        }
                    }
                    break; // 使用第一個可用的驅動
                }
            }

            $totalTags = count($tags);

            // 分頁處理
            $offset = ($page - 1) * $limit;
            $paginatedTags = array_slice($tags, $offset, $limit);

            $responseData = [
                'success' => true,
                'data' => [
                    'tags' => $paginatedTags,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $totalTags,
                        'pages' => ceil($totalTags / $limit),
                    ],
                    'search' => $search,
                ],
                'timestamp' => time(),
            ];

        } catch (\Exception $e) {
            $this->logger?->error('取得標籤列表失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '取得標籤列表失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        $response->getBody()->write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 取得特定標籤詳細資訊
     *
     * GET /admin/cache/tags/{tag}
     */
    public function getTag(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $tagName = urldecode($args['tag'] ?? '');
            
            if (empty($tagName)) {
                throw new \InvalidArgumentException('標籤名稱不能為空');
            }

            $tagInfo = null;

            // 從支援標籤的驅動取得標籤詳細資訊
            foreach (['redis', 'memory'] as $driverName) {
                $driver = $this->cacheManager->getDriver($driverName);
                if ($driver && $driver instanceof TaggedCacheInterface) {
                    if ($driver->tagExists($tagName)) {
                        $keys = $driver->getKeysByTag($tagName);
                        $tagStats = $driver->getTagStatistics();
                        
                        $keyDetails = [];
                        foreach (array_slice($keys, 0, 50) as $key) { // 限制顯示前50個鍵
                            $value = $driver->get($key);
                            $keyDetails[] = [
                                'key' => $key,
                                'has_value' => $value !== null,
                                'value_type' => gettype($value),
                                'value_size' => is_string($value) ? strlen($value) : strlen(serialize($value)),
                            ];
                        }

                        $tagInfo = [
                            'name' => $tagName,
                            'driver' => $driverName,
                            'exists' => true,
                            'key_count' => count($keys),
                            'type' => $this->getTagType($tagName),
                            'created_at' => $this->getTagCreatedAt($tagName),
                            'keys' => $keyDetails,
                            'total_memory_usage' => array_sum(array_column($keyDetails, 'value_size')),
                        ];
                        break;
                    }
                }
            }

            if (!$tagInfo) {
                throw new \RuntimeException('標籤不存在或不可用');
            }

            $responseData = [
                'success' => true,
                'data' => $tagInfo,
                'timestamp' => time(),
            ];

        } catch (\Exception $e) {
            $this->logger?->error('取得標籤詳細資訊失敗', [
                'tag' => $args['tag'] ?? '',
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '取得標籤資訊失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        $response->getBody()->write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 清空特定標籤的所有快取
     *
     * DELETE /admin/cache/tags/{tag}
     */
    public function flushTag(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $tagName = urldecode($args['tag'] ?? '');
            
            if (empty($tagName)) {
                throw new \InvalidArgumentException('標籤名稱不能為空');
            }

            $totalCleared = 0;
            $driversAffected = [];

            // 清空所有驅動中的標籤
            foreach (['redis', 'memory', 'file'] as $driverName) {
                $driver = $this->cacheManager->getDriver($driverName);
                if ($driver && $driver instanceof TaggedCacheInterface) {
                    if ($driver->tagExists($tagName)) {
                        $cleared = $driver->flushByTags($tagName);
                        $totalCleared += $cleared;
                        $driversAffected[] = $driverName;
                        
                        $this->logger?->info('標籤快取已清空', [
                            'tag' => $tagName,
                            'driver' => $driverName,
                            'cleared_count' => $cleared,
                        ]);
                    }
                }
            }

            $responseData = [
                'success' => true,
                'data' => [
                    'tag' => $tagName,
                    'cleared_count' => $totalCleared,
                    'drivers_affected' => $driversAffected,
                ],
                'message' => "標籤 '{$tagName}' 的 {$totalCleared} 個快取項目已清空",
                'timestamp' => time(),
            ];

        } catch (\Exception $e) {
            $this->logger?->error('清空標籤快取失敗', [
                'tag' => $args['tag'] ?? '',
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '清空標籤快取失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        $response->getBody()->write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 批量清空多個標籤
     *
     * DELETE /admin/cache/tags
     * Body: {"tags": ["tag1", "tag2", ...]}
     */
    public function flushTags(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = json_decode((string) $request->getBody(), true);
            $tags = $body['tags'] ?? [];

            if (!is_array($tags) || empty($tags)) {
                throw new \InvalidArgumentException('必須提供標籤陣列');
            }

            $results = [];
            $totalCleared = 0;

            foreach ($tags as $tagName) {
                if (!is_string($tagName) || empty($tagName)) {
                    continue;
                }

                $tagCleared = 0;
                $driversAffected = [];

                foreach (['redis', 'memory', 'file'] as $driverName) {
                    $driver = $this->cacheManager->getDriver($driverName);
                    if ($driver && $driver instanceof TaggedCacheInterface) {
                        if ($driver->tagExists($tagName)) {
                            $cleared = $driver->flushByTags($tagName);
                            $tagCleared += $cleared;
                            $driversAffected[] = $driverName;
                        }
                    }
                }

                $results[$tagName] = [
                    'cleared_count' => $tagCleared,
                    'drivers_affected' => $driversAffected,
                ];
                $totalCleared += $tagCleared;
            }

            $this->logger?->info('批量清空標籤快取', [
                'tags' => $tags,
                'total_cleared' => $totalCleared,
                'results' => $results,
            ]);

            $responseData = [
                'success' => true,
                'data' => [
                    'total_cleared' => $totalCleared,
                    'results' => $results,
                ],
                'message' => "批量清空 " . count($tags) . " 個標籤，共 {$totalCleared} 個快取項目",
                'timestamp' => time(),
            ];

        } catch (\Exception $e) {
            $this->logger?->error('批量清空標籤快取失敗', [
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '批量清空標籤快取失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        $response->getBody()->write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 取得標籤統計資訊
     *
     * GET /admin/cache/tags/statistics
     */
    public function getTagStatistics(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $statistics = [
                'drivers' => [],
                'summary' => [
                    'total_tags' => 0,
                    'total_keys' => 0,
                    'total_memory_usage' => 0,
                ],
                'tag_types' => [
                    'user' => 0,
                    'module' => 0,
                    'temporal' => 0,
                    'group' => 0,
                    'custom' => 0,
                ],
            ];

            foreach (['redis', 'memory', 'file'] as $driverName) {
                $driver = $this->cacheManager->getDriver($driverName);
                if ($driver && $driver instanceof TaggedCacheInterface) {
                    $driverStats = $driver->getTagStatistics();
                    $statistics['drivers'][$driverName] = $driverStats;
                    
                    $statistics['summary']['total_tags'] += $driverStats['total_tags'];
                    
                    foreach ($driverStats['tags'] as $tag => $tagData) {
                        $statistics['summary']['total_keys'] += $tagData['key_count'];
                        
                        // 統計標籤類型
                        $tagType = $this->getTagType($tag);
                        if (isset($statistics['tag_types'][$tagType])) {
                            $statistics['tag_types'][$tagType]++;
                        }
                    }
                }
            }

            // 分組統計（如果有分組管理器）
            if ($this->groupManager) {
                $groupStats = $this->groupManager->getGroupStatistics();
                $statistics['groups'] = $groupStats;
            }

            $responseData = [
                'success' => true,
                'data' => $statistics,
                'timestamp' => time(),
            ];

        } catch (\Exception $e) {
            $this->logger?->error('取得標籤統計失敗', [
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '取得標籤統計失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        $response->getBody()->write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 建立快取分組
     *
     * POST /admin/cache/groups
     * Body: {"name": "group_name", "tags": ["tag1", "tag2"], "dependencies": [...]}
     */
    public function createGroup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = json_decode((string) $request->getBody(), true);
            $groupName = $body['name'] ?? '';
            $tags = $body['tags'] ?? [];
            $dependencies = $body['dependencies'] ?? [];

            if (empty($groupName)) {
                throw new \InvalidArgumentException('分組名稱不能為空');
            }

            if (!$this->groupManager) {
                throw new \RuntimeException('分組管理器不可用');
            }

            if ($this->groupManager->hasGroup($groupName)) {
                throw new \InvalidArgumentException('分組已存在');
            }

            // 建立分組
            $group = $this->groupManager->group($groupName, $tags);

            // 設定依賴關係
            if (!empty($dependencies)) {
                foreach ($dependencies as $dependency) {
                    if (is_array($dependency) && isset($dependency['parent'], $dependency['children'])) {
                        $this->groupManager->setDependencies($dependency['parent'], $dependency['children']);
                    }
                }
            }

            $this->logger?->info('快取分組已建立', [
                'group_name' => $groupName,
                'tags' => $tags,
                'dependencies' => $dependencies,
            ]);

            $responseData = [
                'success' => true,
                'data' => [
                    'group_name' => $groupName,
                    'tags' => $tags,
                    'dependencies' => $dependencies,
                ],
                'message' => "分組 '{$groupName}' 已成功建立",
                'timestamp' => time(),
            ];

        } catch (\Exception $e) {
            $this->logger?->error('建立快取分組失敗', [
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '建立分組失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        $response->getBody()->write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 取得所有分組列表
     *
     * GET /admin/cache/groups
     */
    public function listGroups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            if (!$this->groupManager) {
                throw new \RuntimeException('分組管理器不可用');
            }

            $groups = $this->groupManager->getAllGroups();
            $statistics = $this->groupManager->getGroupStatistics();

            $responseData = [
                'success' => true,
                'data' => [
                    'groups' => $groups,
                    'statistics' => $statistics,
                ],
                'timestamp' => time(),
            ];

        } catch (\Exception $e) {
            $this->logger?->error('取得分組列表失敗', [
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '取得分組列表失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        $response->getBody()->write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 清空特定分組
     *
     * DELETE /admin/cache/groups/{group}
     */
    public function flushGroup(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $groupName = urldecode($args['group'] ?? '');
            $cascade = filter_var($request->getQueryParams()['cascade'] ?? 'true', FILTER_VALIDATE_BOOLEAN);

            if (empty($groupName)) {
                throw new \InvalidArgumentException('分組名稱不能為空');
            }

            if (!$this->groupManager) {
                throw new \RuntimeException('分組管理器不可用');
            }

            if (!$this->groupManager->hasGroup($groupName)) {
                throw new \InvalidArgumentException('分組不存在');
            }

            $clearedCount = $this->groupManager->flushGroup($groupName, $cascade);

            $this->logger?->info('快取分組已清空', [
                'group_name' => $groupName,
                'cascade' => $cascade,
                'cleared_count' => $clearedCount,
            ]);

            $responseData = [
                'success' => true,
                'data' => [
                    'group_name' => $groupName,
                    'cleared_count' => $clearedCount,
                    'cascade' => $cascade,
                ],
                'message' => "分組 '{$groupName}' 的 {$clearedCount} 個項目已清空",
                'timestamp' => time(),
            ];

        } catch (\Exception $e) {
            $this->logger?->error('清空快取分組失敗', [
                'group' => $args['group'] ?? '',
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '清空分組失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        $response->getBody()->write(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 判斷標籤類型
     */
    private function getTagType(string $tag): string
    {
        if (str_starts_with($tag, 'user_')) {
            return 'user';
        } elseif (str_starts_with($tag, 'module_')) {
            return 'module';
        } elseif (str_starts_with($tag, 'time_') || str_starts_with($tag, 'temporal_')) {
            return 'temporal';
        } elseif (str_starts_with($tag, 'group_')) {
            return 'group';
        }
        
        return 'custom';
    }

    /**
     * 取得標籤建立時間（模擬）
     */
    private function getTagCreatedAt(string $tag): string
    {
        // 在實際應用中，這裡應該從標籤倉庫取得真實的建立時間
        // 目前使用模擬值
        return date('Y-m-d H:i:s');
    }
}