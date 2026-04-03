<?php

declare(strict_types=1);

namespace App;

use App\Infrastructure\Http\Response;
use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\Providers\RoutingServiceProvider;
use App\Infrastructure\Routing\RouteDispatcher;
use App\Shared\Config\EnvironmentConfig;
use App\Shared\Monitoring\Contracts\ErrorTrackerInterface;
use App\Shared\Monitoring\Providers\MonitoringServiceProvider;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

class Application
{
    private ContainerInterface $container;

    private RouterInterface $router;

    private RouteDispatcher $routeDispatcher;

    public function __construct()
    {
        $this->initializeContainer();
        $this->initializeEnvironmentConfig();
        $this->initializeMonitoring();
        $this->initializeRouter();
        $this->initializeRouteDispatcher();
        $this->loadRoutes();
    }

    /**
     * 執行應用程式.
     */
    public function run(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->handleRequest($request);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * 初始化監控服務。
     */
    private function initializeMonitoring(): void
    {
        MonitoringServiceProvider::initialize($this->container);
        MonitoringServiceProvider::setupPerformanceBenchmarks($this->container);
        MonitoringServiceProvider::setupHealthCheckSchedule($this->container);
    }

    /**
     * 初始化環境配置.
     */
    private function initializeEnvironmentConfig(): void
    {
        // 從容器獲取並驗證環境配置
        $config = $this->container->get(EnvironmentConfig::class);
        if (!$config instanceof EnvironmentConfig) {
            throw new RuntimeException('無法獲取有效的環境配置');
        }
        // 驗證配置的完整性
        $errors = $config->validate();
        if (!empty($errors)) {
            $errorMessage = "環境配置錯誤:\n" . implode("\n", $errors);

            throw new RuntimeException($errorMessage);
        }
    }

    /**
     * 初始化 DI 容器.
     */
    private function initializeContainer(): void
    {
        $builder = new ContainerBuilder();
        // 載入容器配置檔案
        $containerConfig = require __DIR__ . '/../config/container.php';
        $builder->addDefinitions($containerConfig);
        $this->container = $builder->build();
    }

    /**
     * 初始化路由器.
     */
    private function initializeRouter(): void
    {
        $this->router = $this->container->get(RouterInterface::class);
    }

    /**
     * 初始化路由分派器.
     */
    private function initializeRouteDispatcher(): void
    {
        $this->routeDispatcher = $this->container->get(RouteDispatcher::class);
    }

    /**
     * 載入路由配置.
     */
    private function loadRoutes(): void
    {
        // 使用路由服務提供者載入路由
        RoutingServiceProvider::loadRoutes($this->container);
    }

    /**
     * 處理 HTTP 請求
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->routeDispatcher->dispatch($request);
    }

    /**
     * 處理例外狀況.
     */
    private function handleException(Throwable $e): ResponseInterface
    {
        // 記錄錯誤到監控系統
        try {
            $errorTracker = $this->container->get(ErrorTrackerInterface::class);
            if ($errorTracker instanceof ErrorTrackerInterface) {
                $errorTracker->recordCriticalError($e, [
                    'context' => 'application_exception',
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                    'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
                ]);
            }
        } catch (Throwable $monitoringException) {
            // 如果監控系統本身出錯，記錄到錯誤日誌
            app_log('error', 'Monitoring system error', ['exception' => $monitoringException->getMessage()]);
        }
        // 建立錯誤回應內容
        $appEnv = getenv('APP_ENV') ?: 'production';
        $errorData = [
            'status' => 'error',
            'error' => 'Internal Server Error',
            'message' => $appEnv !== 'production' ? $e->getMessage() : '伺服器內部錯誤，請稍後再試',
        ];
        if ($appEnv !== 'production') {
            $errorData['code'] = $e->getCode();
        }
        $json = json_encode($errorData, JSON_UNESCAPED_UNICODE) ?: '{"error": "Internal Server Error"}';

        return new Response(
            500,
            ['Content-Type' => 'application/json'],
            $json,
        );
    }

    /**
     * 獲取 DI 容器實例.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * 獲取路由器實例.
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }
}
