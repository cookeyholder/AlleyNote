<?php

declare(strict_types=1);

namespace App\Application\Controllers\Admin;

use App\Application\Controllers\BaseController;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class TagManagementController extends BaseController
{
    public function __construct(
        private CacheManagerInterface $cacheManager,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * 列出所有標籤.
     *
     * GET /admin/cache/tags
     */
    public function listTags(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $queryParams = $request->getQueryParams();
            $page = max(1, is_numeric($queryParams['page']) ? (int) $queryParams['page'] : 1);
            $limit = min(100, max(10, is_numeric($queryParams['limit']) ? (int) $queryParams['limit'] : 20));
            $search = is_string($queryParams['search']) ? $queryParams['search'] : '';

            $tags = [];
            $drivers = $this->cacheManager->getDrivers();

            foreach ($drivers as $driverName => $driver) {
                if ($driver instanceof TaggedCacheInterface) {
                    try {
                        $driverTags = $driver->getAllTags();
                        foreach ($driverTags as $tag) {
                            $tags[] = [
                                'name' => $tag,
                                'driver' => $driverName,
                            ];
                        }
                    } catch (Exception $e) {
                        // 記錄錯誤但繼續處理其他驅動
                        if ($this->logger) {
                            $this->logger->warning("獲取標籤失敗: {$driverName}", ['error' => $e->getMessage()]);
                        }
                    }
                }
            }

            // 搜尋過濾
            if (!empty($search)) {
                $tags = array_filter($tags, function ($tag) use ($search) {
                    $tagName = $tag['name'] ?? '';

                    return is_string($tagName) && stripos($tagName, $search) !== false;
                });
            }

            // 分頁
            $total = count($tags);
            $offset = ($page - 1) * $limit;
            $tags = array_slice($tags, $offset, $limit);

            $responseData = [
                'success' => true,
                'data' => [
                    'tags' => $tags,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => (int) ceil($total / $limit),
                    ],
                ],
                'timestamp' => time(),
            ];

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            return $this->json($response, [
                'success' => false,
                'error' => '取得標籤列表失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 列出標籤群組.
     *
     * GET /admin/cache/groups
     */
    public function listGroups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $groups = [];

            // 簡化的群組邏輯 - 實際應該從 GroupManager 獲取
            $responseData = [
                'success' => true,
                'data' => [
                    'groups' => $groups,
                    'total' => count($groups),
                ],
                'timestamp' => time(),
            ];

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            return $this->json($response, [
                'success' => false,
                'error' => '取得群組列表失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 清除單個標籤.
     *
     * POST /admin/cache/tags/{tag}/flush
     *
     * @param array<string, mixed> $args
     */
    public function flushTag(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $tagName = is_string($args['tag']) ? urldecode($args['tag']) : '';

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
                        if ($this->logger) {
                            $this->logger->warning("清除標籤失敗: {$driverName}", ['error' => $e->getMessage()]);
                        }
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

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            return $this->json($response, [
                'success' => false,
                'error' => '清除標籤失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 清除多個標籤.
     *
     * POST /admin/cache/tags/flush
     */
    public function flushTags(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $bodyString = (string) $request->getBody();
            $body = json_decode($bodyString, true);

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
                            if ($this->logger) {
                                $this->logger->warning("清除標籤失敗: {$driverName}", ['error' => $e->getMessage()]);
                            }
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

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            return $this->json($response, [
                'success' => false,
                'error' => '批量清除標籤失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 清空所有快取.
     *
     * POST /admin/cache/flush
     */
    public function flushAllCache(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $success = $this->cacheManager->clear();

            if ($success) {
                $responseData = [
                    'success' => true,
                    'data' => ['message' => '所有快取已成功清空'],
                    'timestamp' => time(),
                ];
            } else {
                throw new RuntimeException('清空快取失敗');
            }

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            return $this->json($response, [
                'success' => false,
                'error' => '清空所有快取失敗',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
