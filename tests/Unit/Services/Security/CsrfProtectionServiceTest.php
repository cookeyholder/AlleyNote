<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Domains\Security\Services\Core\CsrfProtectionService;
use App\Shared\Exceptions\CsrfTokenException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CsrfProtectionServiceTest extends TestCase
{
    private CsrfProtectionService $service;

    protected function setUp(): void
    {
        $this->service = new CsrfProtectionService();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    #[Test]


    public function generatesValidToken(): void
    {
        $token = $this->service->generateToken();

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex characters
        $this->assertEquals($token, $_SESSION['csrf_token']);
        $this->assertIsInt($_SESSION['csrf_token_time']);
    }

    #[Test]


    public function validatesCorrectToken(): void
    {
        $token = $this->service->generateToken();

        $this->expectNotToPerformAssertions();
        $this->service->validateToken($token);
    }

    #[Test]


    public function throwsExceptionForEmptyToken(): void
    {
        $this->expectException(CsrfTokenException::class);
        $this->expectExceptionMessage('缺少 CSRF token');

        $this->service->validateToken(null);
    }

    #[Test]


    public function throwsExceptionForInvalidToken(): void
    {
        $this->service->generateToken();

        $this->expectException(CsrfTokenException::class);
        $this->expectExceptionMessage('CSRF token 驗證失敗');

        $this->service->validateToken('invalid_token');
    }

    #[Test]


    public function throwsExceptionForExpiredToken(): void
    {
        $token = $this->service->generateToken();

        // 設定權杖池中的時間為過期（超過1小時前）
        $_SESSION['csrf_token_pool'][$token] = time() - 3601;
        $_SESSION['csrf_token_time'] = time() - 3601; // 也設定單一權杖時間以防萬一

        $this->expectException(CsrfTokenException::class);
        $this->expectExceptionMessage('CSRF token 已過期');

        $this->service->validateToken($token);
    }

    #[Test]


    public function updatesTokenAfterSuccessfulValidation(): void
    {
        $token = $this->service->generateToken();
        $oldToken = $_SESSION['csrf_token'];

        $this->service->validateToken($token);

        $this->assertNotEquals($oldToken, $_SESSION['csrf_token']);
    }

    #[Test]


    public function initializesTokenPool(): void
    {
        $this->service->initializeTokenPool();

        $this->assertArrayHasKey('csrf_token_pool', $_SESSION);
        $this->assertIsArray($_SESSION['csrf_token_pool']);
        $this->assertGreaterThan(0, count($_SESSION['csrf_token_pool']));
        $this->assertLessThanOrEqual(5, count($_SESSION['csrf_token_pool'])); // TOKEN_POOL_SIZE = 5
    }

    #[Test]


    public function supportsMultipleValidTokensInPool(): void
    {
        $this->service->initializeTokenPool();

        // 生成多個權杖
        $token1 = $this->service->generateToken();
        $token2 = $this->service->generateToken();
        $token3 = $this->service->generateToken();

        // 所有權杖都應該有效
        $this->assertTrue($this->service->isTokenValid($token1));
        $this->assertTrue($this->service->isTokenValid($token2));
        $this->assertTrue($this->service->isTokenValid($token3));
    }

    #[Test]


    public function validatesTokenFromPoolWithConstantTimeComparison(): void
    {
        $this->service->initializeTokenPool();
        $token = $this->service->generateToken();

        // 驗證應該成功，不拋出例外
        $this->expectNotToPerformAssertions();
        $this->service->validateToken($token);
    }

    #[Test]


    public function removesTokenFromPoolAfterUse(): void
    {
        $this->service->initializeTokenPool();
        $token = $this->service->generateToken();

        $poolBefore = $_SESSION['csrf_token_pool'];
        $this->assertArrayHasKey($token, $poolBefore);

        $this->service->validateToken($token);

        $poolAfter = $_SESSION['csrf_token_pool'];
        $this->assertArrayNotHasKey($token, $poolAfter);
    }

    #[Test]


    public function cleansExpiredTokensFromPool(): void
    {
        $this->service->initializeTokenPool();

        // 手動添加過期權杖
        $expiredToken = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_pool'][$expiredToken] = time() - 3601; // 超過1小時

        $this->service->generateToken(); // 這會觸發清理

        $this->assertArrayNotHasKey($expiredToken, $_SESSION['csrf_token_pool']);
    }

    #[Test]


    public function limitsTokenPoolSize(): void
    {
        $this->service->initializeTokenPool();

        // 生成超過池大小限制的權杖
        for ($i = 0; $i < 10; $i++) {
            $this->service->generateToken();
        }

        $this->assertLessThanOrEqual(5, count($_SESSION['csrf_token_pool']));
    }

    #[Test]


    public function getTokenPoolStatusReturnsCorrectInfo(): void
    {
        $this->service->initializeTokenPool();

        $status = $this->service->getTokenPoolStatus();

        $this->assertArrayHasKey('enabled', $status);
        $this->assertArrayHasKey('size', $status);
        $this->assertArrayHasKey('max_size', $status);
        $this->assertArrayHasKey('tokens', $status);

        $this->assertTrue($status['enabled']);
        $this->assertGreaterThan(0, $status['size']);
        $this->assertEquals(5, $status['max_size']);
        $this->assertIsArray($status['tokens']);
    }

    #[Test]


    public function fallsBackToSingleTokenModeWhenPoolNotInitialized(): void
    {
        // 不初始化權杖池，使用舊的單一權杖模式
        $token = $this->service->generateToken();

        $this->expectNotToPerformAssertions();
        $this->service->validateToken($token);
    }

    #[Test]


    public function isTokenValidReturnsFalseForInvalidToken(): void
    {
        $this->service->generateToken();

        $this->assertFalse($this->service->isTokenValid('invalid_token'));
        $this->assertFalse($this->service->isTokenValid(null));
        $this->assertFalse($this->service->isTokenValid(''));
    }
}
