<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;
use Exception;
use Tests\TestCase;

/**
 * ValidationException 單元測試.
 *
 * 測試驗證異常類的所有功能，包括建立、錯誤處理、API 回應格式等
 */
class ValidationExceptionTest extends TestCase
{
    /**
     * 測試使用 ValidationResult 建立異常.
     */
    public function test_create_from_validation_result(): void
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
        $validationResult = ValidationResult::failure($errors, $failedRules);

        // Act
        $exception = new ValidationException($validationResult);

        // Assert
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertEquals(422, $exception->getCode());
        $this->assertEquals('名稱為必填項目', $exception->getMessage()); // 第一個錯誤作為訊息
        $this->assertSame($validationResult, $exception->getValidationResult());
        $this->assertEquals($errors, $exception->getValidationResult()->getErrors());
        $this->assertEquals($failedRules, $exception->getValidationResult()->getFailedRules());
    }

    /**
     * 測試使用自訂訊息建立異常.
     */
    public function test_create_with_custom_message(): void
    {
        // Arrange
        $errors = ['field' => ['某個錯誤']];
        $validationResult = ValidationResult::failure($errors);
        $customMessage = '自訂錯誤訊息';
        $customCode = 400;

        // Act
        $exception = new ValidationException($validationResult, $customMessage, $customCode);

        // Assert
        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
    }

    /**
     * 測試空驗證結果的預設訊息.
     */
    public function test_empty_validation_result_default_message(): void
    {
        // Arrange
        $validationResult = ValidationResult::failure([]);

        // Act
        $exception = new ValidationException($validationResult);

        // Assert
        $this->assertEquals('驗證失敗', $exception->getMessage());
    }

    /**
     * 測試從錯誤陣列建立異常.
     */
    public function test_create_from_errors(): void
    {
        // Arrange
        $errors = [
            'username' => ['使用者名稱為必填項目', '使用者名稱長度不足'],
            'password' => ['密碼強度不足'],
        ];
        $failedRules = [
            'username' => ['required', 'min_length'],
            'password' => ['password_strength'],
        ];

        // Act
        $exception = ValidationException::fromErrors($errors, $failedRules);

        // Assert
        $this->assertEquals($errors, $exception->getErrors());
        $this->assertEquals($failedRules, $exception->getFailedRules());
        $this->assertEquals('使用者名稱為必填項目', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試從錯誤陣列建立異常（含自訂訊息）.
     */
    public function test_create_from_errors_with_custom_message(): void
    {
        // Arrange
        $errors = ['field' => ['錯誤']];
        $customMessage = '表單驗證失敗';

        // Act
        $exception = ValidationException::fromErrors($errors, [], $customMessage);

        // Assert
        $this->assertEquals($customMessage, $exception->getMessage());
    }

    /**
     * 測試從單一錯誤建立異常.
     */
    public function test_create_from_single_error(): void
    {
        // Arrange
        $field = 'email';
        $error = '電子郵件地址無效';
        $rule = 'email';

        // Act
        $exception = ValidationException::fromSingleError($field, $error, $rule);

        // Assert
        $this->assertEquals([$field => [$error]], $exception->getValidationResult()->getErrors());
        $this->assertEquals([$field => [$rule]], $exception->getValidationResult()->getFailedRules());
        $this->assertEquals($error, $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * 測試從單一錯誤建立異常（不含規則）.
     */
    public function test_create_from_single_error_without_rule(): void
    {
        // Arrange
        $field = 'custom_field';
        $error = '自訂驗證錯誤';

        // Act
        $exception = ValidationException::fromSingleError($field, $error);

        // Assert
        $this->assertEquals([$field => [$error]], $exception->getValidationResult()->getErrors());
        $this->assertEquals([], $exception->getValidationResult()->getFailedRules());
        $this->assertEquals($error, $exception->getMessage());
    }

    /**
     * 測試取得特定欄位的錯誤訊息.
     */
    public function test_get_field_errors(): void
    {
        // Arrange
        $errors = [
            'name' => ['名稱為必填項目', '名稱長度不足'],
            'email' => ['電子郵件格式錯誤'],
            'age' => [],
        ];
        $exception = ValidationException::fromErrors($errors);

        // Act & Assert
        $this->assertEquals(['名稱為必填項目', '名稱長度不足'], $exception->getValidationResult()->getFieldErrors('name'));
        $this->assertEquals(['電子郵件格式錯誤'], $exception->getValidationResult()->getFieldErrors('email'));
        $this->assertEquals([], $exception->getValidationResult()->getFieldErrors('age'));
        $this->assertEquals([], $exception->getValidationResult()->getFieldErrors('nonexistent'));
    }

    /**
     * 測試檢查特定欄位是否有錯誤.
     */
    public function test_has_field_errors(): void
    {
        // Arrange
        $errors = [
            'name' => ['錯誤'],
            'email' => ['錯誤'],
            'valid_field' => [],
        ];
        $exception = ValidationException::fromErrors($errors);

        // Act & Assert
        $this->assertTrue($exception->getValidationResult()->hasFieldErrors('name'));
        $this->assertTrue($exception->getValidationResult()->hasFieldErrors('email'));
        $this->assertFalse($exception->getValidationResult()->hasFieldErrors('valid_field'));
        $this->assertFalse($exception->getValidationResult()->hasFieldErrors('nonexistent'));
    }

    /**
     * 測試取得第一個錯誤訊息.
     */
    public function test_get_first_error(): void
    {
        // Arrange
        $errors = [
            'field1' => ['第一個錯誤', '第二個錯誤'],
            'field2' => ['第三個錯誤'],
        ];
        $exception = ValidationException::fromErrors($errors);

        // Act & Assert
        $firstError = $exception->getFirstError();
        $this->assertNotNull($firstError);
        $this->assertContains($firstError, ['第一個錯誤', '第三個錯誤']);
    }

    /**
     * 測試取得特定欄位的第一個錯誤訊息.
     */
    public function test_get_first_field_error(): void
    {
        // Arrange
        $errors = [
            'name' => ['第一個名稱錯誤', '第二個名稱錯誤'],
            'email' => ['電子郵件錯誤'],
            'empty_field' => [],
        ];
        $exception = ValidationException::fromErrors($errors);

        // Act & Assert
        $this->assertEquals('第一個名稱錯誤', $exception->getValidationResult()->getFirstFieldError('name'));
        $this->assertEquals('電子郵件錯誤', $exception->getValidationResult()->getFirstFieldError('email'));
        $this->assertNull($exception->getValidationResult()->getFirstFieldError('empty_field'));
        $this->assertNull($exception->getValidationResult()->getFirstFieldError('nonexistent'));
    }

    /**
     * 測試取得所有錯誤訊息的扁平陣列.
     */
    public function test_get_all_errors(): void
    {
        // Arrange
        $errors = [
            'name' => ['名稱錯誤1', '名稱錯誤2'],
            'email' => ['電子郵件錯誤'],
            'age' => ['年齡錯誤1', '年齡錯誤2', '年齡錯誤3'],
        ];
        $exception = ValidationException::fromErrors($errors);

        // Act
        $allErrors = $exception->getValidationResult()->getAllErrors();

        // Assert
        $expectedErrors = [
            '名稱錯誤1',
            '名稱錯誤2',
            '電子郵件錯誤',
            '年齡錯誤1',
            '年齡錯誤2',
            '年齡錯誤3',
        ];
        $this->assertCount(6, $allErrors);
        foreach ($expectedErrors as $expectedError) {
            $this->assertContains($expectedError, $allErrors);
        }
    }

    /**
     * 測試取得失敗的規則.
     */
    public function test_get_failed_rules(): void
    {
        // Arrange
        $errors = ['name' => ['錯誤'], 'email' => ['錯誤']];
        $failedRules = [
            'name' => ['required', 'min_length'],
            'email' => ['email'],
        ];
        $exception = ValidationException::fromErrors($errors, $failedRules);

        // Act & Assert
        $this->assertEquals($failedRules, $exception->getValidationResult()->getFailedRules());
        $this->assertEquals(['required', 'min_length'], $exception->getValidationResult()->getFieldFailedRules('name'));
        $this->assertEquals(['email'], $exception->getValidationResult()->getFieldFailedRules('email'));
        $this->assertEquals([], $exception->getValidationResult()->getFieldFailedRules('nonexistent'));
    }

    /**
     * 測試 API 回應格式.
     */
    public function test_to_api_response(): void
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
        $exception = ValidationException::fromErrors($errors, $failedRules, '驗證失敗');

        // Act
        $apiResponse = $exception->toApiResponse();

        // Assert
        $this->assertIsArray($apiResponse);
        $this->assertFalse((is_array($apiResponse) && isset((is_array($apiResponse) ? $apiResponse['success'] : (is_object($apiResponse) ? $apiResponse->success : null)))) ? (is_array($apiResponse) ? $apiResponse['success'] : (is_object($apiResponse) ? $apiResponse->success : null)) : null);
        $this->assertEquals('驗證失敗', (is_array($apiResponse) && isset((is_array($apiResponse) ? $apiResponse['message'] : (is_object($apiResponse) ? $apiResponse->message : null)))) ? (is_array($apiResponse) ? $apiResponse['message'] : (is_object($apiResponse) ? $apiResponse->message : null)) : null);
        $this->assertEquals($errors, (is_array($apiResponse) && isset((is_array($apiResponse) ? $apiResponse['errors'] : (is_object($apiResponse) ? $apiResponse->errors : null)))) ? (is_array($apiResponse) ? $apiResponse['errors'] : (is_object($apiResponse) ? $apiResponse->errors : null)) : null);
        $this->assertEquals($failedRules, (is_array($apiResponse) && isset((is_array($apiResponse) ? $apiResponse['failed_rules'] : (is_object($apiResponse) ? $apiResponse->failed_rules : null)))) ? (is_array($apiResponse) ? $apiResponse['failed_rules'] : (is_object($apiResponse) ? $apiResponse->failed_rules : null)) : null);
    }

    /**
     * 測試除錯資訊.
     */
    public function test_to_debug_array(): void
    {
        // Arrange
        $errors = ['field' => ['錯誤']];
        $exception = ValidationException::fromErrors($errors);

        // Act
        $debugInfo = $exception->toDebugArray();

        // Assert
        $this->assertIsArray($debugInfo);
        $this->assertArrayHasKey('message', $debugInfo);
        $this->assertArrayHasKey('code', $debugInfo);
        $this->assertArrayHasKey('file', $debugInfo);
        $this->assertArrayHasKey('line', $debugInfo);
        $this->assertArrayHasKey('validation_result', $debugInfo);
        $this->assertIsArray((is_array($debugInfo) && isset((is_array($debugInfo) ? $debugInfo['validation_result'] : (is_object($debugInfo) ? $debugInfo->validation_result : null)))) ? (is_array($debugInfo) ? $debugInfo['validation_result'] : (is_object($debugInfo) ? $debugInfo->validation_result : null)) : null);
    }

    /**
     * 測試錯誤統計功能.
     */
    public function test_error_statistics(): void
    {
        // Arrange
        $errors = [
            'name' => ['錯誤1', '錯誤2'],
            'email' => ['錯誤3'],
            'age' => ['錯誤4', '錯誤5', '錯誤6'],
        ];
        $exception = ValidationException::fromErrors($errors);

        // Act & Assert
        $this->assertEquals(6, $exception->getValidationResult()->getErrorCount());
        $this->assertEquals(3, $exception->getValidationResult()->getAffectedFieldCount());
    }

    /**
     * 測試檢查是否為特定規則的驗證失敗.
     */
    public function test_has_failed_rule(): void
    {
        // Arrange
        $errors = ['name' => ['錯誤'], 'email' => ['錯誤']];
        $failedRules = [
            'name' => ['required', 'min_length'],
            'email' => ['email'],
        ];
        $exception = ValidationException::fromErrors($errors, $failedRules);

        // Act & Assert
        $this->assertTrue($exception->getValidationResult()->hasFailedRule('required'));
        $this->assertTrue($exception->getValidationResult()->hasFailedRule('min_length'));
        $this->assertTrue($exception->getValidationResult()->hasFailedRule('email'));
        $this->assertFalse($exception->getValidationResult()->hasFailedRule('max_length'));
        $this->assertFalse($exception->getValidationResult()->hasFailedRule('nonexistent'));
    }

    /**
     * 測試檢查特定欄位是否因特定規則失敗.
     */
    public function test_has_field_failed_rule(): void
    {
        // Arrange
        $errors = ['name' => ['錯誤'], 'email' => ['錯誤']];
        $failedRules = [
            'name' => ['required', 'min_length'],
            'email' => ['email'],
        ];
        $exception = ValidationException::fromErrors($errors, $failedRules);

        // Act & Assert
        $this->assertTrue($exception->getValidationResult()->hasFieldFailedRule('name', 'required'));
        $this->assertTrue($exception->getValidationResult()->hasFieldFailedRule('name', 'min_length'));
        $this->assertFalse($exception->getValidationResult()->hasFieldFailedRule('name', 'email'));
        $this->assertTrue($exception->getValidationResult()->hasFieldFailedRule('email', 'email'));
        $this->assertFalse($exception->getValidationResult()->hasFieldFailedRule('email', 'required'));
        $this->assertFalse($exception->getValidationResult()->hasFieldFailedRule('nonexistent', 'required'));
    }

    /**
     * 測試異常鏈（前一個異常）.
     */
    public function test_exception_chaining(): void
    {
        // Arrange
        $previousException = new Exception('前一個異常');
        $errors = ['field' => ['驗證錯誤']];
        $validationResult = ValidationResult::failure($errors);

        // Act
        $exception = new ValidationException($validationResult, 'Main error', 400, $previousException);

        // Assert
        $this->assertSame($previousException, $exception->getPrevious());
        $this->assertEquals('前一個異常', $exception->getPrevious()->getMessage());
    }

    /**
     * 測試 JSON 序列化.
     */
    public function test_json_serialization(): void
    {
        // Arrange
        $errors = ['name' => ['名稱為必填項目']];
        $failedRules = ['name' => ['required']];
        $exception = ValidationException::fromErrors($errors, $failedRules);

        // Act
        $apiResponse = $exception->toApiResponse();
        $json = json_encode($apiResponse);
        $decoded = json_decode($json, true);

        // Assert
        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertFalse((is_array($decoded) && isset((is_array($decoded) ? $decoded['success'] : (is_object($decoded) ? $decoded->success : null)))) ? (is_array($decoded) ? $decoded['success'] : (is_object($decoded) ? $decoded->success : null)) : null);
        $this->assertEquals($errors, (is_array($decoded) && isset((is_array($decoded) ? $decoded['errors'] : (is_object($decoded) ? $decoded->errors : null)))) ? (is_array($decoded) ? $decoded['errors'] : (is_object($decoded) ? $decoded->errors : null)) : null);
        $this->assertEquals($failedRules, (is_array($decoded) && isset((is_array($decoded) ? $decoded['failed_rules'] : (is_object($decoded) ? $decoded->failed_rules : null)))) ? (is_array($decoded) ? $decoded['failed_rules'] : (is_object($decoded) ? $decoded->failed_rules : null)) : null);
    }

    /**
     * 測試大量錯誤的效能.
     */
    public function test_performance_with_many_errors(): void
    {
        // Arrange
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $largeErrors = [];
        $largeFailedRules = [];

        for ($i = 0; $i < 500; $i++) {
            $field = "field_{$i}";
            $largeErrors[$field] = ["錯誤_{$i}_1", "錯誤_{$i}_2"];
            $largeFailedRules[$field] = ["rule_{$i}_1", "rule_{$i}_2"];
        }

        // Act
        $exception = ValidationException::fromErrors($largeErrors);

        // 執行各種操作
        $exception->getValidationResult()->getErrors();
        $exception->getValidationResult()->getAllErrors();
        $exception->getValidationResult()->getErrorCount();
        $exception->getValidationResult()->getAffectedFieldCount();
        $exception->toApiResponse();
        $exception->toDebugArray();

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Assert
        $executionTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        $this->assertLessThan(0.3, $executionTime, '大量錯誤處理應該在 0.3 秒內完成');
        $this->assertLessThan(20 * 1024 * 1024, $memoryUsage, '記憶體使用量應該少於 20MB');
    }

    /**
     * 測試異常訊息國際化支援.
     */
    public function test_internationalization_support(): void
    {
        // Arrange - 測試中文錯誤訊息
        $chineseErrors = [
            'name' => ['姓名為必填項目', '姓名長度必須至少2個字元'],
            'email' => ['電子郵件地址格式不正確'],
        ];
        $chineseException = ValidationException::fromErrors($chineseErrors);

        // Act & Assert
        $this->assertStringContainsString('姓名為必填項目', $chineseException->getMessage());
        $apiResponse = $chineseException->toApiResponse();
        $this->assertEquals($chineseErrors, (is_array($apiResponse) && isset((is_array($apiResponse) ? $apiResponse['errors'] : (is_object($apiResponse) ? $apiResponse->errors : null)))) ? (is_array($apiResponse) ? $apiResponse['errors'] : (is_object($apiResponse) ? $apiResponse->errors : null)) : null);

        // Arrange - 測試英文錯誤訊息
        $englishErrors = [
            'name' => ['Name is required', 'Name must be at least 2 characters'],
            'email' => ['Email format is invalid'],
        ];
        $englishException = ValidationException::fromErrors($englishErrors);

        // Act & Assert
        $this->assertStringContainsString('Name is required', $englishException->getMessage());
        $apiResponse = $englishException->toApiResponse();
        $this->assertEquals($englishErrors, (is_array($apiResponse) && isset((is_array($apiResponse) ? $apiResponse['errors'] : (is_object($apiResponse) ? $apiResponse->errors : null)))) ? (is_array($apiResponse) ? $apiResponse['errors'] : (is_object($apiResponse) ? $apiResponse->errors : null)) : null);
    }

    /**
     * 測試空錯誤情況的邊界條件.
     */
    public function test_edge_cases_with_empty_errors(): void
    {
        // Arrange
        $emptyErrors = [];
        $exception = ValidationException::fromErrors($emptyErrors);

        // Act & Assert
        $this->assertEquals([], $exception->getValidationResult()->getErrors());
        $this->assertEquals([], $exception->getValidationResult()->getAllErrors());
        $this->assertEquals(0, $exception->getValidationResult()->getErrorCount());
        $this->assertEquals(0, $exception->getValidationResult()->getAffectedFieldCount());
        $this->assertNull($exception->getValidationResult()->getFirstError());
        $this->assertEquals('驗證失敗', $exception->getMessage());
    }
}
