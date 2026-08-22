<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\DTOs;

use App\Domains\Auth\DTOs\LogoutRequestDTO;
use Tests\Support\UnitTestCase;

/**
 * 登出請求 DTO 單元測試.
 */
final class LogoutRequestDTOTest extends UnitTestCase
{
    public function testConstructAndToArray(): void
    {
        $dto = new LogoutRequestDTO(
            accessToken: 'token-123',
            refreshToken: 'refresh-456',
            revokeAllTokens: true,
            sessionId: 'sess-789',
        );

        $this->assertSame('token-123', $dto->accessToken);
        $this->assertSame('refresh-456', $dto->refreshToken);
        $this->assertTrue($dto->revokeAllTokens);
        $this->assertSame('sess-789', $dto->sessionId);

        $array = $dto->toArray();
        $this->assertSame('[REDACTED]', $array['access_token']);
        $this->assertSame('[REDACTED]', $array['refresh_token']);
        $this->assertTrue($array['revoke_all_tokens']);
        $this->assertSame('sess-789', $array['session_id']);
    }

    public function testFromArray(): void
    {
        $dto = LogoutRequestDTO::fromArray([
            'access_token'      => 'token-abc',
            'revoke_all_tokens' => true,
        ]);

        $this->assertSame('token-abc', $dto->accessToken);
        $this->assertNull($dto->refreshToken);
        $this->assertTrue($dto->revokeAllTokens);
    }
}
