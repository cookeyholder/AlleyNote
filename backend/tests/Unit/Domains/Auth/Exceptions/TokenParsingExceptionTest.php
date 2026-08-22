<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Exceptions;

use App\Domains\Auth\Exceptions\TokenParsingException;
use Tests\Support\UnitTestCase;

/**
 * Token 解析例外單元測試.
 */
final class TokenParsingExceptionTest extends UnitTestCase
{
    public function testConstructAndGetters(): void
    {
        $e = new TokenParsingException('解析失敗', TokenParsingException::INVALID_FORMAT);

        $this->assertSame('解析失敗', $e->getMessage());
        $this->assertSame(TokenParsingException::ERROR_CODE, $e->getCode());
        $this->assertSame(TokenParsingException::INVALID_FORMAT, $e->getReason());
        $this->assertSame('Token 格式無效，請提供正確格式的 JWT Token。', $e->getUserFriendlyMessage());
    }

    public function testUserFriendlyMessages(): void
    {
        $map = [
            TokenParsingException::EMPTY_TOKEN         => 'Token 不能為空，請提供有效的 Token。',
            TokenParsingException::JSON_DECODE_ERROR   => 'Token 內容格式錯誤，無法解析 JSON 資料。',
            TokenParsingException::BASE64_DECODE_ERROR => 'Token 編碼格式錯誤，無法解析 Base64 資料。',
            'other_reason'                             => 'Token 解析失敗，請提供有效的 Token。',
        ];

        foreach ($map as $reason => $msg) {
            $e = new TokenParsingException('msg', $reason);
            $this->assertSame($msg, $e->getUserFriendlyMessage());
        }
    }

    public function testStaticFactories(): void
    {
        $e1 = TokenParsingException::emptyToken();
        $this->assertSame(TokenParsingException::EMPTY_TOKEN, $e1->getReason());

        $e2 = TokenParsingException::invalidFormat('detail');
        $this->assertSame(TokenParsingException::INVALID_FORMAT, $e2->getReason());
        $this->assertStringContainsString('detail', $e2->getMessage());

        $e3 = TokenParsingException::jsonDecodeError();
        $this->assertSame(TokenParsingException::JSON_DECODE_ERROR, $e3->getReason());

        $e4 = TokenParsingException::base64DecodeError();
        $this->assertSame(TokenParsingException::BASE64_DECODE_ERROR, $e4->getReason());
    }
}
