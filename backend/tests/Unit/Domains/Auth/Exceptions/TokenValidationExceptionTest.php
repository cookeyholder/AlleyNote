<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Exceptions;

use App\Domains\Auth\Exceptions\TokenValidationException;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * Token 驗證例外單元測試.
 */
final class TokenValidationExceptionTest extends UnitTestCase
{
    public function testConstructAndGetters(): void
    {
        $prev = new RuntimeException('inner');
        $e = new TokenValidationException(
            message: '驗證失敗',
            reason: TokenValidationException::INVALID_SIGNATURE,
            previous: $prev,
            additionalContext: ['extra' => 123],
        );

        $this->assertSame('驗證失敗', $e->getMessage());
        $this->assertSame(TokenValidationException::ERROR_CODE, $e->getCode());
        $this->assertSame(TokenValidationException::INVALID_SIGNATURE, $e->getReason());
        $this->assertSame($prev, $e->getPrevious());
        $this->assertSame('Token 簽名驗證失敗，請重新登入。', $e->getUserFriendlyMessage());
    }

    public function testUserFriendlyMessagesForDifferentReasons(): void
    {
        $reasons = [
            TokenValidationException::INVALID_ISSUER        => 'Token 發行者無效，請重新登入。',
            TokenValidationException::INVALID_AUDIENCE      => 'Token 受眾無效，請重新登入。',
            TokenValidationException::ALGORITHM_NOT_ALLOWED => 'Token 演算法不被允許，請重新登入。',
            TokenValidationException::KEY_NOT_FOUND         => 'Token 驗證金鑰不存在，請重新登入。',
            'unknown_reason'                                => 'Token 驗證失敗，請重新登入。',
        ];

        foreach ($reasons as $reason => $expectedMessage) {
            $e = new TokenValidationException('msg', $reason);
            $this->assertSame($expectedMessage, $e->getUserFriendlyMessage());
        }
    }

    public function testStaticFactoryMethods(): void
    {
        $sigEx = TokenValidationException::invalidSignature();
        $this->assertSame(TokenValidationException::INVALID_SIGNATURE, $sigEx->getReason());

        $issEx = TokenValidationException::invalidIssuer('exp_iss', 'act_iss');
        $this->assertSame(TokenValidationException::INVALID_ISSUER, $issEx->getReason());
        $this->assertStringContainsString('期望: exp_iss，實際: act_iss', $issEx->getMessage());

        $audEx = TokenValidationException::invalidAudience('exp_aud', 'act_aud');
        $this->assertSame(TokenValidationException::INVALID_AUDIENCE, $audEx->getReason());
        $this->assertStringContainsString('期望: exp_aud，實際: act_aud', $audEx->getMessage());
    }
}
