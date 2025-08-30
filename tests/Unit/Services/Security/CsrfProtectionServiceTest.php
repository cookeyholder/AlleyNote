<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Domains\Security\Services\Core\CsrfProtectionService;
use App\Shared\Exceptions\CsrfTokenException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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
        $this->assertEquals($token, (is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['csrf_token'] : (is_object($_SESSION) ? $_SESSION->csrf_token : null)))) ? (is_array($_SESSION) ? $_SESSION['csrf_token'] : (is_object($_SESSION) ? $_SESSION->csrf_token : null)) : null);
        $this->assertIsInt((is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['csrf_token_time'] : (is_object($_SESSION) ? $_SESSION->csrf_token_time : null)))) ? (is_array($_SESSION) ? $_SESSION['csrf_token_time'] : (is_object($_SESSION) ? $_SESSION->csrf_token_time : null)) : null);
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
        (is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null))[$token] = time() - 3601;
        (is_array($_SESSION) ? $_SESSION['csrf_token_time'] : (is_object($_SESSION) ? $_SESSION->csrf_token_time : null)) = time() - 3601; // 也設定單一權杖時間以防萬一

        $this->expectException(CsrfTokenException::class);
        $this->expectExceptionMessage('CSRF token 已過期');

        $this->service->validateToken($token);
    }

    #[Test]
    public function updatesTokenAfterSuccessfulValidation(): void
    {
        $token = $this->service->generateToken();
        $oldToken = (is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['csrf_token'] : (is_object($_SESSION) ? $_SESSION->csrf_token : null)))) ? (is_array($_SESSION) ? $_SESSION['csrf_token'] : (is_object($_SESSION) ? $_SESSION->csrf_token : null)) : null;

        $this->service->validateToken($token);

        $this->assertNotEquals($oldToken, (is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['csrf_token'] : (is_object($_SESSION) ? $_SESSION->csrf_token : null)))) ? (is_array($_SESSION) ? $_SESSION['csrf_token'] : (is_object($_SESSION) ? $_SESSION->csrf_token : null)) : null);
    }

    #[Test]
    public function initializesTokenPool(): void
    {
        $this->service->initializeTokenPool();

        $this->assertArrayHasKey('csrf_token_pool', $_SESSION);
        $this->assertIsArray((is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)))) ? (is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)) : null);
        $this->assertGreaterThan(0, count((is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)))) ? (is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)) : null));
        $this->assertLessThanOrEqual(5, count((is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)))) ? (is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)) : null)); // TOKEN_POOL_SIZE = 5
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

        $poolBefore = (is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)))) ? (is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)) : null;
        $this->assertArrayHasKey($token, $poolBefore);

        $this->service->validateToken($token);

        $poolAfter = (is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)))) ? (is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)) : null;
        $this->assertArrayNotHasKey($token, $poolAfter);
    }

    #[Test]
    public function cleansExpiredTokensFromPool(): void
    {
        $this->service->initializeTokenPool();

        // 手動添加過期權杖
        $expiredToken = bin2hex(random_bytes(32));
        (is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null))[$expiredToken] = time() - 3601; // 超過1小時

        $this->service->generateToken(); // 這會觸發清理

        $this->assertArrayNotHasKey($expiredToken, (is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)))) ? (is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)) : null);
    }

    #[Test]
    public function limitsTokenPoolSize(): void
    {
        $this->service->initializeTokenPool();

        // 生成超過池大小限制的權杖
        for ($i = 0; $i < 10; $i++) {
            $this->service->generateToken();
        }

        $this->assertLessThanOrEqual(5, count((is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)))) ? (is_array($_SESSION) ? $_SESSION['csrf_token_pool'] : (is_object($_SESSION) ? $_SESSION->csrf_token_pool : null)) : null));
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

        $this->assertTrue((is_array($status) && isset((is_array($status) ? $status['enabled'] : (is_object($status) ? $status->enabled : null)))) ? (is_array($status) ? $status['enabled'] : (is_object($status) ? $status->enabled : null)) : null);
        $this->assertGreaterThan(0, (is_array($status) && isset((is_array($status) ? $status['size'] : (is_object($status) ? $status->size : null)))) ? (is_array($status) ? $status['size'] : (is_object($status) ? $status->size : null)) : null);
        $this->assertEquals(5, (is_array($status) && isset((is_array($status) ? $status['max_size'] : (is_object($status) ? $status->max_size : null)))) ? (is_array($status) ? $status['max_size'] : (is_object($status) ? $status->max_size : null)) : null);
        $this->assertIsArray((is_array($status) && isset((is_array($status) ? $status['tokens'] : (is_object($status) ? $status->tokens : null)))) ? (is_array($status) ? $status['tokens'] : (is_object($status) ? $status->tokens : null)) : null);
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
