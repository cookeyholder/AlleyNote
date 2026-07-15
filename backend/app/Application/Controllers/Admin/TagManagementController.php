<?php

declare(strict_types=1);

namespace App\Application\Controllers\Admin;

use App\Application\Controllers\BaseController;
use App\Domains\Security\Services\Headers\SecurityHeaderService;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\Services\CacheGroupManager;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class TagManagementController extends BaseController
{
    public function __construct(
        private CacheManagerInterface $cacheManager,
        private ?CacheGroupManager $groupManager = null,
        private ?LoggerInterface $logger = null,
        private ?SecurityHeaderService $headerService = null,
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
                                    'name'        => $tag,
                                    'driver'      => $driverName,
                                    'key_count'   => 0,
                                    'sample_keys' => [],
                                    'type'        => $this->getTagType($tag),
                                    'created_at'  => $this->getTagCreatedAt($tag),
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
                'data'    => [
                    'tags'       => $paginatedTags,
                    'pagination' => [
                        'page'  => $page,
                        'limit' => $limit,
                        'total' => $totalTags,
                        'pages' => ceil($totalTags / $limit),
                    ],
                    'search' => $search,
                ],
                'timestamp' => time(),
            ];
        } catch (Throwable $e) {
            $this->logger?->error('取得標籤列表失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $responseData = [
                'success' => false,
                'error'   => [
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
                            'name'       => $tagName,
                            'driver'     => $driverName,
                            'statistics' => $tagStats['tags'][$tagName],
                            'type'       => $this->getTagType($tagName),
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
                'success'   => true,
                'data'      => $tagInfo,
                'timestamp' => time(),
            ];
        } catch (Throwable $e) {
            $this->logger?->error('取得標籤詳細資訊失敗', [
                'tag'   => $tagName ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            $responseData = [
                'success' => false,
                'error'   => [
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
                'data'    => [
                    'message'          => '標籤已成功刪除',
                    'tag'              => $tagName,
                    'affected_drivers' => $affectedDrivers,
                ],
                'timestamp' => time(),
            ];
        } catch (Throwable $e) {
            $this->logger?->error('刪除標籤失敗', [
                'tag'   => $tagName ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            $responseData = [
                'success' => false,
                'error'   => [
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
                'data'    => [
                    'message'           => '標籤清理完成',
                    'total_cleaned'     => $totalCleaned,
                    'cleaned_by_driver' => $cleanedTags,
                ],
                'timestamp' => time(),
            ];
        } catch (Throwable $e) {
            $this->logger?->error('清理標籤失敗', [
                'error' => $e->getMessage(),
            ]);
            $responseData = [
                'success' => false,
                'error'   => [
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
                                'name'       => $groupName,
                                'tags'       => $groupData['tags'] ?? [],
                                'created_at' => $groupData['created_at'] ?? null,
                            ];
                        }
                    }
                }
            }
            $responseData = [
                'success' => true,
                'data'    => [
                    'groups' => $groups,
                    'total'  => count($groups),
                ],
                'timestamp' => time(),
            ];
        } catch (Throwable $e) {
            $this->logger?->error('取得標籤群組失敗', [
                'error' => $e->getMessage(),
            ]);
            $responseData = [
                'success' => false,
                'error'   => [
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
                'data'    => [
                    'message' => '標籤群組已成功刪除',
                    'group'   => $groupName,
                ],
                'timestamp' => time(),
            ];
        } catch (Throwable $e) {
            $this->logger?->error('刪除標籤群組失敗', [
                'group' => $groupName ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            $responseData = [
                'success' => false,
                'error'   => [
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
                    } catch (Throwable $e) {
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
                'data'    => [
                    'message'          => '標籤快取已成功清除',
                    'tag'              => $tagName,
                    'affected_drivers' => $affectedDrivers,
                ],
                'timestamp' => time(),
            ];
        } catch (Throwable $e) {
            $this->logger?->error('清除標籤失敗', [
                'tag'   => $tagName ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            $responseData = [
                'success' => false,
                'error'   => [
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
                        } catch (Throwable $e) {
                            $this->logger?->warning("無法從 {$driverName} 驅動清除標籤 {$tagName}", [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
                $results[] = [
                    'tag'              => $tagName,
                    'success'          => $flushed,
                    'affected_drivers' => $affectedDrivers,
                ];
                if ($flushed) {
                    $totalFlushed++;
                }
            }
            $responseData = [
                'success' => true,
                'data'    => [
                    'message'         => "成功清除 {$totalFlushed} 個標籤",
                    'results'         => $results,
                    'total_requested' => count($tags),
                    'total_flushed'   => $totalFlushed,
                ],
                'timestamp' => time(),
            ];
        } catch (Throwable $e) {
            $this->logger?->error('批次清除標籤失敗', [
                'error' => $e->getMessage(),
            ]);
            $responseData = [
                'success' => false,
                'error'   => [
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
                'drivers'             => [],
                'total_tags'          => 0,
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
                    } catch (Throwable $e) {
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
                'success'   => true,
                'data'      => $statistics,
                'timestamp' => time(),
            ];
        } catch (Throwable $e) {
            $this->logger?->error('取得標籤統計失敗', [
                'error' => $e->getMessage(),
            ]);
            $responseData = [
                'success' => false,
                'error'   => [
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
                'data'    => [
                    'message' => '標籤群組已成功建立',
                    'group'   => $groupName,
                    'tags'    => $validTags,
                ],
                'timestamp' => time(),
            ];
        } catch (Throwable $e) {
            $this->logger?->error('建立標籤群組失敗', [
                'group' => $groupName ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            $responseData = [
                'success' => false,
                'error'   => [
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
                'data'    => [
                    'message' => '標籤群組快取已成功清除',
                    'group'   => $groupName,
                ],
                'timestamp' => time(),
            ];
        } catch (Throwable $e) {
            $this->logger?->error('清除標籤群組失敗', [
                'group' => $groupName ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            $responseData = [
                'success' => false,
                'error'   => [
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

    /**
     * 渲染標籤管理主頁面.
     *
     * GET /admin/cache/tags
     */
    public function renderTagPage(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $nonce = '';
        if ($this->headerService !== null) {
            $nonce = $this->headerService->generateNonce();
        }

        $html = $this->getTagPageHtml();
        $html = str_replace('CSP_NONCE_PLACEHOLDER', $nonce, $html);

        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * 取得標籤管理頁面的 HTML 樣板.
     */
    private function getTagPageHtml(): string
    {
        return <<<'HTML'
            <!DOCTYPE html>
            <html lang="zh-TW">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>快取標籤管理 - AlleyNote</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
                <style>
                    .tag-badge {
                        font-size: 0.875rem;
                        margin: 2px;
                    }
                    .tag-type-user { background-color: #198754; }
                    .tag-type-module { background-color: #0d6efd; }
                    .tag-type-temporal { background-color: #ffc107; color: #000; }
                    .tag-type-group { background-color: #6f42c1; }
                    .tag-type-custom { background-color: #6c757d; }
                    .card-metric {
                        text-align: center;
                        padding: 1rem;
                    }
                    .metric-number {
                        font-size: 2rem;
                        font-weight: bold;
                        color: #0d6efd;
                    }
                    .metric-label {
                        font-size: 0.875rem;
                        color: #6c757d;
                        text-transform: uppercase;
                    }
                    .search-input {
                        max-width: 300px;
                    }
                    .table-actions {
                        white-space: nowrap;
                    }
                    .loading {
                        display: none;
                    }
                    .btn-group-sm .btn {
                        padding: 0.25rem 0.5rem;
                        font-size: 0.875rem;
                    }
                </style>
            </head>
            <body>
                <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
                    <div class="container-fluid">
                        <a class="navbar-brand" href="/admin">
                            <i class="bi bi-house-door"></i> AlleyNote 管理
                        </a>
                        <div class="navbar-nav">
                            <a class="nav-link" href="/admin/cache">快取監控</a>
                            <a class="nav-link active" href="/admin/cache/tags">標籤管理</a>
                        </div>
                    </div>
                </nav>

                <div class="container-fluid mt-4">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h1><i class="bi bi-tags"></i> 快取標籤管理</h1>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-primary" id="btnRefresh">
                                        <i class="bi bi-arrow-clockwise"></i> 重新整理
                                    </button>
                                    <button class="btn btn-danger" id="btnBulkDelete">
                                        <i class="bi bi-trash"></i> 批量清空
                                    </button>
                                </div>
                            </div>

                            <!-- 統計卡片 -->
                            <div class="row mb-4" id="statisticsCards">
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body card-metric">
                                            <div class="metric-number" id="totalTags">-</div>
                                            <div class="metric-label">總標籤數</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body card-metric">
                                            <div class="metric-number" id="totalKeys">-</div>
                                            <div class="metric-label">總快取鍵數</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body card-metric">
                                            <div class="metric-number" id="totalGroups">-</div>
                                            <div class="metric-label">分組數</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body card-metric">
                                            <div class="metric-number" id="memoryUsage">-</div>
                                            <div class="metric-label">記憶體使用</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 搜尋和分頁控制 -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <input type="text" class="form-control search-input" id="searchInput"
                                           placeholder="搜尋標籤...">
                                </div>
                                <div class="col-md-6 text-end">
                                    <nav aria-label="分頁">
                                        <ul class="pagination pagination-sm" id="pagination">
                                            <!-- 分頁項目將由 JavaScript 動態產生 -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>

                            <!-- 標籤列表 -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-tag"></i> 標籤列表
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="loadingIndicator" class="text-center py-5 loading">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">載入中...</span>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="tagsTable">
                                            <thead>
                                                <tr>
                                                    <th>標籤名稱</th>
                                                    <th>類型</th>
                                                    <th>快取鍵數</th>
                                                    <th>驅動</th>
                                                    <th>建立時間</th>
                                                    <th>操作</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tagsTableBody">
                                                <!-- 資料將由 JavaScript 動態產生 -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 標籤詳細資訊 Modal -->
                <div class="modal fade" id="tagDetailsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">標籤詳細資訊</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="tagDetailsContent">
                                <!-- 詳細資訊內容 -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 批量清空確認 Modal -->
                <div class="modal fade" id="bulkDeleteModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">批量清空標籤</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>選擇要清空的標籤：</p>
                                <div id="bulkDeleteTagsList">
                                    <!-- 標籤清單 -->
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                <button type="button" class="btn btn-danger" id="btnExecuteBulkDelete">確認清空</button>
                            </div>
                        </div>
                    </div>
                </div>

                <script nonce="CSP_NONCE_PLACEHOLDER" src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
                <script nonce="CSP_NONCE_PLACEHOLDER">
                    // 全域變數
                    let currentPage = 1;
                    let currentSearch = '';
                    let allTags = [];

                    // 初始化頁面
                    document.addEventListener('DOMContentLoaded', function() {
                        loadStatistics();
                        loadTags();

                        // 綁定事件監聽器 (符合 CSP 規範)
                        document.getElementById('btnRefresh').addEventListener('click', refreshData);
                        document.getElementById('btnBulkDelete').addEventListener('click', showBulkDeleteModal);
                        document.getElementById('btnExecuteBulkDelete').addEventListener('click', executeBulkDelete);
                        document.getElementById('searchInput').addEventListener('keyup', handleSearch);

                        // 列表事件委派
                        document.getElementById('tagsTableBody').addEventListener('click', function(e) {
                            const btnDetails = e.target.closest('.btn-tag-details');
                            if (btnDetails) {
                                const tagName = btnDetails.getAttribute('data-tag');
                                showTagDetails(tagName);
                                return;
                            }
                            const btnFlush = e.target.closest('.btn-flush-tag');
                            if (btnFlush) {
                                const tagName = btnFlush.getAttribute('data-tag');
                                flushTag(tagName);
                                return;
                            }
                        });

                        // 分頁事件委派
                        document.getElementById('pagination').addEventListener('click', function(e) {
                            const link = e.target.closest('.page-nav-link');
                            if (link) {
                                e.preventDefault();
                                const page = parseInt(link.getAttribute('data-page'), 10);
                                loadTags(page, currentSearch);
                            }
                        });
                    });

                    // 載入統計資訊
                    async function loadStatistics() {
                        try {
                            const response = await fetch('/api/admin/cache/tags/statistics');
                            const data = await response.json();

                            if (data.success) {
                                const stats = data.data;
                                document.getElementById('totalTags').textContent = stats.summary.total_tags || 0;
                                document.getElementById('totalKeys').textContent = stats.summary.total_keys || 0;
                                document.getElementById('totalGroups').textContent = stats.groups?.total_groups || 0;
                                document.getElementById('memoryUsage').textContent = formatBytes(stats.summary.total_memory_usage || 0);
                            }
                        } catch (error) {
                            console.error('載入統計失敗:', error);
                            showAlert('載入統計失敗', 'danger');
                        }
                    }

                    // 載入標籤列表
                    async function loadTags(page = 1, search = '') {
                        showLoading(true);
                        currentPage = page;
                        currentSearch = search;

                        try {
                            const params = new URLSearchParams({
                                page: page.toString(),
                                limit: '20',
                                search: search
                            });

                            const response = await fetch('/api/admin/cache/tags?' + params);
                            const data = await response.json();

                            if (data.success) {
                                allTags = data.data.tags;
                                renderTagsTable(data.data.tags);
                                renderPagination(data.data.pagination);
                            } else {
                                showAlert('載入標籤失敗: ' + data.error.message, 'danger');
                            }
                        } catch (error) {
                            console.error('載入標籤失敗:', error);
                            showAlert('載入標籤失敗', 'danger');
                        } finally {
                            showLoading(false);
                        }
                    }

                    // 渲染標籤表格
                    function renderTagsTable(tags) {
                        const tbody = document.getElementById('tagsTableBody');
                        tbody.innerHTML = '';

                        if (tags.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">沒有找到標籤</td></tr>';
                            return;
                        }

                        tags.forEach(tag => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>
                                    <code>${escapeHtml(tag.name)}</code>
                                    ${tag.sample_keys.length > 0 ?
                                        `<small class="text-muted d-block">範例: ${tag.sample_keys.slice(0, 2).map(k => k).join(', ')}</small>` :
                                        ''
                                    }
                                </td>
                                <td>
                                    <span class="badge tag-badge tag-type-${tag.type}">${getTypeLabel(tag.type)}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info">${tag.key_count}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">${tag.driver}</span>
                                </td>
                                <td>
                                    <small class="text-muted">${tag.created_at}</small>
                                </td>
                                <td class="table-actions">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary btn-tag-details" data-tag="${escapeHtml(tag.name)}">
                                            <i class="bi bi-info-circle"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-flush-tag" data-tag="${escapeHtml(tag.name)}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    }

                    // 渲染分頁
                    function renderPagination(pagination) {
                        const paginationEl = document.getElementById('pagination');
                        paginationEl.innerHTML = '';

                        if (pagination.pages <= 1) return;

                        // 上一頁
                        if (pagination.page > 1) {
                            paginationEl.innerHTML += `<li class="page-item"><a class="page-link page-nav-link" href="#" data-page="${pagination.page - 1}">上一頁</a></li>`;
                        }

                        // 頁碼
                        const start = Math.max(1, pagination.page - 2);
                        const end = Math.min(pagination.pages, pagination.page + 2);

                        for (let i = start; i <= end; i++) {
                            const active = i === pagination.page ? 'active' : '';
                            paginationEl.innerHTML += `<li class="page-item ${active}"><a class="page-link page-nav-link" href="#" data-page="${i}">${i}</a></li>`;
                        }

                        // 下一頁
                        if (pagination.page < pagination.pages) {
                            paginationEl.innerHTML += `<li class="page-item"><a class="page-link page-nav-link" href="#" data-page="${pagination.page + 1}">下一頁</a></li>`;
                        }
                    }

                    // 搜尋處理
                    function handleSearch() {
                        const search = document.getElementById('searchInput').value.trim();
                        if (search !== currentSearch) {
                            loadTags(1, search);
                        }
                    }

                    // 顯示標籤詳細資訊
                    async function showTagDetails(tagName) {
                        try {
                            const response = await fetch('/api/admin/cache/tags/' + encodeURIComponent(tagName));
                            const data = await response.json();

                            if (data.success) {
                                const tag = data.data;
                                const content = document.getElementById('tagDetailsContent');
                                content.innerHTML = `
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>基本資訊</h6>
                                            <table class="table table-sm">
                                                <tr><th>標籤名稱</th><td><code>${escapeHtml(tag.name)}</code></td></tr>
                                                <tr><th>類型</th><td><span class="badge tag-type-${tag.type}">${getTypeLabel(tag.type)}</span></td></tr>
                                                <tr><th>快取鍵數</th><td>${tag.key_count}</td></tr>
                                                <tr><th>總記憶體使用</th><td>${formatBytes(tag.total_memory_usage)}</td></tr>
                                                <tr><th>驅動</th><td>${tag.driver}</td></tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>快取鍵列表</h6>
                                            <div style="max-height: 300px; overflow-y: auto;">
                                                ${tag.keys.length > 0 ?
                                                    tag.keys.map(k => `<div class="mb-1"><code class="small">${escapeHtml(k.key)}</code> <span class="badge bg-light text-dark">${formatBytes(k.value_size)}</span></div>`).join('') :
                                                    '<p class="text-muted">沒有快取鍵</p>'
                                                }
                                            </div>
                                        </div>
                                    </div>
                                `;

                                const modal = new bootstrap.Modal(document.getElementById('tagDetailsModal'));
                                modal.show();
                            } else {
                                showAlert('載入標籤詳細資訊失敗: ' + data.error.message, 'danger');
                            }
                        } catch (error) {
                            console.error('載入標籤詳細資訊失敗:', error);
                            showAlert('載入標籤詳細資訊失敗', 'danger');
                        }
                    }

                    // 清空單一標籤
                    async function flushTag(tagName) {
                        const confirmed = await confirmAction(
                            '確認清空標籤',
                            `確定要清空標籤 "${tagName}" 的所有快取嗎？此操作無法撤銷。`,
                            '確認清空'
                        );

                        if (!confirmed) {
                            return;
                        }

                        try {
                            const response = await fetch('/api/admin/cache/tags/' + encodeURIComponent(tagName), {
                                method: 'DELETE'
                            });
                            const data = await response.json();

                            if (data.success) {
                                showAlert(`標籤 "${tagName}" 已清空 ${data.data.cleared_count} 個項目`, 'success');
                                refreshData();
                            } else {
                                showAlert('清空標籤失敗: ' + data.error.message, 'danger');
                            }
                        } catch (error) {
                            console.error('清空標籤失敗:', error);
                            showAlert('清空標籤失敗', 'danger');
                        }
                    }

                    // 顯示批量刪除對話框
                    function showBulkDeleteModal() {
                        const listEl = document.getElementById('bulkDeleteTagsList');
                        listEl.innerHTML = '';

                        allTags.forEach(tag => {
                            listEl.innerHTML += `
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="${escapeHtml(tag.name)}" id="tag_${tag.name}">
                                    <label class="form-check-label" for="tag_${tag.name}">
                                        <code>${escapeHtml(tag.name)}</code>
                                        <span class="badge bg-info">${tag.key_count} 鍵</span>
                                    </label>
                                </div>
                            `;
                        });

                        const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
                        modal.show();
                    }

                    // 執行批量刪除
                    async function executeBulkDelete() {
                        const checkboxes = document.querySelectorAll('#bulkDeleteTagsList input[type="checkbox"]:checked');
                        const tags = Array.from(checkboxes).map(cb => cb.value);

                        if (tags.length === 0) {
                            showAlert('請選擇要清空的標籤', 'warning');
                            return;
                        }

                        const confirmed = await confirmAction(
                            '確認批量清空',
                            `確定要清空 ${tags.length} 個標籤的所有快取嗎？此操作無法撤銷。`,
                            '確認批量清空'
                        );

                        if (!confirmed) {
                            return;
                        }

                        try {
                            const response = await fetch('/api/admin/cache/tags', {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({ tags: tags })
                            });
                            const data = await response.json();

                            if (data.success) {
                                showAlert(`批量清空完成，共清空 ${data.data.total_cleared} 個項目`, 'success');
                                const modal = bootstrap.Modal.getInstance(document.getElementById('bulkDeleteModal'));
                                modal.hide();
                                refreshData();
                            } else {
                                showAlert('批量清空失敗: ' + data.error.message, 'danger');
                            }
                        } catch (error) {
                            console.error('批量清空失敗:', error);
                            showAlert('批量清空失敗', 'danger');
                        }
                    }

                    // 重新整理資料
                    function refreshData() {
                        loadStatistics();
                        loadTags(currentPage, currentSearch);
                    }

                    // 顯示/隱藏載入指示器
                    function showLoading(show) {
                        document.getElementById('loadingIndicator').style.display = show ? 'block' : 'none';
                        document.getElementById('tagsTable').style.display = show ? 'none' : 'table';
                    }

                    function confirmAction(title, message, confirmText = '確認') {
                        return new Promise((resolve) => {
                            const modalId = 'runtimeConfirmModal';
                            const existing = document.getElementById(modalId);
                            if (existing) {
                                existing.remove();
                            }

                            const element = document.createElement('div');
                            element.className = 'modal fade';
                            element.id = modalId;
                            element.tabIndex = -1;
                            element.setAttribute('aria-hidden', 'true');

                            const dialog = document.createElement('div');
                            dialog.className = 'modal-dialog modal-dialog-centered';

                            const content = document.createElement('div');
                            content.className = 'modal-content border-0 shadow-lg';

                            const header = document.createElement('div');
                            header.className = 'modal-header border-0 pb-0';

                            const titleElement = document.createElement('h5');
                            titleElement.className = 'modal-title';
                            titleElement.textContent = title;

                            const closeButton = document.createElement('button');
                            closeButton.type = 'button';
                            closeButton.className = 'btn-close';
                            closeButton.setAttribute('data-bs-dismiss', 'modal');
                            closeButton.setAttribute('aria-label', 'Close');

                            header.appendChild(titleElement);
                            header.appendChild(closeButton);

                            const body = document.createElement('div');
                            body.className = 'modal-body text-secondary pt-2';
                            body.textContent = message;

                            const footer = document.createElement('div');
                            footer.className = 'modal-footer border-0 pt-0';

                            const cancelButton = document.createElement('button');
                            cancelButton.type = 'button';
                            cancelButton.className = 'btn btn-outline-secondary';
                            cancelButton.setAttribute('data-action', 'cancel');
                            cancelButton.textContent = '取消';

                            const confirmButton = document.createElement('button');
                            confirmButton.type = 'button';
                            confirmButton.className = 'btn btn-danger';
                            confirmButton.setAttribute('data-action', 'confirm');
                            confirmButton.textContent = confirmText;

                            footer.appendChild(cancelButton);
                            footer.appendChild(confirmButton);

                            content.appendChild(header);
                            content.appendChild(body);
                            content.appendChild(footer);
                            dialog.appendChild(content);
                            element.appendChild(dialog);

                            document.body.appendChild(element);
                            const runtimeModal = new bootstrap.Modal(element);
                            let settled = false;

                            const finish = (result) => {
                                if (settled) {
                                    return;
                                }

                                settled = true;
                                resolve(result);
                                runtimeModal.hide();
                            };

                            element.querySelector('[data-action="cancel"]').addEventListener('click', () => finish(false));
                            element.querySelector('[data-action="confirm"]').addEventListener('click', () => finish(true));
                            element.addEventListener('hidden.bs.modal', () => {
                                if (!settled) {
                                    settled = true;
                                    resolve(false);
                                }
                                element.remove();
                            });

                            runtimeModal.show();
                        });
                    }

                    // 顯示警告訊息
                    function showAlert(message, type = 'info') {
                        const alertEl = document.createElement('div');
                        alertEl.className = `alert alert-${type} alert-dismissible fade show`;

                        const messageNode = document.createTextNode(message);
                        const closeButton = document.createElement('button');
                        closeButton.type = 'button';
                        closeButton.className = 'btn-close';
                        closeButton.setAttribute('data-bs-dismiss', 'alert');

                        alertEl.appendChild(messageNode);
                        alertEl.appendChild(closeButton);

                        document.body.insertBefore(alertEl, document.body.firstChild);

                        setTimeout(() => {
                            alertEl.remove();
                        }, 5000);
                    }

                    // 工具函式
                    // 用於 escape 標籤名稱，防範 XSS
                    function escapeHtml(text) {
                        const div = document.createElement('div');
                        div.textContent = text;
                        return div.innerHTML;
                    }

                    function formatBytes(bytes) {
                        if (bytes === 0) return '0 B';
                        const k = 1024;
                        const sizes = ['B', 'KB', 'MB', 'GB'];
                        const i = Math.floor(Math.log(bytes) / Math.log(k));
                        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                    }

                    function getTypeLabel(type) {
                        const labels = {
                            user: '使用者',
                            module: '模組',
                            temporal: '時間',
                            group: '分組',
                            custom: '自訂'
                        };
                        return labels[type] || type;
                    }
                </script>
            </body>
            </html>
            HTML;
    }
}
