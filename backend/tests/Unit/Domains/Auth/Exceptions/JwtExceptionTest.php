<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Exceptions;

use App\Domains\Auth\Exceptions\JwtException;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * JWT 基礎例外類別單元測試.
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class JwtExceptionTest extends TestCase
{
    /**
     * 建立測試用的具體實作類別.
     * @param array<string, mixed> $context
     */
    private function createConcreteJwtException(
        string $message = 'Test exception',
        int $code = 1000,
        ?Exception $previous = null,
        array $context = [],
        string $errorType = 'test_jwt_error',
    ): JwtException {
        return new class ($message, $code, $previous, $context, $errorType) extends JwtException {
            public function __construct(
                string $message,
                int $code,
                ?Exception $previous,
                array $context,
                string $errorType,
            ) {
                parent::__construct($message, $code, $previous, $context);
                $this->errorType = $errorType;
            }
        };
    }

    /**
     * 測試基本建構功能.
     */
    public function testConstructor(): void
    {
        $message = 'Test JWT exception';
        $code = 1001;
        $context = ['user_id' => 123, 'token_type' => 'access'];
        $previous = new Exception('Previous exception');

        $exception = $this->createConcreteJwtException($message, $code, $previous, $context);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($context, $exception->getContext());
    }

    /**
     * 測試預設參數建構.
     */
    public function testConstructorWithDefaults(): void
    {
        $exception = $this->createConcreteJwtException();

        $this->assertSame('Test exception', $exception->getMessage());
        $this->assertSame(1000, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    /**
     * 測試取得和設定上下文資訊.
     */
    public function testContextGetterAndSetter(): void
    {
        $exception = $this->createConcreteJwtException();

        // 測試初始空上下文
        $this->assertSame([], $exception->getContext());

        // 測試設定上下文
        $context = ['user_id' => 456, 'action' => 'login'];
        $result = $exception->setContext($context);

        $this->assertSame($exception, $result); // 測試流暢介面
        $this->assertSame($context, $exception->getContext());
    }

    /**
     * 測試加入上下文資訊.
     */
    public function testAddContext(): void
    {
        $initialContext = ['user_id' => 123];
        $exception = $this->createConcreteJwtException(context: $initialContext);

        // 測試加入新的上下文
        $result = $exception->addContext('token_type', 'refresh');

        $this->assertSame($exception, $result); // 測試流暢介面
        $this->assertSame([
            'user_id' => 123,
            'token_type' => 'refresh',
        ], $exception->getContext());

        // 測試覆寫現有的上下文
        $exception->addContext('user_id', 456);
        $this->assertSame(456, $exception->getContext()['user_id']);
    }

    /**
     * 測試錯誤類型相關功能.
     */
    public function testErrorType(): void
    {
        $errorType = 'custom_jwt_error';
        $exception = $this->createConcreteJwtException(errorType: $errorType);

        $this->assertSame($errorType, $exception->getErrorType());
        $this->assertTrue($exception->isType($errorType));
        $this->assertFalse($exception->isType('other_error'));
    }

    /**
     * 測試錯誤詳細資訊.
     */
    public function testGetErrorDetails(): void
    {
        $message = 'Test error';
        $code = 2000;
        $context = ['key' => 'value'];
        $errorType = 'test_error';

        $exception = $this->createConcreteJwtException($message, $code, null, $context, $errorType);

        $details = $exception->getErrorDetails();

        $this->assertIsArray($details);
        $this->assertSame($errorType, $details['error_type']);
        $this->assertSame($message, $details['message']);
        $this->assertSame($code, $details['code']);
        $this->assertSame($context, $details['context']);
        $this->assertArrayHasKey('file', $details);
        $this->assertArrayHasKey('line', $details);
    }

    /**
     * 測試用戶友好訊息.
     */
    public function testGetUserFriendlyMessage(): void
    {
        $message = 'Technical error message';
        $exception = $this->createConcreteJwtException($message);

        // 基礎實作應該返回原始訊息
        $this->assertSame($message, $exception->getUserFriendlyMessage());
    }

    /**
     * 測試轉換為陣列.
     */
    public function testToArray(): void
    {
        $message = 'Test message';
        $code = 3000;
        $context = ['test' => 'data'];
        $errorType = 'array_test_error';

        $exception = $this->createConcreteJwtException($message, $code, null, $context, $errorType);

        $array = $exception->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('exception', $array);
        $this->assertArrayHasKey('error_type', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('context', $array);
        $this->assertArrayHasKey('file', $array);
        $this->assertArrayHasKey('line', $array);
        $this->assertArrayHasKey('trace', $array);

        $this->assertSame($errorType, $array['error_type']);
        $this->assertSame($message, $array['message']);
        $this->assertSame($code, $array['code']);
        $this->assertSame($context, $array['context']);
    }

    /**
     * 測試字串轉換（無上下文）.
     */
    public function testToStringWithoutContext(): void
    {
        $message = 'Test error';
        $code = 4000;
        $errorType = 'string_test_error';

        $exception = $this->createConcreteJwtException($message, $code, null, [], $errorType);

        $string = (string) $exception;

        $this->assertStringContainsString($errorType, $string);
        $this->assertStringContainsString($message, $string);
        $this->assertStringContainsString((string) $code, $string);
        $this->assertStringNotContainsString('Context:', $string);
    }

    /**
     * 測試字串轉換（有上下文）.
     */
    public function testToStringWithContext(): void
    {
        $message = 'Test error';
        $code = 4001;
        $context = ['user' => 'test', 'action' => 'login'];
        $errorType = 'context_string_test_error';

        $exception = $this->createConcreteJwtException($message, $code, null, $context, $errorType);

        $string = (string) $exception;

        $this->assertStringContainsString($errorType, $string);
        $this->assertStringContainsString($message, $string);
        $this->assertStringContainsString((string) $code, $string);
        $this->assertStringContainsString('Context:', $string);
        $this->assertStringContainsString(json_encode($context), $string);
    }

    /**
     * 測試複雜上下文資料.
     */
    public function testComplexContextData(): void
    {
        $context = [
            'user_id' => 123,
            'token_data' => [
                'type' => 'access',
                'scopes' => ['read', 'write'],
                'metadata' => [
                    'created_at' => '2025-01-01T00:00:00Z',
                    'expires_at' => '2025-01-01T01:00:00Z',
                ],
            ],
            'request_info' => [
                'ip' => '192.168.1.1',
                'user_agent' => 'Test Agent',
            ],
        ];

        $exception = $this->createConcreteJwtException(context: $context);

        $this->assertSame($context, $exception->getContext());

        // 測試嵌套資料的存取
        $tokenData = $exception->getContext()['token_data'];
        $this->assertSame('access', $tokenData['type']);
        $this->assertSame(['read', 'write'], $tokenData['scopes']);
    }

    /**
     * 測試繼承鏈.
     */
    public function testInheritanceChain(): void
    {
        $exception = $this->createConcreteJwtException();

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertInstanceOf(JwtException::class, $exception);
    }

    /**
     * 測試上下文的不可變性（確保陣列是深拷貝）.
     */
    public function testContextImmutability(): void
    {
        $originalContext = ['mutable_array' => ['value1', 'value2']];
        $exception = $this->createConcreteJwtException(context: $originalContext);

        // 修改原始上下文
        $originalContext['mutable_array'][] = 'value3';
        $originalContext['new_key'] = 'new_value';

        // 例外中的上下文應該不受影響
        $exceptionContext = $exception->getContext();
        $this->assertCount(2, $exceptionContext['mutable_array']);
        $this->assertArrayNotHasKey('new_key', $exceptionContext);
    }
}
