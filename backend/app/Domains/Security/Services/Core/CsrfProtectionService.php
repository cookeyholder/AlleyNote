<?php

declare(strict_types=1);

namespace App\Domains\Security\Services\Core;

use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\CsrfProtectionServiceInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityType;
use App\Shared\Exceptions\CsrfTokenException;
use Exception;

class CsrfProtectionService implements CsrfProtectionServiceInterface
{
    private const TOKEN_LENGTH = 32;

    private const TOKEN_EXPIRY = 3600; // 1 hour

    private const TOKEN_LIFETIME = self::TOKEN_EXPIRY; // 權杖存活時間

    private const TOKEN_POOL_SIZE = 5; // 權杖池大小

    private const TOKEN_POOL_KEY = 'csrf_token_pool';

    public function __construct(
        private ActivityLoggingServiceInterface $activityLogger,
    ) {}

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));

        // 初始化權杖池（如果不存在）
        if (!isset($_SESSION[self::TOKEN_POOL_KEY])) {
            $_SESSION[self::TOKEN_POOL_KEY] = [];
        }

        // 加入新權杖到池中
        $_SESSION[self::TOKEN_POOL_KEY][$token] = time();

        // 清理過期的權杖
        $this->cleanExpiredTokens();

        // 限制池大小
        $this->limitPoolSize();

        // 設定當前權杖（向後相容）
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();

        return $token;
    }

    public function validateToken(?string $token): void
    {
        if (!is_string($token) || $token === '') {
            $this->logCsrfAttack($token);

            throw new CsrfTokenException('缺少 CSRF token');
        }

        try {
            // 檢查權杖池模式
            if (isset($_SESSION[self::TOKEN_POOL_KEY]) && is_array($_SESSION[self::TOKEN_POOL_KEY])) {
                $this->validateTokenFromPool($token);
            } else {
                // 降級到單一權杖模式
                $this->validateSingleToken($token);
            }
        } catch (Exception $e) {
            $this->logCsrfAttack($token);

            throw new CsrfTokenException('CSRF token validation failed: ' . $e->getMessage());
        }
    }

    /**
     * 從權杖池中驗證權杖.
     */
    private function validateTokenFromPool(string $token): void
    {
    // $_SESSION[self::TOKEN_POOL_KEY] is confirmed to exist above
    $tokenPool = (array) $_SESSION[self::TOKEN_POOL_KEY];

        // 使用恆定時間比較防止時序攻擊
        $found = false;
        $tokenTime = null;

        foreach ($tokenPool as $poolToken => $timestamp) {
            if (!is_string($poolToken)) {
                continue;
            }

            $ts = is_numeric($timestamp) ? (int) $timestamp : null;

            if (hash_equals($poolToken, $token)) {
                $found = true;
                $tokenTime = $ts;
                break;
            }
        }

        if (!$found || $tokenTime === null) {
            throw new CsrfTokenException('CSRF token 驗證失敗');
        }

        // 檢查權杖是否過期
        if (time() - $tokenTime > self::TOKEN_EXPIRY) {
            throw new CsrfTokenException('CSRF token 已過期');
        }

        // 使用後移除權杖（單次使用）
        unset($_SESSION[self::TOKEN_POOL_KEY][$token]);

        // 產生新權杖以維持池的大小
        $this->generateToken();
    }

    /**
     * 單一權杖驗證（向後相容）.
     */
    private function validateSingleToken(string $token): void
    {

        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || !is_string($_SESSION['csrf_token'])) {
            throw new CsrfTokenException('無效的 CSRF token');
        }

        // 使用恆定時間比較防止時序攻擊
        if (!hash_equals((string) $_SESSION['csrf_token'], $token)) {
            throw new CsrfTokenException('CSRF token 驗證失敗');
        }

        if (time() - (int) $_SESSION['csrf_token_time'] > self::TOKEN_EXPIRY) {
            throw new CsrfTokenException('CSRF token 已過期');
        }

        // 產生新的權杖
        $this->generateToken();
    }

    /**
     * 清理過期的權杖.
     */
    private function cleanExpiredTokens(): void
    {
        if (!isset($_SESSION[self::TOKEN_POOL_KEY])) {
            return;
        }

        $currentTime = time();
    // $_SESSION[self::TOKEN_POOL_KEY] is confirmed to exist above
    $tokenPool = (array) $_SESSION[self::TOKEN_POOL_KEY];

        foreach ($tokenPool as $token => $timestamp) {
            $ts = is_numeric($timestamp) ? (int) $timestamp : null;
            if ($ts !== null && $currentTime - $ts > self::TOKEN_LIFETIME) {
                unset($_SESSION[self::TOKEN_POOL_KEY][$token]);
            }
        }
    }

    /**
     * 限制權杖池大小.
     */
    private function limitPoolSize(): void
    {
        if (!isset($_SESSION[self::TOKEN_POOL_KEY])) {
            return;
        }

        $now = time();
            // $_SESSION[self::TOKEN_POOL_KEY] is confirmed to exist above
            $tokenPool = (array) $_SESSION[self::TOKEN_POOL_KEY];

        // 如果池大小超過限制，移除最舊的權杖
        while (count($tokenPool) > self::TOKEN_POOL_SIZE) {
            $oldestToken = array_key_first($tokenPool);
                // array_key_first should not be null here because count($tokenPool) > TOKEN_POOL_SIZE
                $oldestTokenKey = (string) $oldestToken;
                unset($_SESSION[self::TOKEN_POOL_KEY][$oldestTokenKey]);
                // refresh tokenPool from session
                $tokenPool = (array) $_SESSION[self::TOKEN_POOL_KEY];
        }
    }

    /**
     * 檢查權杖是否有效（不會使用掉權杖）.
     */
    public function isTokenValid(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        try {
            // 檢查權杖池模式
            if (isset($_SESSION[self::TOKEN_POOL_KEY]) && is_array($_SESSION[self::TOKEN_POOL_KEY])) {
                // refresh tokenPool from session
                $tokenPool = (array) $_SESSION[self::TOKEN_POOL_KEY];

                foreach ($tokenPool as $poolToken => $timestamp) {
                    if (!is_string($poolToken)) {
                        continue;
                    }

                    $ts = is_numeric($timestamp) ? (int) $timestamp : null;
                    if ($ts === null) {
                        continue;
                    }

                    if (hash_equals($poolToken, $token)) {
                        return (time() - $ts) <= self::TOKEN_EXPIRY;
                    }
                }
            } else {
                // 降級到單一權杖模式
                if (isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) && isset($_SESSION['csrf_token_time'])) {
                    return hash_equals((string) $_SESSION['csrf_token'], $token)
                        && (time() - (int) $_SESSION['csrf_token_time']) <= self::TOKEN_EXPIRY;
                }
            }

            return false;
        } catch (Exception $e) {
            $this->logCsrfAttack($token);

            throw new CsrfTokenException('CSRF token validation failed: ' . $e->getMessage());
        }
    }

    /**
     * 預填權杖池.
     */
    public function initializeTokenPool(): void
    {
        $_SESSION[self::TOKEN_POOL_KEY] = [];

        // 產生初始權杖填滿池
        for ($i = 0; $i < self::TOKEN_POOL_SIZE; $i++) {
            $this->generateToken();
        }
    }

    /**
     * 取得權杖池狀態（用於除錯）.
     * @return array<string, mixed>
     */
    public function getTokenPoolStatus(): array
    {
        if (!isset($_SESSION[self::TOKEN_POOL_KEY])) {
            return [
                'enabled' => false,
                'size' => 0,
                'tokens' => [],
            ];
        }

    // $_SESSION[self::TOKEN_POOL_KEY] is confirmed to exist above
    $pool = (array) $_SESSION[self::TOKEN_POOL_KEY];
        $currentTime = time();

        $tokens = [];
        foreach ($pool as $token => $timestamp) {
            if (!is_string($token)) {
                continue;
            }

            $ts = is_numeric($timestamp) ? (int) $timestamp : null;
            $age = $ts !== null ? $currentTime - $ts : null;
                $tokens[] = [
                    // $token is guaranteed to be string due to the loop guard above
                    'token' => substr($token, 0, 8) . '...', // 只顯示前8位
                'age' => $age,
                'expires_in' => $ts !== null ? self::TOKEN_LIFETIME - $age : null,
                'expired' => $ts !== null ? ($age > self::TOKEN_LIFETIME) : false,
            ];
        }

        return [
            'enabled' => true,
            'size' => count($pool),
            'max_size' => self::TOKEN_POOL_SIZE,
            'tokens' => $tokens,
        ];
    }

    /**
     * 從請求中取得 CSRF token.
     * @return string|null 如果找到 token 則返回，否則返回 null
     */
    public function getTokenFromRequest(): ?string
    {
        // 從請求標頭、POST 資料或查詢參數中尋找 token
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_GET['_token'] ?? null;

        // 確保返回類型是 string|null
        return is_string($token) ? $token : null;
    }

    /**
     * 記錄 CSRF 攻擊事件.
     */
    private function logCsrfAttack(?string $attemptedToken): void
    {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            // 確保類型正確
            $ipAddress = is_string($ipAddress) ? $ipAddress : null;
            $userAgent = is_string($userAgent) ? $userAgent : null;

            $dto = CreateActivityLogDTO::securityEvent(
                actionType: ActivityType::CSRF_ATTACK_BLOCKED,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                description: 'CSRF token validation failed',
                metadata: [
                    'attempted_token' => is_string($attemptedToken) ? substr($attemptedToken, 0, 8) . '...' : null,
                    'referer' => isset($_SERVER['HTTP_REFERER']) && is_string($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
                    'method' => isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'unknown',
                ],
            );

            $this->activityLogger->log($dto);
        } catch (Exception $e) {
            // 記錄失敗但不影響主要功能
            error_log('Failed to log CSRF attack: ' . $e->getMessage());
        }
    }
}
