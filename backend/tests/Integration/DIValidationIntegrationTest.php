<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\Factory\ValidatorFactory;
use DI\Container;
use DI\ContainerBuilder;
use Tests\TestCase;

/**
 * DI 容器驗證服務整合測試.
 *
 * 測試 DI 容器中的驗證服務是否正確配置和注入
 */
class DIValidationIntegrationTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        // 載入 DI 容器配置
        $containerBuilder = new ContainerBuilder();
        $definitions = require __DIR__ . '/../../app/Infrastructure/Config/container.php';
        $containerBuilder->addDefinitions($definitions);
        $this->container = $containerBuilder->build();
    }

    /**
     * 測試從容器中取得 ValidatorInterface 實例.
     */
    public function test_can_resolve_validator_interface_from_container(): void
    {
        // Act
        $validator = $this->container->get(ValidatorInterface::class);

        // Assert
        $this->assertInstanceOf(ValidatorInterface::class, $validator);
        $this->assertNotNull($validator);
    }

    /**
     * 測試從容器中取得 ValidatorFactory 實例.
     */
    public function test_can_resolve_validator_factory_from_container(): void
    {
        // Act
        $factory = $this->container->get(ValidatorFactory::class);

        // Assert
        $this->assertInstanceOf(ValidatorFactory::class, $factory);
        $this->assertNotNull($factory);
    }

    /**
     * 測試透過 ValidatorFactory 建立的驗證器包含繁體中文錯誤訊息.
     */
    public function test_validator_from_factory_has_chinese_messages(): void
    {
        // Arrange
        $factory = $this->container->get(ValidatorFactory::class);
        $validator = $factory->createForDTO();

        $data = [];
        $rules = ['name' => 'required'];

        // Act
        $result = $validator->validate($data, $rules);

        // Assert
        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertStringContainsString('必填項目', $errors['name'][0]);
    }

    /**
     * 測試 DI 容器注入的驗證器具有自訂驗證規則.
     */
    public function test_container_validator_has_custom_rules(): void
    {
        // Arrange
        $validator = $this->container->get(ValidatorInterface::class);

        // Act & Assert - 測試 username 規則
        $this->assertTrue($validator->checkRule('valid_user', 'username', [3, 50]));
        $this->assertFalse($validator->checkRule('123invalid', 'username', [3, 50])); // 不能數字開頭
        $this->assertFalse($validator->checkRule('ab', 'username', [3, 50])); // 太短

        // Act & Assert - 測試 password_strength 規則
        $this->assertTrue($validator->checkRule('Password123', 'password_strength', [8]));
        $this->assertFalse($validator->checkRule('password', 'password_strength', [8])); // 缺少大寫和數字
        $this->assertFalse($validator->checkRule('Pass1', 'password_strength', [8])); // 太短

        // Act & Assert - 測試 email_enhanced 規則
        $this->assertTrue($validator->checkRule('user@example.com', 'email_enhanced'));
        $this->assertFalse($validator->checkRule('invalid-email', 'email_enhanced'));
        $this->assertFalse($validator->checkRule('user@<script>alert(1)</script>.com', 'email_enhanced'));

        // Act & Assert - 測試 ip_or_cidr 規則
        $this->assertTrue($validator->checkRule('192.168.1.1', 'ip_or_cidr'));
        $this->assertTrue($validator->checkRule('192.168.1.0/24', 'ip_or_cidr'));
        $this->assertTrue($validator->checkRule('2001:db8::1', 'ip_or_cidr'));
        $this->assertFalse($validator->checkRule('invalid-ip', 'ip_or_cidr'));
        $this->assertFalse($validator->checkRule('192.168.1.0/999', 'ip_or_cidr'));

        // Act & Assert - 測試 filename 規則
        $this->assertTrue($validator->checkRule('document.pdf', 'filename', [255]));
        $this->assertTrue($validator->checkRule('我的文件.txt', 'filename', [255]));
        $this->assertFalse($validator->checkRule('file/with/slash.txt', 'filename', [255]));
        $this->assertFalse($validator->checkRule('.hidden', 'filename', [255]));
    }

    /**
     * 測試驗證器工廠的不同建立方法.
     */
    public function test_validator_factory_create_methods(): void
    {
        // Arrange
        $factory = $this->container->get(ValidatorFactory::class);

        // Act
        $standardValidator = $factory->create();
        $dtoValidator = $factory->createForDTO();
        $customValidator = $factory->createWithConfig([
            'messages' => [
                'required' => '自訂必填錯誤訊息 :field',
            ],
            'rules' => [
                'custom_test' => fn($value) => $value === 'test',
            ],
        ]);

        // Assert
        $this->assertInstanceOf(ValidatorInterface::class, $standardValidator);
        $this->assertInstanceOf(ValidatorInterface::class, $dtoValidator);
        $this->assertInstanceOf(ValidatorInterface::class, $customValidator);

        // 測試自訂配置是否生效
        $result = $customValidator->validate([], ['field' => 'required']);
        $errors = $result->getErrors();
        $this->assertStringContainsString('自訂必填錯誤訊息', $errors['field'][0]);

        // 測試自訂規則是否生效
        $this->assertTrue($customValidator->checkRule('test', 'custom_test'));
        $this->assertFalse($customValidator->checkRule('not_test', 'custom_test'));
    }

    /**
     * 測試 DTO 專用驗證器包含密碼確認規則.
     */
    public function test_dto_validator_has_password_confirmation_rule(): void
    {
        // Arrange
        $validator = $this->container->get(ValidatorInterface::class);

        // Act & Assert - 測試密碼確認規則驗證成功
        $validData = [
            'password' => 'MyPassword123',
            'password_confirmation' => 'MyPassword123',
        ];

        // 驗證相符的密碼確認
        $result = $validator->validate($validData, [
            'password_confirmation' => 'password_confirmed',
        ]);

        $this->assertTrue($result->isValid());

        // 測試密碼確認不匹配的情況
        $this->expectException(ValidationException::class);

        $invalidData = [
            'password' => 'MyPassword123',
            'password_confirmation' => 'DifferentPassword456',
        ];

        $validator->validateOrFail($invalidData, [
            'password_confirmation' => 'password_confirmed',
        ]);
    }

    /**
     * 測試容器解析的一致性 - 每次取得的應該是同一個實例.
     */
    public function test_container_validator_consistency(): void
    {
        // Act
        $validator1 = $this->container->get(ValidatorInterface::class);
        $validator2 = $this->container->get(ValidatorInterface::class);

        // Assert - 由於我們使用的是 factory，每次都會建立新實例
        // 但它們的行為應該一致
        $this->assertInstanceOf(ValidatorInterface::class, $validator1);
        $this->assertInstanceOf(ValidatorInterface::class, $validator2);

        // 測試它們有相同的規則
        $this->assertTrue($validator1->checkRule('test@example.com', 'email_enhanced'));
        $this->assertTrue($validator2->checkRule('test@example.com', 'email_enhanced'));
    }

    /**
     * 測試驗證器的錯誤訊息本地化.
     */
    public function test_validator_error_message_localization(): void
    {
        // Arrange
        $validator = $this->container->get(ValidatorInterface::class);

        $testCases = [
            ['rules' => ['name' => 'required'], 'data' => [], 'expected' => '必填項目'],
            ['rules' => ['email' => 'email'], 'data' => ['email' => 'invalid'], 'expected' => '有效的電子郵件地址'],
            ['rules' => ['age' => 'integer'], 'data' => ['age' => 'not_number'], 'expected' => '整數'],
            ['rules' => ['url' => 'url'], 'data' => ['url' => 'not_url'], 'expected' => '有效的 URL'],
            ['rules' => ['ip' => 'ip'], 'data' => ['ip' => 'not_ip'], 'expected' => '有效的 IP 地址'],
        ];

        foreach ($testCases as $testCase) {
            // Act
            $result = $validator->validate($testCase['data'], $testCase['rules']);

            // Assert
            $this->assertFalse(
                $result->isValid(),
                '測試案例失敗: ' . json_encode($testCase, JSON_UNESCAPED_UNICODE),
            );

            $errors = $result->getErrors();
            $field = array_key_first($testCase['rules']);
            $this->assertArrayHasKey($field, $errors);
            $this->assertStringContainsString(
                $testCase['expected'],
                $errors[$field][0],
                '錯誤訊息不包含預期的中文內容: ' . $errors[$field][0],
            );
        }
    }

    /**
     * 測試驗證器效能和記憶體使用.
     */
    public function test_validator_performance_and_memory(): void
    {
        // Arrange
        $validator = $this->container->get(ValidatorInterface::class);
        $startMemory = memory_get_usage();

        // Act - 進行多次驗證操作
        for ($i = 0; $i < 100; $i++) {
            $data = [
                'name' => "測試用戶_{$i}",
                'email' => "user{$i}@example.com",
                'age' => $i + 18,
            ];

            $rules = [
                'name' => 'required|string|min_length:2|max_length:50',
                'email' => 'required|email_enhanced',
                'age' => 'required|integer|min:18|max:120',
            ];

            $result = $validator->validate($data, $rules);
            $this->assertTrue($result->isValid());
        }

        // Assert - 檢查記憶體使用量沒有大幅增加
        $endMemory = memory_get_usage();
        $memoryIncrease = $endMemory - $startMemory;

        // 記憶體增加應該在合理範圍內（小於 1MB）
        $this->assertLessThan(
            1024 * 1024,
            $memoryIncrease,
            '記憶體使用量增加過多: ' . number_format($memoryIncrease / 1024, 2) . ' KB',
        );
    }

    /**
     * 測試簡化的驗證場景（不使用巢狀驗證）.
     */
    public function test_simplified_validation_scenarios(): void
    {
        // Arrange
        $validator = $this->container->get(ValidatorInterface::class);

        $userData = [
            'username' => 'valid_user123',
            'email' => 'user@example.com',
            'password' => 'SecurePass123',
        ];

        $userRules = [
            'username' => 'required|username:3,50',
            'email' => 'required|email_enhanced',
            'password' => 'required|password_strength:8',
        ];

        $profileData = [
            'display_name' => '顯示名稱',
            'bio' => '這是一個簡短的個人簡介',
        ];

        $profileRules = [
            'display_name' => 'required|string|min_length:1|max_length:100',
            'bio' => 'string|max_length:500',
        ];

        $settingsData = [
            'notifications' => true,
            'privacy_level' => 'public',
        ];

        $settingsRules = [
            'notifications' => 'boolean',
            'privacy_level' => 'required|in:public,private,friends',
        ];

        // Act & Assert
        $userResult = $validator->validate($userData, $userRules);
        $this->assertTrue(
            $userResult->isValid(),
            '使用者資料驗證失敗: ' . json_encode($userResult->getErrors(), JSON_UNESCAPED_UNICODE),
        );

        $profileResult = $validator->validate($profileData, $profileRules);
        $this->assertTrue(
            $profileResult->isValid(),
            '個人檔案驗證失敗: ' . json_encode($profileResult->getErrors(), JSON_UNESCAPED_UNICODE),
        );

        $settingsResult = $validator->validate($settingsData, $settingsRules);
        $this->assertTrue(
            $settingsResult->isValid(),
            '設定驗證失敗: ' . json_encode($settingsResult->getErrors(), JSON_UNESCAPED_UNICODE),
        );
    }
}
