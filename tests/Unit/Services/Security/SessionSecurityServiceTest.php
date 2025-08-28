<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Domains\Auth\Services\SessionSecurityService;
use App\Domains\User\Entities\User;
use PHPUnit\Framework\TestCase;

class SessionSecurityServiceTest extends TestCase
{
    private SessionSecurityService $service;

    protected function setUp(): void
    {
        $this->service = new SessionSecurityService();

        // 清理 Session 狀態
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];

        // 清理環境變數
        unset($_ENV['APP_ENV']);
    }

    public function testInitializesSecureSessionInProduction(): void
    {
        $_ENV['APP_ENV'] = 'production';

        $this->service->initializeSecureSession();

        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertEquals('1', ini_get('session.cookie_httponly'));
        $this->assertEquals('1', ini_get('session.cookie_secure')); // Production = secure
        $this->assertEquals('Strict', ini_get('session.cookie_samesite'));
        $this->assertEquals('ALLEYNOTE_SESSION', session_name());
    }

    public function testInitializesSecureSessionInDevelopment(): void
    {
        $_ENV['APP_ENV'] = 'development';

        $this->service->initializeSecureSession();

        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertEquals('0', ini_get('session.cookie_secure')); // Development = not secure
    }

    public function testSetsUserSessionWithUserAgentBinding(): void
    {
        $this->service->initializeSecureSession();

        $userId = 123;
        $userIp = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 (Test Browser)';

        $this->service->setUserSession($userId, $userIp, $userAgent);

        $this->assertEquals($userId, $_SESSION['user_id']);
        $this->assertEquals($userIp, $_SESSION['user_ip']);
        $this->assertEquals(hash('sha256', $userAgent), $_SESSION['user_agent']);
        $this->assertIsInt($_SESSION['session_created_at']);
        $this->assertIsInt($_SESSION['last_activity']);
        $this->assertFalse($_SESSION['requires_ip_verification']);
    }

    public function testValidatesUserAgentCorrectly(): void
    {
        $this->service->initializeSecureSession();

        $userAgent = 'Mozilla/5.0 (Test Browser)';
        $this->service->setUserSession(123, '192.168.1.1', $userAgent);

        // 相同的 User-Agent 應該驗證成功
        $this->assertTrue($this->service->validateSessionUserAgent($userAgent));

        // 不同的 User-Agent 應該驗證失敗
        $this->assertFalse($this->service->validateSessionUserAgent('Different Browser'));
    }

    public function testValidatesSessionIpCorrectly(): void
    {
        $this->service->initializeSecureSession();

        $userIp = '192.168.1.1';
        $this->service->setUserSession(123, $userIp, 'Test Browser');

        // 相同的 IP 應該驗證成功
        $this->assertTrue($this->service->validateSessionIp($userIp));

        // 不同的 IP 應該驗證失敗
        $this->assertFalse($this->service->validateSessionIp('192.168.1.2'));
    }

    public function testPerformsComprehensiveSecurityCheck(): void
    {
        $this->service->initializeSecureSession();

        $userIp = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 (Test Browser)';
        $this->service->setUserSession(123, $userIp, $userAgent);

        // 正常情況應該通過檢查
        $result = $this->service->performSecurityCheck($userIp, $userAgent);

        $this->assertTrue($result['valid']);
        $this->assertFalse($result['requires_action']);
        $this->assertNull($result['action_type']);
    }

    public function testDetectsUserAgentChange(): void
    {
        $this->service->initializeSecureSession();

        $userIp = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 (Test Browser)';
        $this->service->setUserSession(123, $userIp, $userAgent);

        // 不同的 User-Agent 應該導致 Session 無效
        $result = $this->service->performSecurityCheck($userIp, 'Different Browser');

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('瀏覽器指紋不符', $result['message']);
    }

    public function testDetectsIpChange(): void
    {
        $this->service->initializeSecureSession();

        $userIp = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 (Test Browser)';
        $this->service->setUserSession(123, $userIp, $userAgent);

        // 不同的 IP 應該觸發驗證流程
        $result = $this->service->performSecurityCheck('192.168.1.2', $userAgent);

        $this->assertTrue($result['valid']); // Session 仍有效，但需要動作
        $this->assertTrue($result['requires_action']);
        $this->assertEquals('ip_verification', $result['action_type']);
        $this->assertStringContainsString('IP 位址變更', $result['message']);
    }

    public function testHandlesIpVerificationFlow(): void
    {
        $this->service->initializeSecureSession();

        $originalIp = '192.168.1.1';
        $newIp = '192.168.1.2';
        $userAgent = 'Mozilla/5.0 (Test Browser)';

        $this->service->setUserSession(123, $originalIp, $userAgent);

        // 首次檢測 IP 變更
        $this->service->markIpChangeDetected($newIp);
        $this->assertTrue($this->service->requiresIpVerification());

        // 確認 IP 變更
        $this->service->confirmIpChange();
        $this->assertFalse($this->service->requiresIpVerification());
        $this->assertEquals($newIp, $_SESSION['user_ip']);
    }

    public function testDetectsExpiredSession(): void
    {
        $this->service->initializeSecureSession();

        $this->service->setUserSession(123, '192.168.1.1', 'Test Browser');

        // 模擬過期的 Session（設定過去的時間）
        $_SESSION['last_activity'] = time() - 7201; // 超過 2 小時

        $this->assertFalse($this->service->isSessionValid());
    }

    public function testUpdatesActivityTime(): void
    {
        $this->service->initializeSecureSession();

        $this->service->setUserSession(123, '192.168.1.1', 'Test Browser');

        $originalActivity = $_SESSION['last_activity'];
        sleep(1); // 等待 1 秒

        $this->service->updateActivity();

        $this->assertGreaterThan($originalActivity, $_SESSION['last_activity']);
    }

    public function testDestroysSessionSecurely(): void
    {
        $this->service->initializeSecureSession();
        $this->service->setUserSession(123, '192.168.1.1', 'Test Browser');

        $this->service->destroySession();

        $this->assertEquals(PHP_SESSION_NONE, session_status());
        $this->assertEmpty($_SESSION);
    }

    public function testRegeneratesSessionId(): void
    {
        $this->service->initializeSecureSession();
        $oldSessionId = session_id();

        $this->service->regenerateSessionId();
        $newSessionId = session_id();

        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    public function testHandlesMissingSessionData(): void
    {
        $this->service->initializeSecureSession();

        // Session 沒有使用者資料
        $result = $this->service->performSecurityCheck('192.168.1.1', 'Test Browser');

        $this->assertFalse($result['valid']);
    }

    public function testHandlesIpVerificationTimeout(): void
    {
        $this->service->initializeSecureSession();

        $this->service->setUserSession(123, '192.168.1.1', 'Test Browser');
        $this->service->markIpChangeDetected('192.168.1.2');

        // 模擬驗證超時
        $_SESSION['ip_change_detected_at'] = time() - 301; // 超過 5 分鐘

        $this->assertTrue($this->service->isIpVerificationExpired());

        $result = $this->service->performSecurityCheck('192.168.1.2', 'Test Browser');
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('IP 驗證超時', $result['message']);
    }
}
