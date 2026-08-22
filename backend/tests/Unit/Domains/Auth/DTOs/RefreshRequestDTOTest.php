<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\DTOs;

use App\Domains\Auth\DTOs\RefreshRequestDTO;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

/**
 * 權杖更新請求 DTO 單元測試.
 */
final class RefreshRequestDTOTest extends UnitTestCase
{
    public function testConstructAndToArray(): void
    {
        $dto = new RefreshRequestDTO(
            refreshToken: 'refresh-xyz',
            scopes: ['user:read'],
        );

        $this->assertSame('refresh-xyz', $dto->refreshToken);
        $this->assertSame(['user:read'], $dto->scopes);

        $array = $dto->toArray();
        $this->assertSame('[REDACTED]', $array['refresh_token']);
        $this->assertSame(['user:read'], $array['scopes']);
    }

    public function testFromArray(): void
    {
        $dto = RefreshRequestDTO::fromArray([
            'refresh_token' => 'token-123',
            'scopes'        => ['profile'],
        ]);

        $this->assertSame('token-123', $dto->refreshToken);
        $this->assertSame(['profile'], $dto->scopes);
    }

    public function testInvalidScopesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scopes must be a non-empty string list');

        new RefreshRequestDTO('token', ['']);
    }
}
