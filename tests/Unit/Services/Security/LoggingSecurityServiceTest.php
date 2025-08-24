<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Domains\Security\Services\Logging\LoggingSecurityService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class LoggingSecurityServiceTest extends TestCase
{
    private LoggingSecurityService $service;

    private string $tempLogsDir;

    protected function setUp(): void
    {
        // 建立臨時日誌目錄
        $this->tempLogsDir = sys_get_temp_dir() . '/alleynote_test_logs_' . uniqid();
        mkdir($this->tempLogsDir, 0o750, true);

        // 模擬 storage_path 函式
        if (!function_exists('storage_path')) {
            function storage_path(string $path = ''): string
            {
                global $tempLogsDir;

                return $tempLogsDir . ($path ? '/' . ltrim($path, '/') : '');
            }
        }

        // 設定全域變數供函式使用
        global $tempLogsDir;
        $tempLogsDir = $this->tempLogsDir;

        $this->service = new LoggingSecurityService();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_SERVER = [];

        // 清理臨時目錄
        if (is_dir($this->tempLogsDir)) {
            $this->recursiveDelete($this->tempLogsDir);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /** @test */
    public function sanitizesContextDataCorrectly(): void
    {
        $sensitiveData = [
            'username' => 'testuser',
            'password' => 'secret123',
            'csrf_token' => 'abc123',
            'safe_data' => 'this is safe',
            'nested' => [
                'api_key' => 'secret_key',
                'public_info' => 'public',
            ],
        ];

        // 使用反射呼叫私有方法進行測試
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('sanitizeContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $sensitiveData);

        $this->assertEquals('testuser', $result['username']);
        $this->assertEquals('[REDACTED]', $result['password']);
        $this->assertEquals('[REDACTED]', $result['csrf_token']);
        $this->assertEquals('this is safe', $result['safe_data']);
        $this->assertEquals('[REDACTED]', $result['nested']['api_key']);
        $this->assertEquals('public', $result['nested']['public_info']);
    }

    /** @test */
    public function appliesRequestWhitelistCorrectly(): void
    {
        $requestData = [
            'method' => 'POST',
            'uri' => '/api/posts',
            'password' => 'secret123', // 不在白名單中
            'user_id' => 123,
            'sensitive_param' => 'should_be_filtered', // 不在白名單中
            'status_code' => 200,
        ];

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('applyRequestWhitelist');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $requestData);

        $this->assertArrayHasKey('method', $result);
        $this->assertArrayHasKey('uri', $result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('status_code', $result);
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('sensitive_param', $result);
    }

    /** @test */
    public function truncatesLongStrings(): void
    {
        $longString = str_repeat('A', 1500);
        $data = ['long_field' => $longString];

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('sanitizeContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $data);

        $this->assertStringEndsWith('[TRUNCATED]', $result['long_field']);
        $this->assertLessThan(1500, strlen($result['long_field']));
    }

    /** @test */
    public function enrichesSecurityContext(): void
    {
        $_SESSION['user_id'] = 123;
        session_id('test_session_id');

        $context = ['event' => 'test_event'];

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('enrichSecurityContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $context);

        $this->assertArrayHasKey('server_time', $result);
        $this->assertArrayHasKey('process_id', $result);
        $this->assertEquals('test_event', $result['event']);
        $this->assertEquals(123, $result['user_id']);
    }

    /** @test */
    public function enrichesRequestContext(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Test Browser';

        $context = ['method' => 'GET'];

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('enrichRequestContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $context);

        $this->assertArrayHasKey('server_time', $result);
        $this->assertArrayHasKey('user_agent_hash', $result);
        $this->assertEquals('GET', $result['method']);
        $this->assertEquals(
            hash('sha256', 'Mozilla/5.0 Test Browser'),
            $result['user_agent_hash'],
        );
    }

    /** @test */
    public function logsSecurityEventsCorrectly(): void
    {
        $this->expectNotToPerformAssertions();

        // 這些方法應該不會拋出例外
        $this->service->logSecurityEvent('Test security event', ['test' => 'data']);
        $this->service->logCriticalSecurityEvent('Critical event', ['critical' => 'data']);
        $this->service->logAuthenticationFailure('Invalid credentials', ['user' => 'test']);
        $this->service->logAuthorizationFailure('posts', 'delete', ['user_id' => 123]);
    }

    /** @test */
    public function logsRequestsWithWhitelist(): void
    {
        $requestData = [
            'method' => 'POST',
            'uri' => '/api/posts',
            'password' => 'secret123', // 應該被過濾
            'user_id' => 123,
            'status_code' => 201,
        ];

        $this->expectNotToPerformAssertions();
        $this->service->logRequest($requestData);
    }

    /** @test */
    public function handlesSensitiveFieldVariations(): void
    {
        $data = [
            'Password' => 'secret1',
            'API_KEY' => 'secret2',
            'user_token' => 'secret3',
            'csrf_TOKEN' => 'secret4',
            'safe_field' => 'public',
        ];

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('sanitizeContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $data);

        $this->assertEquals('[REDACTED]', $result['Password']);
        $this->assertEquals('[REDACTED]', $result['API_KEY']);
        $this->assertEquals('[REDACTED]', $result['user_token']);
        $this->assertEquals('[REDACTED]', $result['csrf_TOKEN']);
        $this->assertEquals('public', $result['safe_field']);
    }

    /** @test */
    public function handlesEmptyAndNullValues(): void
    {
        $data = [
            'empty_string' => '',
            'null_value' => null,
            'zero' => 0,
            'false_value' => false,
            'password' => '',  // 敏感欄位即使為空也要遮罩
        ];

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('sanitizeContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $data);

        $this->assertEquals('', $result['empty_string']);
        $this->assertNull($result['null_value']);
        $this->assertEquals(0, $result['zero']);
        $this->assertFalse($result['false_value']);
        $this->assertEquals('[REDACTED]', $result['password']);
    }

    /** @test */
    public function returnsLogStatistics(): void
    {
        $stats = $this->service->getLogStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('directory', $stats);
        $this->assertArrayHasKey('directory_permissions', $stats);
        $this->assertArrayHasKey('files', $stats);
    }
}
