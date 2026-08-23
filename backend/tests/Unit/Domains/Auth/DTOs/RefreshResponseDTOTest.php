<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\DTOs;

use App\Domains\Auth\DTOs\RefreshResponseDTO;
use App\Domains\Auth\ValueObjects\TokenPair;
use DateTimeImmutable;
use Tests\Support\UnitTestCase;

/**
 * 權杖更新回應 DTO 單元測試.
 */
final class RefreshResponseDTOTest extends UnitTestCase
{
    public function testConstructAndToArray(): void
    {
        $now = new DateTimeImmutable();
        $accessExpires = $now->modify('+1 hour');
        $refreshExpires = $now->modify('+7 days');
        $validJwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgN_pGMstVEMm2Ym_sQxhENkJndk_n_K5v_E_5G8';
        $validRefresh = 'valid_refresh_token_longer_than_16_chars';
        $tokens = new TokenPair($validJwt, $validRefresh, $accessExpires, $refreshExpires, 'Bearer');
        $expiresAt = $accessExpires->getTimestamp();

        $dto = new RefreshResponseDTO(
            tokens: $tokens,
            userId: 5,
            expiresAt: $expiresAt,
            sessionId: 'sess-abc',
            permissions: ['posts.create'],
        );

        $this->assertSame($tokens, $dto->tokens);
        $this->assertSame(5, $dto->userId);
        $this->assertSame($expiresAt, $dto->expiresAt);
        $this->assertSame('sess-abc', $dto->sessionId);
        $this->assertSame(['posts.create'], $dto->permissions);

        $array = $dto->toArray();
        $this->assertSame($validJwt, $array['access_token']);
        $this->assertSame($validRefresh, $array['refresh_token']);
        $this->assertSame(5, $array['user_id']);
        $this->assertSame('sess-abc', $array['session_id']);
        $this->assertSame(['posts.create'], $array['permissions']);
    }
}
