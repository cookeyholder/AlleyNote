<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\ValueObjects;

use App\Domains\Auth\ValueObjects\Password;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

/**
 * 密碼值物件單元測試.
 */
final class PasswordTest extends UnitTestCase
{
    /**
     * 測試從明文建立 Password 與驗證.
     */
    public function testFromPlainTextAndVerify(): void
    {
        $plain = 'ValidP@ssword123';
        $password = Password::fromPlainText($plain);

        $this->assertNotEmpty($password->getHash());
        $this->assertTrue($password->verify($plain));
        $this->assertFalse($password->verify('WrongPassword123'));
    }

    /**
     * 測試從已雜湊字串建立 Password.
     */
    public function testFromHash(): void
    {
        $hash = password_hash('ValidP@ssword123', PASSWORD_ARGON2ID);
        $password = Password::fromHash($hash);

        $this->assertSame($hash, $password->getHash());
        $this->assertTrue($password->verify('ValidP@ssword123'));
    }

    /**
     * 測試空雜湊拋出 InvalidArgumentException.
     */
    public function testFromEmptyHashThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('雜湊密碼不能為空');
        Password::fromHash('');
    }

    /**
     * 測試明文密碼無效規則拋出 InvalidArgumentException.
     */
    public function testInvalidPlainTextThrowsException(): void
    {
        // 空密碼
        try {
            Password::fromPlainText('   ');
            $this->fail('應該拋出空密碼例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('密碼不能為空', $e->getMessage());
        }

        // 太短 (< 8)
        try {
            Password::fromPlainText('Ab1!');
            $this->fail('應該拋出太短密碼例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('密碼至少需要 8 個字元', $e->getMessage());
        }

        // 太長 (> 100)
        try {
            Password::fromPlainText(str_repeat('Aa1!', 30));
            $this->fail('應該拋出太長密碼例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('密碼不能超過 100 個字元', $e->getMessage());
        }

        // 缺少強度（無大寫、小寫或數字）
        try {
            Password::fromPlainText('alllowercase123');
            $this->fail('應該拋出缺少大寫字母例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('密碼必須包含至少一個大寫字母', $e->getMessage());
        }
    }

    /**
     * 測試 needsRehash 與 rehash.
     */
    public function testRehash(): void
    {
        $plain = 'ValidP@ssword123';
        $password = Password::fromPlainText($plain);

        // Argon2id 雜湊不需要重新雜湊
        $this->assertFalse($password->needsRehash());

        // 成功 rehash
        $rehashed = $password->rehash($plain);
        $this->assertInstanceOf(Password::class, $rehashed);
        $this->assertTrue($rehashed->verify($plain));

        // 錯誤密碼 rehash 拋出例外
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('密碼驗證失敗，無法重新雜湊');
        $password->rehash('WrongPass123!');
    }

    /**
     * 測試 jsonSerialize 與 toArray.
     */
    public function testJsonSerializeAndToArray(): void
    {
        $password = Password::fromPlainText('ValidP@ssword123');

        $this->assertSame('********', $password->jsonSerialize());
        $this->assertSame(json_encode('********'), json_encode($password));

        $array = $password->toArray();
        $this->assertSame('********', $array['password']);
        $this->assertArrayHasKey('algorithm', $array);
    }
}
