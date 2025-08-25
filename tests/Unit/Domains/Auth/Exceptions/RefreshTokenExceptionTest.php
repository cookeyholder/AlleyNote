<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Exceptions;

use AlleyNote\Domains\Auth\Exceptions\RefreshTokenException;
use PHPUnit\Framework\TestCase;

/**
 * Refresh Token 操作例外單元測試.
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class RefreshTokenExceptionTest extends TestCase
{
    /**
     * 測試基本建構功能.
     */
    public function testConstructor(): void
    {
        $exception = new RefreshTokenException();

        $this->assertSame(RefreshTokenException::ERROR_CODE, $exception->getCode());
        $this->assertSame('refresh_token_error', $exception->getErrorType());
        $this->assertStringContainsString('Refresh token not found or does not exist', $exception->getMessage());
    }

    /**
     * 測試具體原因建構.
     */
    public function testConstructorWithReason(): void
    {
        $exception = new RefreshTokenException(RefreshTokenException::REASON_REVOKED);

        $this->assertSame('Refresh token has been revoked', $exception->getMessage());
        $this->assertSame(RefreshTokenException::REASON_REVOKED, $exception->getReason());
    }

    /**
     * 測試自定義錯誤訊息.
     */
    public function testCustomMessage(): void
    {
        $customMessage = 'Custom refresh token error message';
        $exception = new RefreshTokenException(
            RefreshTokenException::REASON_ALREADY_USED,
            $customMessage,
        );

        $this->assertSame($customMessage, $exception->getMessage());
        $this->assertSame(RefreshTokenException::REASON_ALREADY_USED, $exception->getReason());
    }

    /**
     * 測試額外上下文資訊.
     */
    public function testAdditionalContext(): void
    {
        $additionalContext = [
            'token_id' => 'refresh-123',
            'user_id' => 456,
            'device_fingerprint' => 'abc123',
        ];

        $exception = new RefreshTokenException(
            RefreshTokenException::REASON_DEVICE_MISMATCH,
            '',
            $additionalContext,
        );

        $context = $exception->getContext();
        $this->assertSame('refresh-123', $context['token_id']);
        $this->assertSame(456, $context['user_id']);
        $this->assertSame('abc123', $context['device_fingerprint']);
        $this->assertSame(RefreshTokenException::REASON_DEVICE_MISMATCH, $context['reason']);
        $this->assertArrayHasKey('operation_id', $context);
        $this->assertStringStartsWith('refresh_', $context['operation_id']);
    }

    /**
     * 測試所有預設訊息.
     */
    public function testAllDefaultMessages(): void
    {
        $reasons = [
            RefreshTokenException::REASON_NOT_FOUND => 'not found or does not exist',
            RefreshTokenException::REASON_REVOKED => 'has been revoked',
            RefreshTokenException::REASON_ALREADY_USED => 'has already been used',
            RefreshTokenException::REASON_DEVICE_MISMATCH => 'device fingerprint does not match',
            RefreshTokenException::REASON_USER_MISMATCH => 'does not belong to the specified user',
            RefreshTokenException::REASON_STORAGE_FAILED => 'Failed to store refresh token',
            RefreshTokenException::REASON_DELETION_FAILED => 'Failed to delete refresh token',
            RefreshTokenException::REASON_ROTATION_FAILED => 'Failed to rotate refresh token',
            RefreshTokenException::REASON_LIMIT_EXCEEDED => 'limit exceeded for this user',
            RefreshTokenException::REASON_FAMILY_MISMATCH => 'does not belong to the expected token family',
        ];

        foreach ($reasons as $reason => $expectedPhrase) {
            $exception = new RefreshTokenException($reason);
            $this->assertStringContainsString($expectedPhrase, $exception->getMessage());
        }
    }

    /**
     * 測試用戶友好訊息.
     */
    public function testUserFriendlyMessages(): void
    {
        $testCases = [
            [RefreshTokenException::REASON_NOT_FOUND, '找不到有效的 Refresh Token'],
            [RefreshTokenException::REASON_REVOKED, '登入憑證已被撤銷'],
            [RefreshTokenException::REASON_ALREADY_USED, '已經使用過'],
            [RefreshTokenException::REASON_DEVICE_MISMATCH, '裝置驗證失敗'],
            [RefreshTokenException::REASON_USER_MISMATCH, '不屬於當前用戶'],
            [RefreshTokenException::REASON_STORAGE_FAILED, '系統暫時無法處理'],
            [RefreshTokenException::REASON_DELETION_FAILED, '系統暫時無法處理'],
            [RefreshTokenException::REASON_ROTATION_FAILED, 'Token 更新失敗'],
            [RefreshTokenException::REASON_LIMIT_EXCEEDED, '登入裝置數量已達上限'],
            [RefreshTokenException::REASON_FAMILY_MISMATCH, 'Token 系列驗證失敗'],
        ];

        foreach ($testCases as [$reason, $expectedPhrase]) {
            $exception = new RefreshTokenException($reason);
            $message = $exception->getUserFriendlyMessage();
            $this->assertStringContainsString($expectedPhrase, $message);
        }
    }

    /**
     * 測試取得操作 ID.
     */
    public function testOperationId(): void
    {
        $exception = new RefreshTokenException();
        $operationId = $exception->getOperationId();

        $this->assertIsString($operationId);
        $this->assertStringStartsWith('refresh_', $operationId);
        $this->assertGreaterThan(8, strlen($operationId));
    }

    /**
     * 測試上下文取值方法.
     */
    public function testContextGetters(): void
    {
        $context = [
            'user_id' => 123,
            'token_id' => 'token-abc',
            'device_info' => ['browser' => 'Chrome', 'os' => 'Windows'],
        ];

        $exception = new RefreshTokenException(
            RefreshTokenException::REASON_DEVICE_MISMATCH,
            '',
            $context,
        );

        $this->assertSame(123, $exception->getUserId());
        $this->assertSame('token-abc', $exception->getTokenId());
        $this->assertSame(['browser' => 'Chrome', 'os' => 'Windows'], $exception->getDeviceInfo());
    }

    /**
     * 測試空上下文的 getter 方法.
     */
    public function testContextGettersWithEmptyContext(): void
    {
        $exception = new RefreshTokenException();

        $this->assertNull($exception->getUserId());
        $this->assertNull($exception->getTokenId());
        $this->assertNull($exception->getDeviceInfo());
    }

    /**
     * 測試原因檢查方法.
     */
    public function testIsReason(): void
    {
        $exception = new RefreshTokenException(RefreshTokenException::REASON_REVOKED);

        $this->assertTrue($exception->isReason(RefreshTokenException::REASON_REVOKED));
        $this->assertFalse($exception->isReason(RefreshTokenException::REASON_NOT_FOUND));
    }

    /**
     * 測試分類檢查方法.
     */
    public function testCategoryChecks(): void
    {
        // 測試安全相關錯誤
        $securityReasons = [
            RefreshTokenException::REASON_REVOKED,
            RefreshTokenException::REASON_ALREADY_USED,
            RefreshTokenException::REASON_DEVICE_MISMATCH,
            RefreshTokenException::REASON_USER_MISMATCH,
            RefreshTokenException::REASON_FAMILY_MISMATCH,
        ];

        foreach ($securityReasons as $reason) {
            $exception = new RefreshTokenException($reason);
            $this->assertTrue($exception->isSecurityRelated(), "Reason $reason should be security related");
            $this->assertFalse($exception->isDatabaseRelated(), "Reason $reason should not be database related");
        }

        // 測試資料庫相關錯誤
        $databaseReasons = [
            RefreshTokenException::REASON_STORAGE_FAILED,
            RefreshTokenException::REASON_DELETION_FAILED,
        ];

        foreach ($databaseReasons as $reason) {
            $exception = new RefreshTokenException($reason);
            $this->assertTrue($exception->isDatabaseRelated(), "Reason $reason should be database related");
            $this->assertFalse($exception->isSecurityRelated(), "Reason $reason should not be security related");
        }

        // 測試可重試錯誤
        $retryableReasons = [
            RefreshTokenException::REASON_STORAGE_FAILED,
            RefreshTokenException::REASON_DELETION_FAILED,
            RefreshTokenException::REASON_ROTATION_FAILED,
        ];

        foreach ($retryableReasons as $reason) {
            $exception = new RefreshTokenException($reason);
            $this->assertTrue($exception->isRetryable(), "Reason $reason should be retryable");
        }

        // 測試需要重新登入
        $nonRetryableException = new RefreshTokenException(RefreshTokenException::REASON_REVOKED);
        $this->assertTrue($nonRetryableException->requiresReauth());

        $databaseException = new RefreshTokenException(RefreshTokenException::REASON_STORAGE_FAILED);
        $this->assertFalse($databaseException->requiresReauth());
    }

    /**
     * 測試靜態工廠方法 - notFound.
     */
    public function testNotFoundFactoryMethod(): void
    {
        $tokenId = 'token-123';
        $userId = 456;
        $exception = RefreshTokenException::notFound($tokenId, $userId);

        $this->assertInstanceOf(RefreshTokenException::class, $exception);
        $this->assertTrue($exception->isReason(RefreshTokenException::REASON_NOT_FOUND));
        $this->assertSame($tokenId, $exception->getTokenId());
        $this->assertSame($userId, $exception->getUserId());
    }

    /**
     * 測試靜態工廠方法 - revoked.
     */
    public function testRevokedFactoryMethod(): void
    {
        $tokenId = 'token-abc';
        $revokedAt = 1640995200;
        $reason = 'Security breach detected';

        $exception = RefreshTokenException::revoked($tokenId, $revokedAt, $reason);

        $this->assertTrue($exception->isReason(RefreshTokenException::REASON_REVOKED));
        $this->assertTrue($exception->isSecurityRelated());
        $this->assertSame($tokenId, $exception->getTokenId());

        $context = $exception->getContext();
        $this->assertSame($revokedAt, $context['revoked_at']);
        $this->assertSame($reason, $context['revoked_reason']);
        $this->assertArrayHasKey('revoked_at_human', $context);
    }

    /**
     * 測試靜態工廠方法 - alreadyUsed.
     */
    public function testAlreadyUsedFactoryMethod(): void
    {
        $tokenId = 'token-def';
        $usedAt = 1640995800;

        $exception = RefreshTokenException::alreadyUsed($tokenId, $usedAt);

        $this->assertTrue($exception->isReason(RefreshTokenException::REASON_ALREADY_USED));
        $this->assertTrue($exception->isSecurityRelated());

        $context = $exception->getContext();
        $this->assertSame($tokenId, $context['token_id']);
        $this->assertSame($usedAt, $context['used_at']);
        $this->assertArrayHasKey('used_at_human', $context);
    }

    /**
     * 測試靜態工廠方法 - deviceMismatch.
     */
    public function testDeviceMismatchFactoryMethod(): void
    {
        $expectedFingerprint = 'fingerprint-expected';
        $actualFingerprint = 'fingerprint-actual';
        $tokenId = 'token-ghi';

        $exception = RefreshTokenException::deviceMismatch($expectedFingerprint, $actualFingerprint, $tokenId);

        $this->assertTrue($exception->isReason(RefreshTokenException::REASON_DEVICE_MISMATCH));
        $this->assertTrue($exception->isSecurityRelated());

        $context = $exception->getContext();
        $this->assertSame($expectedFingerprint, $context['expected_fingerprint']);
        $this->assertSame($actualFingerprint, $context['actual_fingerprint']);
        $this->assertSame($tokenId, $context['token_id']);
    }

    /**
     * 測試靜態工廠方法 - userMismatch.
     */
    public function testUserMismatchFactoryMethod(): void
    {
        $expectedUserId = 123;
        $actualUserId = 456;
        $tokenId = 'token-jkl';

        $exception = RefreshTokenException::userMismatch($expectedUserId, $actualUserId, $tokenId);

        $this->assertTrue($exception->isReason(RefreshTokenException::REASON_USER_MISMATCH));
        $this->assertTrue($exception->isSecurityRelated());

        $context = $exception->getContext();
        $this->assertSame($expectedUserId, $context['expected_user_id']);
        $this->assertSame($actualUserId, $context['actual_user_id']);
        $this->assertSame($tokenId, $context['token_id']);
    }

    /**
     * 測試靜態工廠方法 - storageFailed.
     */
    public function testStorageFailedFactoryMethod(): void
    {
        $error = 'Database connection timeout';
        $tokenData = ['user_id' => 123, 'expires_at' => 1641000000];

        $exception = RefreshTokenException::storageFailed($error, $tokenData);

        $this->assertTrue($exception->isReason(RefreshTokenException::REASON_STORAGE_FAILED));
        $this->assertTrue($exception->isDatabaseRelated());
        $this->assertTrue($exception->isRetryable());

        $context = $exception->getContext();
        $this->assertSame($error, $context['storage_error']);
        $this->assertSame($tokenData, $context['token_data']);
    }

    /**
     * 測試靜態工廠方法 - deletionFailed.
     */
    public function testDeletionFailedFactoryMethod(): void
    {
        $tokenId = 'token-mno';
        $error = 'Database lock timeout';

        $exception = RefreshTokenException::deletionFailed($tokenId, $error);

        $this->assertTrue($exception->isReason(RefreshTokenException::REASON_DELETION_FAILED));
        $this->assertTrue($exception->isDatabaseRelated());
        $this->assertTrue($exception->isRetryable());

        $context = $exception->getContext();
        $this->assertSame($tokenId, $context['token_id']);
        $this->assertSame($error, $context['deletion_error']);
    }

    /**
     * 測試靜態工廠方法 - rotationFailed.
     */
    public function testRotationFailedFactoryMethod(): void
    {
        $oldTokenId = 'token-old-123';
        $error = 'New token generation failed';

        $exception = RefreshTokenException::rotationFailed($oldTokenId, $error);

        $this->assertTrue($exception->isReason(RefreshTokenException::REASON_ROTATION_FAILED));
        $this->assertTrue($exception->isRetryable());

        $context = $exception->getContext();
        $this->assertSame($oldTokenId, $context['old_token_id']);
        $this->assertSame($error, $context['rotation_error']);
    }

    /**
     * 測試靜態工廠方法 - limitExceeded.
     */
    public function testLimitExceededFactoryMethod(): void
    {
        $userId = 789;
        $currentCount = 5;
        $maxLimit = 5;

        $exception = RefreshTokenException::limitExceeded($userId, $currentCount, $maxLimit);

        $this->assertTrue($exception->isReason(RefreshTokenException::REASON_LIMIT_EXCEEDED));

        $context = $exception->getContext();
        $this->assertSame($userId, $context['user_id']);
        $this->assertSame($currentCount, $context['current_count']);
        $this->assertSame($maxLimit, $context['max_limit']);
    }

    /**
     * 測試靜態工廠方法 - familyMismatch.
     */
    public function testFamilyMismatchFactoryMethod(): void
    {
        $expectedFamily = 'family-abc';
        $actualFamily = 'family-def';
        $tokenId = 'token-pqr';

        $exception = RefreshTokenException::familyMismatch($expectedFamily, $actualFamily, $tokenId);

        $this->assertTrue($exception->isReason(RefreshTokenException::REASON_FAMILY_MISMATCH));
        $this->assertTrue($exception->isSecurityRelated());

        $context = $exception->getContext();
        $this->assertSame($expectedFamily, $context['expected_family']);
        $this->assertSame($actualFamily, $context['actual_family']);
        $this->assertSame($tokenId, $context['token_id']);
    }

    /**
     * 測試錯誤詳細資訊.
     */
    public function testErrorDetails(): void
    {
        $exception = new RefreshTokenException(RefreshTokenException::REASON_REVOKED);

        $details = $exception->getErrorDetails();

        $this->assertSame('refresh_token_error', $details['error_type']);
        $this->assertSame(RefreshTokenException::ERROR_CODE, $details['code']);
        $this->assertArrayHasKey('context', $details);
        $this->assertSame(RefreshTokenException::REASON_REVOKED, $details['context']['reason']);
    }

    /**
     * 測試預設值
     */
    public function testDefaults(): void
    {
        $exception = new RefreshTokenException();

        $this->assertSame(RefreshTokenException::REASON_NOT_FOUND, $exception->getReason());
    }

    /**
     * 測試複雜場景組合.
     */
    public function testComplexScenario(): void
    {
        $additionalContext = [
            'user_id' => 123,
            'token_id' => 'refresh-token-456',
            'device_info' => [
                'fingerprint' => 'device-abc-123',
                'user_agent' => 'Chrome/91.0',
                'ip_address' => '192.168.1.100',
            ],
            'family_id' => 'family-xyz',
            'request_id' => 'req-789',
            'attempt_count' => 3,
        ];

        $exception = new RefreshTokenException(
            RefreshTokenException::REASON_DEVICE_MISMATCH,
            'Device fingerprint mismatch detected during token refresh attempt',
            $additionalContext,
        );

        $this->assertSame('Device fingerprint mismatch detected during token refresh attempt', $exception->getMessage());
        $this->assertTrue($exception->isSecurityRelated());
        $this->assertTrue($exception->requiresReauth());

        $context = $exception->getContext();
        $this->assertSame(123, $context['user_id']);
        $this->assertSame('refresh-token-456', $context['token_id']);
        $this->assertIsArray($context['device_info']);
        $this->assertSame('device-abc-123', $context['device_info']['fingerprint']);

        $details = $exception->getErrorDetails();
        $this->assertArrayHasKey('user_id', $details['context']);
        $this->assertArrayHasKey('operation_id', $details['context']);
    }
}
