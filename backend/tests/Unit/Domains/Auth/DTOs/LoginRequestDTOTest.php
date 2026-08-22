<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\DTOs;

use App\Domains\Auth\DTOs\LoginRequestDTO;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

/**
 * 登入請求 DTO 單元測試.
 */
final class LoginRequestDTOTest extends UnitTestCase
{
    /**
     * 測試建構與 toArray.
     */
    public function testConstructAndToArray(): void
    {
        $dto = new LoginRequestDTO(
            email: 'user@example.com',
            password: 'SecretPassword!',
            rememberMe: true,
            scopes: ['read', 'write'],
        );

        $this->assertSame('user@example.com', $dto->email);
        $this->assertSame('SecretPassword!', $dto->password);
        $this->assertTrue($dto->rememberMe);
        $this->assertSame(['read', 'write'], $dto->scopes);

        $array = $dto->toArray();
        $this->assertSame('[REDACTED]', $array['password']);
        $this->assertSame('user@example.com', $array['email']);
        $this->assertTrue($array['remember_me']);
        $this->assertSame(['read', 'write'], $array['scopes']);
    }

    /**
     * 測試 fromArray.
     */
    public function testFromArray(): void
    {
        $dto = LoginRequestDTO::fromArray([
            'email'       => 'test@example.com',
            'password'    => 'mypass',
            'remember_me' => true,
            'scopes'      => ['posts:read'],
        ]);

        $this->assertSame('test@example.com', $dto->email);
        $this->assertSame('mypass', $dto->password);
        $this->assertTrue($dto->rememberMe);
        $this->assertSame(['posts:read'], $dto->scopes);
    }

    /**
     * 測試無效 Scope 拋出 InvalidArgumentException.
     */
    public function testInvalidScopes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scopes must be a non-empty string list');

        new LoginRequestDTO('a@b.com', 'pass', false, ['']);
    }
}
