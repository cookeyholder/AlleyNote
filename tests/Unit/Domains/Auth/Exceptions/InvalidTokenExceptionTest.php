<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Exceptions;

use App\Domains\Auth\Exceptions\InvalidTokenException;
use PHPUnit\Framework\TestCase;

/**
 * 無效 Token 例外單元測試.
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class InvalidTokenExceptionTest extends TestCase
{
    /**
     * 測試基本建構功能.
     */
    public function testConstructor(): void
    {
        $exception = new InvalidTokenException();

        $this->assertSame(InvalidTokenException::ERROR_CODE, $exception->getCode());
        $this->assertSame('invalid_token', $exception->getErrorType());
        $this->assertStringContainsString('Access token is invalid', $exception->getMessage());
    }

    /**
     * 測試具體原因建構.
     */
    public function testConstructorWithReason(): void
    {
        $exception = new InvalidTokenException(
            InvalidTokenException::REASON_SIGNATURE_INVALID,
            InvalidTokenException::REFRESH_TOKEN,
        );

        $this->assertSame('Refresh token signature verification failed', $exception->getMessage());
        $this->assertSame(InvalidTokenException::REASON_SIGNATURE_INVALID, $exception->getReason());
        $this->assertSame(InvalidTokenException::REFRESH_TOKEN, $exception->getTokenType());
        $this->assertTrue($exception->isRefreshTokenInvalid());
        $this->assertFalse($exception->isAccessTokenInvalid());
    }

    /**
     * 測試自定義錯誤訊息.
     */
    public function testCustomMessage(): void
    {
        $customMessage = 'Custom invalid token message';
        $exception = new InvalidTokenException(
            InvalidTokenException::REASON_MALFORMED,
            InvalidTokenException::ACCESS_TOKEN,
            $customMessage,
        );

        $this->assertSame($customMessage, $exception->getMessage());
        $this->assertSame(InvalidTokenException::REASON_MALFORMED, $exception->getReason());
    }

    /**
     * 測試額外上下文資訊.
     */
    public function testAdditionalContext(): void
    {
        $additionalContext = [
            'token_id' => 'test-token-123',
            'request_id' => 'req-456',
        ];

        $exception = new InvalidTokenException(
            InvalidTokenException::REASON_BLACKLISTED,
            InvalidTokenException::ACCESS_TOKEN,
            '',
            $additionalContext,
        );

        $context = $exception->getContext();
        $this->assertSame('test-token-123', (is_array($context) ? $context['token_id'] : (is_object($context) ? $context->token_id : null)));
        $this->assertSame('req-456', (is_array($context) ? $context['request_id'] : (is_object($context) ? $context->request_id : null)));
        $this->assertSame(InvalidTokenException::REASON_BLACKLISTED, (is_array($context) ? $context['reason'] : (is_object($context) ? $context->reason : null)));
        $this->assertSame(InvalidTokenException::ACCESS_TOKEN, (is_array($context) ? $context['token_type'] : (is_object($context) ? $context->token_type : null)));
        $this->assertArrayHasKey('timestamp', $context);
    }

    /**
     * 測試所有預設訊息.
     */
    public function testAllDefaultMessages(): void
    {
        $reasons = [
            InvalidTokenException::REASON_MALFORMED => 'Access token format is malformed',
            InvalidTokenException::REASON_SIGNATURE_INVALID => 'Access token signature verification failed',
            InvalidTokenException::REASON_ALGORITHM_MISMATCH => 'Access token algorithm does not match expected algorithm',
            InvalidTokenException::REASON_ISSUER_INVALID => 'Access token issuer is invalid',
            InvalidTokenException::REASON_AUDIENCE_INVALID => 'Access token audience is invalid',
            InvalidTokenException::REASON_SUBJECT_MISSING => 'Access token subject is missing',
            InvalidTokenException::REASON_CLAIMS_INVALID => 'Access token contains invalid claims',
            InvalidTokenException::REASON_BLACKLISTED => 'Access token has been blacklisted',
            InvalidTokenException::REASON_NOT_BEFORE => 'Access token is not valid yet',
        ];

        foreach ($reasons as $reason => $expectedMessage) {
            $exception = new InvalidTokenException($reason);
            $this->assertSame($expectedMessage, $exception->getMessage());
        }
    }

    /**
     * 測試用戶友好訊息.
     */
    public function testUserFriendlyMessages(): void
    {
        $testCases = [
            [InvalidTokenException::REASON_MALFORMED, '格式錯誤或已損壞'],
            [InvalidTokenException::REASON_SIGNATURE_INVALID, '格式錯誤或已損壞'],
            [InvalidTokenException::REASON_ALGORITHM_MISMATCH, '格式錯誤或已損壞'],
            [InvalidTokenException::REASON_DECODE_FAILED, '格式錯誤或已損壞'],
            [InvalidTokenException::REASON_ISSUER_INVALID, '不適用於當前應用程式'],
            [InvalidTokenException::REASON_AUDIENCE_INVALID, '不適用於當前應用程式'],
            [InvalidTokenException::REASON_SUBJECT_MISSING, '缺少必要的用戶資訊'],
            [InvalidTokenException::REASON_CLAIMS_INVALID, '包含無效的聲明資訊'],
            [InvalidTokenException::REASON_BLACKLISTED, '已被撤銷'],
            [InvalidTokenException::REASON_NOT_BEFORE, '尚未生效'],
        ];

        foreach ($testCases as [$reason, $expectedPhrase]) {
            $exception = new InvalidTokenException($reason);
            $message = $exception->getUserFriendlyMessage();
            $this->assertStringContainsString($expectedPhrase, $message);
            $this->assertStringContainsString('重新登入', $message);
        }
    }

    /**
     * 測試原因檢查方法.
     */
    public function testIsReason(): void
    {
        $exception = new InvalidTokenException(InvalidTokenException::REASON_SIGNATURE_INVALID);

        $this->assertTrue($exception->isReason(InvalidTokenException::REASON_SIGNATURE_INVALID));
        $this->assertFalse($exception->isReason(InvalidTokenException::REASON_MALFORMED));
    }

    /**
     * 測試分類檢查方法.
     */
    public function testCategoryChecks(): void
    {
        // 測試簽章相關錯誤
        $signatureException = new InvalidTokenException(InvalidTokenException::REASON_SIGNATURE_INVALID);
        $this->assertTrue($signatureException->isSignatureRelated());
        $this->assertFalse($signatureException->isFormatRelated());
        $this->assertFalse($signatureException->isClaimsRelated());

        $algorithmException = new InvalidTokenException(InvalidTokenException::REASON_ALGORITHM_MISMATCH);
        $this->assertTrue($algorithmException->isSignatureRelated());

        // 測試格式相關錯誤
        $malformedException = new InvalidTokenException(InvalidTokenException::REASON_MALFORMED);
        $this->assertTrue($malformedException->isFormatRelated());
        $this->assertFalse($malformedException->isSignatureRelated());

        $decodeException = new InvalidTokenException(InvalidTokenException::REASON_DECODE_FAILED);
        $this->assertTrue($decodeException->isFormatRelated());

        // 測試聲明相關錯誤
        $issuerException = new InvalidTokenException(InvalidTokenException::REASON_ISSUER_INVALID);
        $this->assertTrue($issuerException->isClaimsRelated());
        $this->assertFalse($issuerException->isFormatRelated());

        $audienceException = new InvalidTokenException(InvalidTokenException::REASON_AUDIENCE_INVALID);
        $this->assertTrue($audienceException->isClaimsRelated());

        $subjectException = new InvalidTokenException(InvalidTokenException::REASON_SUBJECT_MISSING);
        $this->assertTrue($subjectException->isClaimsRelated());

        $claimsException = new InvalidTokenException(InvalidTokenException::REASON_CLAIMS_INVALID);
        $this->assertTrue($claimsException->isClaimsRelated());
    }

    /**
     * 測試靜態工廠方法 - malformed.
     */
    public function testMalformedFactoryMethod(): void
    {
        $context = ['raw_token' => 'invalid.format'];
        $exception = InvalidTokenException::malformed(InvalidTokenException::REFRESH_TOKEN, $context);

        $this->assertInstanceOf(InvalidTokenException::class, $exception);
        $this->assertTrue($exception->isReason(InvalidTokenException::REASON_MALFORMED));
        $this->assertTrue($exception->isRefreshTokenInvalid());
        $this->assertSame('invalid.format', $exception->getContext()['raw_token']);
    }

    /**
     * 測試靜態工廠方法 - signatureInvalid.
     */
    public function testSignatureInvalidFactoryMethod(): void
    {
        $context = ['signature_method' => 'RS256'];
        $exception = InvalidTokenException::signatureInvalid(InvalidTokenException::ACCESS_TOKEN, $context);

        $this->assertTrue($exception->isReason(InvalidTokenException::REASON_SIGNATURE_INVALID));
        $this->assertTrue($exception->isAccessTokenInvalid());
        $this->assertTrue($exception->isSignatureRelated());
    }

    /**
     * 測試靜態工廠方法 - algorithmMismatch.
     */
    public function testAlgorithmMismatchFactoryMethod(): void
    {
        $exception = InvalidTokenException::algorithmMismatch('RS256', 'HS256', InvalidTokenException::ACCESS_TOKEN);

        $this->assertTrue($exception->isReason(InvalidTokenException::REASON_ALGORITHM_MISMATCH));
        $this->assertTrue($exception->isSignatureRelated());

        $context = $exception->getContext();
        $this->assertSame('RS256', (is_array($context) ? $context['expected_algorithm'] : (is_object($context) ? $context->expected_algorithm : null)));
        $this->assertSame('HS256', (is_array($context) ? $context['actual_algorithm'] : (is_object($context) ? $context->actual_algorithm : null)));
    }

    /**
     * 測試靜態工廠方法 - issuerInvalid.
     */
    public function testIssuerInvalidFactoryMethod(): void
    {
        $exception = InvalidTokenException::issuerInvalid('https://auth.example.com', 'https://fake.com');

        $this->assertTrue($exception->isReason(InvalidTokenException::REASON_ISSUER_INVALID));
        $this->assertTrue($exception->isClaimsRelated());

        $context = $exception->getContext();
        $this->assertSame('https://auth.example.com', (is_array($context) ? $context['expected_issuer'] : (is_object($context) ? $context->expected_issuer : null)));
        $this->assertSame('https://fake.com', (is_array($context) ? $context['actual_issuer'] : (is_object($context) ? $context->actual_issuer : null)));
    }

    /**
     * 測試靜態工廠方法 - audienceInvalid.
     */
    public function testAudienceInvalidFactoryMethod(): void
    {
        $exception = InvalidTokenException::audienceInvalid('api.example.com', 'wrong.com', InvalidTokenException::REFRESH_TOKEN);

        $this->assertTrue($exception->isReason(InvalidTokenException::REASON_AUDIENCE_INVALID));
        $this->assertTrue($exception->isRefreshTokenInvalid());
        $this->assertTrue($exception->isClaimsRelated());

        $context = $exception->getContext();
        $this->assertSame('api.example.com', (is_array($context) ? $context['expected_audience'] : (is_object($context) ? $context->expected_audience : null)));
        $this->assertSame('wrong.com', (is_array($context) ? $context['actual_audience'] : (is_object($context) ? $context->actual_audience : null)));
    }

    /**
     * 測試靜態工廠方法 - subjectMissing.
     */
    public function testSubjectMissingFactoryMethod(): void
    {
        $exception = InvalidTokenException::subjectMissing(InvalidTokenException::ACCESS_TOKEN);

        $this->assertTrue($exception->isReason(InvalidTokenException::REASON_SUBJECT_MISSING));
        $this->assertTrue($exception->isAccessTokenInvalid());
        $this->assertTrue($exception->isClaimsRelated());
    }

    /**
     * 測試靜態工廠方法 - claimsInvalid.
     */
    public function testClaimsInvalidFactoryMethod(): void
    {
        $invalidClaims = ['exp' => 'invalid-timestamp', 'aud' => null];
        $exception = InvalidTokenException::claimsInvalid($invalidClaims);

        $this->assertTrue($exception->isReason(InvalidTokenException::REASON_CLAIMS_INVALID));
        $this->assertTrue($exception->isClaimsRelated());

        $context = $exception->getContext();
        $this->assertSame($invalidClaims, (is_array($context) ? $context['invalid_claims'] : (is_object($context) ? $context->invalid_claims : null)));
    }

    /**
     * 測試靜態工廠方法 - blacklisted.
     */
    public function testBlacklistedFactoryMethod(): void
    {
        $tokenId = 'token-123-456';
        $exception = InvalidTokenException::blacklisted($tokenId, InvalidTokenException::REFRESH_TOKEN);

        $this->assertTrue($exception->isReason(InvalidTokenException::REASON_BLACKLISTED));
        $this->assertTrue($exception->isRefreshTokenInvalid());

        $context = $exception->getContext();
        $this->assertSame($tokenId, (is_array($context) ? $context['token_id'] : (is_object($context) ? $context->token_id : null)));
    }

    /**
     * 測試靜態工廠方法 - notBefore.
     */
    public function testNotBeforeFactoryMethod(): void
    {
        $notBefore = time() + 3600; // 1 hour in future
        $exception = InvalidTokenException::notBefore($notBefore);

        $this->assertTrue($exception->isReason(InvalidTokenException::REASON_NOT_BEFORE));

        $context = $exception->getContext();
        $this->assertSame($notBefore, (is_array($context) ? $context['not_before'] : (is_object($context) ? $context->not_before : null)));
        $this->assertArrayHasKey('not_before_human', $context);
        $this->assertSame(date('Y-m-d H:i:s', $notBefore), (is_array($context) ? $context['not_before_human'] : (is_object($context) ? $context->not_before_human : null)));
    }

    /**
     * 測試錯誤詳細資訊.
     */
    public function testErrorDetails(): void
    {
        $exception = new InvalidTokenException(
            InvalidTokenException::REASON_SIGNATURE_INVALID,
            InvalidTokenException::ACCESS_TOKEN,
        );

        $details = $exception->getErrorDetails();

        $this->assertSame('invalid_token', (is_array($details) ? $details['error_type'] : (is_object($details) ? $details->error_type : null)));
        $this->assertSame(InvalidTokenException::ERROR_CODE, (is_array($details) ? $details['code'] : (is_object($details) ? $details->code : null)));
        $this->assertArrayHasKey('context', $details);
        $this->assertSame(InvalidTokenException::REASON_SIGNATURE_INVALID, (is_array($details) ? $details['context'] : (is_object($details) ? $details->context : null))['reason']);
    }

    /**
     * 測試預設值
     */
    public function testDefaults(): void
    {
        $exception = new InvalidTokenException();

        $this->assertSame(InvalidTokenException::REASON_DECODE_FAILED, $exception->getReason());
        $this->assertSame(InvalidTokenException::ACCESS_TOKEN, $exception->getTokenType());
        $this->assertTrue($exception->isAccessTokenInvalid());
        $this->assertFalse($exception->isRefreshTokenInvalid());
    }

    /**
     * 測試複雜場景組合.
     */
    public function testComplexScenario(): void
    {
        // 模擬一個複雜的無效 Token 場景
        $additionalContext = [
            'request_id' => 'req-12345',
            'user_agent' => 'TestAgent/1.0',
            'ip_address' => '192.168.1.100',
            'attempted_resource' => '/api/user/profile',
        ];

        $exception = new InvalidTokenException(
            InvalidTokenException::REASON_SIGNATURE_INVALID,
            InvalidTokenException::ACCESS_TOKEN,
            'Token signature verification failed during profile access',
            $additionalContext,
        );

        $this->assertSame('Token signature verification failed during profile access', $exception->getMessage());
        $this->assertTrue($exception->isSignatureRelated());
        $this->assertTrue($exception->isAccessTokenInvalid());

        $context = $exception->getContext();
        $this->assertSame('req-12345', (is_array($context) ? $context['request_id'] : (is_object($context) ? $context->request_id : null)));
        $this->assertSame('/api/user/profile', (is_array($context) ? $context['attempted_resource'] : (is_object($context) ? $context->attempted_resource : null)));

        $details = $exception->getErrorDetails();
        $this->assertArrayHasKey('request_id', (is_array($details) ? $details['context'] : (is_object($details) ? $details->context : null)));
    }
}
