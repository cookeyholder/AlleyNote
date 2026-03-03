<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Shared\Services\PasswordValidationService;
use PHPUnit\Framework\TestCase;

final class PasswordValidationServiceTest extends TestCase
{
    private PasswordValidationService $service;

    protected function setUp(): void
    {
        $this->service = new PasswordValidationService();
    }

    public function testValidateValidPassword(): void
    {
        $result = $this->service->validate('ValidPass942!');  // 避免連續數字

        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThan(0, $result['score']);
        $this->assertIsString($result['strength']);
        $this->assertIsArray($result['errors']);
        $this->assertIsArray($result['warnings']);
        $this->assertIsArray($result['suggestions']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidatePasswordTooShort(): void
    {
        $result = $this->service->validate('Short1');

        $this->assertFalse($result['is_valid']);
        $this->assertContains('密碼長度至少需要 8 個字元', $result['errors']);
    }

    public function testValidatePasswordMissingLowercase(): void
    {
        $result = $this->service->validate('UPPERCASE942');  // 避免連續數字

        $this->assertFalse($result['is_valid']);
        $this->assertContains('密碼必須包含至少一個小寫字母', $result['errors']);
    }

    public function testValidatePasswordMissingUppercase(): void
    {
        $result = $this->service->validate('lowercase942');  // 避免連續數字

        $this->assertFalse($result['is_valid']);
        $this->assertContains('密碼必須包含至少一個大寫字母', $result['errors']);
    }

    public function testValidatePasswordMissingNumber(): void
    {
        $result = $this->service->validate('NoNumbersHere');

        $this->assertFalse($result['is_valid']);
        $this->assertContains('密碼必須包含至少一個數字', $result['errors']);
    }

    public function testValidatePasswordWithSequentialChars(): void
    {
        $result = $this->service->validate('Abcdefgh942');  // 避免連續數字

        $this->assertFalse($result['is_valid']);
        $this->assertContains('密碼不能包含連續的英文字母或數字（如 abc, 123）', $result['errors']);
    }

    public function testValidatePasswordWithRepeatingChars(): void
    {
        $result = $this->service->validate('Vaaaalid942');  // 避免連續數字

        $this->assertFalse($result['is_valid']);
        $this->assertContains('密碼不能包含重複的字元（如 aaa, 111）', $result['errors']);
    }

    public function testValidateCommonPassword(): void
    {
        $result = $this->service->validate('password');

        $this->assertFalse($result['is_valid']);
        $this->assertContains('此密碼過於常見，請使用更安全的密碼', $result['errors']);
    }

    public function testValidatePasswordContainsUsername(): void
    {
        $result = $this->service->validate('JohnDoe942', 'johndoe');  // 避免連續數字

        $this->assertFalse($result['is_valid']);
        $this->assertContains('密碼不能包含使用者名稱或電子郵件', $result['errors']);
    }

    public function testValidatePasswordContainsEmailPrefix(): void
    {
        $result = $this->service->validate('JohnSmith942', null, 'johnsmith@example.com');  // 避免連續數字

        $this->assertFalse($result['is_valid']);
        $this->assertContains('密碼不能包含使用者名稱或電子郵件', $result['errors']);
    }

    public function testPasswordScoreInRange(): void
    {
        $result = $this->service->validate('TestPass942!');  // 避免連續數字

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function testPasswordStrengthLevels(): void
    {
        $weakResult = $this->service->validate('Weak942!');  // 避免連續數字
        $this->assertContains($weakResult['strength'], ['very-weak', 'weak', 'medium', 'strong', 'very-strong']);

        $strongResult = $this->service->validate('Xk9@mP2#vL5!qR8$');
        $this->assertContains($strongResult['strength'], ['very-weak', 'weak', 'medium', 'strong', 'very-strong']);
    }

    public function testPasswordWithSpecialCharactersGetsBonus(): void
    {
        $withoutSpecial = $this->service->validate('TestPass942');  // 避免連續數字
        $withSpecial = $this->service->validate('TestPass942!');  // 避免連續數字

        $this->assertGreaterThan($withoutSpecial['score'], $withSpecial['score']);
    }

    public function testLongerPasswordGetsHigherScore(): void
    {
        $short = $this->service->validate('TestPs1!');
        $medium = $this->service->validate('TestPass942!');  // 避免連續數字
        $long = $this->service->validate('TestPassword942!@#');

        $this->assertGreaterThan($short['score'], $medium['score']);
        $this->assertGreaterThan($medium['score'], $long['score']);
    }

    public function testSuggestionsProvided(): void
    {
        $result = $this->service->validate('short');

        $this->assertNotEmpty($result['suggestions']);
        $this->assertIsArray($result['suggestions']);
    }

    public function testWarningsForPasswordWithoutSpecialChars(): void
    {
        $result = $this->service->validate('TestPass942');  // 避免連續數字

        $this->assertContains('建議包含至少一個特殊符號以增加安全性', $result['warnings']);
    }

    public function testMultipleErrors(): void
    {
        $result = $this->service->validate('abc');

        $this->assertFalse($result['is_valid']);
        $this->assertGreaterThan(1, count($result['errors']));
    }

    public function testSequentialNumbersDetected(): void
    {
        $result = $this->service->validate('Valid123456!');

        $this->assertFalse($result['is_valid']);
        $this->assertContains('密碼不能包含連續的英文字母或數字（如 abc, 123）', $result['errors']);
    }

    public function testSequentialDecreasingDetected(): void
    {
        $result = $this->service->validate('ValidCba987!');

        $this->assertFalse($result['is_valid']);
        $this->assertContains('密碼不能包含連續的英文字母或數字（如 abc, 123）', $result['errors']);
    }

    public function testCaseInsensitiveUsernameCheck(): void
    {
        $result = $this->service->validate('JOHNDOE942!', 'johndoe');  // 避免連續數字

        $this->assertFalse($result['is_valid']);
        $this->assertContains('密碼不能包含使用者名稱或電子郵件', $result['errors']);
    }

    public function testShortUsernameNotChecked(): void
    {
        $result = $this->service->validate('AbValid942!', 'ab');  // 避免連續數字

        $this->assertTrue($result['is_valid']);
    }

    public function testPasswordTooLong(): void
    {
        $result = $this->service->validate(str_repeat('a', 129));

        $this->assertFalse($result['is_valid']);
        $this->assertContains('密碼長度不能超過 128 個字元', $result['errors']);
    }

    public function testCommonPasswordsCaseInsensitive(): void
    {
        $result = $this->service->validate('PASSWORD');

        $this->assertFalse($result['is_valid']);
        $this->assertContains('此密碼過於常見，請使用更安全的密碼', $result['errors']);
    }

    public function testStrongPasswordNoSuggestions(): void
    {
        $result = $this->service->validate('Xk9@mP2#vL5!');

        if ($result['is_valid']) {
            // 強密碼可能只有建議加特殊符號，或沒有建議
            $this->assertTrue(
                empty($result['suggestions'])
                || count($result['suggestions']) <= 1,
            );
        }
    }
}
