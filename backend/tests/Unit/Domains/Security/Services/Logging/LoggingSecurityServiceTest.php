<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\Services\Logging;

use App\Domains\Security\Services\Logging\LoggingSecurityService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * LoggingSecurityService 測試.
 *
 * 驗證日誌內容時直接讀取 storage/logs 下的輸出檔案。
 */
#[CoversClass(LoggingSecurityService::class)]
class LoggingSecurityServiceTest extends UnitTestCase
{
    private LoggingSecurityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LoggingSecurityService();
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        parent::tearDown();
    }

    #[Test]
    public function application_logs_are_sanitized_before_persistence(): void
    {
        $marker = 'sanity_' . uniqid();

        $this->service->info($marker, [
            'password' => 'topsecret-value',
            'nested'   => ['api_key' => 'nested-key'],
            'plain'    => 'visible-text',
        ]);

        $content = $this->readLatestLog('app');
        $this->assertStringContainsString($marker, $content);
        $this->assertStringContainsString('[REDACTED]', $content);
        $this->assertStringNotContainsString('topsecret-value', $content);
        $this->assertStringNotContainsString('nested-key', $content);
        $this->assertStringContainsString('visible-text', $content);
    }

    #[Test]
    public function warning_and_error_levels_are_supported(): void
    {
        $warningMarker = 'warn_' . uniqid();
        $errorMarker = 'error_' . uniqid();

        $this->service->warning($warningMarker, ['note' => 'w']);
        $this->service->error($errorMarker, ['note' => 'e']);

        $content = $this->readLatestLog('app');
        $this->assertStringContainsString($warningMarker, $content);
        $this->assertStringContainsString($errorMarker, $content);
    }

    #[Test]
    public function request_logs_apply_whitelist_and_hash_user_agent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnitAgent/1.0';

        $this->service->logRequest([
            'method'       => 'POST',
            'uri'          => '/api/v1/posts',
            'status_code'  => 200,
            'evil_payload' => '<script>alert(1)</script>',
            'password'     => 'should-not-appear',
        ]);

        $content = $this->readLatestLog('app');
        $this->assertStringContainsString('HTTP Request', $content);
        $this->assertStringContainsString('"method":"POST"', $content);
        // User-Agent 僅以雜湊形式記錄
        $this->assertStringContainsString(hash('sha256', 'PHPUnitAgent/1.0'), $content);
        $this->assertStringNotContainsString('evil_payload', $content);
        $this->assertStringNotContainsString('<script>', $content);
        $this->assertStringNotContainsString('should-not-appear', $content);
    }

    #[Test]
    public function security_events_are_enriched_and_redacted(): void
    {
        $event = 'event_' . uniqid();

        $this->service->logSecurityEvent($event, [
            'csrf_token' => 'token-value',
            'action'     => 'login',
        ]);

        $content = $this->readLatestLog('security');
        $this->assertStringContainsString($event, $content);
        $this->assertStringContainsString('[REDACTED]', $content);
        $this->assertStringContainsString('"action":"login"', $content);
        $this->assertStringContainsString('server_time', $content);
        $this->assertStringContainsString('process_id', $content);
    }

    #[Test]
    public function critical_security_events_also_written_to_audit_log(): void
    {
        $breach = 'breach_' . uniqid();

        $this->service->logCriticalSecurityEvent($breach);

        $this->assertStringContainsString($breach, $this->readLatestLog('security'));
        $this->assertStringContainsString($breach, $this->readLatestLog('audit'));
    }

    #[Test]
    public function authentication_and_authorization_failures_are_recorded(): void
    {
        $reason = 'bad-credentials_' . uniqid();

        $this->service->logAuthenticationFailure($reason, ['username' => 'u1']);
        $this->service->logAuthorizationFailure('/admin/settings', 'update');

        $content = $this->readLatestLog('security');
        $this->assertStringContainsString('Authentication Failure: ' . $reason, $content);
        $this->assertStringContainsString(
            'Authorization Failure: Access denied to /admin/settings for action update',
            $content,
        );
        $this->assertStringContainsString('"username":"u1"', $content);
    }

    #[Test]
    public function log_file_permissions_are_verified_and_corrected(): void
    {
        $results = $this->service->verifyLogFilePermissions();

        $this->assertSame([], $results['error'] ?? []);
        $this->assertNotEmpty($results);

        foreach ($results as $entry) {
            /** @var array<string, mixed> $entry */
            if (!is_array($entry)) {
                continue;
            }
            $this->assertSame('640', $entry['expected_permissions'] ?? null);
        }

        // 檢查後 audit.log 的實際權限應為 0640
        $auditPath = storage_path('logs') . '/audit.log';
        if (file_exists($auditPath)) {
            $this->assertSame(0o640, fileperms($auditPath) & 0o777);
        }
    }

    #[Test]
    public function log_statistics_report_directory_and_files(): void
    {
        // 確保至少有一個日誌檔案存在
        $this->service->info('stats_probe_' . uniqid());

        $stats = $this->service->getLogStatistics();

        $this->assertSame(storage_path('logs'), $stats['directory']);
        $this->assertArrayHasKey('directory_permissions', $stats);

        /** @var array<string, array<string, mixed>> $files */
        $files = $stats['files'];
        $this->assertArrayHasKey('audit.log', $files);

        $auditStats = $files['audit.log'];
        $size = $auditStats['size'] ?? 0;
        $this->assertGreaterThan(0, is_numeric($size) ? (int) $size : 0);
        $this->assertSame('640', is_scalar($auditStats['permissions'] ?? null)
            ? (string) $auditStats['permissions']
            : '');
        $lastModified = $auditStats['last_modified'] ?? '';
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            is_scalar($lastModified) ? (string) $lastModified : '',
        );
    }

    /**
     * 讀取日誌目錄中指定前綴最新檔案內容.
     */
    private function readLatestLog(string $prefix): string
    {
        $candidates = glob(storage_path('logs') . '/' . $prefix . '*.log*');
        if ($candidates === false || $candidates === []) {
            $this->fail("找不到日誌檔案：{$prefix}");
        }
        usort($candidates, static fn(string $a, string $b): int => (int) filemtime($b) <=> (int) filemtime($a));

        return (string) file_get_contents((string) $candidates[0]);
    }
}
