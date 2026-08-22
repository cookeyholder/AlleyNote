<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\DTOs;

use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\Validator;
use Tests\Support\UnitTestCase;

/**
 * 註冊使用者 DTO 單元測試.
 */
final class RegisterUserDTOTest extends UnitTestCase
{
    private function createValidator(): Validator
    {
        return new Validator();
    }

    /**
     * 測試有效資料註冊與屬性取得.
     */
    public function testValidRegistration(): void
    {
        $validator = $this->createValidator();
        $data = [
            'username'         => 'alice_dev',
            'email'            => 'alice@customcompany.org',
            'password'         => 'S3cur3Pass!',
            'confirm_password' => 'S3cur3Pass!',
            'user_ip'          => '192.168.1.100',
        ];

        $dto = new RegisterUserDTO($validator, $data);

        $this->assertSame('alice_dev', $dto->username);
        $this->assertSame('alice@customcompany.org', $dto->email);
        $this->assertSame('S3cur3Pass!', $dto->password);
        $this->assertSame('S3cur3Pass!', $dto->confirmPassword);
        $this->assertSame('192.168.1.100', $dto->userIp);

        $this->assertSame([
            'username' => 'alice_dev',
            'email'    => 'alice@customcompany.org',
            'password' => 'S3cur3Pass!',
            'user_ip'  => '192.168.1.100',
        ], $dto->toArray());

        $this->assertSame([
            'password'         => 'S3cur3Pass!',
            'confirm_password' => 'S3cur3Pass!',
        ], $dto->getPasswordData());

        $this->assertSame('alice_dev', $dto->getDisplayUsername());
        $this->assertSame('customcompany.org', $dto->getEmailDomain());
        $this->assertTrue($dto->isBusinessEmail());
    }

    /**
     * 測試一般公開信箱判斷為非企業郵箱.
     */
    public function testGmailIsNotBusinessEmail(): void
    {
        $validator = $this->createValidator();
        $data = [
            'username'         => 'bob_user',
            'email'            => 'bob@gmail.com',
            'password'         => 'S3cur3Pass!',
            'confirm_password' => 'S3cur3Pass!',
            'user_ip'          => '127.0.0.1',
        ];

        $dto = new RegisterUserDTO($validator, $data);
        $this->assertFalse($dto->isBusinessEmail());
    }

    /**
     * 測試密碼強度評分 (weak, medium, strong).
     */
    public function testPasswordStrengthLevels(): void
    {
        $validator = $this->createValidator();

        // strong
        $strongData = [
            'username'         => 'strong_user',
            'email'            => 'strong@example.com',
            'password'         => 'VeryStr0ngP@ssword!',
            'confirm_password' => 'VeryStr0ngP@ssword!',
            'user_ip'          => '127.0.0.1',
        ];
        $strongDto = new RegisterUserDTO($validator, $strongData);
        $this->assertSame('strong', $strongDto->getPasswordStrength());

        // medium
        $mediumData = [
            'username'         => 'medium_user',
            'email'            => 'medium@example.com',
            'password'         => 'MediumP1',
            'confirm_password' => 'MediumP1',
            'user_ip'          => '127.0.0.1',
        ];
        $mediumDto = new RegisterUserDTO($this->createValidator(), $mediumData);
        $this->assertSame('medium', $mediumDto->getPasswordStrength());
    }

    /**
     * 測試使用者名稱以數字開頭驗證失敗.
     */
    public function testUsernameStartsWithNumberFails(): void
    {
        $this->expectException(ValidationException::class);

        new RegisterUserDTO($this->createValidator(), [
            'username'         => '123badname',
            'email'            => 'user@example.com',
            'password'         => 'S3cur3Pass!',
            'confirm_password' => 'S3cur3Pass!',
            'user_ip'          => '127.0.0.1',
        ]);
    }

    /**
     * 測試密碼與確認密碼不一致驗證失敗.
     */
    public function testPasswordMismatchFails(): void
    {
        $this->expectException(ValidationException::class);

        new RegisterUserDTO($this->createValidator(), [
            'username'         => 'validname',
            'email'            => 'user@example.com',
            'password'         => 'S3cur3Pass!',
            'confirm_password' => 'DifferentPass!',
            'user_ip'          => '127.0.0.1',
        ]);
    }

    /**
     * 測試拋棄式電子郵件網域驗證失敗.
     */
    public function testDisposableEmailFails(): void
    {
        $this->expectException(ValidationException::class);

        new RegisterUserDTO($this->createValidator(), [
            'username'         => 'validname',
            'email'            => 'temp@mailinator.com',
            'password'         => 'S3cur3Pass!',
            'confirm_password' => 'S3cur3Pass!',
            'user_ip'          => '127.0.0.1',
        ]);
    }

    /**
     * 測試無效 IP 地址驗證失敗.
     */
    public function testInvalidIpFails(): void
    {
        $this->expectException(ValidationException::class);

        new RegisterUserDTO($this->createValidator(), [
            'username'         => 'validname',
            'email'            => 'user@example.com',
            'password'         => 'S3cur3Pass!',
            'confirm_password' => 'S3cur3Pass!',
            'user_ip'          => 'invalid-ip-address',
        ]);
    }
}
