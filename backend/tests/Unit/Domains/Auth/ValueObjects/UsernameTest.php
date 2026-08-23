<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\ValueObjects;

use App\Domains\Auth\ValueObjects\Username;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

/**
 * 使用者名稱值物件單元測試.
 */
final class UsernameTest extends UnitTestCase
{
    /**
     * 測試有效使用者名稱與各公開方法.
     */
    public function testValidUsername(): void
    {
        $username = Username::fromString('John_Doe-123');

        $this->assertSame('John_Doe-123', $username->getValue());
        $this->assertSame(12, $username->getLength());
        $this->assertSame('john_doe-123', $username->toLowercase());
        $this->assertSame('John_Doe-123', $username->toString());
        $this->assertSame('John_Doe-123', (string) $username);
        $this->assertSame('John_Doe-123', $username->jsonSerialize());
        $this->assertSame(['username' => 'John_Doe-123', 'length' => 12], $username->toArray());

        $same = new Username('John_Doe-123');
        $this->assertTrue($username->equals($same));

        $differentCase = new Username('john_doe-123');
        $this->assertFalse($username->equals($differentCase));
        $this->assertTrue($username->equalsIgnoreCase('john_doe-123'));
    }

    /**
     * 測試無效的使用者名稱格式拋出 InvalidArgumentException.
     */
    public function testInvalidUsernameThrowsException(): void
    {
        // 空字串
        try {
            new Username('   ');
            $this->fail('應該拋出空字串例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('使用者名稱不能為空', $e->getMessage());
        }

        // 太短 (< 3)
        try {
            new Username('ab');
            $this->fail('應該拋出長度過短例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('至少需要 3 個字元', $e->getMessage());
        }

        // 太長 (> 50)
        try {
            new Username(str_repeat('a', 51));
            $this->fail('應該拋出長度過長例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('不能超過 50 個字元', $e->getMessage());
        }

        // 包含非法字元（如 @、空白、中文）
        try {
            new Username('invalid@user');
            $this->fail('應該拋出非法字元例外');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('只能包含字母、數字、底線和連字號', $e->getMessage());
        }
    }
}
