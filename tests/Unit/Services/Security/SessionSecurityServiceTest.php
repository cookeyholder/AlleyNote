<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Domains\Auth\Services\SessionSecurityService;
use App\Domains\User\Entities\User;
use PHPUnit\Framework\Attributes\Test;
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
        unset((is_array($_ENV) ? $_ENV['APP_ENV'] : (is_object($_ENV) ? $_ENV->APP_ENV : null)));
    }

    #[Test]
    public function initializesSecureSessionInProduction(): void
    {
        (is_array($_ENV) ? $_ENV['APP_ENV'] : (is_object($_ENV) ? $_ENV->APP_ENV : null)) = 'production';

        $this->service->initializeSecureSession();

        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertEquals('1', ini_get('session.cookie_httponly'));
        $this->assertEquals('1', ini_get('session.cookie_secure')); // Production = secure
        $this->assertEquals('Strict', ini_get('session.cookie_samesite'));
        $this->assertEquals('ALLEYNOTE_SESSION', session_name());
    }

    #[Test]
    public function initializesSecureSessionInDevelopment(): void
    {
        (is_array($_ENV) ? $_ENV['APP_ENV'] : (is_object($_ENV) ? $_ENV->APP_ENV : null)) = 'development';

        $this->service->initializeSecureSession();

        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertEquals('0', ini_get('session.cookie_secure')); // Development = not secure
    }

    #[Test]
    public function setsUserSessionWithUserAgentBinding(): void
    {
        $this->service->initializeSecureSession();

        $userId = 123;
        $userIp = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 (Test Browser)';

        $this->service->setUserSession($userId, $userIp, $userAgent);

        $this->assertEquals($userId, (is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['user_id'] : (is_object($_SESSION) ? $_SESSION->user_id : null)))) ? (is_array($_SESSION) ? $_SESSION['user_id'] : (is_object($_SESSION) ? $_SESSION->user_id : null)) : null);
        $this->assertEquals($userIp, (is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['user_ip'] : (is_object($_SESSION) ? $_SESSION->user_ip : null)))) ? (is_array($_SESSION) ? $_SESSION['user_ip'] : (is_object($_SESSION) ? $_SESSION->user_ip : null)) : null);
        $this->assertEquals(hash('sha256', $userAgent), (is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['user_agent'] : (is_object($_SESSION) ? $_SESSION->user_agent : null)))) ? (is_array($_SESSION) ? $_SESSION['user_agent'] : (is_object($_SESSION) ? $_SESSION->user_agent : null)) : null);
        $this->assertIsInt((is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['session_created_at'] : (is_object($_SESSION) ? $_SESSION->session_created_at : null)))) ? (is_array($_SESSION) ? $_SESSION['session_created_at'] : (is_object($_SESSION) ? $_SESSION->session_created_at : null)) : null);
        $this->assertIsInt((is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['last_activity'] : (is_object($_SESSION) ? $_SESSION->last_activity : null)))) ? (is_array($_SESSION) ? $_SESSION['last_activity'] : (is_object($_SESSION) ? $_SESSION->last_activity : null)) : null);
        $this->assertFalse((is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['requires_ip_verification'] : (is_object($_SESSION) ? $_SESSION->requires_ip_verification : null)))) ? (is_array($_SESSION) ? $_SESSION['requires_ip_verification'] : (is_object($_SESSION) ? $_SESSION->requires_ip_verification : null)) : null);
    }

    #[Test]
    public function validatesUserAgentCorrectly(): void
    {
        $this->service->initializeSecureSession();

        $userAgent = 'Mozilla/5.0 (Test Browser)';
        $this->service->setUserSession(123, '192.168.1.1', $userAgent);

        // 相同的 User-Agent 應該驗證成功
        $this->assertTrue($this->service->validateSessionUserAgent($userAgent));

        // 不同的 User-Agent 應該驗證失敗
        $this->assertFalse($this->service->validateSessionUserAgent('Different Browser'));
    }

    #[Test]
    public function validatesSessionIpCorrectly(): void
    {
        $this->service->initializeSecureSession();

        $userIp = '192.168.1.1';
        $this->service->setUserSession(123, $userIp, 'Test Browser');

        // 相同的 IP 應該驗證成功
        $this->assertTrue($this->service->validateSessionIp($userIp));

        // 不同的 IP 應該驗證失敗
        $this->assertFalse($this->service->validateSessionIp('192.168.1.2'));
    }

    #[Test]
    public function performsComprehensiveSecurityCheck(): void
    {
        $this->service->initializeSecureSession();

        $userIp = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 (Test Browser)';
        $this->service->setUserSession(123, $userIp, $userAgent);

        // 正常情況應該通過檢查
        $result = $this->service->performSecurityCheck($userIp, $userAgent);

        $this->assertTrue((is_array($result) && isset((is_array($result) ? $result['valid'] : (is_object($result) ? $result->valid : null)))) ? (is_array($result) ? $result['valid'] : (is_object($result) ? $result->valid : null)) : null);
        $this->assertFalse((is_array($result) && isset((is_array($result) ? $result['requires_action'] : (is_object($result) ? $result->requires_action : null)))) ? (is_array($result) ? $result['requires_action'] : (is_object($result) ? $result->requires_action : null)) : null);
        $this->assertNull((is_array($result) && isset((is_array($result) ? $result['action_type'] : (is_object($result) ? $result->action_type : null)))) ? (is_array($result) ? $result['action_type'] : (is_object($result) ? $result->action_type : null)) : null);
    }

    #[Test]
    public function detectsUserAgentChange(): void
    {
        $this->service->initializeSecureSession();

        $userIp = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 (Test Browser)';
        $this->service->setUserSession(123, $userIp, $userAgent);

        // 不同的 User-Agent 應該導致 Session 無效
        $result = $this->service->performSecurityCheck($userIp, 'Different Browser');

        $this->assertFalse((is_array($result) && isset((is_array($result) ? $result['valid'] : (is_object($result) ? $result->valid : null)))) ? (is_array($result) ? $result['valid'] : (is_object($result) ? $result->valid : null)) : null);
        $this->assertStringContainsString('瀏覽器指紋不符', (is_array($result) && isset((is_array($result) ? $result['message'] : (is_object($result) ? $result->message : null)))) ? (is_array($result) ? $result['message'] : (is_object($result) ? $result->message : null)) : null);
    }

    #[Test]
    public function detectsIpChange(): void
    {
        $this->service->initializeSecureSession();

        $userIp = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 (Test Browser)';
        $this->service->setUserSession(123, $userIp, $userAgent);

        // 不同的 IP 應該觸發驗證流程
        $result = $this->service->performSecurityCheck('192.168.1.2', $userAgent);

        $this->assertTrue((is_array($result) && isset((is_array($result) ? $result['valid'] : (is_object($result) ? $result->valid : null)))) ? (is_array($result) ? $result['valid'] : (is_object($result) ? $result->valid : null)) : null); // Session 仍有效，但需要動作
        $this->assertTrue((is_array($result) && isset((is_array($result) ? $result['requires_action'] : (is_object($result) ? $result->requires_action : null)))) ? (is_array($result) ? $result['requires_action'] : (is_object($result) ? $result->requires_action : null)) : null);
        $this->assertEquals('ip_verification', (is_array($result) && isset((is_array($result) ? $result['action_type'] : (is_object($result) ? $result->action_type : null)))) ? (is_array($result) ? $result['action_type'] : (is_object($result) ? $result->action_type : null)) : null);
        $this->assertStringContainsString('IP 位址變更', (is_array($result) && isset((is_array($result) ? $result['message'] : (is_object($result) ? $result->message : null)))) ? (is_array($result) ? $result['message'] : (is_object($result) ? $result->message : null)) : null);
    }

    #[Test]
    public function handlesIpVerificationFlow(): void
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
        $this->assertEquals($newIp, (is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['user_ip'] : (is_object($_SESSION) ? $_SESSION->user_ip : null)))) ? (is_array($_SESSION) ? $_SESSION['user_ip'] : (is_object($_SESSION) ? $_SESSION->user_ip : null)) : null);
    }

    #[Test]
    public function detectsExpiredSession(): void
    {
        $this->service->initializeSecureSession();

        $this->service->setUserSession(123, '192.168.1.1', 'Test Browser');

        // 模擬過期的 Session（設定過去的時間）
        (is_array($_SESSION) ? $_SESSION['last_activity'] : (is_object($_SESSION) ? $_SESSION->last_activity : null)) = time() - 7201; // 超過 2 小時

        $this->assertFalse($this->service->isSessionValid());
    }

    #[Test]
    public function updatesActivityTime(): void
    {
        $this->service->initializeSecureSession();

        $this->service->setUserSession(123, '192.168.1.1', 'Test Browser');

        $originalActivity = (is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['last_activity'] : (is_object($_SESSION) ? $_SESSION->last_activity : null)))) ? (is_array($_SESSION) ? $_SESSION['last_activity'] : (is_object($_SESSION) ? $_SESSION->last_activity : null)) : null;
        sleep(1); // 等待 1 秒

        $this->service->updateActivity();

        $this->assertGreaterThan($originalActivity, (is_array($_SESSION) && isset((is_array($_SESSION) ? $_SESSION['last_activity'] : (is_object($_SESSION) ? $_SESSION->last_activity : null)))) ? (is_array($_SESSION) ? $_SESSION['last_activity'] : (is_object($_SESSION) ? $_SESSION->last_activity : null)) : null);
    }

    #[Test]
    public function destroysSessionSecurely(): void
    {
        $this->service->initializeSecureSession();
        $this->service->setUserSession(123, '192.168.1.1', 'Test Browser');

        $this->service->destroySession();

        $this->assertEquals(PHP_SESSION_NONE, session_status());
        $this->assertEmpty($_SESSION);
    }

    #[Test]
    public function regeneratesSessionId(): void
    {
        $this->service->initializeSecureSession();
        $oldSessionId = session_id();

        $this->service->regenerateSessionId();
        $newSessionId = session_id();

        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    #[Test]
    public function handlesMissingSessionData(): void
    {
        $this->service->initializeSecureSession();

        // Session 沒有使用者資料
        $result = $this->service->performSecurityCheck('192.168.1.1', 'Test Browser');

        $this->assertFalse((is_array($result) && isset((is_array($result) ? $result['valid'] : (is_object($result) ? $result->valid : null)))) ? (is_array($result) ? $result['valid'] : (is_object($result) ? $result->valid : null)) : null);
    }

    #[Test]
    public function handlesIpVerificationTimeout(): void
    {
        $this->service->initializeSecureSession();

        $this->service->setUserSession(123, '192.168.1.1', 'Test Browser');
        $this->service->markIpChangeDetected('192.168.1.2');

        // 模擬驗證超時
        (is_array($_SESSION) ? $_SESSION['ip_change_detected_at'] : (is_object($_SESSION) ? $_SESSION->ip_change_detected_at : null)) = time() - 301; // 超過 5 分鐘

        $this->assertTrue($this->service->isIpVerificationExpired());

        $result = $this->service->performSecurityCheck('192.168.1.2', 'Test Browser');
        $this->assertFalse((is_array($result) && isset((is_array($result) ? $result['valid'] : (is_object($result) ? $result->valid : null)))) ? (is_array($result) ? $result['valid'] : (is_object($result) ? $result->valid : null)) : null);
        $this->assertStringContainsString('IP 驗證超時', (is_array($result) && isset((is_array($result) ? $result['message'] : (is_object($result) ? $result->message : null)))) ? (is_array($result) ? $result['message'] : (is_object($result) ? $result->message : null)) : null);
    }
}
