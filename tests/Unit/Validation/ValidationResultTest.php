<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use App\Shared\Validation\ValidationResult;
use Tests\TestCase;

/**
 * ValidationResult 單元測試.
 *
 * 測試驗證結果類的所有功能，包括建立、查詢、合併和序列化
 */
class ValidationResultTest extends TestCase
{
    /**
     * 測試建立成功的驗證結果.
     */
    public function test_create_success_result(): void
    {
        // Arrange
        $validatedData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 25,
        ];

        // Act
        $result = ValidationResult::success($validatedData);

        // Assert
        $this->assertTrue($result->isValid());
        $this->assertFalse($result->isInvalid());
        $this->assertEquals($validatedData, $result->getValidatedData());
        $this->assertEmpty($result->getErrors());
        $this->assertEmpty($result->getFailedRules());
        $this->assertNull($result->getFirstError());
    }

    /**
     * 測試建立失敗的驗證結果.
     */
    public function test_create_failure_result(): void
    {
        // Arrange
        $errors = [
            'name' => ['欄位 name 為必填項目'],
            'email' => ['欄位 email 必須是有效的電子郵件地址'],
        ];
        $failedRules = [
            'name' => ['required'],
            'email' => ['email'],
        ];

        // Act
        $result = ValidationResult::failure($errors, $failedRules);

        // Assert
        $this->assertFalse($result->isValid());
        $this->assertTrue($result->isInvalid());
        $this->assertEquals($errors, $result->getErrors());
        $this->assertEquals($failedRules, $result->getFailedRules());
        $this->assertEmpty($result->getValidatedData());
    }

    /**
     * 測試使用建構函式建立驗證結果.
     */
    public function test_constructor(): void
    {
        // Arrange
        $isValid = false;
        $errors = ['field' => ['error message']];
        $validatedData = ['valid_field' => 'value'];
        $failedRules = ['field' => ['rule']];

        // Act
        $result = new ValidationResult($isValid, $errors, $validatedData, $failedRules);

        // Assert
        $this->assertFalse($result->isValid());
        $this->assertTrue($result->isInvalid());
        $this->assertEquals($errors, $result->getErrors());
        $this->assertEquals($validatedData, $result->getValidatedData());
        $this->assertEquals($failedRules, $result->getFailedRules());
    }

    /**
     * 測試取得特定欄位的錯誤訊息.
     */
    public function test_get_field_errors(): void
    {
        // Arrange
        $errors = [
            'name' => ['名稱為必填項目', '名稱長度至少需要2個字元'],
            'email' => ['電子郵件格式不正確'],
            'age' => [],
        ];
        $result = ValidationResult::failure($errors);

        // Act & Assert
        $this->assertEquals(['名稱為必填項目', '名稱長度至少需要2個字元'], $result->getFieldErrors('name'));
        $this->assertEquals(['電子郵件格式不正確'], $result->getFieldErrors('email'));
        $this->assertEquals([], $result->getFieldErrors('age'));
        $this->assertEquals([], $result->getFieldErrors('nonexistent'));
    }

    /**
     * 測試檢查特定欄位是否有錯誤.
     */
    public function test_has_field_errors(): void
    {
        // Arrange
        $errors = [
            'name' => ['名稱為必填項目'],
            'email' => ['電子郵件格式不正確'],
            'valid_field' => [],
        ];
        $result = ValidationResult::failure($errors);

        // Act & Assert
        $this->assertTrue($result->hasFieldErrors('name'));
        $this->assertTrue($result->hasFieldErrors('email'));
        $this->assertFalse($result->hasFieldErrors('valid_field'));
        $this->assertFalse($result->hasFieldErrors('nonexistent'));
    }

    /**
     * 測試取得第一個錯誤訊息.
     */
    public function test_get_first_error(): void
    {
        // Arrange - 測試有錯誤的情況
        $errors = [
            'name' => ['名稱為必填項目', '名稱長度不足'],
            'email' => ['電子郵件格式不正確'],
        ];
        $result = ValidationResult::failure($errors);

        // Act & Assert
        $firstError = $result->getFirstError();
        $this->assertNotNull($firstError);
        $this->assertContains($firstError, ['名稱為必填項目', '電子郵件格式不正確']);

        // Arrange - 測試沒有錯誤的情況
        $successResult = ValidationResult::success(['name' => 'John']);

        // Act & Assert
        $this->assertNull($successResult->getFirstError());
    }

    /**
     * 測試取得特定欄位的第一個錯誤訊息.
     */
    public function test_get_first_field_error(): void
    {
        // Arrange
        $errors = [
            'name' => ['名稱為必填項目', '名稱長度不足'],
            'email' => ['電子郵件格式不正確'],
            'empty_field' => [],
        ];
        $result = ValidationResult::failure($errors);

        // Act & Assert
        $this->assertEquals('名稱為必填項目', $result->getFirstFieldError('name'));
        $this->assertEquals('電子郵件格式不正確', $result->getFirstFieldError('email'));
        $this->assertNull($result->getFirstFieldError('empty_field'));
        $this->assertNull($result->getFirstFieldError('nonexistent'));
    }

    /**
     * 測試取得所有錯誤訊息的扁平陣列.
     */
    public function test_get_all_errors(): void
    {
        // Arrange
        $errors = [
            'name' => ['名稱為必填項目', '名稱長度不足'],
            'email' => ['電子郵件格式不正確'],
            'age' => ['年齡必須是數字'],
        ];
        $result = ValidationResult::failure($errors);

        // Act
        $allErrors = $result->getAllErrors();

        // Assert
        $expectedErrors = [
            '名稱為必填項目',
            '名稱長度不足',
            '電子郵件格式不正確',
            '年齡必須是數字',
        ];
        $this->assertCount(4, $allErrors);
        foreach ($expectedErrors as $expectedError) {
            $this->assertContains($expectedError, $allErrors);
        }
    }

    /**
     * 測試錯誤計數功能.
     */
    public function test_error_count(): void
    {
        // Arrange
        $errors = [
            'name' => ['錯誤1', '錯誤2'],
            'email' => ['錯誤3'],
            'age' => ['錯誤4', '錯誤5', '錯誤6'],
        ];
        $result = ValidationResult::failure($errors);

        // Act & Assert
        $allErrors = $result->getAllErrors();
        $this->assertCount(6, $allErrors); // 總錯誤數
        $this->assertCount(3, $result->getErrors()); // 有錯誤的欄位數

        // 測試成功結果
        $successResult = ValidationResult::success(['name' => 'John']);
        $this->assertCount(0, $successResult->getAllErrors());
        $this->assertCount(0, $successResult->getErrors());
    }

    /**
     * 測試取得失敗的規則.
     */
    public function test_get_failed_rules(): void
    {
        // Arrange
        $errors = [
            'name' => ['名稱為必填項目'],
            'email' => ['電子郵件格式不正確'],
        ];
        $failedRules = [
            'name' => ['required'],
            'email' => ['email'],
        ];
        $result = ValidationResult::failure($errors, $failedRules);

        // Act & Assert
        $this->assertEquals($failedRules, $result->getFailedRules());
        $this->assertEquals(['required'], $result->getFieldFailedRules('name'));
        $this->assertEquals(['email'], $result->getFieldFailedRules('email'));
        $this->assertEquals([], $result->getFieldFailedRules('nonexistent'));
    }

    /**
     * 測試添加錯誤功能.
     */
    public function test_add_error(): void
    {
        // Arrange
        $result = ValidationResult::success(['name' => 'John']);
        $this->assertTrue($result->isValid());

        // Act
        $result->addError('email', '電子郵件為必填項目');
        $result->addError('email', '電子郵件格式不正確');
        $result->addError('age', '年齡必須是數字');

        // Assert
        $this->assertFalse($result->isValid());
        $this->assertTrue($result->isInvalid());
        $this->assertEquals(['電子郵件為必填項目', '電子郵件格式不正確'], $result->getFieldErrors('email'));
        $this->assertEquals(['年齡必須是數字'], $result->getFieldErrors('age'));
        $this->assertCount(3, $result->getAllErrors());
    }

    /**
     * 測試添加失敗規則功能.
     */
    public function test_add_failed_rule(): void
    {
        // Arrange
        $result = ValidationResult::success(['name' => 'John']);

        // Act
        $result->addFailedRule('email', 'required');
        $result->addFailedRule('email', 'email');
        $result->addFailedRule('age', 'integer');

        // Assert
        $this->assertEquals(['required', 'email'], $result->getFieldFailedRules('email'));
        $this->assertEquals(['integer'], $result->getFieldFailedRules('age'));
    }

    /**
     * 測試驗證資料的取得功能.
     */
    public function test_validated_data_access(): void
    {
        // Arrange
        $originalData = ['name' => 'John', 'email' => 'john@example.com'];
        $result = ValidationResult::success($originalData);

        // Act & Assert
        $this->assertEquals($originalData, $result->getValidatedData());
        $this->assertEquals('John', $result->getValidatedField('name'));
        $this->assertEquals('john@example.com', $result->getValidatedField('email'));
        $this->assertNull($result->getValidatedField('nonexistent'));
        $this->assertEquals('default', $result->getValidatedField('nonexistent', 'default'));
    }

    /**
     * 測試合併驗證結果功能.
     */
    public function test_merge_results(): void
    {
        // Arrange
        $result1 = new ValidationResult(
            false,
            ['name' => ['名稱錯誤']],
            ['age' => 25],
            ['name' => ['required']],
        );

        $result2 = new ValidationResult(
            false,
            ['email' => ['電子郵件錯誤'], 'name' => ['名稱長度錯誤']],
            ['city' => 'Taipei'],
            ['email' => ['email'], 'name' => ['min_length']],
        );

        // Act
        $mergedResult = $result1->merge($result2);

        // Assert
        $this->assertSame($result1, $mergedResult); // 應該返回同一個物件
        $this->assertFalse($mergedResult->isValid());

        // 檢查錯誤合併
        $expectedErrors = [
            'name' => ['名稱錯誤', '名稱長度錯誤'],
            'email' => ['電子郵件錯誤'],
        ];
        $this->assertEquals($expectedErrors, $mergedResult->getErrors());

        // 檢查失敗規則合併
        $expectedFailedRules = [
            'name' => ['required', 'min_length'],
            'email' => ['email'],
        ];
        $this->assertEquals($expectedFailedRules, $mergedResult->getFailedRules());

        // 檢查驗證資料合併
        $expectedValidatedData = ['age' => 25, 'city' => 'Taipei'];
        $this->assertEquals($expectedValidatedData, $mergedResult->getValidatedData());
    }

    /**
     * 測試 toArray 功能.
     */
    public function test_to_array(): void
    {
        // Arrange
        $isValid = false;
        $errors = ['name' => ['錯誤訊息']];
        $validatedData = ['email' => 'test@example.com'];
        $failedRules = ['name' => ['required']];

        $result = new ValidationResult($isValid, $errors, $validatedData, $failedRules);

        // Act
        $array = $result->toArray();

        // Assert
        $expected = [
            'is_valid' => false,
            'errors' => $errors,
            'validated_data' => $validatedData,
            'failed_rules' => $failedRules,
        ];
        $this->assertEquals($expected, $array);
    }

    /**
     * 測試 JSON 序列化功能.
     */
    public function test_json_serialization(): void
    {
        // Arrange
        $errors = ['name' => ['名稱為必填項目']];
        $failedRules = ['name' => ['required']];
        $result = ValidationResult::failure($errors, $failedRules);

        // Act
        $json = json_encode($result);
        $decoded = json_decode($json, true);

        // Assert
        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals(false, $decoded['is_valid']);
        $this->assertEquals($errors, $decoded['errors']);
        $this->assertEquals($failedRules, $decoded['failed_rules']);
        $this->assertEquals([], $decoded['validated_data']);
    }

    /**
     * 測試 __toString 功能.
     */
    public function test_to_string(): void
    {
        // Arrange & Act - 成功結果
        $successResult = ValidationResult::success(['name' => 'John', 'email' => 'john@example.com']);
        $successString = (string) $successResult;

        // Assert
        $this->assertStringContainsString('Validation passed', $successString);
        $this->assertStringContainsString('2 fields', $successString);

        // Arrange & Act - 失敗結果
        $errors = [
            'name' => ['名稱為必填項目'],
            'email' => ['電子郵件格式不正確', '電子郵件為必填項目'],
        ];
        $failureResult = ValidationResult::failure($errors);
        $failureString = (string) $failureResult;

        // Assert
        $this->assertStringContainsString('Validation failed', $failureString);
        $this->assertStringContainsString('3 errors', $failureString);
        $this->assertStringContainsString('名稱為必填項目', $failureString);
    }

    /**
     * 測試空結果的邊界情況
     */
    public function test_empty_results(): void
    {
        // Arrange
        $emptyResult = new ValidationResult(true, [], [], []);

        // Act & Assert
        $this->assertTrue($emptyResult->isValid());
        $this->assertEquals([], $emptyResult->getErrors());
        $this->assertEquals([], $emptyResult->getValidatedData());
        $this->assertEquals([], $emptyResult->getFailedRules());
        $this->assertCount(0, $emptyResult->getAllErrors());
        $this->assertCount(0, $emptyResult->getErrors());
        $this->assertNull($emptyResult->getFirstError());
        $this->assertEquals([], $emptyResult->getAllErrors());
    }

    /**
     * 測試大量資料的效能.
     */
    public function test_performance_with_large_data(): void
    {
        // Arrange
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $largeErrors = [];
        $largeFailedRules = [];
        $largeValidatedData = [];

        // 建立大量測試資料
        for ($i = 0; $i < 1000; $i++) {
            $field = "field_{$i}";
            $largeErrors[$field] = ["錯誤訊息_{$i}_1", "錯誤訊息_{$i}_2"];
            $largeFailedRules[$field] = ["rule_{$i}_1", "rule_{$i}_2"];
            $largeValidatedData[$field] = "value_{$i}";
        }

        // Act
        $result = new ValidationResult(false, $largeErrors, $largeValidatedData, $largeFailedRules);

        // 執行各種操作
        $result->getErrors();
        $result->getAllErrors();
        count($result->getAllErrors()); // 計算錯誤數
        count($result->getErrors()); // 計算欄位數
        $result->toArray();
        json_encode($result);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Assert
        $executionTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        $this->assertLessThan(0.5, $executionTime, '大量資料處理應該在 0.5 秒內完成');
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsage, '記憶體使用量應該少於 50MB');
    }

    /**
     * 測試深層複製和物件獨立性.
     */
    public function test_object_independence(): void
    {
        // Arrange
        $originalErrors = ['name' => ['原始錯誤']];
        $originalData = ['email' => 'original@example.com'];
        $result1 = new ValidationResult(false, $originalErrors, $originalData);

        // Act
        $result2 = ValidationResult::success(['name' => 'John']);
        $result2->merge($result1);

        // 修改原始陣列
        $originalErrors['name'][] = '新增錯誤';
        $originalData['email'] = 'modified@example.com';

        // Assert - result1 不應該受到外部陣列修改的影響
        $this->assertEquals(['name' => ['原始錯誤']], $result1->getErrors());
        $this->assertEquals(['email' => 'original@example.com'], $result1->getValidatedData());

        // Assert - result2 也不應該受到影響
        $result2Errors = $result2->getErrors();
        $this->assertEquals(['原始錯誤'], $result2Errors['name']);
    }
}
