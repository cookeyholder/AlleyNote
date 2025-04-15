<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Services\Security\CsrfProtectionService;
use App\Exceptions\CsrfTokenException;
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

    /** @test */
    public function generatesValidToken(): void
    {
        $token = $this->service->generateToken();

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex characters
        $this->assertEquals($token, $_SESSION['csrf_token']);
        $this->assertIsInt($_SESSION['csrf_token_time']);
    }

    /** @test */
    public function validatesCorrectToken(): void
    {
        $token = $this->service->generateToken();

        $this->expectNotToPerformAssertions();
        $this->service->validateToken($token);
    }

    /** @test */
    public function throwsExceptionForEmptyToken(): void
    {
        $this->expectException(CsrfTokenException::class);
        $this->expectExceptionMessage('缺少 CSRF token');

        $this->service->validateToken(null);
    }

    /** @test */
    public function throwsExceptionForInvalidToken(): void
    {
        $this->service->generateToken();

        $this->expectException(CsrfTokenException::class);
        $this->expectExceptionMessage('CSRF token 驗證失敗');

        $this->service->validateToken('invalid_token');
    }

    /** @test */
    public function throwsExceptionForExpiredToken(): void
    {
        $token = $this->service->generateToken();
        $_SESSION['csrf_token_time'] = time() - 3601; // Set time to more than 1 hour ago

        $this->expectException(CsrfTokenException::class);
        $this->expectExceptionMessage('CSRF token 已過期');

        $this->service->validateToken($token);
    }

    /** @test */
    public function updatesTokenAfterSuccessfulValidation(): void
    {
        $token = $this->service->generateToken();
        $oldToken = $_SESSION['csrf_token'];

        $this->service->validateToken($token);

        $this->assertNotEquals($oldToken, $_SESSION['csrf_token']);
    }
}
