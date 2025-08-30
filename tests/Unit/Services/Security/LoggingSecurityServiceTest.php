<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Domains\Security\Services\Logging\LoggingSecurityService;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
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

        $this->assertEquals('testuser', (is_array($result) && isset((is_array($result) ? $result['username'] : (is_object($result) ? $result->username : null)))) ? (is_array($result) ? $result['username'] : (is_object($result) ? $result->username : null)) : null);
        $this->assertEquals('[REDACTED]', (is_array($result) && isset((is_array($result) ? $result['password'] : (is_object($result) ? $result->password : null)))) ? (is_array($result) ? $result['password'] : (is_object($result) ? $result->password : null)) : null);
        $this->assertEquals('[REDACTED]', (is_array($result) && isset((is_array($result) ? $result['csrf_token'] : (is_object($result) ? $result->csrf_token : null)))) ? (is_array($result) ? $result['csrf_token'] : (is_object($result) ? $result->csrf_token : null)) : null);
        $this->assertEquals('this is safe', (is_array($result) && isset((is_array($result) ? $result['safe_data'] : (is_object($result) ? $result->safe_data : null)))) ? (is_array($result) ? $result['safe_data'] : (is_object($result) ? $result->safe_data : null)) : null);
        $this->assertEquals('[REDACTED]', (is_array($result) ? $result['nested'] : (is_object($result) ? $result->nested : null))['api_key']);
        $this->assertEquals('public', (is_array($result) ? $result['nested'] : (is_object($result) ? $result->nested : null))['public_info']);
    }

    #[Test]
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

    #[Test]
    public function truncatesLongStrings(): void
    {
        $longString = str_repeat('A', 1500);
        $data = ['long_field' => $longString];

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('sanitizeContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $data);

        $this->assertStringEndsWith('[TRUNCATED]', (is_array($result) && isset((is_array($result) ? $result['long_field'] : (is_object($result) ? $result->long_field : null)))) ? (is_array($result) ? $result['long_field'] : (is_object($result) ? $result->long_field : null)) : null);
        $this->assertLessThan(1500, strlen((is_array($result) && isset((is_array($result) ? $result['long_field'] : (is_object($result) ? $result->long_field : null)))) ? (is_array($result) ? $result['long_field'] : (is_object($result) ? $result->long_field : null)) : null));
    }

    #[Test]
    public function enrichesSecurityContext(): void
    {
        (is_array($_SESSION) ? $_SESSION['user_id'] : (is_object($_SESSION) ? $_SESSION->user_id : null)) = 123;
        session_id('test_session_id');

        $context = ['event' => 'test_event'];

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('enrichSecurityContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $context);

        $this->assertArrayHasKey('server_time', $result);
        $this->assertArrayHasKey('process_id', $result);
        $this->assertEquals('test_event', (is_array($result) && isset((is_array($result) ? $result['event'] : (is_object($result) ? $result->event : null)))) ? (is_array($result) ? $result['event'] : (is_object($result) ? $result->event : null)) : null);
        $this->assertEquals(123, (is_array($result) && isset((is_array($result) ? $result['user_id'] : (is_object($result) ? $result->user_id : null)))) ? (is_array($result) ? $result['user_id'] : (is_object($result) ? $result->user_id : null)) : null);
    }

    #[Test]
    public function enrichesRequestContext(): void
    {
        (is_array($_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : (is_object($_SERVER) ? $_SERVER->HTTP_USER_AGENT : null)) = 'Mozilla/5.0 Test Browser';

        $context = ['method' => 'GET'];

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('enrichRequestContext');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $context);

        $this->assertArrayHasKey('server_time', $result);
        $this->assertArrayHasKey('user_agent_hash', $result);
        $this->assertEquals('GET', (is_array($result) && isset((is_array($result) ? $result['method'] : (is_object($result) ? $result->method : null)))) ? (is_array($result) ? $result['method'] : (is_object($result) ? $result->method : null)) : null);
        $this->assertEquals(
            hash('sha256', 'Mozilla/5.0 Test Browser'),
            (is_array($result) && isset((is_array($result) ? $result['user_agent_hash'] : (is_object($result) ? $result->user_agent_hash : null)))) ? (is_array($result) ? $result['user_agent_hash'] : (is_object($result) ? $result->user_agent_hash : null)) : null,
        );
    }

    #[Test]
    public function logsSecurityEventsCorrectly(): void
    {
        $this->expectNotToPerformAssertions();

        // 這些方法應該不會拋出例外
        $this->service->logSecurityEvent('Test security event', ['test' => 'data']);
        $this->service->logCriticalSecurityEvent('Critical event', ['critical' => 'data']);
        $this->service->logAuthenticationFailure('Invalid credentials', ['user' => 'test']);
        $this->service->logAuthorizationFailure('posts', 'delete', ['user_id' => 123]);
    }

    #[Test]
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

    #[Test]
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

        $this->assertEquals('[REDACTED]', (is_array($result) && isset((is_array($result) ? $result['Password'] : (is_object($result) ? $result->Password : null)))) ? (is_array($result) ? $result['Password'] : (is_object($result) ? $result->Password : null)) : null);
        $this->assertEquals('[REDACTED]', (is_array($result) && isset((is_array($result) ? $result['API_KEY'] : (is_object($result) ? $result->API_KEY : null)))) ? (is_array($result) ? $result['API_KEY'] : (is_object($result) ? $result->API_KEY : null)) : null);
        $this->assertEquals('[REDACTED]', (is_array($result) && isset((is_array($result) ? $result['user_token'] : (is_object($result) ? $result->user_token : null)))) ? (is_array($result) ? $result['user_token'] : (is_object($result) ? $result->user_token : null)) : null);
        $this->assertEquals('[REDACTED]', (is_array($result) && isset((is_array($result) ? $result['csrf_TOKEN'] : (is_object($result) ? $result->csrf_TOKEN : null)))) ? (is_array($result) ? $result['csrf_TOKEN'] : (is_object($result) ? $result->csrf_TOKEN : null)) : null);
        $this->assertEquals('public', (is_array($result) && isset((is_array($result) ? $result['safe_field'] : (is_object($result) ? $result->safe_field : null)))) ? (is_array($result) ? $result['safe_field'] : (is_object($result) ? $result->safe_field : null)) : null);
    }

    #[Test]
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

        $this->assertEquals('', (is_array($result) && isset((is_array($result) ? $result['empty_string'] : (is_object($result) ? $result->empty_string : null)))) ? (is_array($result) ? $result['empty_string'] : (is_object($result) ? $result->empty_string : null)) : null);
        $this->assertNull((is_array($result) && isset((is_array($result) ? $result['null_value'] : (is_object($result) ? $result->null_value : null)))) ? (is_array($result) ? $result['null_value'] : (is_object($result) ? $result->null_value : null)) : null);
        $this->assertEquals(0, (is_array($result) && isset((is_array($result) ? $result['zero'] : (is_object($result) ? $result->zero : null)))) ? (is_array($result) ? $result['zero'] : (is_object($result) ? $result->zero : null)) : null);
        $this->assertFalse((is_array($result) && isset((is_array($result) ? $result['false_value'] : (is_object($result) ? $result->false_value : null)))) ? (is_array($result) ? $result['false_value'] : (is_object($result) ? $result->false_value : null)) : null);
        $this->assertEquals('[REDACTED]', (is_array($result) && isset((is_array($result) ? $result['password'] : (is_object($result) ? $result->password : null)))) ? (is_array($result) ? $result['password'] : (is_object($result) ? $result->password : null)) : null);
    }

    #[Test]
    public function returnsLogStatistics(): void
    {
        $stats = $this->service->getLogStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('directory', $stats);
        $this->assertArrayHasKey('directory_permissions', $stats);
        $this->assertArrayHasKey('files', $stats);
    }
}
