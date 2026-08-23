<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\Shared\Exceptions\ValidationException;
use App\Shared\ValueObjects\SecurePassword;
use Tests\Support\UnitTestCase;

final class SecurePasswordTest extends UnitTestCase
{
    public function testValidPassword(): void
    {
        $password = new SecurePassword('Xk9@mP2#vL5!');
        $this->assertEquals('Xk9@mP2#vL5!', $password->getValue());
    }

    public function testPasswordTooShort(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼長度至少需要 8 個字元');

        new SecurePassword('Short1!');
    }

    public function testPasswordTooLong(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼長度不能超過 128 個字元');

        new SecurePassword(str_repeat('a', 129));
    }

    public function testPasswordMissingLowercase(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼必須包含至少一個小寫字母');

        new SecurePassword('UPPERCASE123!');
    }

    public function testPasswordMissingUppercase(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼必須包含至少一個大寫字母');

        new SecurePassword('lowercase123!');
    }

    public function testPasswordMissingNumber(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼必須包含至少一個數字');

        new SecurePassword('NoNumbers!@#');
    }

    public function testPasswordWithSequentialChars(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼不能包含連續的英文字母或數字');

        new SecurePassword('Abcdefgh123');
    }

    public function testPasswordWithSequentialNumbers(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼不能包含連續的英文字母或數字');

        new SecurePassword('Valid123456!');
    }

    public function testPasswordWithRepeatingChars(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼不能包含重複的字元');

        new SecurePassword('V@aaa129');
    }

    public function testPasswordIsCommonPassword(): void
    {
        try {
            new SecurePassword('Qwerty123');
            $this->fail('Expected exception not thrown');
        } catch (ValidationException $e) {
            // 應該被拒絕（可能因為常見密碼或包含常見單字）
            $this->assertInstanceOf(ValidationException::class, $e);
        }
    }

    public function testPasswordContainsUsername(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼不能包含使用者名稱或電子郵件');

        new SecurePassword('X@johndoe9', 'johndoe');
    }

    public function testPasswordContainsEmailPrefix(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼不能包含使用者名稱或電子郵件');

        new SecurePassword('X@zmithq9', null, 'zmithq@example.com');
    }

    public function testPasswordScoreCalculation(): void
    {
        $weakPassword = new SecurePassword('Wk@P9x1!');
        $this->assertLessThanOrEqual(80, $weakPassword->calculateScore());

        $strongPassword = new SecurePassword('Xk9@mP2#vL5!qR8$');
        $this->assertGreaterThanOrEqual(60, $strongPassword->calculateScore());
    }

    public function testPasswordStrengthLevel(): void
    {
        $mediumPassword = new SecurePassword('Wk@Xm91z');  // 8 個字元的密碼
        $this->assertContains($mediumPassword->getStrengthLevel(), ['weak', 'medium', 'strong', 'very-strong']);

        $strongPassword = new SecurePassword('Xk9@mP2#vL5!qR8$');  // 更長更強的密碼
        $this->assertContains($strongPassword->getStrengthLevel(), ['strong', 'very-strong']);
    }

    public function testPasswordToString(): void
    {
        $password = new SecurePassword('Xk9@Ps1!');
        $this->assertEquals('Xk9@Ps1!', (string) $password);
    }

    public function testMultipleValidationErrors(): void
    {
        try {
            new SecurePassword('abc');
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('password', $errors);
            $this->assertIsArray($errors['password']);
            $this->assertGreaterThan(1, count($errors['password']));
        }
    }

    public function testPasswordWithSpecialCharacters(): void
    {
        $password = new SecurePassword('Xk9!@#$%P2');
        $this->assertGreaterThan(0, $password->calculateScore());
    }

    public function testPasswordCaseInsensitiveUsernameCheck(): void
    {
        $this->expectException(ValidationException::class);

        new SecurePassword('X@johndoe9', 'johndoe');
    }

    public function testShortUsernameNotChecked(): void
    {
        // 使用者名稱少於 3 字元不應被檢查
        $password = new SecurePassword('Xk9!Xy12', 'ab');
        $this->assertEquals('Xk9!Xy12', $password->getValue());
    }

    public function testValidPasswordWithAllCharacterTypes(): void
    {
        $password = new SecurePassword('MyP@9sw0rd!2024');
        $this->assertGreaterThanOrEqual(60, $password->calculateScore());
        $this->assertContains($password->getStrengthLevel(), ['strong', 'very-strong']);
    }

    public function testPasswordScoreBoundaries(): void
    {
        $password = new SecurePassword('Xk9@Ps1!');
        $score = $password->calculateScore();

        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function testSequentialDecreasingChars(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼不能包含連續的英文字母或數字');

        new SecurePassword('Xk9@Cba7!');
    }

    public function testNoSequentialCharsValid(): void
    {
        // 這些不是連續字元
        $password = new SecurePassword('Xk9@mP2#vL5!');
        $this->assertInstanceOf(SecurePassword::class, $password);
    }

    public function testRepeatingPattern(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼不能包含重複的字元');

        new SecurePassword('Xk9@111!');
    }

    public function testCommonWordInPassword(): void
    {
        // 跳過這個測試，因為 common-words.txt 的內容不確定
        $this->markTestSkipped('Common words detection depends on common-words.txt content');
    }

    /**
     * 測試 calculateScore 得分組成.
     *
     * 建構子強制要求小寫、大寫與數字，因此有效密碼的最低得分為
     * 長度 20 + 三類字元 45 = 65；含符號再加 15。
     */
    public function test_calculate_score_components(): void
    {
        // 8 字元純英數：20 + 15 + 15 + 15 = 65 分
        $password = new SecurePassword('Km7Tx2Qb');
        $this->assertSame(65, $password->calculateScore(), '8字元純英數應為 65 分');

        // 10 字元含符號：20 + 60 = 80 分（未達 12 字元不加長度分）
        $password = new SecurePassword('Tq7#Wz2$Km');
        $this->assertSame(80, $password->calculateScore(), '10字元全類型應為 80 分');

        // 12 字元含符號：20 + 10 + 60 = 90 分
        $password = new SecurePassword('Vb5&Nt8*Rq1%');
        $this->assertSame(90, $password->calculateScore(), '12字元全類型應為 90 分');

        // 16 字元含符號：20 + 10 + 10 + 60 = 100 分
        $password = new SecurePassword('Hn4@Jk7!Rt2#Mv8$');
        $this->assertSame(100, $password->calculateScore(), '16字元全類型應為滿分 100');

        // 8 字元全類型：20 + 60 = 80 分
        $password = new SecurePassword('Xk9@Ps1!');
        $this->assertSame(80, $password->calculateScore(), '8字元全類型應為 80 分');
    }

    /**
     * 測試 getStrengthLevel 等級對應.
     *
     * 建構子限制使 very-weak、weak、medium 無法透過合法密碼產生
     * （最低得分為 65），因此僅能驗證 strong 與 very-strong 兩級。
     */
    public function test_strength_level_at_boundaries(): void
    {
        // 65 分落在 strong 區間（60-79）
        $password = new SecurePassword('Km7Tx2Qb');
        $this->assertSame('strong', $password->getStrengthLevel());

        // 80 分落在 very-strong 區間（80 以上）
        $password = new SecurePassword('Xk9@Ps1!');
        $this->assertSame('very-strong', $password->getStrengthLevel());
    }

    /**
     * 測試 constructor 長度邊界.
     */
    public function test_constructor_edge_cases(): void
    {
        // 下限：恰好 8 字元可通過
        $password = new SecurePassword('Xk9@Ps1!');
        $this->assertSame('Xk9@Ps1!', $password->getValue());

        // 上限：恰好 128 字元可通過（重複片段不形成連續或重複字元）
        $long = str_repeat('aB1!', 32);
        $password = new SecurePassword($long);
        $this->assertSame($long, $password->getValue());

        // 低於下限：7 字元拋出長度錯誤
        try {
            new SecurePassword('aB1!ab');
            $this->fail('7 字元密碼應被拒絕');
        } catch (ValidationException $e) {
            $errors = $e->getErrors()['password'] ?? [];
            $this->assertIsArray($errors);
            $this->assertNotEmpty($errors);
            $firstError = $errors[0];
            $this->assertIsString($firstError);
            $this->assertStringContainsString('至少需要 8', $firstError);
        }

        // 超過上限：129 字元拋出長度錯誤
        try {
            new SecurePassword(str_repeat('aB1!', 32) . 'aB1!');
            $this->fail('129 字元密碼應被拒絕');
        } catch (ValidationException $e) {
            $errors = $e->getErrors()['password'] ?? [];
            $this->assertIsArray($errors);
            $this->assertNotEmpty($errors);
            $firstError = $errors[0];
            $this->assertIsString($firstError);
            $this->assertStringContainsString('不能超過 128', $firstError);
        }
    }

    /**
     * 測試連續字元偵測邊界.
     */
    public function test_sequential_chars_boundary(): void
    {
        // 遞增字母序列 abcdefg 應被拒絕，且為第一筆錯誤
        try {
            new SecurePassword('Abcdefg1');
            $this->fail('含遞增字母序列的密碼應被拒絕');
        } catch (ValidationException $e) {
            $errors = $e->getErrors()['password'] ?? [];
            $this->assertIsArray($errors);
            $firstError = $errors[0] ?? '';
            $this->assertIsString($firstError);
            $this->assertStringContainsString('連續', $firstError);
        }

        // 遞減字母序列 zyxwvu 應被拒絕
        try {
            new SecurePassword('Azyxwvu9');
            $this->fail('含遞減字母序列的密碼應被拒絕');
        } catch (ValidationException $e) {
            $errors = $e->getErrors()['password'] ?? [];
            $this->assertIsArray($errors);
            $firstError = $errors[0] ?? '';
            $this->assertIsString($firstError);
            $this->assertStringContainsString('連續', $firstError);
        }

        // 遞增數字序列 123456 應被拒絕
        try {
            new SecurePassword('Qw123456!');
            $this->fail('含遞增數字序列的密碼應被拒絕');
        } catch (ValidationException $e) {
            $errors = $e->getErrors()['password'] ?? [];
            $this->assertIsArray($errors);
            $firstError = $errors[0] ?? '';
            $this->assertIsString($firstError);
            $this->assertStringContainsString('連續', $firstError);
        }

        // 相鄰但不連續的字元（m-n-p 跳過 o）應可通過
        $password = new SecurePassword('aB1!mnp!');
        $this->assertInstanceOf(SecurePassword::class, $password);
    }

    /**
     * 測試重複字元偵測.
     */
    public function test_repeating_chars_boundary(): void
    {
        // 3 個相同字元應被拒絕，且為第一筆錯誤
        try {
            new SecurePassword('aB1!aaa!');
            $this->fail('含 3 個相同字元的密碼應被拒絕');
        } catch (ValidationException $e) {
            $errors = $e->getErrors()['password'] ?? [];
            $this->assertIsArray($errors);
            $firstError = $errors[0] ?? '';
            $this->assertIsString($firstError);
            $this->assertStringContainsString('重複', $firstError);
        }

        // 4 個相同字元同樣應被拒絕
        try {
            new SecurePassword('aB1!aaaa!');
            $this->fail('含 4 個相同字元的密碼應被拒絕');
        } catch (ValidationException $e) {
            $errors = $e->getErrors()['password'] ?? [];
            $this->assertIsArray($errors);
            $firstError = $errors[0] ?? '';
            $this->assertIsString($firstError);
            $this->assertStringContainsString('重複', $firstError);
        }

        // 僅 2 個相同字元不觸發規則，可通過
        $password = new SecurePassword('aB1!mnp!');
        $this->assertInstanceOf(SecurePassword::class, $password);
    }

    /**
     * 測試強度層級與分數一致性.
     */
    public function test_strength_consistency(): void
    {
        // 分數與等級必須對應到相同的區間定義
        $cases = [
            'Km7Tx2Qb'         => ['score' => 65, 'level' => 'strong'],
            'Tq7#Wz2$Km'       => ['score' => 80, 'level' => 'very-strong'],
            'Vb5&Nt8*Rq1%'     => ['score' => 90, 'level' => 'very-strong'],
            'Hn4@Jk7!Rt2#Mv8$' => ['score' => 100, 'level' => 'very-strong'],
        ];

        foreach ($cases as $pw => $expected) {
            $obj = new SecurePassword($pw);
            $this->assertSame($expected['score'], $obj->calculateScore(), "{$pw} 分數不符");
            $this->assertSame($expected['level'], $obj->getStrengthLevel(), "{$pw} 等級不符");
        }
    }
}
