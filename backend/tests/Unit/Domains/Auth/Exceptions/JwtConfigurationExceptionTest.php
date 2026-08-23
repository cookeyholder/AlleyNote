<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Exceptions;

use App\Domains\Auth\Exceptions\JwtConfigurationException;
use Tests\Support\UnitTestCase;

/**
 * JWT 配置例外單元測試.
 */
final class JwtConfigurationExceptionTest extends UnitTestCase
{
    public function testConstructAndGetters(): void
    {
        $e = new JwtConfigurationException('配置錯誤', JwtConfigurationException::INVALID_KEY_FORMAT);

        $this->assertSame('配置錯誤', $e->getMessage());
        $this->assertSame(JwtConfigurationException::ERROR_CODE, $e->getCode());
        $this->assertSame(JwtConfigurationException::INVALID_KEY_FORMAT, $e->getReason());
        $this->assertSame('JWT 金鑰格式錯誤，請檢查金鑰檔案格式。', $e->getUserFriendlyMessage());
    }

    public function testUserFriendlyMessages(): void
    {
        $map = [
            JwtConfigurationException::KEY_FILE_NOT_READABLE => 'JWT 金鑰檔案無法讀取，請檢查檔案權限和路徑。',
            JwtConfigurationException::KEY_FILE_READ_ERROR   => 'JWT 金鑰檔案無法讀取，請檢查檔案權限和路徑。',
            JwtConfigurationException::KEY_MISMATCH          => 'JWT 私鑰和公鑰不匹配，請檢查金鑰對。',
            JwtConfigurationException::MISSING_CONFIGURATION => 'JWT 配置缺失，請檢查環境變數設定。',
            JwtConfigurationException::INVALID_TTL           => 'JWT Token 存活時間設定無效。',
            JwtConfigurationException::INVALID_ALGORITHM     => 'JWT 演算法設定無效。',
            'other'                                          => 'JWT 配置錯誤，請聯絡系統管理員。',
        ];

        foreach ($map as $reason => $msg) {
            $e = new JwtConfigurationException('msg', $reason);
            $this->assertSame($msg, $e->getUserFriendlyMessage());
        }
    }

    public function testStaticFactories(): void
    {
        $f1 = JwtConfigurationException::invalidKeyFormat('details');
        $this->assertSame(JwtConfigurationException::INVALID_KEY_FORMAT, $f1->getReason());
        $this->assertStringContainsString('details', $f1->getMessage());

        $f2 = JwtConfigurationException::keyFileNotReadable('/path/to/key');
        $this->assertSame(JwtConfigurationException::KEY_FILE_NOT_READABLE, $f2->getReason());

        $f3 = JwtConfigurationException::invalidPrivateKeyFormat();
        $this->assertSame(JwtConfigurationException::INVALID_PRIVATE_KEY_FORMAT, $f3->getReason());

        $f4 = JwtConfigurationException::invalidPublicKeyFormat();
        $this->assertSame(JwtConfigurationException::INVALID_PUBLIC_KEY_FORMAT, $f4->getReason());

        $f5 = JwtConfigurationException::keyMismatch();
        $this->assertSame(JwtConfigurationException::KEY_MISMATCH, $f5->getReason());
    }
}
