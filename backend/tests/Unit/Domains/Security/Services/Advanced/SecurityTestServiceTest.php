<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\Services\Advanced;

use App\Domains\Attachment\Contracts\FileSecurityServiceInterface;
use App\Domains\Auth\Contracts\AuthorizationServiceInterface;
use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\Contracts\SessionSecurityServiceInterface;
use App\Domains\Security\Contracts\ErrorHandlerServiceInterface;
use App\Domains\Security\Contracts\SecretsManagerInterface;
use App\Domains\Security\Contracts\SecurityHeaderServiceInterface;
use App\Domains\Security\Services\Advanced\SecurityTestService;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * SecurityTestService 測試.
 */
#[CoversClass(SecurityTestService::class)]
class SecurityTestServiceTest extends UnitTestCase
{
    /** @var SessionSecurityServiceInterface&MockInterface */
    private SessionSecurityServiceInterface $session;

    /** @var AuthorizationServiceInterface&MockInterface */
    private AuthorizationServiceInterface $auth;

    /** @var FileSecurityServiceInterface&MockInterface */
    private FileSecurityServiceInterface $file;

    /** @var SecurityHeaderServiceInterface&MockInterface */
    private SecurityHeaderServiceInterface $header;

    /** @var ErrorHandlerServiceInterface&MockInterface */
    private ErrorHandlerServiceInterface $error;

    /** @var PasswordSecurityServiceInterface&MockInterface */
    private PasswordSecurityServiceInterface $password;

    /** @var SecretsManagerInterface&MockInterface */
    private SecretsManagerInterface $secrets;

    private const XSS_FILENAME = 'test<script>alert("xss")</script>.txt';

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = Mockery::mock(SessionSecurityServiceInterface::class);
        $this->auth = Mockery::mock(AuthorizationServiceInterface::class);
        $this->file = Mockery::mock(FileSecurityServiceInterface::class);
        $this->header = Mockery::mock(SecurityHeaderServiceInterface::class);
        $this->error = Mockery::mock(ErrorHandlerServiceInterface::class);
        $this->password = Mockery::mock(PasswordSecurityServiceInterface::class);
        $this->secrets = Mockery::mock(SecretsManagerInterface::class);

        // 預設允許所有呼叫，各測試再覆寫明確期望
        $this->session->shouldIgnoreMissing();
        $this->auth->shouldIgnoreMissing();
        $this->file->shouldIgnoreMissing();
        $this->header->shouldIgnoreMissing();
        $this->error->shouldIgnoreMissing();
        $this->password->shouldIgnoreMissing();
        $this->secrets->shouldIgnoreMissing();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function session_security_reports_pass_and_fail_entries(): void
    {
        // CLI 環境下 session_id() 前後相同，重新產生檢查必標記 FAIL
        $this->session->shouldReceive('initializeSecureSession')->once();

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testSessionSecurity();

        $this->assertSame('Session Security', $this->nameOf($results));
        $this->assertCount(2, $this->entriesOf($results));
        $this->assertSame('PASS', $this->statusOf($results, 0));
        $this->assertSame('FAIL', $this->statusOf($results, 1));
        $this->assertSame('Session ID 未變更', $this->messageOf($results, 1));
        $this->assertSame(1, $this->passedOf($results));
        $this->assertSame(1, $this->failedOf($results));
    }

    #[Test]
    public function session_security_records_exception_as_failure(): void
    {
        $this->session->shouldReceive('initializeSecureSession')->once()->andThrow(new RuntimeException('boom'));

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testSessionSecurity();

        $this->assertSame('FAIL', $this->statusOf($results, 0));
        $this->assertSame('boom', $this->messageOf($results, 0));
        $this->assertSame(0, $this->passedOf($results));
        $this->assertSame(2, $this->failedOf($results));
    }

    #[Test]
    public function authorization_reports_successful_checks(): void
    {
        $this->auth->shouldReceive('hasPermission')->with(1, 'read_posts')->once()->andReturn(true);
        $this->auth->shouldReceive('can')->with(1, 'manage_posts', 'posts')->once()->andReturn(false);

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testAuthorization();

        $this->assertSame(2, $this->passedOf($results));
        $this->assertSame(0, $this->failedOf($results));
        $this->assertSame('PASS', $this->statusOf($results, 0));
        $this->assertSame('PASS', $this->statusOf($results, 1));
    }

    #[Test]
    public function authorization_handles_thrown_exception(): void
    {
        $this->auth->shouldReceive('hasPermission')->once()->andThrow(new RuntimeException('db down'));
        $this->auth->shouldReceive('can')->once()->andReturn(true);

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testAuthorization();

        $this->assertSame('FAIL', $this->statusOf($results, 0));
        $this->assertSame('db down', $this->messageOf($results, 0));
        $this->assertSame(1, $this->passedOf($results));
        $this->assertSame(1, $this->failedOf($results));
    }

    #[Test]
    public function file_security_detects_sanitized_filename(): void
    {
        $this->file->shouldReceive('sanitizeFileName')
            ->with(self::XSS_FILENAME)
            ->once()
            ->andReturn('testalertxsstxt.txt');

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testFileSecurity();

        $this->assertSame('File Security', $this->nameOf($results));
        $this->assertSame('SKIP', $this->statusOf($results, 0));
        $this->assertSame('PASS', $this->statusOf($results, 1));
        $this->assertSame(2, $this->passedOf($results));
        $this->assertSame(0, $this->failedOf($results));
    }

    #[Test]
    public function file_security_flags_uncleaned_filename(): void
    {
        $this->file->shouldReceive('sanitizeFileName')->once()->andReturn(self::XSS_FILENAME);

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testFileSecurity();

        $this->assertSame('FAIL', $this->statusOf($results, 1));
        $this->assertSame('檔名未被清理', $this->messageOf($results, 1));
        $this->assertSame(1, $this->passedOf($results));
        $this->assertSame(1, $this->failedOf($results));
    }

    #[Test]
    public function file_security_handles_sanitize_exception(): void
    {
        $this->file->shouldReceive('sanitizeFileName')->once()->andThrow(new RuntimeException('fs error'));

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testFileSecurity();

        $this->assertSame('FAIL', $this->statusOf($results, 1));
        $this->assertSame('fs error', $this->messageOf($results, 1));
    }

    #[Test]
    public function security_headers_fail_in_cli_environment(): void
    {
        // CLI 下 headers_list() 為空，找不到任何標頭
        $this->header->shouldReceive('setSecurityHeaders')->once();

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testSecurityHeaders();

        $this->assertSame('FAIL', $this->statusOf($results, 0));
        $this->assertStringContainsString('只找到 0 個安全標頭', $this->messageOf($results, 0));
        $this->assertSame(0, $this->passedOf($results));
        $this->assertSame(1, $this->failedOf($results));
    }

    #[Test]
    public function error_handling_accepts_valid_response_shape(): void
    {
        $this->error->shouldReceive('handleException')
            ->once()
            ->andReturn(['error' => '系統暫時無法處理您的請求']);

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testErrorHandling();

        $this->assertSame('Error Handling', $this->nameOf($results));
        $this->assertSame('PASS', $this->statusOf($results, 0));
        $this->assertSame(1, $this->passedOf($results));
        $this->assertSame(0, $this->failedOf($results));
    }

    #[Test]
    public function error_handling_rejects_invalid_response_shape(): void
    {
        $this->error->shouldReceive('handleException')->once()->andReturn([]);

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testErrorHandling();

        $this->assertSame('FAIL', $this->statusOf($results, 0));
        $this->assertSame('錯誤處理回應格式不正確', $this->messageOf($results, 0));
    }

    #[Test]
    public function error_handling_handles_thrown_exception(): void
    {
        $this->error->shouldReceive('handleException')->once()->andThrow(new Exception('handler exploded'));

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testErrorHandling();

        $this->assertSame('FAIL', $this->statusOf($results, 0));
        $this->assertSame('handler exploded', $this->messageOf($results, 0));
    }

    #[Test]
    public function password_security_verifies_hash_and_strength_ordering(): void
    {
        $plain = 'TestPassword123!';
        $this->password->shouldReceive('hashPassword')
            ->with($plain)
            ->once()
            ->andReturn(password_hash($plain, PASSWORD_DEFAULT));
        $this->password->shouldReceive('calculatePasswordStrength')
            ->with('123456')
            ->once()
            ->andReturn([]);
        $this->password->shouldReceive('calculatePasswordStrength')
            ->with('StrongP@ssw0rd123!')
            ->once()
            ->andReturn(['score' => 90]);

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testPasswordSecurity();

        $this->assertSame('Password Security', $this->nameOf($results));
        $this->assertCount(2, $this->entriesOf($results));
        $this->assertSame(2, $this->passedOf($results));
        $this->assertSame(0, $this->failedOf($results));
    }

    #[Test]
    public function password_security_flags_bad_hash_and_wrong_ordering(): void
    {
        $this->password->shouldReceive('hashPassword')->once()->andReturn('not-a-valid-hash');
        $this->password->shouldReceive('calculatePasswordStrength')->twice()->andReturn([]);

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testPasswordSecurity();

        $this->assertSame('FAIL', $this->statusOf($results, 0));
        $this->assertSame('密碼雜湊驗證失敗', $this->messageOf($results, 0));
        $this->assertSame('FAIL', $this->statusOf($results, 1));
        $this->assertSame('密碼強度檢查不正確', $this->messageOf($results, 1));
        $this->assertSame(0, $this->passedOf($results));
        $this->assertSame(2, $this->failedOf($results));
    }

    #[Test]
    public function password_security_catches_exceptions_from_service(): void
    {
        $this->password->shouldReceive('hashPassword')->once()->andThrow(new RuntimeException('bcrypt unavailable'));
        $this->password->shouldReceive('calculatePasswordStrength')->once()->andThrow(new RuntimeException('weak engine'));

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testPasswordSecurity();

        $this->assertSame('bcrypt unavailable', $this->messageOf($results, 0));
        $this->assertSame('weak engine', $this->messageOf($results, 1));
        $this->assertSame(2, $this->failedOf($results));
    }

    #[Test]
    public function secrets_management_counts_warning_for_env_issues(): void
    {
        $this->secrets->shouldReceive('load')->once();
        $this->secrets->shouldReceive('validateEnvFile')->once()->andReturn(['第 3 行格式錯誤']);

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testSecretsManagement();

        $this->assertSame('WARNING', $this->statusOf($results, 1));
        $this->assertStringContainsString('Found 1 issues', $this->messageOf($results, 1));
        $this->assertSame(1, $this->passedOf($results));
        $this->assertSame(1, $this->failedOf($results));
    }

    #[Test]
    public function secrets_management_passes_with_clean_env(): void
    {
        $this->secrets->shouldReceive('load')->once();
        $this->secrets->shouldReceive('validateEnvFile')->once()->andReturn([]);

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testSecretsManagement();

        $this->assertSame('Secrets Management', $this->nameOf($results));
        $this->assertSame('PASS', $this->statusOf($results, 0));
        $this->assertSame('PASS', $this->statusOf($results, 1));
        $this->assertSame(2, $this->passedOf($results));
        $this->assertSame(0, $this->failedOf($results));
    }

    #[Test]
    public function secrets_management_records_load_failure(): void
    {
        $this->secrets->shouldReceive('load')->once()->andThrow(new RuntimeException('.env unreadable'));

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testSecretsManagement();

        $this->assertSame('FAIL', $this->statusOf($results, 0));
        $this->assertSame('.env unreadable', $this->messageOf($results, 0));
        // 載入失敗只影響第一項檢查，第二項仍會嘗試驗證並通過
        $this->assertSame(1, $this->passedOf($results));
        $this->assertSame(1, $this->failedOf($results));
    }

    #[Test]
    public function system_security_fails_when_directories_missing(): void
    {
        /** @var array<string, mixed> $results */
        $results = $this->makeService()->testSystemSecurity();

        $this->assertSame('System Security', $this->nameOf($results));
        $this->assertSame('PASS', $this->statusOf($results, 0));
        $this->assertStringContainsString('符合要求', $this->messageOf($results, 0));
        $this->assertCount(3, $this->entriesOf($results));
        $this->assertSame('FAIL', $this->statusOf($results, 1));
        $this->assertSame('目錄不存在', $this->messageOf($results, 1));
        $this->assertSame('FAIL', $this->statusOf($results, 2));
        $this->assertSame(1, $this->passedOf($results));
        $this->assertSame(2, $this->failedOf($results));
    }

    #[Test]
    public function run_all_tests_aggregates_every_category(): void
    {
        $this->configureHappyPathMocks();

        /** @var array<string, mixed> $results */
        $results = $this->makeService()->runAllTests();

        $expectedKeys = [
            'session_security',
            'authorization',
            'file_security',
            'security_headers',
            'error_handling',
            'password_security',
            'secrets_management',
            'system_security',
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $results);
        }
        $this->assertCount(8, $results);

        /** @var array<string, mixed> $headers */
        $headers = $results['security_headers'];
        $this->assertSame(1, $this->failedOf($headers));
    }

    #[Test]
    public function generate_security_report_summarizes_totals_and_issues(): void
    {
        $this->configureHappyPathMocks();

        /** @var array<string, mixed> $report */
        $report = $this->makeService()->generateSecurityReport();

        /** @var array<string, mixed> $summary */
        $summary = $report['summary'];
        $this->assertSame(15, $this->toInt($summary['total_tests']));
        $this->assertSame(11, $this->toInt($summary['passed']));
        $this->assertSame(4, $this->toInt($summary['failed']));
        $this->assertSame('73.33%', $this->toStr($summary['success_rate']));
        $this->assertSame('可接受 (Acceptable)', $this->toStr($summary['security_level']));

        // 失敗項：session ID 未變更、CLI 標頭、兩個不存在的系統目錄
        /** @var array<int, array<string, mixed>> $criticalIssues */
        $criticalIssues = $report['critical_issues'];
        $this->assertCount(4, $criticalIssues);
        /** @var array<int, mixed> $categories */
        $categories = array_column($criticalIssues, 'category');
        $this->assertContains('System Security', $categories);

        /** @var array<int, string> $recommendations */
        $recommendations = $report['recommendations'];
        $this->assertStringContainsString('發現 4 個安全問題', $this->toStr($recommendations[0] ?? ''));
        $this->assertContains('定期更新相依套件和安全補丁', $recommendations);
        $this->assertContains('實施定期的滲透測試和漏洞掃描', $recommendations);
        $this->assertContains('確保所有開發人員接受安全培訓', $recommendations);

        /** @var array<string, mixed> $detailed */
        $detailed = $report['detailed_results'];
        /** @var array<string, mixed> $authorization */
        $authorization = $detailed['authorization'];
        $this->assertSame('Authorization System', $this->toStr($authorization['test_name'] ?? ''));
    }

    /**
     * 建立被測服務.
     */
    private function makeService(): SecurityTestService
    {
        return new SecurityTestService(
            $this->session,
            $this->auth,
            $this->file,
            $this->header,
            $this->error,
            $this->password,
            $this->secrets,
        );
    }

    /**
     * 設定讓大多數子測試通過的 mock 行為.
     */
    private function configureHappyPathMocks(): void
    {
        $plain = 'TestPassword123!';
        $this->session->shouldReceive('initializeSecureSession');
        $this->auth->shouldReceive('hasPermission')->andReturn(true);
        $this->auth->shouldReceive('can')->andReturn(true);
        $this->file->shouldReceive('sanitizeFileName')->andReturn('cleaned.txt');
        $this->header->shouldReceive('setSecurityHeaders');
        $this->error->shouldReceive('handleException')->andReturn(['error' => 'ok']);
        $this->password->shouldReceive('hashPassword')->with($plain)->andReturn(password_hash($plain, PASSWORD_DEFAULT));
        $this->password->shouldReceive('calculatePasswordStrength')->with('123456')->andReturn([]);
        $this->password->shouldReceive('calculatePasswordStrength')->with('StrongP@ssw0rd123!')->andReturn(['score' => 95]);
        $this->secrets->shouldReceive('load');
        $this->secrets->shouldReceive('validateEnvFile')->andReturn([]);
    }

    /**
     * 取得結果中的測試項目清單.
     *
     * @param array<string, mixed> $results 測試結果
     *
     * @return array<int, array<string, mixed>> 測試項目
     */
    private function entriesOf(array $results): array
    {
        /** @var array<int, array<string, mixed>> $entries */
        $entries = $results['tests'];

        return $entries;
    }

    /**
     * 取得指定索引項目的狀態.
     *
     * @param array<string, mixed> $results 測試結果
     */
    private function statusOf(array $results, int $index): string
    {
        $entry = $this->entriesOf($results)[$index] ?? [];

        return $this->toStr($entry['status'] ?? '');
    }

    /**
     * 取得指定索引項目的訊息.
     *
     * @param array<string, mixed> $results 測試結果
     */
    private function messageOf(array $results, int $index): string
    {
        $entry = $this->entriesOf($results)[$index] ?? [];

        return $this->toStr($entry['message'] ?? '');
    }

    /**
     * 取得通過數量.
     *
     * @param array<string, mixed> $results 測試結果
     */
    private function passedOf(array $results): int
    {
        return $this->toInt($results['passed'] ?? 0);
    }

    /**
     * 取得失敗數量.
     *
     * @param array<string, mixed> $results 測試結果
     */
    private function failedOf(array $results): int
    {
        return $this->toInt($results['failed'] ?? 0);
    }

    /**
     * 取得測試類別名稱.
     *
     * @param array<string, mixed> $results 測試結果
     */
    private function nameOf(array $results): string
    {
        return $this->toStr($results['test_name'] ?? '');
    }

    /**
     * 安全地將混合型別轉為字串.
     */
    private function toStr(mixed $value): string
    {
        return is_scalar($value) || $value === null ? (string) $value : '';
    }

    /**
     * 安全地將混合型別轉為整數.
     */
    private function toInt(mixed $value): int
    {
        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);
    }
}
