<?php

declare(strict_types=1);

namespace App\Application\Controllers\Admin;

use App\Application\Controllers\BaseController;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\Services\CacheGroupManager;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * 標籤管理控制器.
 *
 * 提供快取標籤管理的 REST API 端點
 */
class TagManagementController extends BaseController
{
    public function __construct(
        private CacheManagerInterface $cacheManager,
        private ?CacheGroupManager $groupManager = null,
        private ?LoggerInterface $logger = null,
    ) {
        // 不調用 parent::__construct()，因為 BaseController 沒有構造函式
    }

    /**
     * 取得所有標籤列表.
     *
     * GET /admin/cache/tags
     */
    public function listTags(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $queryParams = $request->getQueryParams();
            $page = max(1, isset($queryParams['page']) && is_numeric($queryParams['page']) ? (int) $queryParams['page'] : 1);
            $limit = min(100, max(10, isset($queryParams['limit']) && is_numeric($queryParams['limit']) ? (int) $queryParams['limit'] : 20));
            $search = isset($queryParams['search']) && is_string($queryParams['search']) ? $queryParams['search'] : '';

            $tags = [];
            $totalTags = 0;

            // 從支援標籤的驅動取得標籤資訊
            foreach (['redis', 'memory'] as $driverName) {
                $driver = $this->cacheManager->getDriver($driverName);
                if ($driver && $driver instanceof TaggedCacheInterface) {
                    $driverTags = $driver->getAllTags();
                    $tagStats = $driver->getTagStatistics();

                    if ($driverTags) {
                        foreach ($driverTags as $tag) {
                            if (empty($search) || stripos($tag, $search) !== false) {
                                $tagData = [
                                    'name' => $tag,
                                    'driver' => $driverName,
                                    'key_count' => 0,
                                    'sample_keys' => [],
                                    'type' => $this->getTagType($tag),
                                    'created_at' => $this->getTagCreatedAt($tag),
                                ];

                                // 安全地取得統計資料
                                if ($tagStats && isset($tagStats['tags'][$tag]) && is_array($tagStats['tags'][$tag])) {
                                    $tagData['key_count'] = $tagStats['tags'][$tag]['key_count'] ?? 0;
                                    $tagData['sample_keys'] = $tagStats['tags'][$tag]['sample_keys'] ?? [];
                                }

                                $tags[] = $tagData;
                            }
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
        } catch (Exception $e) {
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

        return $this->json($response, $responseData);
    }

    /**
     * 取得特定標籤詳細資訊.
     *
     * GET /admin/cache/tags/{tag}
     */
    public function getTag(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $tagName = isset($args['tag']) && is_string($args['tag']) ? urldecode($args['tag']) : '';

            if (empty($tagName)) {
                throw new InvalidArgumentException('標籤名稱不能為空');
            }

            $tagInfo = null;

            // 從支援標籤的驅動取得標籤詳細資訊
            foreach (['redis', 'memory'] as $driverName) {
                $driver = $this->cacheManager->getDriver($driverName);
                if ($driver && $driver instanceof TaggedCacheInterface) {
                    $tagStats = $driver->getTagStatistics();

                    if ($tagStats && isset($tagStats['tags'][$tagName])) {
                        $tagInfo = [
                            'name' => $tagName,
                            'driver' => $driverName,
                            'statistics' => $tagStats['tags'][$tagName],
                            'type' => $this->getTagType($tagName),
                            'created_at' => $this->getTagCreatedAt($tagName),
                        ];
                        break;
                    }
                }
            }

            if (!$tagInfo) {
                throw new RuntimeException('標籤不存在');
            }

            $responseData = [
                'success' => true,
                'data' => $tagInfo,
                'timestamp' => time(),
            ];
        } catch (Exception $e) {
            $this->logger?->error('取得標籤詳細資訊失敗', [
                'tag' => $tagName ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '取得標籤詳細資訊失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        return $this->json($response, $responseData);
    }

    /**
     * 刪除標籤.
     *
     * DELETE /admin/cache/tags/{tag}
     */
    public function deleteTag(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $tagName = isset($args['tag']) && is_string($args['tag']) ? urldecode($args['tag']) : '';

            if (empty($tagName)) {
                throw new InvalidArgumentException('標籤名稱不能為空');
            }

            $deleted = false;
            $affectedDrivers = [];

            // 從所有支援標籤的驅動刪除標籤
            foreach (['redis', 'memory'] as $driverName) {
                $driver = $this->cacheManager->getDriver($driverName);
                if ($driver && $driver instanceof TaggedCacheInterface) {
                    // 使用 flushByTags 來刪除標籤下的所有快取
                    $deletedCount = $driver->flushByTags([$tagName]);
                    if ($deletedCount > 0) {
                        $deleted = true;
                        $affectedDrivers[] = $driverName;
                    }
                }
            }

            if (!$deleted) {
                throw new RuntimeException('標籤刪除失敗或標籤不存在');
            }

            $responseData = [
                'success' => true,
                'data' => [
                    'message' => '標籤已成功刪除',
                    'tag' => $tagName,
                    'affected_drivers' => $affectedDrivers,
                ],
                'timestamp' => time(),
            ];
        } catch (Exception $e) {
            $this->logger?->error('刪除標籤失敗', [
                'tag' => $tagName ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '刪除標籤失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        return $this->json($response, $responseData);
    }

    /**
     * 清理過期標籤.
     *
     * POST /admin/cache/tags/cleanup
     */
    public function cleanupTags(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $cleanedTags = [];
            $totalCleaned = 0;

            // 從所有支援標籤的驅動清理過期標籤
            foreach (['redis', 'memory'] as $driverName) {
                $driver = $this->cacheManager->getDriver($driverName);
                if ($driver && $driver instanceof TaggedCacheInterface) {
                    // 使用 cleanupUnusedTags 來清理未使用的標籤
                    $cleaned = $driver->cleanupUnusedTags();
                    $cleanedTags[$driverName] = [$cleaned . ' unused tags'];
                    $totalCleaned += $cleaned;
                }
            }

            $responseData = [
                'success' => true,
                'data' => [
                    'message' => '標籤清理完成',
                    'total_cleaned' => $totalCleaned,
                    'cleaned_by_driver' => $cleanedTags,
                ],
                'timestamp' => time(),
            ];
        } catch (Exception $e) {
            $this->logger?->error('清理標籤失敗', [
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '清理標籤失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        return $this->json($response, $responseData);
    }

    /**
     * 取得標籤群組列表.
     *
     * GET /admin/cache/groups
     */
    public function listGroups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $groups = [];

            if ($this->groupManager) {
                $allGroups = $this->groupManager->getAllGroups();

                if ($allGroups) {
                    foreach ($allGroups as $groupName => $groupData) {
                        if (is_string($groupName) && is_array($groupData)) {
                            $groups[] = [
                                'name' => $groupName,
                                'tags' => $groupData['tags'] ?? [],
                                'created_at' => $groupData['created_at'] ?? null,
                            ];
                        }
                    }
                }
            }

            $responseData = [
                'success' => true,
                'data' => [
                    'groups' => $groups,
                    'total' => count($groups),
                ],
                'timestamp' => time(),
            ];
        } catch (Exception $e) {
            $this->logger?->error('取得標籤群組失敗', [
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '取得標籤群組失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        return $this->json($response, $responseData);
    }

    /**
     * 刪除標籤群組.
     *
     * DELETE /admin/cache/groups/{group}
     */
    public function deleteGroup(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $groupName = isset($args['group']) && is_string($args['group']) ? urldecode($args['group']) : '';

            if (empty($groupName)) {
                throw new InvalidArgumentException('群組名稱不能為空');
            }

            if (!$this->groupManager) {
                throw new RuntimeException('群組管理器未設定');
            }

            $this->groupManager->removeGroup($groupName);

            $responseData = [
                'success' => true,
                'data' => [
                    'message' => '標籤群組已成功刪除',
                    'group' => $groupName,
                ],
                'timestamp' => time(),
            ];
        } catch (Exception $e) {
            $this->logger?->error('刪除標籤群組失敗', [
                'group' => $groupName ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '刪除標籤群組失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        return $this->json($response, $responseData);
    }

    /**
     * 取得標籤類型.
     */
    private function getTagType(string $tagName): string
    {
        if (str_starts_with($tagName, 'user:')) {
            return 'user';
        }
        if (str_starts_with($tagName, 'post:')) {
            return 'post';
        }
        if (str_starts_with($tagName, 'category:')) {
            return 'category';
        }

        return 'other';
    }

    /**
     * 清除單一標籤.
     *
     * POST /admin/cache/tags/{tag}/flush
     */
    public function flushTag(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $tagName = isset($args['tag']) && is_string($args['tag']) ? urldecode($args['tag']) : '';

            if (empty($tagName)) {
                throw new InvalidArgumentException('標籤名稱不能為空');
            }

            $flushed = false;
            $affectedDrivers = [];

            // 從支援標籤的驅動清除標籤
            foreach (['redis', 'memory'] as $driverName) {
                $driver = $this->cacheManager->getDriver($driverName);
                if ($driver && $driver instanceof TaggedCacheInterface) {
                    try {
                        $flushedCount = $driver->flushByTags([$tagName]);
                        if ($flushedCount > 0) {
                            $flushed = true;
                            $affectedDrivers[] = $driverName;
                        }
                    } catch (Exception $e) {
                        $this->logger?->warning("無法從 {$driverName} 驅動清除標籤 {$tagName}", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            if (!$flushed) {
                throw new RuntimeException('沒有找到可以清除的標籤');
            }

            $responseData = [
                'success' => true,
                'data' => [
                    'message' => '標籤快取已成功清除',
                    'tag' => $tagName,
                    'affected_drivers' => $affectedDrivers,
                ],
                'timestamp' => time(),
            ];
        } catch (Exception $e) {
            $this->logger?->error('清除標籤失敗', [
                'tag' => $tagName ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '清除標籤失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        return $this->json($response, $responseData);
    }

    /**
     * 清除多個標籤.
     *
     * POST /admin/cache/tags/flush
     */
    public function flushTags(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = json_decode((string) $request->getBody(), true);
            if (!is_array($body)) {
                throw new InvalidArgumentException('請求主體必須是有效的 JSON 格式');
            }
            $tags = $body['tags'] ?? [];

            if (!is_array($tags) || empty($tags)) {
                throw new InvalidArgumentException('必須提供要清除的標籤列表');
            }

            $results = [];
            $totalFlushed = 0;

            foreach ($tags as $tagName) {
                if (!is_string($tagName) || empty($tagName)) {
                    continue;
                }

                $flushed = false;
                $affectedDrivers = [];

                foreach (['redis', 'memory'] as $driverName) {
                    $driver = $this->cacheManager->getDriver($driverName);
                    if ($driver && $driver instanceof TaggedCacheInterface) {
                        try {
                            $flushedCount = $driver->flushByTags([$tagName]);
                            if ($flushedCount > 0) {
                                $flushed = true;
                                $affectedDrivers[] = $driverName;
                            }
                        } catch (Exception $e) {
                            $this->logger?->warning("無法從 {$driverName} 驅動清除標籤 {$tagName}", [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                $results[] = [
                    'tag' => $tagName,
                    'success' => $flushed,
                    'affected_drivers' => $affectedDrivers,
                ];

                if ($flushed) {
                    $totalFlushed++;
                }
            }

            $responseData = [
                'success' => true,
                'data' => [
                    'message' => "成功清除 {$totalFlushed} 個標籤",
                    'results' => $results,
                    'total_requested' => count($tags),
                    'total_flushed' => $totalFlushed,
                ],
                'timestamp' => time(),
            ];
        } catch (Exception $e) {
            $this->logger?->error('批次清除標籤失敗', [
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '批次清除標籤失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        return $this->json($response, $responseData);
    }

    /**
     * 取得標籤統計資訊.
     *
     * GET /admin/cache/tags/statistics
     */
    public function getTagStatistics(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $statistics = [
                'drivers' => [],
                'total_tags' => 0,
                'total_cache_entries' => 0,
            ];

            // 從支援標籤的驅動收集統計資訊
            foreach (['redis', 'memory'] as $driverName) {
                $driver = $this->cacheManager->getDriver($driverName);
                if ($driver && $driver instanceof TaggedCacheInterface) {
                    try {
                        $tagStats = $driver->getTagStatistics();
                        if (!empty($tagStats)) {
                            $statistics['drivers'][$driverName] = $tagStats;
                            $tagsArray = $tagStats['tags'] ?? [];
                            if (is_array($tagsArray)) {
                                $statistics['total_tags'] += count($tagsArray);
                                $keyCounts = array_column($tagsArray, 'key_count');
                                $statistics['total_cache_entries'] += array_sum($keyCounts);
                            }
                        }
                    } catch (Exception $e) {
                        $this->logger?->warning("無法從 {$driverName} 驅動取得統計資訊", [
                            'error' => $e->getMessage(),
                        ]);
                        $statistics['drivers'][$driverName] = [
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            $responseData = [
                'success' => true,
                'data' => $statistics,
                'timestamp' => time(),
            ];
        } catch (Exception $e) {
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

        return $this->json($response, $responseData);
    }

    /**
     * 建立標籤群組.
     *
     * POST /admin/cache/groups
     */
    public function createGroup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = json_decode((string) $request->getBody(), true);
            if (!is_array($body)) {
                throw new InvalidArgumentException('請求內容必須是有效的 JSON');
            }
            
            $groupName = $body['name'] ?? '';
            $tags = $body['tags'] ?? [];

            if (empty($groupName) || !is_string($groupName)) {
                throw new InvalidArgumentException('群組名稱不能為空');
            }

            if (!is_array($tags)) {
                throw new InvalidArgumentException('標籤必須是陣列');
            }

            // 確保所有標籤都是字串
            $validTags = array_filter($tags, 'is_string');
            if (count($validTags) !== count($tags)) {
                throw new InvalidArgumentException('所有標籤必須是字串');
            }

            if (!$this->groupManager) {
                throw new RuntimeException('群組管理器未初始化');
            }

            // 建立群組
            $this->groupManager->group($groupName, $validTags);

            $responseData = [
                'success' => true,
                'data' => [
                    'message' => '標籤群組已成功建立',
                    'group' => $groupName,
                    'tags' => $validTags,
                ],
                'timestamp' => time(),
            ];
        } catch (Exception $e) {
            $this->logger?->error('建立標籤群組失敗', [
                'group' => $groupName ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '建立標籤群組失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        return $this->json($response, $responseData);
    }

    /**
     * 清除標籤群組.
     *
     * POST /admin/cache/groups/{group}/flush
     */
    public function flushGroup(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $groupName = isset($args['group']) && is_string($args['group']) ? urldecode($args['group']) : '';

            if (empty($groupName)) {
                throw new InvalidArgumentException('群組名稱不能為空');
            }

            if (!$this->groupManager) {
                throw new RuntimeException('群組管理器未初始化');
            }

            // 清除群組快取
            $this->groupManager->flushGroup($groupName);

            $responseData = [
                'success' => true,
                'data' => [
                    'message' => '標籤群組快取已成功清除',
                    'group' => $groupName,
                ],
                'timestamp' => time(),
            ];
        } catch (Exception $e) {
            $this->logger?->error('清除標籤群組失敗', [
                'group' => $groupName ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            $responseData = [
                'success' => false,
                'error' => [
                    'message' => '清除標籤群組失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ];
        }

        return $this->json($response, $responseData);
    }

    /**
     * 取得標籤建立時間.
     */
    private function getTagCreatedAt(string $tagName): null
    {
        // 這裡可以從資料庫或其他來源取得實際的建立時間
        // 目前返回 null
        return null;
    }
}
