<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\Services\Secrets;

use App\Domains\Security\Services\Secrets\SecretsManager;
use App\Shared\Exceptions\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * SecretsManager 測試.
 */
#[CoversClass(SecretsManager::class)]
class SecretsManagerTest extends UnitTestCase
{
    private string $tmpDir;

    /** @var array<int, string> 測試中設定的環境變數名稱 */
    private array $envKeys = [];

    /** @var false|string APP_ENV 原始值（putenv 層） */
    private false|string $appEnvBackup = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appEnvBackup = getenv('APP_ENV');
        $this->tmpDir = sys_get_temp_dir() . '/secrets_manager_test_' . uniqid('', true);
        if (!mkdir($this->tmpDir, 0o700, true) && !is_dir($this->tmpDir)) {
            throw new RuntimeException('無法建立暫存目錄');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->envKeys as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
        $this->envKeys = [];
        // 還原 APP_ENV（putenv 與超全域兩層）
        if ($this->appEnvBackup === false) {
            putenv('APP_ENV');
            unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
        } else {
            putenv('APP_ENV=' . $this->appEnvBackup);
            $_ENV['APP_ENV'] = $this->appEnvBackup;
            $_SERVER['APP_ENV'] = $this->appEnvBackup;
        }
        foreach (glob($this->tmpDir . '/*') ?: [] as $file) {
            @unlink((string) $file);
        }
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    /**
     * 設定測試環境變數並記錄以供清理.
     */
    private function setEnv(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $this->envKeys[] = $key;
    }

    /**
     * 建立指向不存在檔案的管理器.
     */
    private function makeManager(string $envFile = 'missing.env'): SecretsManager
    {
        return new SecretsManager($this->tmpDir . '/' . $envFile);
    }

    /**
     * 建立含範例內容的 .env 檔案.
     *
     * @param array<int, string> $lines 以行內容為值的陣列
     * @param int $perms 檔案權限
     */
    private function writeEnvFile(array $lines, int $perms = 0o644): string
    {
        $path = $this->tmpDir . '/.env';
        file_put_contents($path, implode("\n", $lines));
        chmod($path, $perms);

        return $path;
    }

    #[Test]
    public function it_returns_default_for_missing_keys(): void
    {
        $manager = $this->makeManager();

        $this->assertNull($manager->get('SM_UNDEFINED_KEY'));
        $this->assertSame('fallback', $manager->get('SM_UNDEFINED_KEY', 'fallback'));
        $this->assertFalse($manager->has('SM_UNDEFINED_KEY'));
    }

    #[Test]
    public function get_prefers_real_environment_over_superglobals_and_file(): void
    {
        $this->setEnv('SM_PRIORITY', 'from_putenv');
        $file = $this->writeEnvFile(['SM_PRIORITY=from_file']);
        $manager = new SecretsManager($file);

        $this->assertSame('from_putenv', $manager->get('SM_PRIORITY'));

        // 移除 getenv 後，檔案載入的值生效
        putenv('SM_PRIORITY');
        $this->assertSame('from_file', $manager->get('SM_PRIORITY'));
    }

    #[Test]
    public function get_parses_booleans_null_and_numbers(): void
    {
        $file = $this->writeEnvFile([
            'SM_FLAG_ON=true',
            'SM_FLAG_OFF=false',
            'SM_NULL_VALUE=null',
            'SM_INT_VALUE=42',
            'SM_FLOAT_VALUE=3.5',
            'SM_TEXT="hello world"',
            "SM_SINGLE='quoted'",
        ]);
        $manager = new SecretsManager($file);

        $this->assertTrue($manager->get('SM_FLAG_ON'));
        $this->assertFalse($manager->get('SM_FLAG_OFF'));
        $this->assertNull($manager->get('SM_NULL_VALUE'));
        $this->assertSame(42, $manager->get('SM_INT_VALUE'));
        $this->assertSame(3.5, $manager->get('SM_FLOAT_VALUE'));
        $this->assertSame('hello world', $manager->get('SM_TEXT'));
        $this->assertSame('quoted', $manager->get('SM_SINGLE'));
    }

    #[Test]
    public function set_stores_value_in_all_layers(): void
    {
        $manager = $this->makeManager();
        $this->setEnv('SM_SET_KEY', 'unused');

        $manager->set('SM_SET_KEY', 'runtime-value');

        $this->assertTrue($manager->has('SM_SET_KEY'));
        $this->assertSame('runtime-value', $manager->get('SM_SET_KEY'));
        $this->assertSame('runtime-value', getenv('SM_SET_KEY'));
        $this->assertSame('runtime-value', $_ENV['SM_SET_KEY']);
        $this->assertSame('runtime-value', $_SERVER['SM_SET_KEY']);
    }

    #[Test]
    public function get_required_returns_value_or_throws(): void
    {
        $this->setEnv('SM_REQUIRED_KEY', 'secret-value');
        $manager = $this->makeManager();

        $this->assertSame('secret-value', $manager->getRequired('SM_REQUIRED_KEY'));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("必需的環境變數 'SM_MISSING_REQUIRED' 未設定");

        $manager->getRequired('SM_MISSING_REQUIRED');
    }

    #[Test]
    public function get_required_rejects_empty_string_values(): void
    {
        $this->setEnv('SM_EMPTY_KEY', '');
        $manager = $this->makeManager();

        $this->expectException(ValidationException::class);

        $manager->getRequired('SM_EMPTY_KEY');
    }

    #[Test]
    public function validate_required_secrets_reports_all_missing_keys(): void
    {
        $this->setEnv('SM_KNOWN', '1');
        $manager = $this->makeManager();

        try {
            $manager->validateRequiredSecrets([
                'non_string'   => 123,
                'SM_MISSING_A' => 'SM_MISSING_A',
                'SM_MISSING_B' => 'SM_MISSING_B',
                'SM_KNOWN'     => 'SM_KNOWN',
            ]);
            $this->fail('應拋出 ValidationException');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('缺少必需的環境變數: SM_MISSING_A, SM_MISSING_B', $e->getMessage());
        }
    }

    #[Test]
    public function validate_required_secrets_passes_when_all_present(): void
    {
        $this->setEnv('SM_PRESENT', 'value');
        $manager = $this->makeManager();

        $manager->validateRequiredSecrets(['SM_PRESENT' => 'SM_PRESENT']);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function environment_flags_default_to_production(): void
    {
        // 暫時移除 APP_ENV 以驗證預設行為（tearDown 會還原）
        putenv('APP_ENV');
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
        $manager = $this->makeManager();

        $this->assertTrue($manager->isProduction());
        $this->assertFalse($manager->isDevelopment());
    }

    #[Test]
    public function environment_flags_follow_app_env(): void
    {
        $this->setEnv('APP_ENV', 'development');
        $manager = $this->makeManager();

        $this->assertTrue($manager->isDevelopment());
        $this->assertFalse($manager->isProduction());

        putenv('APP_ENV=staging');
        $freshManager = new SecretsManager($this->tmpDir . '/missing2.env');
        $this->assertFalse($freshManager->isDevelopment());
        $this->assertFalse($freshManager->isProduction());
    }

    #[Test]
    public function generate_secret_produces_unique_hex_strings(): void
    {
        $manager = $this->makeManager();

        $first = $manager->generateSecret(16);
        $second = $manager->generateSecret(16);

        $this->assertSame(32, strlen($first));
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $first);
        $this->assertNotSame($first, $second);
    }

    #[Test]
    public function secrets_summary_redacts_sensitive_values(): void
    {
        $file = $this->writeEnvFile([
            'DB_PASSWORD=super-long-password-value',
            'APP_NAME=AlleyNote',
        ]);
        $manager = new SecretsManager($file);

        /** @var array<string, array<string, mixed>> $summary */
        $summary = $manager->getSecretsSummary();

        $this->assertArrayHasKey('DB_PASSWORD', $summary);
        $this->assertTrue((bool) $summary['DB_PASSWORD']['set']);
        $this->assertTrue((bool) $summary['DB_PASSWORD']['sensitive']);
        $this->assertSame('[REDACTED]', $this->toStr($summary['DB_PASSWORD']['value']));
        $this->assertGreaterThan(0, $this->toInt($summary['DB_PASSWORD']['length']));

        $this->assertArrayHasKey('APP_NAME', $summary);
        $this->assertFalse((bool) $summary['APP_NAME']['sensitive']);
        $this->assertSame('AlleyNote', $this->toStr($summary['APP_NAME']['value']));
    }

    #[Test]
    public function validate_env_file_reports_missing_file(): void
    {
        $manager = $this->makeManager();

        $issues = $manager->validateEnvFile();

        $this->assertSame(['.env 檔案不存在'], $issues);
    }

    #[Test]
    public function validate_env_file_accepts_well_formed_file(): void
    {
        $path = $this->writeEnvFile([
            '# 註解',
            '',
            'SITE_NAME=AlleyNote',
            'SITE_DESCRIPTION=AlleyNote 公告欄系統',
        ]);

        $manager = $this->makeManager('.env');
        $issues = $manager->validateEnvFile($path);

        $this->assertSame([], $issues);
    }

    #[Test]
    public function validate_env_file_collects_multiple_issue_types(): void
    {
        $path = $this->writeEnvFile([
            'GOOD_LINE=value',
            'MALFORMED_LINE_WITHOUT_EQUALS',
            'lowercase-key=x',
            'DB_PASSWORD=short',
            'API_KEY=password',
        ], 0o600);

        $manager = $this->makeManager('.env');
        $issues = $manager->validateEnvFile($path);

        // API_KEY=password 同時觸發「值過短」與「不安全預設值」；
        // lowercase-key 同時觸發「名稱格式不正確」與「值過短」（鍵含 key 視為敏感）
        $this->assertCount(6, $issues);
        $this->assertStringContainsString('格式錯誤', $this->toStr($issues[0]));
        $this->assertStringContainsString('環境變數名稱格式不正確 (lowercase-key)', $this->toStr($issues[1]));
        $this->assertStringContainsString('敏感環境變數 lowercase-key 值過短', $this->toStr($issues[2]));
        $this->assertStringContainsString('敏感環境變數 DB_PASSWORD 值過短', $this->toStr($issues[3]));
        $this->assertStringContainsString('敏感環境變數 API_KEY 值過短', $this->toStr($issues[4]));
        $this->assertStringContainsString('敏感環境變數 API_KEY 使用不安全的預設值', $this->toStr($issues[5]));
    }

    #[Test]
    public function validate_env_file_flags_insecure_permissions(): void
    {
        $path = $this->writeEnvFile(['SITE_MODE=fast'], 0o666);

        $manager = $this->makeManager('.env');
        $issues = $manager->validateEnvFile($path);

        $this->assertCount(1, $issues);
        $this->assertStringContainsString('檔案權限不安全 (666)', $this->toStr($issues[0]));
    }

    #[Test]
    public function load_is_idempotent_once_loaded(): void
    {
        $path = $this->writeEnvFile(['SM_LOAD_ONCE=loaded']);
        $manager = new SecretsManager($path);

        $manager->load();
        // 第二次 load() 不會重新讀取；此處僅驗證可重複呼叫且值仍在
        $manager->load();

        $this->assertSame('loaded', $manager->get('SM_LOAD_ONCE'));
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
