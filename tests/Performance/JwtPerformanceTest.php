<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use App\Domains\Auth\ValueObjects\JwtPayload;
use App\Shared\Config\JwtConfig;
use DI\ContainerBuilder;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * JWT 效能測試.
 *
 * 測試 JWT token 產生和驗證的效能
 */
#[Group('performance')]
class JwtPerformanceTest extends TestCase
{
    private JwtTokenServiceInterface $jwtTokenService;

    private JwtConfig $jwtConfig;

    protected function setUp(): void
    {
        parent::setUp();

        // 使用實際的服務容器來取得服務
        try {
            $containerConfigPath = __DIR__ . '/../../config/container.php';
            $containerConfig = require $containerConfigPath;

            $builder = new ContainerBuilder();
            $builder->addDefinitions($containerConfig);
            $container = $builder->build();

            $this->jwtTokenService = $container->get(JwtTokenServiceInterface::class);
            $this->jwtConfig = $container->get(JwtConfig::class);
        } catch (Exception $e) {
            $this->markTestSkipped('無法初始化 JWT 服務: ' . $e->getMessage());
        }
    }

    /**
     * 測試 JWT token 產生效能
     * 要求：每個 token 產生時間 < 10ms.
     */
    public function testJwtTokenGenerationPerformance(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: 'perf-test-device-001',
            deviceName: 'Test Device',
            userAgent: 'Performance Test Agent',
            ipAddress: '127.0.0.1',
        );

        $userId = 1;
        $iterations = 100;
        $maxTimePerToken = 0.010; // 10ms

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $tokenPair = $this->jwtTokenService->generateTokenPair($userId, $deviceInfo);
            $this->assertNotEmpty($tokenPair->getAccessToken());
            $this->assertNotEmpty($tokenPair->getRefreshToken());
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $averageTime = $totalTime / $iterations;

        // 輸出效能統計
        $this->addToAssertionCount(1);
        echo "\n效能統計:\n";
        echo '總時間: ' . round($totalTime * 1000, 2) . "ms\n";
        echo '平均時間: ' . round($averageTime * 1000, 2) . "ms/token\n";
        echo '每秒可產生: ' . round(1 / $averageTime) . " tokens\n";

        // 驗證效能要求
        $this->assertLessThan(
            $maxTimePerToken,
            $averageTime,
            "JWT token 產生平均時間 ({$averageTime}s) 超過要求 ({$maxTimePerToken}s)",
        );
    }

    /**
     * 測試 JWT token 驗證效能
     * 要求：每個 token 驗證時間 < 5ms.
     */
    public function testJwtTokenValidationPerformance(): void
    {
        // 先產生一個有效的 token
        $deviceInfo = new DeviceInfo(
            deviceId: 'perf-test-device-002',
            deviceName: 'Test Device',
            userAgent: 'Performance Test Agent',
            ipAddress: '127.0.0.1',
        );

        $tokenPair = $this->jwtTokenService->generateTokenPair(1, $deviceInfo);
        $accessToken = $tokenPair->getAccessToken();

        $iterations = 500;
        $maxTimePerValidation = 0.005; // 5ms

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $payload = $this->jwtTokenService->validateAccessToken($accessToken);
            $this->assertInstanceOf(JwtPayload::class, $payload);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $averageTime = $totalTime / $iterations;

        // 輸出效能統計
        echo "\n驗證效能統計:\n";
        echo '總時間: ' . round($totalTime * 1000, 2) . "ms\n";
        echo '平均時間: ' . round($averageTime * 1000, 2) . "ms/validation\n";
        echo '每秒可驗證: ' . round(1 / $averageTime) . " tokens\n";

        // 驗證效能要求
        $this->assertLessThan(
            $maxTimePerValidation,
            $averageTime,
            "JWT token 驗證平均時間 ({$averageTime}s) 超過要求 ({$maxTimePerValidation}s)",
        );
    }

    /**
     * 測試並發 token 產生
     */
    public function testConcurrentTokenGeneration(): void
    {
        $this->markTestSkipped('並發測試需要特殊環境設定');
    }

    /**
     * 測試記憶體使用量.
     */
    public function testMemoryUsage(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: 'memory-test-device-003',
            deviceName: 'Test Device',
            userAgent: 'Memory Test Agent',
            ipAddress: '127.0.0.1',
        );

        $initialMemory = memory_get_usage();
        $tokens = [];

        // 產生 1000 個 token
        for ($i = 0; $i < 1000; $i++) {
            $tokenPair = $this->jwtTokenService->generateTokenPair($i + 1, $deviceInfo);
            $tokens[] = $tokenPair;
        }

        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;
        $memoryPerToken = $memoryUsed / 1000;

        echo "\n記憶體使用統計:\n";
        echo '初始記憶體: ' . round($initialMemory / 1024) . "KB\n";
        echo '最終記憶體: ' . round($finalMemory / 1024) . "KB\n";
        echo '使用記憶體: ' . round($memoryUsed / 1024) . "KB\n";
        echo '每個 token: ' . round($memoryPerToken) . " bytes\n";

        // 驗證記憶體使用合理（每個 token 不超過 5KB）
        $this->assertLessThan(
            5120,
            $memoryPerToken,
            "每個 token 記憶體使用量 ({$memoryPerToken} bytes) 超過 5KB",
        );

        // 清理記憶體
        unset($tokens);
    }

    /**
     * 測試 token 大小.
     */
    public function testTokenSize(): void
    {
        $deviceInfo = new DeviceInfo(
            deviceId: 'size-test-device-004',
            deviceName: 'Test Device',
            userAgent: 'Size Test Agent',
            ipAddress: '127.0.0.1',
        );

        $tokenPair = $this->jwtTokenService->generateTokenPair(1, $deviceInfo);

        $accessTokenSize = strlen($tokenPair->getAccessToken());
        $refreshTokenSize = strlen($tokenPair->getRefreshToken());

        echo "\nToken 大小統計:\n";
        echo "Access Token: {$accessTokenSize} bytes\n";
        echo "Refresh Token: {$refreshTokenSize} bytes\n";

        // 驗證 token 大小合理（通常 JWT 不超過 8KB）
        $this->assertLessThan(8192, $accessTokenSize, 'Access token 太大');
        $this->assertLessThan(8192, $refreshTokenSize, 'Refresh token 太大');

        // 驗證 token 不會太小（至少要有基本結構）
        $this->assertGreaterThan(50, $accessTokenSize, 'Access token 太小');
        $this->assertGreaterThan(50, $refreshTokenSize, 'Refresh token 太小');
    }
}
