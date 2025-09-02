<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Config;

use App\Shared\Config\EnvironmentConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EnvironmentConfigSimpleTest extends TestCase
{
    private string $testConfigPath;

    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        // 保存和清空所有相關的環境變數
        $envKeys = [
            'APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL', 'APP_KEY',
            'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
            'JWT_PRIVATE_KEY', 'JWT_PUBLIC_KEY',
            'FORCE_HTTPS', 'ADMIN_PASSWORD',
        ];

        foreach ($envKeys as $key) {
            $this->originalEnv[$key] = [
                'env' => $_ENV[$key] ?? null,
                'getenv' => getenv($key) ?: null,
            ];

            // 清空環境變數以確保從檔案載入
            unset($_ENV[$key]);
            putenv("{$key}=");
        }

        // 建立測試配置目錄
        $this->testConfigPath = sys_get_temp_dir() . '/alleynote-test-' . uniqid();
        mkdir($this->testConfigPath, 0o755, true);
    }

    protected function tearDown(): void
    {
        // 恢復原始環境變數
        foreach ($this->originalEnv as $key => $values) {
            if ($values['env'] === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $values['env'];
            }

            if ($values['getenv'] === null) {
                putenv("{$key}=");
            } else {
                putenv("{$key}={$values['getenv']}");
            }
        }

        // 清理測試配置目錄
        $this->removeDirectory($this->testConfigPath);

        parent::tearDown();
    }

    public function testConstructorAcceptsValidEnvironment(): void
    {
        $config = new EnvironmentConfig('development', $this->testConfigPath);

        $this->assertInstanceOf(EnvironmentConfig::class, $config);
    }

    public function testConstructorRejectsInvalidEnvironment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的環境: invalid');

        new EnvironmentConfig('invalid', $this->testConfigPath);
    }

    public function testBasicConfigOperations(): void
    {
        // 建立測試配置檔
        $envContent = "APP_NAME=TestApp\nAPP_ENV=testing\nDB_CONNECTION=sqlite\nDB_DATABASE=:memory:\nJWT_PRIVATE_KEY=test-key\nJWT_PUBLIC_KEY=test-pub\n";
        file_put_contents($this->testConfigPath . '/.env.testing', $envContent);

        $config = new EnvironmentConfig('testing', $this->testConfigPath);
        $config->load();

        $this->assertEquals('TestApp', $config->get('APP_NAME'));
        $this->assertEquals('testing', $config->get('APP_ENV'));
        $this->assertEquals('default-value', $config->get('NON_EXISTENT', 'default-value'));
    }

    public function testValidation(): void
    {
        // 使用記憶體資料庫配置以符合測試環境要求
        $envContent = "APP_NAME=TestApp\nAPP_ENV=testing\nDB_CONNECTION=sqlite\nDB_DATABASE=:memory:\nJWT_PRIVATE_KEY=test-key\nJWT_PUBLIC_KEY=test-pub\n";
        file_put_contents($this->testConfigPath . '/.env.testing', $envContent);

        $config = new EnvironmentConfig('testing', $this->testConfigPath);
        $config->load();

        // 檢查 DB_DATABASE 是否正確載入
        $dbDatabase = $config->get('DB_DATABASE');
        if ($dbDatabase !== ':memory:') {
            $this->fail('DB_DATABASE was not loaded correctly: ' . var_export($dbDatabase, true));
        }

        $errors = $config->validate();
        if (!empty($errors)) {
            $this->fail('Validation failed with errors: ' . implode(', ', $errors));
        }
        $this->assertEmpty($errors);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
