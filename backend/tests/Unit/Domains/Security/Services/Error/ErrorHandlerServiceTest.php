<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\Services\Error;

use App\Domains\Security\Services\Error\ErrorHandlerService;
use App\Shared\Exceptions\CsrfTokenException;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\StateTransitionException;
use App\Shared\Exceptions\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * ErrorHandlerService 測試.
 *
 * 建構子會註冊全域錯誤處理器，各測試建立實例後立即還原堆疊，
 * 避免影響 PHPUnit 自身的錯誤處理。致命錯誤分支以子程序覆蓋。
 */
#[CoversClass(ErrorHandlerService::class)]
class ErrorHandlerServiceTest extends UnitTestCase
{
    private string $logDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logDir = sys_get_temp_dir() . '/error_handler_test_' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->logDir . '/*') ?: [] as $file) {
            @unlink((string) $file);
        }
        @rmdir($this->logDir);
        parent::tearDown();
    }

    /**
     * 建立服務並立即還原全域處理器堆疊.
     *
     * @param array<int, string> $sensitiveKeys
     */
    private function makeService(bool $isDevelopment = false, array $sensitiveKeys = []): ErrorHandlerService
    {
        $service = new ErrorHandlerService($this->logDir, $isDevelopment, $sensitiveKeys);
        restore_error_handler();
        restore_exception_handler();

        return $service;
    }

    #[Test]
    public function constructor_creates_log_directory_with_secure_permissions(): void
    {
        $service = $this->makeService();

        $this->assertInstanceOf(ErrorHandlerService::class, $service);
        $this->assertDirectoryExists($this->logDir);
        $perms = fileperms($this->logDir) & 0o777;

        $this->assertSame(0o750, $perms);
    }

    #[Test]
    public function public_mode_returns_friendly_payload_without_internals(): void
    {
        $payload = $this->makeService()->handleException(new RuntimeException('secret internals'));

        /** @var array<string, mixed> $payload */
        $this->assertSame('系統暫時無法處理您的請求，請稍後再試', $payload['error']);
        $this->assertSame('INTERNAL_ERROR', $payload['code']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            is_scalar($payload['timestamp']) ? (string) $payload['timestamp'] : '',
        );
        $this->assertArrayNotHasKey('trace', $payload);
    }

    #[Test]
    public function mapped_exceptions_use_specific_messages_and_codes(): void
    {
        $service = $this->makeService();
        $cases = [
            ValidationException::class      => ['欄位格式不正確', 'VALIDATION_ERROR'],
            NotFoundException::class        => ['請求的資源不存在', 'NOT_FOUND'],
            CsrfTokenException::class       => ['安全驗證失敗，請重新載入頁面', 'CSRF_ERROR'],
            StateTransitionException::class => ['操作失敗，請稍後再試', 'STATE_ERROR'],
        ];

        foreach ($cases as $exceptionClass => [$expectedMessage, $expectedCode]) {
            $exception = match ($exceptionClass) {
                ValidationException::class => ValidationException::fromSingleError('field', $expectedMessage),
                default                    => new $exceptionClass($expectedMessage),
            };
            $payload = $service->handleException($exception);

            /** @var array<string, mixed> $payload */
            $this->assertSame($expectedMessage, $payload['error'], $exceptionClass);
            $this->assertSame($expectedCode, $payload['code'], $exceptionClass);
        }
    }

    #[Test]
    public function development_mode_exposes_debug_details(): void
    {
        $service = $this->makeService(isDevelopment: true);

        $debugPayload = $service->handleException(new RuntimeException('boom'), false);

        /** @var array<string, mixed> $debugPayload */
        $this->assertSame('boom', $debugPayload['error']);
        $this->assertSame(RuntimeException::class, $debugPayload['type']);
        $this->assertArrayHasKey('file', $debugPayload);
        $this->assertArrayHasKey('line', $debugPayload);
        $this->assertArrayHasKey('trace', $debugPayload);

        // 明確標記為公開錯誤時仍回傳友善訊息
        $publicPayload = $service->handleException(new RuntimeException('boom'), true);

        /** @var array<string, mixed> $publicPayload */
        $this->assertSame('系統暫時無法處理您的請求，請稍後再試', $publicPayload['error']);
    }

    #[Test]
    public function sanitize_log_data_redacts_sensitive_keys_recursively(): void
    {
        $service = $this->makeService(sensitiveKeys: ['my_custom_secret']);

        $sanitized = $service->sanitizeLogData([
            'Password'         => 'plain-text-pw',
            'nested'           => ['API_KEY' => 'key-value', 'keep_me' => 42],
            'my_custom_secret' => 'company-secret',
            'normal'           => 'visible',
        ]);

        $this->assertSame('[REDACTED]', $sanitized['Password']);

        /** @var array<string, mixed> $nested */
        $nested = $sanitized['nested'];
        $this->assertSame('[REDACTED]', $nested['API_KEY']);
        $this->assertSame(42, $nested['keep_me']);

        // 建構子注入的自訂敏感鍵同樣被遮罩
        $this->assertSame('[REDACTED]', $sanitized['my_custom_secret']);
        $this->assertSame('visible', $sanitized['normal']);
    }

    #[Test]
    public function sanitize_log_data_truncates_overlong_strings(): void
    {
        // 純英數長字串會被判斷為雜湊值而遮罩，此處使用含空白的長字串驗證截斷
        $sanitized = $this->makeService()->sanitizeLogData([
            'blob' => str_repeat('word ', 240),
        ]);

        $value = is_string($sanitized['blob']) ? $sanitized['blob'] : '';
        $this->assertSame(1000 + strlen('... [truncated]'), strlen($value));
        $this->assertStringContainsString('word ', $value);
        $this->assertTrue(str_ends_with($value, '... [truncated]'));
    }

    #[Test]
    public function sanitize_log_data_flags_hash_like_and_card_numbers_but_keeps_email(): void
    {
        $sanitized = $this->makeService()->sanitizeLogData([
            'hash_like'  => 'Abcdef1234567890abcdef12',
            'card_space' => '4111 1111 1111 1111',
            'card_dash'  => '4111-1111-1111-1111',
            'email'      => 'user@example.com',
        ]);

        $this->assertSame('[REDACTED]', $sanitized['hash_like']);
        $this->assertSame('[REDACTED]', $sanitized['card_space']);
        $this->assertSame('[REDACTED]', $sanitized['card_dash']);
        $this->assertSame('user@example.com', $sanitized['email']);
    }

    #[Test]
    public function security_events_are_written_with_sanitized_context(): void
    {
        $service = $this->makeService();

        $service->logSecurityEvent('Brute force detected', [
            'password' => 'p@ssword',
            'user'     => 'bob',
        ]);

        $content = $this->readLatestLog('security');
        $this->assertStringContainsString('Security Event: Brute force detected', $content);
        $this->assertStringContainsString('[REDACTED]', $content);
        $this->assertStringContainsString('"user":"bob"', $content);
        // CLI 環境缺少請求資訊時使用預設值
        $this->assertStringContainsString('"ip":"unknown"', $content);
    }

    #[Test]
    public function authentication_attempts_are_logged_with_outcome(): void
    {
        $service = $this->makeService();

        $service->logAuthenticationAttempt(true, 'alice', ['password' => 'good-password']);
        $service->logAuthenticationAttempt(false, 'mallory');

        $content = $this->readLatestLog('security');
        $this->assertStringContainsString('Authentication Success', $content);
        $this->assertStringContainsString('Authentication Failed', $content);
        $this->assertStringContainsString('"username":"alice"', $content);
        $this->assertStringContainsString('"username":"mallory"', $content);
    }

    #[Test]
    public function suspicious_activity_is_logged_as_error(): void
    {
        $service = $this->makeService();

        $service->logSuspiciousActivity('SQL injection attempt', ['query' => "'; DROP TABLE"]);

        $securityContent = $this->readLatestLog('security');
        $this->assertStringContainsString('Suspicious Activity: SQL injection attempt', $securityContent);

        $errorContent = $this->readLatestLog('error');
        $this->assertStringContainsString('Suspicious Activity: SQL injection attempt', $errorContent);
    }

    #[Test]
    public function global_error_handler_ignores_suppressed_severities_and_logs_others(): void
    {
        $service = $this->makeService();

        $originalLevel = error_reporting();

        try {
            // 被抑制的嚴重程度應回傳 false 且不記錄
            error_reporting($originalLevel & ~E_USER_NOTICE);
            $this->assertFalse(
                $service->globalErrorHandler(E_USER_NOTICE, 'suppressed', __FILE__, __LINE__),
            );

            // 未被抑制的嚴重程度會轉為日誌並回傳 true
            error_reporting(E_ALL);
            $this->assertTrue(
                $service->globalErrorHandler(E_WARNING, 'converted to log', __FILE__, __LINE__),
            );
        } finally {
            error_reporting($originalLevel);
        }

        $this->assertStringContainsString('converted to log', $this->readLatestLog('error'));
    }

    #[Test]
    public function fatal_error_triggers_shutdown_json_response_in_subprocess(): void
    {
        $output = $this->runFatalScript(<<<'PHP'
            <?php
            declare(strict_types=1);
            ini_set('display_errors', '0');
            require '/var/www/html/vendor/autoload.php';
            $service = new App\Domains\Security\Services\Error\ErrorHandlerService(%s, false);
            undefined_function_that_does_not_exist();
            PHP, true);

        $this->assertStringContainsString('"code":"INTERNAL_ERROR"', $output);
    }

    #[Test]
    public function fatal_global_error_handler_outputs_json_in_subprocess(): void
    {
        $output = $this->runFatalScript(<<<'PHP'
            <?php
            declare(strict_types=1);
            ini_set('display_errors', '0');
            require '/var/www/html/vendor/autoload.php';
            $service = new App\Domains\Security\Services\Error\ErrorHandlerService(%s, false);
            $service->globalErrorHandler(E_ERROR, 'Simulated fatal', __FILE__, 42);
            echo 'SHOULD_NOT_BE_REACHED';
            PHP, false);

        $this->assertStringContainsString('"code":"INTERNAL_ERROR"', $output);
        $this->assertStringNotContainsString('SHOULD_NOT_BE_REACHED', $output);
    }

    /**
     * 於子程序執行觸發致命錯誤的腳本並回傳輸出.
     */
    private function runFatalScript(string $template, bool $freshDirectory): string
    {
        if ($freshDirectory) {
            // 子程序使用獨立日誌目錄，避免與目前測試共用
            $childDir = sys_get_temp_dir() . '/error_handler_child_' . uniqid('', true);
        } else {
            $childDir = $this->logDir;
        }
        $script = sprintf($template, var_export($childDir, true));

        $path = tempnam(sys_get_temp_dir(), 'fatal_probe_');
        if ($path === false) {
            throw new RuntimeException('無法建立暫存腳本');
        }
        file_put_contents($path, $script);

        try {
            return (string) shell_exec('php ' . escapeshellarg($path) . ' 2>&1');
        } finally {
            @unlink($path);
            foreach (glob($childDir . '/*') ?: [] as $file) {
                @unlink((string) $file);
            }
            @rmdir($childDir);
        }
    }

    /**
     * 讀取日誌目錄中指定前綴最新檔案內容.
     */
    private function readLatestLog(string $prefix): string
    {
        $candidates = glob($this->logDir . '/' . $prefix . '*.log*');
        if ($candidates === false || $candidates === []) {
            $this->fail("找不到日誌檔案：{$prefix}");
        }
        usort($candidates, static fn(string $a, string $b): int => (int) filemtime($b) <=> (int) filemtime($a));

        return (string) file_get_contents((string) $candidates[0]);
    }
}
