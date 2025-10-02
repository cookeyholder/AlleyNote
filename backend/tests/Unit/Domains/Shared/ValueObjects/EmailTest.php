<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Shared\ValueObjects;

use App\Domains\Shared\ValueObjects\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Email 值物件測試.
 */
class EmailTest extends TestCase
{
    public function test_can_create_valid_email(): void
    {
        $email = new Email('test@example.com');

        $this->assertInstanceOf(Email::class, $email);
        $this->assertEquals('test@example.com', $email->getValue());
    }

    public function test_email_is_converted_to_lowercase(): void
    {
        $email = new Email('Test@Example.COM');

        $this->assertEquals('test@example.com', $email->getValue());
    }

    public function test_can_create_from_string(): void
    {
        $email = Email::fromString('test@example.com');

        $this->assertInstanceOf(Email::class, $email);
    }

    public function test_throws_exception_for_empty_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email 不能為空');

        new Email('');
    }

    public function test_throws_exception_for_invalid_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的 Email 格式');

        new Email('invalid-email');
    }

    public function test_throws_exception_for_too_long_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // filter_var 會先檢查格式，所以超長 email 會得到格式錯誤訊息

        $longLocal = str_repeat('a', 240);
        $longEmail = $longLocal . '@example.com';
        new Email($longEmail);
    }

    public function test_can_get_local_part(): void
    {
        $email = new Email('user@example.com');

        $this->assertEquals('user', $email->getLocalPart());
    }

    public function test_can_get_domain(): void
    {
        $email = new Email('user@example.com');

        $this->assertEquals('example.com', $email->getDomain());
    }

    public function test_can_check_equality(): void
    {
        $email1 = new Email('test@example.com');
        $email2 = new Email('test@example.com');
        $email3 = new Email('other@example.com');

        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    public function test_can_mask_email(): void
    {
        $email = new Email('user@example.com');

        $masked = $email->mask();
        $this->assertEquals('u***r@example.com', $masked);
    }

    public function test_can_mask_short_email(): void
    {
        $email = new Email('ab@example.com');

        $masked = $email->mask();
        $this->assertEquals('a***@example.com', $masked);
    }

    public function test_can_convert_to_string(): void
    {
        $email = new Email('test@example.com');

        $this->assertEquals('test@example.com', $email->toString());
        $this->assertEquals('test@example.com', (string) $email);
    }

    public function test_can_json_serialize(): void
    {
        $email = new Email('test@example.com');

        $this->assertEquals('"test@example.com"', json_encode($email));
    }

    public function test_can_convert_to_array(): void
    {
        $email = new Email('user@example.com');

        $array = $email->toArray();
        $this->assertEquals('user@example.com', $array['email']);
        $this->assertEquals('user', $array['local_part']);
        $this->assertEquals('example.com', $array['domain']);
    }
}
