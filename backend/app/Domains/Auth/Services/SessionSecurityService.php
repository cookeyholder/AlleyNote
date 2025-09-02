<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\SessionSecurityServiceInterface;

class SessionSecurityService implements SessionSecurityServiceInterface
{
    /**
     * 初始化安全的 Session 設定.
     */
    public function initializeSecureSession(): void
    {
        // 確保 Session 尚未啟動
        if (session_status() === PHP_SESSION_NONE) {
            // 設定安全的 Session 參數
            ini_set('session.cookie_httponly', '1');

            // 根據環境決定是否啟用 secure cookie
            $isProduction = ($_ENV['APP_ENV'] ?? 'production') === 'production';
            ini_set('session.cookie_secure', $isProduction ? '1' : '0');

            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_lifetime', '0'); // Session cookie (瀏覽器關閉時過期)

            // 設定 Session 名稱 (避免使用預設的 PHPSESSID)
            session_name('ALLEYNOTE_SESSION');

            session_start();
        }
    }

    /**
     * 在使用者登入後重新產生 Session ID.
     */
    public function regenerateSessionId(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true); // 刪除舊的 Session 檔案
        }
    }

    /**
     * 安全地銷毀 Session.
     */
    public function destroySession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // 清空 Session 資料
            $_SESSION = [];

            // 刪除 Session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly'],
                );
            }

            // 銷毀 Session
            session_destroy();
        }
    }

    /**
     * 檢查 Session 是否有效.
     */
    public function isSessionValid(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        // 檢查是否有必要的 Session 資料
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_created_at'])) {
            return false;
        }

        // 檢查 Session 是否過期 (最大閒置時間 2 小時)
        $maxIdleTime = 7200; // 2 hours
        if (
            isset($_SESSION['last_activity'])
            && (time() - $_SESSION['last_activity']) > $maxIdleTime
        ) {
            return false;
        }

        // 檢查 Session 是否超過最大生命週期 (8 小時)
        $maxLifetime = 28800; // 8 hours
        if ((time() - $_SESSION['session_created_at']) > $maxLifetime) {
            return false;
        }

        return true;
    }

    /**
     * 更新 Session 活動時間.
     */
    public function updateActivity(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * 設定使用者登入後的 Session 資料.
     */
    public function setUserSession(int $userId, string $userIp, string $userAgent): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_ip'] = $userIp;
        $_SESSION['user_agent'] = hash('sha256', $userAgent); // 儲存 User-Agent 的雜湊值
        $_SESSION['session_created_at'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['requires_ip_verification'] = false; // IP 驗證狀態

        // 重新產生 Session ID 防止 Session 固定攻擊
        $this->regenerateSessionId();
    }

    /**
     * 驗證 Session 的 IP 位址是否一致.
     */
    public function validateSessionIp(string $currentIp): bool
    {
        if (!isset($_SESSION['user_ip'])) {
            return false;
        }

        return $_SESSION['user_ip'] === $currentIp;
    }

    /**
     * 驗證 Session 的 User-Agent 是否一致.
     */
    public function validateSessionUserAgent(string $currentUserAgent): bool
    {
        if (!isset($_SESSION['user_agent'])) {
            return false;
        }

        return $_SESSION['user_agent'] === hash('sha256', $currentUserAgent);
    }

    /**
     * 檢查是否需要 IP 變更驗證.
     */
    public function requiresIpVerification(): bool
    {
        return isset($_SESSION['requires_ip_verification']) && $_SESSION['requires_ip_verification'] === true;
    }

    /**
     * 標記需要 IP 變更驗證.
     */
    public function markIpChangeDetected(string $newIp): void
    {
        $_SESSION['requires_ip_verification'] = true;
        $_SESSION['new_detected_ip'] = $newIp;
        $_SESSION['ip_change_detected_at'] = time();
    }

    /**
     * 完成 IP 變更驗證.
     */
    public function confirmIpChange(): void
    {
        if (isset($_SESSION['new_detected_ip'])) {
            $_SESSION['user_ip'] = $_SESSION['new_detected_ip'];
            unset($_SESSION['new_detected_ip']);
        }
        $_SESSION['requires_ip_verification'] = false;
        unset($_SESSION['ip_change_detected_at']);
    }

    /**
     * 檢查 IP 變更驗證是否過期（5 分鐘）.
     */
    public function isIpVerificationExpired(): bool
    {
        if (!isset($_SESSION['ip_change_detected_at'])) {
            return false;
        }

        return (time() - $_SESSION['ip_change_detected_at']) > 300; // 5 分鐘
    }

    /**
     * 全面的 Session 安全檢查.
     */
    public function performSecurityCheck(string $currentIp, string $currentUserAgent): array
    {
        $result = [
            'valid' => true,
            'requires_action' => false,
            'action_type' => null,
            'message' => null,
        ];

        // 基本 Session 有效性檢查
        if (!$this->isSessionValid()) {
            $result['valid'] = false;
            $result['message'] = 'Session 已過期';

            return $result;
        }

        // User-Agent 檢查
        if (!$this->validateSessionUserAgent($currentUserAgent)) {
            $result['valid'] = false;
            $result['message'] = '瀏覽器指紋不符，可能的 Session 劫持';

            return $result;
        }

        // IP 變更檢查
        if (!$this->validateSessionIp($currentIp)) {
            if ($this->requiresIpVerification()) {
                // 已經在等待驗證中
                if ($this->isIpVerificationExpired()) {
                    $result['valid'] = false;
                    $result['message'] = 'IP 驗證超時，請重新登入';
                } else {
                    $result['requires_action'] = true;
                    $result['action_type'] = 'ip_verification';
                    $result['message'] = '檢測到 IP 位址變更，請進行身分驗證';
                }
            } else {
                // 首次檢測到 IP 變更
                $this->markIpChangeDetected($currentIp);
                $result['requires_action'] = true;
                $result['action_type'] = 'ip_verification';
                $result['message'] = '檢測到 IP 位址變更，請進行身分驗證以確保帳號安全';
            }
        }

        return $result;
    }
}
