<?php

declare(strict_types=1);

namespace App\Application\Controllers\Admin;

use App\Application\Controllers\BaseController;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Monitoring\Contracts\CacheMonitorInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CacheMonitorController extends BaseController
{
    public function __construct(
        private CacheMonitorInterface $cacheMonitor,
        private CacheManagerInterface $cacheManager
    ) {
        // 不調用 parent::__construct()，因為 BaseController 沒有構造函式
    }

    /**
     * 取得快取效能統計資料
     */
    public function getStats(Request $request, Response $response): Response
    {
        try {
            $stats = $this->cacheManager->getStats();
            $health = $this->cacheManager->getHealthStatus();

            $data = [
                'stats' => $stats,
                'health' => $health,
                'timestamp' => time(),
            ];

            return $this->json($response, $data);
        } catch (\Exception $e) {
            return $this->json($response, [
                'error' => '無法取得快取統計資料',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 取得詳細的快取指標
     */
    public function getMetrics(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $timeRange = is_string($queryParams['timeRange'] ?? '1h') ? $queryParams['timeRange'] ?? '1h' : '1h';

            $metrics = [
                'hitRate' => $this->cacheMonitor->getHitRateStats($timeRange),
                'performance' => $this->cacheMonitor->getDriverPerformanceComparison(),
                'capacity' => $this->cacheMonitor->getCacheCapacityStats(),
                'errors' => $this->cacheMonitor->getErrorStats($timeRange),
                'slowOperations' => $this->cacheMonitor->getSlowCacheOperations(10, 100),
            ];

            return $this->json($response, $metrics);
        } catch (\Exception $e) {
            return $this->json($response, [
                'error' => '無法取得快取指標',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 取得快取健康狀態
     */
    public function getHealth(Request $request, Response $response): Response
    {
        try {
            $healthOverview = $this->cacheMonitor->getHealthOverview();
            $driverHealth = $this->cacheManager->getHealthStatus();

            $healthData = [
                'overview' => $healthOverview,
                'drivers' => $driverHealth,
                'timestamp' => time(),
            ];

            return $this->json($response, $healthData);
        } catch (\Exception $e) {
            return $this->json($response, [
                'error' => '無法取得健康狀態',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 重置統計資料
     */
    public function resetStats(Request $request, Response $response): Response
    {
        try {
            // 清理舊的監控資料
            $cleaned = $this->cacheMonitor->cleanup(0);

            return $this->json($response, [
                'message' => '統計資料已重置',
                'cleanedRecords' => $cleaned,
            ]);
        } catch (\Exception $e) {
            return $this->json($response, [
                'error' => '無法重置統計資料',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 清空快取
     */
    public function flushCache(Request $request, Response $response): Response
    {
        try {
            $success = $this->cacheManager->clear();

            if ($success) {
                return $this->json($response, [
                    'message' => '快取已清空',
                ]);
            } else {
                return $this->json($response, [
                    'error' => '清空快取失敗',
                ], 500);
            }
        } catch (\Exception $e) {
            return $this->json($response, [
                'error' => '無法清空快取',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 取得驅動資訊
     */
    public function getDriverInfo(Request $request, Response $response): Response
    {
        try {
            $drivers = $this->cacheManager->getDrivers();
            $driverInfo = [];

            foreach ($drivers as $name => $driver) {
                if (is_object($driver) && method_exists($driver, 'isAvailable')) {
                    $driverInfo[] = [
                        'name' => $name,
                        'class' => get_class($driver),
                        'available' => $driver->isAvailable(),
                    ];
                }
            }

            return $this->json($response, [
                'drivers' => $driverInfo,
            ]);
        } catch (\Exception $e) {
            return $this->json($response, [
                'error' => '無法取得驅動資訊',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
