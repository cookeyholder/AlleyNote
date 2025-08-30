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
        parent::__construct();
    }

    /**
     * 取得快取效能統計資料
     */
    public function getStats(Request $request, Response $response): Response
    {
        try {
            $data = [
                'metrics' => $this->cacheMonitor->getMetrics(),
                'health' => $this->cacheMonitor->getHealth(),
                'uptime' => time() - $this->cacheMonitor->getStartTime(),
                'timestamp' => time(),
            ];

            return $this->json($response, $data);
        } catch (\Exception $e) {
            $this->logger->error('取得快取統計資料失敗', [
                'error' => $e->getMessage(),
            ]);

            return $this->json($response, [
                'error' => '無法取得快取統計資料',
            ], 500);
        }
    }

    /**
     * 取得詳細的快取指標
     */
    public function getMetrics(Request $request, Response $response): Response
    {
        try {
            $metrics = $this->cacheMonitor->getMetrics();
            
            // 計算額外的統計資料
            $totalOperations = array_sum([
                $metrics['total_hits'] ?? 0,
                $metrics['total_misses'] ?? 0,
                $metrics['total_sets'] ?? 0,
                $metrics['total_deletes'] ?? 0,
            ]);
            
            $hitRate = $totalOperations > 0 
                ? round(($metrics['total_hits'] / $totalOperations) * 100, 2)
                : 0;
            
            $data = [
                'basic_metrics' => $metrics,
                'calculated_metrics' => [
                    'total_operations' => $totalOperations,
                    'hit_rate_percentage' => $hitRate,
                    'miss_rate_percentage' => round(100 - $hitRate, 2),
                ],
                'driver_performance' => $this->cacheMonitor->getDriverPerformance(),
                'timestamp' => time(),
            ];

            return $this->json($response, $data);
        } catch (\Exception $e) {
            $this->logger->error('取得快取指標失敗', [
                'error' => $e->getMessage(),
            ]);

            return $this->json($response, [
                'error' => '無法取得快取指標',
            ], 500);
        }
    }

    /**
     * 取得快取健康狀況
     */
    public function getHealth(Request $request, Response $response): Response
    {
        try {
            $health = $this->cacheMonitor->getHealth();
            
            // 判斷整體健康狀況
            $overallHealth = 'healthy';
            foreach ($health as $driver => $status) {
                if ($status !== 'healthy') {
                    $overallHealth = 'degraded';
                    break;
                }
            }
            
            $data = [
                'overall_status' => $overallHealth,
                'driver_status' => $health,
                'checked_at' => time(),
            ];

            // 根據健康狀況設定適當的 HTTP 狀態碼
            $statusCode = $overallHealth === 'healthy' ? 200 : 503;

            return $this->json($response, $data, $statusCode);
        } catch (\Exception $e) {
            $this->logger->error('取得快取健康狀況失敗', [
                'error' => $e->getMessage(),
            ]);

            return $this->json($response, [
                'error' => '無法取得快取健康狀況',
            ], 500);
        }
    }

    /**
     * 重設統計資料
     */
    public function resetStats(Request $request, Response $response): Response
    {
        try {
            $this->cacheMonitor->reset();
            
            $this->logger->info('快取統計資料已重設');

            return $this->json($response, [
                'message' => '快取統計資料已重設',
                'reset_at' => time(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('重設快取統計資料失敗', [
                'error' => $e->getMessage(),
            ]);

            return $this->json($response, [
                'error' => '無法重設快取統計資料',
            ], 500);
        }
    }

    /**
     * 清空所有快取
     */
    public function flushCache(Request $request, Response $response): Response
    {
        try {
            $result = $this->cacheManager->flush();
            
            if ($result) {
                $this->logger->info('所有快取已透過 API 清空');
                
                return $this->json($response, [
                    'message' => '所有快取已成功清空',
                    'flushed_at' => time(),
                ]);
            } else {
                return $this->json($response, [
                    'error' => '快取清空失敗',
                ], 500);
            }
        } catch (\Exception $e) {
            $this->logger->error('透過 API 清空快取失敗', [
                'error' => $e->getMessage(),
            ]);

            return $this->json($response, [
                'error' => '無法清空快取',
            ], 500);
        }
    }

    /**
     * 取得快取驅動資訊
     */
    public function getDriverInfo(Request $request, Response $response): Response
    {
        try {
            $drivers = $this->cacheManager->getDrivers();
            $driverInfo = [];
            
            foreach ($drivers as $name => $driver) {
                $driverInfo[$name] = [
                    'class' => get_class($driver),
                    'available' => $this->cacheManager->isDriverAvailable($name),
                ];
            }

            $data = [
                'drivers' => $driverInfo,
                'default_driver' => $this->cacheManager->getDefaultDriver(),
                'available_drivers' => array_keys(array_filter(
                    $driverInfo, 
                    fn($info) => $info['available']
                )),
            ];

            return $this->json($response, $data);
        } catch (\Exception $e) {
            $this->logger->error('取得快取驅動資訊失敗', [
                'error' => $e->getMessage(),
            ]);

            return $this->json($response, [
                'error' => '無法取得快取驅動資訊',
            ], 500);
        }
    }
}