<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\Services\Advanced\PwnedPasswordService;
use App\Domains\Auth\Services\PasswordSecurityService;
use App\Shared\Exceptions\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\Support\UnitTestCase;

/**
 * 密碼安全性服務單元測試.
 */
final class PasswordSecurityServiceTest extends UnitTestCase
{
    private PwnedPasswordService&MockInterface $pwnedService;

    private PasswordSecurityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pwnedService = Mockery::mock(PwnedPasswordService::class);
        $this->service = new PasswordSecurityService($this->pwnedService);
    }

    /**
     * 測試預設建構子.
     */
    public function testDefaultConstructor(): void
    {
        $service = new PasswordSecurityService();
        $this->assertInstanceOf(PasswordSecurityService::class, $service);
    }

    /**
     * 測試雜湊與驗證密碼成功
     */
    public function testHashAndVerifyPasswordSuccess(): void
    {
        $this->pwnedService
            ->shouldReceive('isPasswordPwned')
            ->once()
            ->andReturn(['api_available' => true, 'is_leaked' => false, 'count' => 0]);

        $password = 'V@lidPass#9Kx!2';
        $hash = $this->service->hashPassword($password);

        $this->assertNotEmpty($hash);
        $this->assertTrue($this->service->verifyPassword($password, $hash));
        $this->assertFalse($this->service->verifyPassword('WrongPass#9Kx!2', $hash));
    }

    /**
     * 測試 needsRehash 方法.
     */
    public function testNeedsRehash(): void
    {
        $hash = password_hash('V@lidPass#9Kx!2', PASSWORD_BCRYPT, ['cost' => 4]);
        // BCRYPT cost 4 compared to ARGON2ID or higher cost
        $this->assertTrue($this->service->needsRehash($hash));
    }

    /**
     * 測試密碼長度小於 8 拋出 ValidationException.
     */
    public function testValidatePasswordTooShortThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼長度必須至少為 8 個字元');
        $this->service->validatePassword('Ab1!');
    }

    /**
     * 測試密碼長度大於 128 拋出 ValidationException.
     */
    public function testValidatePasswordTooLongThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼長度不能超過 128 個字元');
        $this->service->validatePassword(str_repeat('Aa1!', 33));
    }

    /**
     * 測試缺少大寫、小寫、數字或特殊符號或唯一字元不足拋出 ValidationException.
     */
    public function testValidatePasswordComplexityFailures(): void
    {
        // 缺少大寫
        try {
            $this->service->validatePassword('lowercase123!@#');
            $this->fail('應該拋出大寫字母驗證失敗');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('大寫字母', $e->getMessage());
        }

        // 缺少小寫
        try {
            $this->service->validatePassword('UPPERCASE123!@#');
            $this->fail('應該拋出小寫字母驗證失敗');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('小寫字母', $e->getMessage());
        }

        // 缺少數字
        try {
            $this->service->validatePassword('NoDigitsHere!@#');
            $this->fail('應該拋出數字驗證失敗');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('數字', $e->getMessage());
        }

        // 缺少特殊符號
        try {
            $this->service->validatePassword('NoSpecialChar123');
            $this->fail('應該拋出特殊符號驗證失敗');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('特殊符號', $e->getMessage());
        }
    }

    /**
     * 測試 HIBP API 發現洩漏密碼拋出 ValidationException.
     */
    public function testValidatePasswordPwnedApiLeakedThrowsException(): void
    {
        $this->pwnedService
            ->shouldReceive('isPasswordPwned')
            ->once()
            ->with('P@ssw0rd!#9Km')
            ->andReturn([
                'api_available' => true,
                'is_leaked'     => true,
                'count'         => 1500,
            ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('此密碼已在 1500 次資料外洩中被發現');
        $this->service->validatePassword('P@ssw0rd!#9Km');
    }

    /**
     * 測試 HIBP API 不可用時 strength 計算觸發常見弱密碼清單.
     */
    public function testCalculatePasswordStrengthFallbackCommonList(): void
    {
        $this->pwnedService
            ->shouldReceive('isPasswordPwned')
            ->once()
            ->with('password')
            ->andReturn([
                'api_available' => false,
                'is_leaked'     => false,
                'count'         => 0,
            ]);

        $result = $this->service->calculatePasswordStrength('password');
        $feedback = $result['feedback'];
        $this->assertIsArray($feedback);
        $this->assertTrue(in_array('這是常見的弱密碼', $feedback, true));
    }

    /**
     * 測試重複字元 (超過 3 個連續相同字元) 拋出 ValidationException.
     */
    public function testValidatePasswordExcessiveRepetitionThrowsException(): void
    {
        $this->pwnedService
            ->shouldReceive('isPasswordPwned')
            ->once()
            ->andReturn(['api_available' => true, 'is_leaked' => false, 'count' => 0]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼包含過多重複字元');
        $this->service->validatePassword('Aaaaa12!@Kx#9');
    }

    /**
     * 測試連續序列字元 (如 qwerty, 1234, asdf) 拋出 ValidationException.
     */
    public function testValidatePasswordSequentialCharsThrowsException(): void
    {
        $this->pwnedService
            ->shouldReceive('isPasswordPwned')
            ->once()
            ->andReturn(['api_available' => true, 'is_leaked' => false, 'count' => 0]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼不能包含連續的字元序列');
        $this->service->validatePassword('MyPass1234!#K');
    }

    /**
     * 測試生成安全密碼與自訂長度.
     */
    public function testGenerateSecurePassword(): void
    {
        // 預設長度 16
        $password = $this->service->generateSecurePassword();
        $this->assertSame(16, strlen($password));
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[a-z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);
        $this->assertMatchesRegularExpression('/[^A-Za-z0-9]/', $password);

        // 小於最小值 8 會被設為 8
        $short = $this->service->generateSecurePassword(4);
        $this->assertSame(8, strlen($short));

        // 大於最大值 128 會被設為 128
        $long = $this->service->generateSecurePassword(200);
        $this->assertSame(128, strlen($long));
    }

    /**
     * 測試計算密碼強度評分各等級與反饋.
     */
    public function testCalculatePasswordStrength(): void
    {
        // 1. 非常強的密碼
        $this->pwnedService
            ->shouldReceive('isPasswordPwned')
            ->with('K#9vL$2mP&8xQ!5z')
            ->andReturn(['api_available' => true, 'is_leaked' => false, 'count' => 0]);

        $strength = $this->service->calculatePasswordStrength('K#9vL$2mP&8xQ!5z');
        $this->assertGreaterThanOrEqual(80, $strength['score']);
        $this->assertSame('very_strong', $strength['strength']);

        // 2. 弱密碼（短、無特殊符號等）
        $this->pwnedService
            ->shouldReceive('isPasswordPwned')
            ->with('abc')
            ->andReturn(['api_available' => true, 'is_leaked' => false, 'count' => 0]);

        $weak = $this->service->calculatePasswordStrength('abc');
        $this->assertLessThan(40, $weak['score']);
        $this->assertNotEmpty($weak['feedback']);

        // 3. 已外洩密碼（HIBP 扣分）
        $this->pwnedService
            ->shouldReceive('isPasswordPwned')
            ->with('LeakedPass12!@#')
            ->andReturn(['api_available' => true, 'is_leaked' => true, 'count' => 500]);

        $leaked = $this->service->calculatePasswordStrength('LeakedPass12!@#');
        $leakedFeedback = $leaked['feedback'];
        $this->assertIsArray($leakedFeedback);
        $this->assertTrue(in_array('此密碼已在 500 次資料外洩中被發現', $leakedFeedback, true));

        // 4. 重複字元與連續字元扣分
        $this->pwnedService
            ->shouldReceive('isPasswordPwned')
            ->with('AAAA1234abcd!@#$')
            ->andReturn(['api_available' => true, 'is_leaked' => false, 'count' => 0]);

        $penalized = $this->service->calculatePasswordStrength('AAAA1234abcd!@#$');
        $penalizedFeedback = $penalized['feedback'];
        $this->assertIsArray($penalizedFeedback);
        $this->assertTrue(in_array('避免重複字元', $penalizedFeedback, true));
        $this->assertTrue(in_array('避免使用連續字元', $penalizedFeedback, true));
    }
}
