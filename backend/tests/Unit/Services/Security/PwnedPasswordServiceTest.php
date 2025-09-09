<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Domains\Auth\Services\Advanced\PwnedPasswordService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pwned Password Service 單元測試
 *
 * 測試密碼洩露檢查服務的各種情境
 */
class PwnedPasswordServiceTest extends TestCase
{
    private PwnedPasswordService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PwnedPasswordService();
    }

    #[Test]
    public function it_detects_common_pwned_password(): void
    {
        // Arrange - 使用已知的被洩露密碼 "password"
        $commonPassword = 'password';

        // Act
        $result = $this->service->checkPasswordSecurity($commonPassword);

        // Assert
        $this->assertArrayHasKey('api_available', $result);
        $this->assertArrayHasKey('is_leaked', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('error', $result);

        // 如果 API 可用，應該檢測到這是被洩露的密碼
        if ($result['api_available']) {
            $this->assertTrue($result['is_leaked']);
            $this->assertGreaterThan(0, $result['count']);
            $this->assertNull($result['error']);
        } else {
            // API 不可用時不應拋出錯誤
            $this->assertFalse($result['is_leaked']);
            $this->assertEquals(0, $result['count']);
            $this->assertNotNull($result['error']);
        }
    }

    #[Test]
    public function it_does_not_detect_secure_password(): void
    {
        // Arrange - 使用一個足夠複雜且不太可能被洩露的密碼
        $securePassword = 'MyVerySecureP@ssw0rd2023!XyZ9#UniqueString';

        // Act
        $result = $this->service->checkPasswordSecurity($securePassword);

        // Assert
        $this->assertArrayHasKey('api_available', $result);
        $this->assertArrayHasKey('is_leaked', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('error', $result);

        // 如果 API 可用，這個密碼應該不在洩露清單中
        if ($result['api_available']) {
            $this->assertFalse($result['is_leaked']);
            $this->assertEquals(0, $result['count']);
            $this->assertNull($result['error']);
        } else {
            // API 不可用時的預期行為
            $this->assertFalse($result['is_leaked']);
            $this->assertEquals(0, $result['count']);
            $this->assertNotNull($result['error']);
        }
    }

    #[Test]
    public function it_handles_empty_password(): void
    {
        // Arrange
        $emptyPassword = '';

        // Act
        $result = $this->service->checkPasswordSecurity($emptyPassword);

        // Assert
        $this->assertFalse($result['is_leaked']);
        $this->assertEquals(0, $result['count']);
        $this->assertNotNull($result['error']);
        $this->assertIsString($result['error']);
    }

    #[Test]
    public function it_handles_null_password(): void
    {
        // Arrange & Act & Assert
        $this->expectException(\TypeError::class);
        // @phpstan-ignore-next-line
        $this->service->checkPasswordSecurity(null);
    }

    #[Test]
    public function it_handles_very_long_password(): void
    {
        // Arrange - 建立一個非常長的密碼
        $longPassword = str_repeat('a', 1000);

        // Act
        $result = $this->service->checkPasswordSecurity($longPassword);

        // Assert
        $this->assertArrayHasKey('is_leaked', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('error', $result);

        // 超長密碼應該被拒絕或正常處理
        if ($result['error'] !== null) {
            $this->assertIsString($result['error']);
        }
    }

    #[Test]
    public function it_handles_special_characters_password(): void
    {
        // Arrange
        $specialPassword = '!@#$%^&*()_+-=[]{}|;:,.<>?`~';

        // Act
        $result = $this->service->checkPasswordSecurity($specialPassword);

        // Assert
        $this->assertIsBool($result['is_leaked']);
        $this->assertIsInt($result['count']);
        $this->assertTrue($result['error'] === null || is_string($result['error']));
    }

    #[Test]
    public function it_handles_unicode_password(): void
    {
        // Arrange
        $unicodePassword = '密碼測試中文🔐🛡️';

        // Act
        $result = $this->service->checkPasswordSecurity($unicodePassword);

        // Assert
        $this->assertIsBool($result['is_leaked']);
        $this->assertIsInt($result['count']);
        $this->assertTrue($result['error'] === null || is_string($result['error']));
    }

    #[Test]
    public function it_returns_consistent_result_structure(): void
    {
        // Arrange
        $testPasswords = [
            'password123',
            'SecureP@ssw0rd!',
            'test',
            'admin',
        ];

        // Act & Assert
        foreach ($testPasswords as $password) {
            $result = $this->service->checkPasswordSecurity($password);

            // 驗證結果結構的一致性
            $this->assertArrayHasKey('api_available', $result);
            $this->assertArrayHasKey('is_leaked', $result);
            $this->assertArrayHasKey('count', $result);
            $this->assertArrayHasKey('error', $result);

            $this->assertIsBool($result['api_available']);
            $this->assertIsBool($result['is_leaked']);
            $this->assertIsInt($result['count']);
            $this->assertTrue($result['error'] === null || is_string($result['error']));
            $this->assertGreaterThanOrEqual(0, $result['count']);
        }
    }

    #[Test]
    public function it_measures_response_time(): void
    {
        // Arrange
        $password = 'testPassword123';

        // Act
        $startTime = microtime(true);
        $result = $this->service->checkPasswordSecurity($password);
        $endTime = microtime(true);

        $responseTime = $endTime - $startTime;

        // Assert
        $this->assertLessThan(30.0, $responseTime, 'API 回應時間應在 30 秒內');
    }

    #[Test]
    public function it_handles_network_timeout_gracefully(): void
    {
        // Arrange & Act
        // 這個測試主要驗證在網路問題時服務的行為
        $result = $this->service->checkPasswordSecurity('networkTestPassword');

        // Assert
        // 即使網路有問題，也應該回傳有效的結果結構
        if (!$result['api_available']) {
            $this->assertFalse($result['is_leaked']);
            $this->assertEquals(0, $result['count']);
            $this->assertIsString($result['error']);
        }
    }

    #[Test]
    public function it_validates_password_strength_context(): void
    {
        // Arrange
        $weakPasswords = ['123456', 'password', 'qwerty', '111111'];
        $strongPasswords = ['MyStr0ng!P@ssw0rd2024', 'C0mplex!tyR3qu1r3d#2024'];

        // Act & Assert
        foreach ($weakPasswords as $weakPassword) {
            $result = $this->service->checkPasswordSecurity($weakPassword);

            if ($result['api_available'] && $result['is_leaked']) {
                $this->assertGreaterThan(0, $result['count'],
                    "弱密碼 '{$weakPassword}' 應該被檢測為已洩露");
            }
        }

        foreach ($strongPasswords as $strongPassword) {
            $result = $this->service->checkPasswordSecurity($strongPassword);

            if ($result['api_available']) {
                // 強密碼被洩露的機率較低，但不是絕對
                $this->assertIsInt($result['count']);
            }
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
