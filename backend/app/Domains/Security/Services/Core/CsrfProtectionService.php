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

    private const TOKEN_POOL_SIZE = 5; // 權杖池大小

    private const TOKEN_POOL_KEY = 'csrf_token_pool';

    public function __construct(
        private ActivityLoggingServiceInterface $activityLogger,
    ) {}

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));

        // 初始化權杖池（如果不存在）
        if (!isset($_SESSION[self::TOKEN_POOL_KEY]) || !is_array($_SESSION[self::TOKEN_POOL_KEY])) {
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
        if (empty($token)) {
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
        } catch (CsrfTokenException $e) {
            $this->logCsrfAttack($token);

            throw $e;
        }
    }

    /**
     * 從權杖池中驗證權杖.
     */
    private function validateTokenFromPool(string $token): void
    {
        $tokenPool = $_SESSION[self::TOKEN_POOL_KEY] ?? [];
        if (!is_array($tokenPool)) {
            throw new CsrfTokenException('CSRF token 池無效');
        }

        // 使用恆定時間比較防止時序攻擊
        $found = false;
        $tokenTime = null;

        foreach ($tokenPool as $poolToken => $timestamp) {
            if (is_string($poolToken) && is_int($timestamp) && hash_equals($poolToken, $token)) {
                $found = true;
                $tokenTime = $timestamp;
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
        if (isset($_SESSION[self::TOKEN_POOL_KEY]) && is_array($_SESSION[self::TOKEN_POOL_KEY])) {
            unset($_SESSION[self::TOKEN_POOL_KEY][$token]);
        }

        // 產生新權杖以維持池的大小
        $this->generateToken();
    }

    /**
     * 單一權杖驗證（向後相容）.
     */
    private function validateSingleToken(string $token): void
    {
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        $sessionTime = $_SESSION['csrf_token_time'] ?? null;

        if (!is_string($sessionToken) || !is_int($sessionTime)) {
            throw new CsrfTokenException('無效的 CSRF token');
        }

        // 使用恆定時間比較防止時序攻擊
        if (!hash_equals($sessionToken, $token)) {
            throw new CsrfTokenException('CSRF token 驗證失敗');
        }

        if (time() - $sessionTime > self::TOKEN_EXPIRY) {
            throw new CsrfTokenException('CSRF token 已過期');
        }

        // 更新 token 以防止重放攻擊
        $this->generateToken();
    }

    /**
     * 清理過期的權杖.
     */
    private function cleanExpiredTokens(): void
    {
        $tokenPool = $_SESSION[self::TOKEN_POOL_KEY] ?? null;
        if (!is_array($tokenPool)) {
            return;
        }

        $currentTime = time();

        foreach ($tokenPool as $token => $timestamp) {
            if (is_string($token) && is_int($timestamp) && ($currentTime - $timestamp) > self::TOKEN_EXPIRY) {
                if (isset($_SESSION[self::TOKEN_POOL_KEY]) && is_array($_SESSION[self::TOKEN_POOL_KEY])) {
                    unset($_SESSION[self::TOKEN_POOL_KEY][$token]);
                }
            }
        }
    }

    /**
     * 限制權杖池大小.
     */
    private function limitPoolSize(): void
    {
        $tokenPool = $_SESSION[self::TOKEN_POOL_KEY] ?? null;
        if (!is_array($tokenPool)) {
            return;
        }

        // 如果池大小超過限制，移除最舊的權杖
        while (count($tokenPool) > self::TOKEN_POOL_SIZE) {
            $oldestToken = array_key_first($tokenPool);
            if (is_string($oldestToken) && isset($_SESSION[self::TOKEN_POOL_KEY]) && is_array($_SESSION[self::TOKEN_POOL_KEY])) {
                unset($_SESSION[self::TOKEN_POOL_KEY][$oldestToken]);
            }
            $tokenPool = $_SESSION[self::TOKEN_POOL_KEY] ?? [];
            if (!is_array($tokenPool)) {
                break;
            }
        }
    }

    /**
     * 檢查權杖是否有效（不會使用掉權杖）.
     */
    public function isTokenValid(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        try {
            // 檢查權杖池模式
            $tokenPool = $_SESSION[self::TOKEN_POOL_KEY] ?? null;
            if (is_array($tokenPool)) {
                foreach ($tokenPool as $poolToken => $timestamp) {
                    if (is_string($poolToken) && is_int($timestamp) && hash_equals($poolToken, $token)) {
                        return (time() - $timestamp) <= self::TOKEN_EXPIRY;
                    }
                }
            } else {
                // 降級到單一權杖模式
                $sessionToken = $_SESSION['csrf_token'] ?? null;
                $sessionTime = $_SESSION['csrf_token_time'] ?? null;

                if (is_string($sessionToken) && is_int($sessionTime)) {
                    return hash_equals($sessionToken, $token)
                        && (time() - $sessionTime) <= self::TOKEN_EXPIRY;
                }
            }

            return false;
        } catch (Exception $e) {
            return false;
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
     */
    public function getTokenPoolStatus(): array
    {
        $pool = $_SESSION[self::TOKEN_POOL_KEY] ?? null;
        if (!is_array($pool)) {
            return [
                'enabled' => false,
                'size' => 0,
                'tokens' => [],
            ];
        }

        $currentTime = time();

        $tokens = [];
        foreach ($pool as $token => $timestamp) {
            if (!is_string($token) || !is_int($timestamp)) {
                continue;
            }
            $tokens[] = [
                'token' => substr($token, 0, 8) . '...', // 只顯示前8位
                'age' => $currentTime - $timestamp,
                'expires_in' => self::TOKEN_EXPIRY - ($currentTime - $timestamp),
                'expired' => ($currentTime - $timestamp) > self::TOKEN_EXPIRY,
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
     *
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
                    'attempted_token' => $attemptedToken ? substr($attemptedToken, 0, 8) . '...' : null,
                    'referer' => $_SERVER['HTTP_REFERER'] ?? null,
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                ],
            );

            $this->activityLogger->log($dto);
        } catch (Exception) {
            // 記錄失敗不應影響主要功能
        }
    }
}
