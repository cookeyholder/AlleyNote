<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Exceptions;

use App\Domains\Auth\Exceptions\TokenGenerationException;
use PHPUnit\Framework\TestCase;

/**
 * Token 生成失敗例外單元測試.
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class TokenGenerationExceptionTest extends TestCase
{
    /**
     * 測試基本建構功能.
     */
    public function testConstructor(): void
    {
        $exception = new TokenGenerationException();

        $this->assertSame(TokenGenerationException::ERROR_CODE, $exception->getCode());
        $this->assertSame('token_generation_failed', $exception->getErrorType());
        $this->assertStringContainsString('Failed to generate Access token: encoding process failed', $exception->getMessage());
    }

    /**
     * 測試具體原因建構.
     */
    public function testConstructorWithReason(): void
    {
        $exception = new TokenGenerationException(
            TokenGenerationException::REASON_KEY_INVALID,
            TokenGenerationException::REFRESH_TOKEN,
        );

        $this->assertSame('Failed to generate Refresh token: private key is invalid or corrupted', $exception->getMessage());
        $this->assertSame(TokenGenerationException::REASON_KEY_INVALID, $exception->getReason());
        $this->assertSame(TokenGenerationException::REFRESH_TOKEN, $exception->getTokenType());
        $this->assertTrue($exception->isRefreshTokenGeneration());
        $this->assertFalse($exception->isAccessTokenGeneration());
    }

    /**
     * 測試自定義錯誤訊息.
     */
    public function testCustomMessage(): void
    {
        $customMessage = 'Custom generation failure message';
        $exception = new TokenGenerationException(
            TokenGenerationException::REASON_PAYLOAD_INVALID,
            TokenGenerationException::ACCESS_TOKEN,
            $customMessage,
        );

        $this->assertSame($customMessage, $exception->getMessage());
        $this->assertSame(TokenGenerationException::REASON_PAYLOAD_INVALID, $exception->getReason());
    }

    /**
     * 測試額外上下文資訊.
     */
    public function testAdditionalContext(): void
    {
        $additionalContext = [
            'user_id' => 123,
            'request_id' => 'req-456',
            'key_length' => 2048,
        ];

        $exception = new TokenGenerationException(
            TokenGenerationException::REASON_SIGNATURE_FAILED,
            TokenGenerationException::ACCESS_TOKEN,
            '',
            $additionalContext,
        );

        $context = $exception->getContext();
        $this->assertSame(123, $context['user_id']);
        $this->assertSame('req-456', $context['request_id']);
        $this->assertSame(2048, $context['key_length']);
        $this->assertSame(TokenGenerationException::REASON_SIGNATURE_FAILED, $context['reason']);
        $this->assertArrayHasKey('generation_attempt_id', $context);
        $this->assertStringStartsWith('gen_', $context['generation_attempt_id']);
    }

    /**
     * 測試所有預設訊息.
     */
    public function testAllDefaultMessages(): void
    {
        $reasons = [
            TokenGenerationException::REASON_KEY_INVALID => 'private key is invalid or corrupted',
            TokenGenerationException::REASON_KEY_MISSING => 'private key is missing',
            TokenGenerationException::REASON_PAYLOAD_INVALID => 'payload contains invalid data',
            TokenGenerationException::REASON_ALGORITHM_UNSUPPORTED => 'algorithm is not supported',
            TokenGenerationException::REASON_CLAIMS_INVALID => 'claims validation failed',
            TokenGenerationException::REASON_SIGNATURE_FAILED => 'signature generation failed',
            TokenGenerationException::REASON_RESOURCE_EXHAUSTED => 'system resources exhausted',
            TokenGenerationException::REASON_ENCODING_FAILED => 'encoding process failed',
        ];

        foreach ($reasons as $reason => $expectedPhrase) {
            $exception = new TokenGenerationException($reason);
            $this->assertStringContainsString($expectedPhrase, $exception->getMessage());
        }
    }

    /**
     * 測試用戶友好訊息.
     */
    public function testUserFriendlyMessages(): void
    {
        $testCases = [
            [TokenGenerationException::REASON_KEY_INVALID, '系統配置錯誤'],
            [TokenGenerationException::REASON_KEY_MISSING, '系統配置錯誤'],
            [TokenGenerationException::REASON_PAYLOAD_INVALID, '用戶資訊格式錯誤'],
            [TokenGenerationException::REASON_CLAIMS_INVALID, '用戶資訊格式錯誤'],
            [TokenGenerationException::REASON_ALGORITHM_UNSUPPORTED, '系統安全演算法配置錯誤'],
            [TokenGenerationException::REASON_RESOURCE_EXHAUSTED, '系統資源不足'],
            [TokenGenerationException::REASON_SIGNATURE_FAILED, '數位簽章產生失敗'],
            [TokenGenerationException::REASON_ENCODING_FAILED, 'Token 生成過程發生錯誤'],
        ];

        foreach ($testCases as [$reason, $expectedPhrase]) {
            $exception = new TokenGenerationException($reason);
            $message = $exception->getUserFriendlyMessage();
            $this->assertStringContainsString($expectedPhrase, $message);
        }
    }

    /**
     * 測試原因檢查方法.
     */
    public function testIsReason(): void
    {
        $exception = new TokenGenerationException(TokenGenerationException::REASON_KEY_INVALID);

        $this->assertTrue($exception->isReason(TokenGenerationException::REASON_KEY_INVALID));
        $this->assertFalse($exception->isReason(TokenGenerationException::REASON_KEY_MISSING));
    }

    /**
     * 測試生成嘗試 ID.
     */
    public function testGenerationAttemptId(): void
    {
        $exception = new TokenGenerationException();
        $attemptId = $exception->getGenerationAttemptId();

        $this->assertIsString($attemptId);
        $this->assertStringStartsWith('gen_', $attemptId);
        $this->assertGreaterThan(4, strlen($attemptId));
    }

    /**
     * 測試分類檢查方法.
     */
    public function testCategoryChecks(): void
    {
        // 測試金鑰相關錯誤
        $keyInvalidException = new TokenGenerationException(TokenGenerationException::REASON_KEY_INVALID);
        $this->assertTrue($keyInvalidException->isKeyRelated());
        $this->assertTrue($keyInvalidException->isSystemConfigurationError());
        $this->assertFalse($keyInvalidException->isDataRelated());
        $this->assertFalse($keyInvalidException->isRetryable());

        $keyMissingException = new TokenGenerationException(TokenGenerationException::REASON_KEY_MISSING);
        $this->assertTrue($keyMissingException->isKeyRelated());
        $this->assertTrue($keyMissingException->isSystemConfigurationError());

        // 測試資料相關錯誤
        $payloadException = new TokenGenerationException(TokenGenerationException::REASON_PAYLOAD_INVALID);
        $this->assertTrue($payloadException->isDataRelated());
        $this->assertFalse($payloadException->isKeyRelated());
        $this->assertFalse($payloadException->isSystemConfigurationError());
        $this->assertFalse($payloadException->isRetryable());

        $claimsException = new TokenGenerationException(TokenGenerationException::REASON_CLAIMS_INVALID);
        $this->assertTrue($claimsException->isDataRelated());

        // 測試系統配置錯誤
        $algorithmException = new TokenGenerationException(TokenGenerationException::REASON_ALGORITHM_UNSUPPORTED);
        $this->assertTrue($algorithmException->isSystemConfigurationError());
        $this->assertFalse($algorithmException->isKeyRelated());
        $this->assertFalse($algorithmException->isDataRelated());

        // 測試可重試錯誤
        $resourceException = new TokenGenerationException(TokenGenerationException::REASON_RESOURCE_EXHAUSTED);
        $this->assertTrue($resourceException->isRetryable());
        $this->assertFalse($resourceException->isSystemConfigurationError());

        $encodingException = new TokenGenerationException(TokenGenerationException::REASON_ENCODING_FAILED);
        $this->assertTrue($encodingException->isRetryable());
    }

    /**
     * 測試靜態工廠方法 - keyInvalid.
     */
    public function testKeyInvalidFactoryMethod(): void
    {
        $keyInfo = 'RSA-2048';
        $exception = TokenGenerationException::keyInvalid($keyInfo, TokenGenerationException::REFRESH_TOKEN);

        $this->assertInstanceOf(TokenGenerationException::class, $exception);
        $this->assertTrue($exception->isReason(TokenGenerationException::REASON_KEY_INVALID));
        $this->assertTrue($exception->isRefreshTokenGeneration());
        $this->assertTrue($exception->isKeyRelated());
        $this->assertSame($keyInfo, $exception->getContext()['key_info']);
    }

    /**
     * 測試靜態工廠方法 - keyMissing.
     */
    public function testKeyMissingFactoryMethod(): void
    {
        $exception = TokenGenerationException::keyMissing(TokenGenerationException::ACCESS_TOKEN);

        $this->assertTrue($exception->isReason(TokenGenerationException::REASON_KEY_MISSING));
        $this->assertTrue($exception->isAccessTokenGeneration());
        $this->assertTrue($exception->isKeyRelated());
    }

    /**
     * 測試靜態工廠方法 - payloadInvalid.
     */
    public function testPayloadInvalidFactoryMethod(): void
    {
        $invalidFields = ['exp' => 'invalid', 'sub' => null];
        $exception = TokenGenerationException::payloadInvalid($invalidFields);

        $this->assertTrue($exception->isReason(TokenGenerationException::REASON_PAYLOAD_INVALID));
        $this->assertTrue($exception->isDataRelated());

        $context = $exception->getContext();
        $this->assertSame($invalidFields, $context['invalid_fields']);
    }

    /**
     * 測試靜態工廠方法 - algorithmUnsupported.
     */
    public function testAlgorithmUnsupportedFactoryMethod(): void
    {
        $algorithm = 'HS512';
        $exception = TokenGenerationException::algorithmUnsupported($algorithm, TokenGenerationException::REFRESH_TOKEN);

        $this->assertTrue($exception->isReason(TokenGenerationException::REASON_ALGORITHM_UNSUPPORTED));
        $this->assertTrue($exception->isRefreshTokenGeneration());
        $this->assertTrue($exception->isSystemConfigurationError());

        $context = $exception->getContext();
        $this->assertSame($algorithm, $context['algorithm']);
    }

    /**
     * 測試靜態工廠方法 - claimsInvalid.
     */
    public function testClaimsInvalidFactoryMethod(): void
    {
        $invalidClaims = ['iss' => '', 'aud' => []];
        $exception = TokenGenerationException::claimsInvalid($invalidClaims);

        $this->assertTrue($exception->isReason(TokenGenerationException::REASON_CLAIMS_INVALID));
        $this->assertTrue($exception->isDataRelated());

        $context = $exception->getContext();
        $this->assertSame($invalidClaims, $context['invalid_claims']);
    }

    /**
     * 測試靜態工廠方法 - signatureFailed.
     */
    public function testSignatureFailedFactoryMethod(): void
    {
        $details = 'OpenSSL signature generation failed';
        $exception = TokenGenerationException::signatureFailed($details, TokenGenerationException::ACCESS_TOKEN);

        $this->assertTrue($exception->isReason(TokenGenerationException::REASON_SIGNATURE_FAILED));
        $this->assertTrue($exception->isAccessTokenGeneration());

        $context = $exception->getContext();
        $this->assertSame($details, $context['failure_details']);
    }

    /**
     * 測試靜態工廠方法 - resourceExhausted.
     */
    public function testResourceExhaustedFactoryMethod(): void
    {
        $resourceType = 'cpu';
        $exception = TokenGenerationException::resourceExhausted($resourceType, TokenGenerationException::REFRESH_TOKEN);

        $this->assertTrue($exception->isReason(TokenGenerationException::REASON_RESOURCE_EXHAUSTED));
        $this->assertTrue($exception->isRefreshTokenGeneration());
        $this->assertTrue($exception->isRetryable());

        $context = $exception->getContext();
        $this->assertSame($resourceType, $context['resource_type']);
    }

    /**
     * 測試靜態工廠方法 - encodingFailed.
     */
    public function testEncodingFailedFactoryMethod(): void
    {
        $details = 'JSON encoding failed';
        $exception = TokenGenerationException::encodingFailed($details);

        $this->assertTrue($exception->isReason(TokenGenerationException::REASON_ENCODING_FAILED));
        $this->assertTrue($exception->isRetryable());

        $context = $exception->getContext();
        $this->assertSame($details, $context['failure_details']);
    }

    /**
     * 測試工廠方法使用預設參數.
     */
    public function testFactoryMethodsWithDefaults(): void
    {
        $keyInvalidException = TokenGenerationException::keyInvalid();
        $this->assertTrue($keyInvalidException->isAccessTokenGeneration());
        $this->assertArrayNotHasKey('key_info', $keyInvalidException->getContext());

        $signatureException = TokenGenerationException::signatureFailed();
        $this->assertTrue($signatureException->isAccessTokenGeneration());
        $this->assertArrayNotHasKey('failure_details', $signatureException->getContext());

        $resourceException = TokenGenerationException::resourceExhausted();
        $this->assertTrue($resourceException->isAccessTokenGeneration());
        $this->assertSame('memory', $resourceException->getContext()['resource_type']);

        $encodingException = TokenGenerationException::encodingFailed();
        $this->assertTrue($encodingException->isAccessTokenGeneration());
        $this->assertArrayNotHasKey('failure_details', $encodingException->getContext());
    }

    /**
     * 測試錯誤詳細資訊.
     */
    public function testErrorDetails(): void
    {
        $exception = new TokenGenerationException(
            TokenGenerationException::REASON_KEY_INVALID,
            TokenGenerationException::REFRESH_TOKEN,
        );

        $details = $exception->getErrorDetails();

        $this->assertSame('token_generation_failed', $details['error_type']);
        $this->assertSame(TokenGenerationException::ERROR_CODE, $details['code']);
        $this->assertArrayHasKey('context', $details);
        $this->assertSame(TokenGenerationException::REASON_KEY_INVALID, $details['context']['reason']);
    }

    /**
     * 測試預設值
     */
    public function testDefaults(): void
    {
        $exception = new TokenGenerationException();

        $this->assertSame(TokenGenerationException::REASON_ENCODING_FAILED, $exception->getReason());
        $this->assertSame(TokenGenerationException::ACCESS_TOKEN, $exception->getTokenType());
        $this->assertTrue($exception->isAccessTokenGeneration());
        $this->assertFalse($exception->isRefreshTokenGeneration());
    }

    /**
     * 測試複雜場景組合.
     */
    public function testComplexScenario(): void
    {
        $additionalContext = [
            'user_id' => 123,
            'key_algorithm' => 'RS256',
            'key_size' => 2048,
            'payload_size' => 512,
            'memory_usage' => '128MB',
            'generation_duration' => 0.15,
        ];

        $exception = new TokenGenerationException(
            TokenGenerationException::REASON_RESOURCE_EXHAUSTED,
            TokenGenerationException::ACCESS_TOKEN,
            'Token generation failed due to memory exhaustion during key operations',
            $additionalContext,
        );

        $this->assertSame('Token generation failed due to memory exhaustion during key operations', $exception->getMessage());
        $this->assertTrue($exception->isRetryable());
        $this->assertTrue($exception->isAccessTokenGeneration());

        $context = $exception->getContext();
        $this->assertSame(123, $context['user_id']);
        $this->assertSame('RS256', $context['key_algorithm']);
        $this->assertSame(2048, $context['key_size']);
        $this->assertSame('128MB', $context['memory_usage']);

        $details = $exception->getErrorDetails();
        $this->assertArrayHasKey('user_id', $details['context']);
        $this->assertArrayHasKey('generation_attempt_id', $details['context']);
    }
}
