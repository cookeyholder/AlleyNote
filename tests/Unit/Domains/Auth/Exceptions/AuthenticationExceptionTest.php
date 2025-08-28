<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Exceptions;

use AlleyNote\Domains\Auth\Exceptions\AuthenticationException;
use PHPUnit\Framework\TestCase;

/**
 * 身份驗證失敗例外單元測試.
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class AuthenticationExceptionTest extends TestCase
{
    /**
     * 測試基本建構功能.
     */
    public function testConstructor(): void
    {
        $exception = new AuthenticationException();

        $this->assertSame(AuthenticationException::ERROR_CODE, $exception->getCode());
        $this->assertSame('authentication_failed', $exception->getErrorType());
        $this->assertStringContainsString('Invalid username or password', $exception->getMessage());
    }

    /**
     * 測試具體原因建構.
     */
    public function testConstructorWithReason(): void
    {
        $exception = new AuthenticationException(AuthenticationException::REASON_ACCOUNT_LOCKED);

        $this->assertSame('Account has been locked due to security reasons', $exception->getMessage());
        $this->assertSame(AuthenticationException::REASON_ACCOUNT_LOCKED, $exception->getReason());
    }

    /**
     * 測試自定義錯誤訊息.
     */
    public function testCustomMessage(): void
    {
        $customMessage = 'Custom authentication error message';
        $exception = new AuthenticationException(
            AuthenticationException::REASON_ACCOUNT_DISABLED,
            $customMessage,
        );

        $this->assertSame($customMessage, $exception->getMessage());
        $this->assertSame(AuthenticationException::REASON_ACCOUNT_DISABLED, $exception->getReason());
    }

    /**
     * 測試額外上下文資訊.
     */
    public function testAdditionalContext(): void
    {
        $additionalContext = [
            'username' => 'testuser',
            'ip_address' => '192.168.1.100',
            'user_id' => 123,
            'attempt_count' => 3,
        ];

        $exception = new AuthenticationException(
            AuthenticationException::REASON_TOO_MANY_ATTEMPTS,
            '',
            $additionalContext,
        );

        $context = $exception->getContext();
        $this->assertSame('testuser', $context['username']);
        $this->assertSame('192.168.1.100', $context['ip_address']);
        $this->assertSame(123, $context['user_id']);
        $this->assertSame(3, $context['attempt_count']);
        $this->assertSame(AuthenticationException::REASON_TOO_MANY_ATTEMPTS, $context['reason']);
        $this->assertArrayHasKey('attempt_id', $context);
        $this->assertStringStartsWith('auth_', $context['attempt_id']);
    }

    /**
     * 測試所有預設訊息.
     */
    public function testAllDefaultMessages(): void
    {
        $reasons = [
            AuthenticationException::REASON_INVALID_CREDENTIALS => 'Invalid username or password',
            AuthenticationException::REASON_ACCOUNT_LOCKED => 'Account has been locked due to security reasons',
            AuthenticationException::REASON_ACCOUNT_DISABLED => 'Account has been disabled',
            AuthenticationException::REASON_ACCOUNT_NOT_VERIFIED => 'Account has not been verified',
            AuthenticationException::REASON_TOO_MANY_ATTEMPTS => 'Too many failed authentication attempts',
            AuthenticationException::REASON_USER_NOT_FOUND => 'User not found',
            AuthenticationException::REASON_PASSWORD_EXPIRED => 'Password has expired and needs to be changed',
            AuthenticationException::REASON_MISSING_CREDENTIALS => 'Authentication credentials are missing',
            AuthenticationException::REASON_INVALID_TOKEN => 'Invalid authentication token',
            AuthenticationException::REASON_TOKEN_REQUIRED => 'Authentication token is required',
            AuthenticationException::REASON_INSUFFICIENT_PRIVILEGES => 'Insufficient privileges to access this resource',
        ];

        foreach ($reasons as $reason => $expectedMessage) {
            $exception = new AuthenticationException($reason);
            $this->assertSame($expectedMessage, $exception->getMessage());
        }
    }

    /**
     * 測試用戶友好訊息.
     */
    public function testUserFriendlyMessages(): void
    {
        $testCases = [
            [AuthenticationException::REASON_INVALID_CREDENTIALS, '用戶名或密碼錯誤'],
            [AuthenticationException::REASON_ACCOUNT_LOCKED, '帳戶已被鎖定'],
            [AuthenticationException::REASON_ACCOUNT_DISABLED, '帳戶已被停用'],
            [AuthenticationException::REASON_ACCOUNT_NOT_VERIFIED, '帳戶尚未驗證'],
            [AuthenticationException::REASON_TOO_MANY_ATTEMPTS, '登入嘗試次數過多'],
            [AuthenticationException::REASON_USER_NOT_FOUND, '找不到此用戶'],
            [AuthenticationException::REASON_PASSWORD_EXPIRED, '密碼已過期'],
            [AuthenticationException::REASON_MISSING_CREDENTIALS, '請提供完整的登入資訊'],
            [AuthenticationException::REASON_INVALID_TOKEN, '認證 Token 無效'],
            [AuthenticationException::REASON_TOKEN_REQUIRED, '需要提供認證 Token'],
            [AuthenticationException::REASON_INSUFFICIENT_PRIVILEGES, '沒有足夠的權限'],
        ];

        foreach ($testCases as [$reason, $expectedPhrase]) {
            $exception = new AuthenticationException($reason);
            $message = $exception->getUserFriendlyMessage();
            $this->assertStringContainsString($expectedPhrase, $message);
        }
    }

    /**
     * 測試取得嘗試 ID.
     */
    public function testAttemptId(): void
    {
        $exception = new AuthenticationException();
        $attemptId = $exception->getAttemptId();

        $this->assertIsString($attemptId);
        $this->assertStringStartsWith('auth_', $attemptId);
        $this->assertGreaterThan(5, strlen($attemptId));
    }

    /**
     * 測試上下文取值方法.
     */
    public function testContextGetters(): void
    {
        $context = [
            'user_id' => 123,
            'username' => 'testuser',
            'ip_address' => '192.168.1.100',
            'attempt_count' => 5,
            'lockout_until' => 1641000000,
        ];

        $exception = new AuthenticationException(
            AuthenticationException::REASON_TOO_MANY_ATTEMPTS,
            '',
            $context,
        );

        $this->assertSame(123, $exception->getUserId());
        $this->assertSame('testuser', $exception->getUsername());
        $this->assertSame('192.168.1.100', $exception->getIpAddress());
        $this->assertSame(5, $exception->getAttemptCount());
        $this->assertSame(1641000000, $exception->getLockoutUntil());
    }

    /**
     * 測試空上下文的 getter 方法.
     */
    public function testContextGettersWithEmptyContext(): void
    {
        $exception = new AuthenticationException();

        $this->assertNull($exception->getUserId());
        $this->assertNull($exception->getUsername());
        $this->assertNull($exception->getIpAddress());
        $this->assertNull($exception->getAttemptCount());
        $this->assertNull($exception->getLockoutUntil());
    }

    /**
     * 測試原因檢查方法.
     */
    public function testIsReason(): void
    {
        $exception = new AuthenticationException(AuthenticationException::REASON_ACCOUNT_LOCKED);

        $this->assertTrue($exception->isReason(AuthenticationException::REASON_ACCOUNT_LOCKED));
        $this->assertFalse($exception->isReason(AuthenticationException::REASON_INVALID_CREDENTIALS));
    }

    /**
     * 測試分類檢查方法.
     */
    public function testCategoryChecks(): void
    {
        // 測試帳戶相關錯誤
        $accountReasons = [
            AuthenticationException::REASON_ACCOUNT_LOCKED,
            AuthenticationException::REASON_ACCOUNT_DISABLED,
            AuthenticationException::REASON_ACCOUNT_NOT_VERIFIED,
            AuthenticationException::REASON_PASSWORD_EXPIRED,
        ];

        foreach ($accountReasons as $reason) {
            $exception = new AuthenticationException($reason);
            $this->assertTrue($exception->isAccountRelated(), "Reason $reason should be account related");
            $this->assertFalse($exception->isCredentialsRelated(), "Reason $reason should not be credentials related");
            $this->assertFalse($exception->isTokenRelated(), "Reason $reason should not be token related");
        }

        // 測試憑證相關錯誤
        $credentialsReasons = [
            AuthenticationException::REASON_INVALID_CREDENTIALS,
            AuthenticationException::REASON_MISSING_CREDENTIALS,
            AuthenticationException::REASON_USER_NOT_FOUND,
        ];

        foreach ($credentialsReasons as $reason) {
            $exception = new AuthenticationException($reason);
            $this->assertTrue($exception->isCredentialsRelated(), "Reason $reason should be credentials related");
            $this->assertFalse($exception->isAccountRelated(), "Reason $reason should not be account related");
            $this->assertFalse($exception->isTokenRelated(), "Reason $reason should not be token related");
        }

        // 測試 Token 相關錯誤
        $tokenReasons = [
            AuthenticationException::REASON_INVALID_TOKEN,
            AuthenticationException::REASON_TOKEN_REQUIRED,
        ];

        foreach ($tokenReasons as $reason) {
            $exception = new AuthenticationException($reason);
            $this->assertTrue($exception->isTokenRelated(), "Reason $reason should be token related");
            $this->assertFalse($exception->isAccountRelated(), "Reason $reason should not be account related");
            $this->assertFalse($exception->isCredentialsRelated(), "Reason $reason should not be credentials related");
        }

        // 測試安全相關錯誤
        $securityReasons = [
            AuthenticationException::REASON_ACCOUNT_LOCKED,
            AuthenticationException::REASON_TOO_MANY_ATTEMPTS,
        ];

        foreach ($securityReasons as $reason) {
            $exception = new AuthenticationException($reason);
            $this->assertTrue($exception->isSecurityRelated(), "Reason $reason should be security related");
        }
    }

    /**
     * 測試可重試檢查.
     */
    public function testRetryability(): void
    {
        // 不可重試的原因
        $nonRetryableReasons = [
            AuthenticationException::REASON_ACCOUNT_LOCKED,
            AuthenticationException::REASON_ACCOUNT_DISABLED,
            AuthenticationException::REASON_TOO_MANY_ATTEMPTS,
        ];

        foreach ($nonRetryableReasons as $reason) {
            $exception = new AuthenticationException($reason);
            $this->assertFalse($exception->isRetryable(), "Reason $reason should not be retryable");
        }

        // 可重試的原因
        $retryableReasons = [
            AuthenticationException::REASON_INVALID_CREDENTIALS,
            AuthenticationException::REASON_USER_NOT_FOUND,
            AuthenticationException::REASON_MISSING_CREDENTIALS,
        ];

        foreach ($retryableReasons as $reason) {
            $exception = new AuthenticationException($reason);
            $this->assertTrue($exception->isRetryable(), "Reason $reason should be retryable");
        }
    }

    /**
     * 測試需要帳戶操作檢查.
     */
    public function testRequiresAccountAction(): void
    {
        $actionRequiredReasons = [
            AuthenticationException::REASON_ACCOUNT_NOT_VERIFIED,
            AuthenticationException::REASON_PASSWORD_EXPIRED,
        ];

        foreach ($actionRequiredReasons as $reason) {
            $exception = new AuthenticationException($reason);
            $this->assertTrue($exception->requiresAccountAction(), "Reason $reason should require account action");
        }

        $otherException = new AuthenticationException(AuthenticationException::REASON_INVALID_CREDENTIALS);
        $this->assertFalse($otherException->requiresAccountAction());
    }

    /**
     * 測試靜態工廠方法 - invalidCredentials.
     */
    public function testInvalidCredentialsFactoryMethod(): void
    {
        $username = 'testuser';
        $ipAddress = '192.168.1.100';
        $exception = AuthenticationException::invalidCredentials($username, $ipAddress);

        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertTrue($exception->isReason(AuthenticationException::REASON_INVALID_CREDENTIALS));
        $this->assertTrue($exception->isCredentialsRelated());
        $this->assertSame($username, $exception->getUsername());
        $this->assertSame($ipAddress, $exception->getIpAddress());
    }

    /**
     * 測試靜態工廠方法 - accountLocked.
     */
    public function testAccountLockedFactoryMethod(): void
    {
        $userId = 123;
        $lockoutUntil = 1641000000;
        $reason = 'Too many failed attempts';

        $exception = AuthenticationException::accountLocked($userId, $lockoutUntil, $reason);

        $this->assertTrue($exception->isReason(AuthenticationException::REASON_ACCOUNT_LOCKED));
        $this->assertTrue($exception->isAccountRelated());
        $this->assertTrue($exception->isSecurityRelated());
        $this->assertSame($userId, $exception->getUserId());
        $this->assertSame($lockoutUntil, $exception->getLockoutUntil());

        $context = $exception->getContext();
        $this->assertSame($reason, $context['lock_reason']);
        $this->assertArrayHasKey('lockout_until_human', $context);
    }

    /**
     * 測試靜態工廠方法 - accountDisabled.
     */
    public function testAccountDisabledFactoryMethod(): void
    {
        $userId = 456;
        $reason = 'Account suspended by admin';

        $exception = AuthenticationException::accountDisabled($userId, $reason);

        $this->assertTrue($exception->isReason(AuthenticationException::REASON_ACCOUNT_DISABLED));
        $this->assertTrue($exception->isAccountRelated());
        $this->assertSame($userId, $exception->getUserId());

        $context = $exception->getContext();
        $this->assertSame($reason, $context['disable_reason']);
    }

    /**
     * 測試靜態工廠方法 - accountNotVerified.
     */
    public function testAccountNotVerifiedFactoryMethod(): void
    {
        $userId = 789;
        $email = 'test@example.com';

        $exception = AuthenticationException::accountNotVerified($userId, $email);

        $this->assertTrue($exception->isReason(AuthenticationException::REASON_ACCOUNT_NOT_VERIFIED));
        $this->assertTrue($exception->isAccountRelated());
        $this->assertTrue($exception->requiresAccountAction());
        $this->assertSame($userId, $exception->getUserId());

        $context = $exception->getContext();
        $this->assertSame($email, $context['email']);
    }

    /**
     * 測試靜態工廠方法 - tooManyAttempts.
     */
    public function testTooManyAttemptsFactoryMethod(): void
    {
        $username = 'testuser';
        $attemptCount = 5;
        $lockoutUntil = 1641000000;
        $ipAddress = '192.168.1.100';

        $exception = AuthenticationException::tooManyAttempts($username, $attemptCount, $lockoutUntil, $ipAddress);

        $this->assertTrue($exception->isReason(AuthenticationException::REASON_TOO_MANY_ATTEMPTS));
        $this->assertTrue($exception->isSecurityRelated());
        $this->assertFalse($exception->isRetryable());

        $this->assertSame($username, $exception->getUsername());
        $this->assertSame($attemptCount, $exception->getAttemptCount());
        $this->assertSame($lockoutUntil, $exception->getLockoutUntil());
        $this->assertSame($ipAddress, $exception->getIpAddress());

        $context = $exception->getContext();
        $this->assertArrayHasKey('lockout_until_human', $context);
    }

    /**
     * 測試靜態工廠方法 - userNotFound.
     */
    public function testUserNotFoundFactoryMethod(): void
    {
        $username = 'nonexistentuser';
        $exception = AuthenticationException::userNotFound($username);

        $this->assertTrue($exception->isReason(AuthenticationException::REASON_USER_NOT_FOUND));
        $this->assertTrue($exception->isCredentialsRelated());
        $this->assertSame($username, $exception->getUsername());
    }

    /**
     * 測試靜態工廠方法 - passwordExpired.
     */
    public function testPasswordExpiredFactoryMethod(): void
    {
        $userId = 321;
        $expiredAt = 1640995200;

        $exception = AuthenticationException::passwordExpired($userId, $expiredAt);

        $this->assertTrue($exception->isReason(AuthenticationException::REASON_PASSWORD_EXPIRED));
        $this->assertTrue($exception->isAccountRelated());
        $this->assertTrue($exception->requiresAccountAction());
        $this->assertSame($userId, $exception->getUserId());

        $context = $exception->getContext();
        $this->assertSame($expiredAt, $context['expired_at']);
        $this->assertArrayHasKey('expired_at_human', $context);
    }

    /**
     * 測試靜態工廠方法 - missingCredentials.
     */
    public function testMissingCredentialsFactoryMethod(): void
    {
        $missingFields = ['username', 'password'];
        $exception = AuthenticationException::missingCredentials($missingFields);

        $this->assertTrue($exception->isReason(AuthenticationException::REASON_MISSING_CREDENTIALS));
        $this->assertTrue($exception->isCredentialsRelated());

        $context = $exception->getContext();
        $this->assertSame($missingFields, $context['missing_fields']);
    }

    /**
     * 測試靜態工廠方法 - invalidToken.
     */
    public function testInvalidTokenFactoryMethod(): void
    {
        $tokenType = 'refresh_token';
        $reason = 'Token signature verification failed';

        $exception = AuthenticationException::invalidToken($tokenType, $reason);

        $this->assertTrue($exception->isReason(AuthenticationException::REASON_INVALID_TOKEN));
        $this->assertTrue($exception->isTokenRelated());

        $context = $exception->getContext();
        $this->assertSame($tokenType, $context['token_type']);
        $this->assertSame($reason, $context['invalid_reason']);
    }

    /**
     * 測試靜態工廠方法 - tokenRequired.
     */
    public function testTokenRequiredFactoryMethod(): void
    {
        $resource = '/api/admin/users';
        $exception = AuthenticationException::tokenRequired($resource);

        $this->assertTrue($exception->isReason(AuthenticationException::REASON_TOKEN_REQUIRED));
        $this->assertTrue($exception->isTokenRelated());

        $context = $exception->getContext();
        $this->assertSame($resource, $context['required_for_resource']);
    }

    /**
     * 測試靜態工廠方法 - insufficientPrivileges.
     */
    public function testInsufficientPrivilegesFactoryMethod(): void
    {
        $requiredPrivilege = 'admin';
        $userPrivileges = ['user', 'editor'];
        $userId = 654;

        $exception = AuthenticationException::insufficientPrivileges($requiredPrivilege, $userPrivileges, $userId);

        $this->assertTrue($exception->isReason(AuthenticationException::REASON_INSUFFICIENT_PRIVILEGES));
        $this->assertSame($userId, $exception->getUserId());

        $context = $exception->getContext();
        $this->assertSame($requiredPrivilege, $context['required_privilege']);
        $this->assertSame($userPrivileges, $context['user_privileges']);
    }

    /**
     * 測試錯誤詳細資訊.
     */
    public function testErrorDetails(): void
    {
        $exception = new AuthenticationException(AuthenticationException::REASON_INVALID_CREDENTIALS);

        $details = $exception->getErrorDetails();

        $this->assertSame('authentication_failed', $details['error_type']);
        $this->assertSame(AuthenticationException::ERROR_CODE, $details['code']);
        $this->assertArrayHasKey('context', $details);
        $this->assertSame(AuthenticationException::REASON_INVALID_CREDENTIALS, $details['context']['reason']);
    }

    /**
     * 測試預設值
     */
    public function testDefaults(): void
    {
        $exception = new AuthenticationException();

        $this->assertSame(AuthenticationException::REASON_INVALID_CREDENTIALS, $exception->getReason());
    }

    /**
     * 測試複雜場景組合.
     */
    public function testComplexScenario(): void
    {
        $additionalContext = [
            'username' => 'compromised_user',
            'user_id' => 987,
            'ip_address' => '192.168.1.200',
            'attempt_count' => 10,
            'lockout_until' => time() + 3600, // 1 hour from now
            'user_agent' => 'Suspicious Bot/1.0',
            'request_id' => 'req-security-alert-123',
            'geolocation' => ['country' => 'Unknown', 'city' => 'Unknown'],
            'previous_successful_login' => time() - 86400, // 1 day ago
        ];

        $exception = new AuthenticationException(
            AuthenticationException::REASON_TOO_MANY_ATTEMPTS,
            'Account locked due to suspicious activity from unknown location',
            $additionalContext,
        );

        $this->assertSame('Account locked due to suspicious activity from unknown location', $exception->getMessage());
        $this->assertTrue($exception->isSecurityRelated());
        $this->assertFalse($exception->isRetryable());

        $context = $exception->getContext();
        $this->assertSame('compromised_user', $context['username']);
        $this->assertSame(987, $context['user_id']);
        $this->assertSame(10, $context['attempt_count']);
        $this->assertArrayHasKey('geolocation', $context);
        $this->assertSame('Unknown', $context['geolocation']['country']);

        $details = $exception->getErrorDetails();
        $this->assertArrayHasKey('username', $details['context']);
        $this->assertArrayHasKey('attempt_id', $details['context']);
        $this->assertArrayHasKey('request_id', $details['context']);
    }
}
