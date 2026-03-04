<?php

declare(strict_types=1);

namespace Tests\Integration\Security;

use App\Shared\Services\PasswordValidationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\IntegrationTestCase;

#[Group('integration')]
#[Group('security')]
final class PasswordPolicyIntegrationTest extends IntegrationTestCase
{
    private PasswordValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PasswordValidationService();
    }

    public static function invalidPasswordCases(): array
    {
        return [
            'too_short' => ['Ab1!', '密碼長度至少需要 8 個字元'],
            'missing_uppercase' => ['abcd1234!', '大寫字母'],
            'missing_number' => ['Abcdefg!', '數字'],
            'sequential' => ['Abc12345!', '連續'],
            'repeating' => ['Aaaa1234!', '重複'],
        ];
    }

    #[DataProvider('invalidPasswordCases')]
    public function testWeakPasswordRulesAreRejected(string $password, string $expectedErrorFragment): void
    {
        $result = $this->service->validate($password, 'tester', 'tester@example.com');

        $this->assertFalse($result['is_valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertGreaterThanOrEqual(0, $result['score']);

        $joinedErrors = implode(' | ', $result['errors']);
        $this->assertStringContainsString($expectedErrorFragment, $joinedErrors);
    }

    public function testStrongPasswordPassesValidationMatrix(): void
    {
        $result = $this->service->validate('Xk9@mP2#vL5!', 'tester', 'tester@example.com');

        $this->assertTrue($result['is_valid']);
        $this->assertEmpty($result['errors']);
        $this->assertContains($result['strength'], ['strong', 'very-strong']);
        $this->assertGreaterThanOrEqual(60, $result['score']);
    }
}
