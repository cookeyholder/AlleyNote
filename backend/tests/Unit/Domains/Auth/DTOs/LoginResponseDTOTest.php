<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\DTOs;

use App\Domains\Auth\DTOs\LoginResponseDTO;
use App\Domains\Auth\ValueObjects\TokenPair;
use DateTimeImmutable;
use Tests\Support\UnitTestCase;

/**
 * 登入回應 DTO 單元測試.
 */
final class LoginResponseDTOTest extends UnitTestCase
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

        $dto = new LoginResponseDTO(
            tokens: $tokens,
            userId: 1,
            userEmail: 'user@example.com',
            expiresAt: $expiresAt,
            userName: 'JohnDoe',
            sessionId: 'sess-123',
            permissions: ['posts.view'],
            roles: [['name' => 'admin', 'display_name' => '管理員']],
        );

        $this->assertSame($tokens, $dto->tokens);
        $this->assertSame(1, $dto->userId);
        $this->assertSame('user@example.com', $dto->userEmail);
        $this->assertSame('JohnDoe', $dto->userName);
        $this->assertSame('sess-123', $dto->sessionId);
        $this->assertSame(['posts.view'], $dto->permissions);

        $array = $dto->toArray();
        $this->assertSame($validJwt, $array['access_token']);
        $this->assertSame($validRefresh, $array['refresh_token']);
        $this->assertSame('Bearer', $array['token_type']);
        $this->assertSame($expiresAt, $array['expires_at']);
        $this->assertSame(1, $array['user']['id']);
        $this->assertSame('user@example.com', $array['user']['email']);
        $this->assertSame('JohnDoe', $array['user']['name']);
        $this->assertSame('admin', $array['user']['role']);
        $this->assertSame('sess-123', $array['session_id']);
        $this->assertSame(['posts.view'], $array['permissions']);
    }
}
