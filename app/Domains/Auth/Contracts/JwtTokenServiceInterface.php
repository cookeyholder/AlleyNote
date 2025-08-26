<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\Contracts;

use AlleyNote\Domains\Auth\Exceptions\InvalidTokenException;
use AlleyNote\Domains\Auth\Exceptions\TokenExpiredException;
use AlleyNote\Domains\Auth\Exceptions\TokenGenerationException;
use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use AlleyNote\Domains\Auth\ValueObjects\JwtPayload;
use AlleyNote\Domains\Auth\ValueObjects\TokenPair;

/**
 * JWT Token 服務介面.
 *
 * 定義JWT token的核心操作方法，包含token產生、驗證、解析等功能。
 * 採用RS256演算法進行token簽名，確保token的安全性和可驗證性。
 */
interface JwtTokenServiceInterface
{
    /**
     * 為使用者產生JWT token對（access token 和 refresh token）.
     *
     * @param int $userId 使用者ID
     * @param DeviceInfo $deviceInfo 裝置資訊
     * @param array<string, mixed> $customClaims 額外的自訂聲明
     * @return TokenPair JWT token對
     *
     * @throws TokenGenerationException 當token產生失敗時
     */
    public function generateTokenPair(int $userId, DeviceInfo $deviceInfo, array $customClaims = []): TokenPair;

    /**
     * 驗證access token的有效性.
     *
     * @param string $token JWT access token
     * @param bool $checkBlacklist 是否檢查黑名單，預設為true
     * @return JwtPayload 驗證成功時回傳payload資訊
     *
     * @throws InvalidTokenException 當token格式無效或簽名驗證失敗時
     * @throws TokenExpiredException 當token已過期時
     */
    public function validateAccessToken(string $token, bool $checkBlacklist = true): JwtPayload;

    /**
     * 驗證refresh token的有效性.
     *
     * @param string $token JWT refresh token
     * @param bool $checkBlacklist 是否檢查黑名單，預設為true
     * @return JwtPayload 驗證成功時回傳payload資訊
     *
     * @throws InvalidTokenException 當token格式無效或簽名驗證失敗時
     * @throws TokenExpiredException 當token已過期時
     */
    public function validateRefreshToken(string $token, bool $checkBlacklist = true): JwtPayload;

    /**
     * 從token中提取payload資訊（不驗證簽名）.
     *
     * 注意：此方法不驗證token簽名，僅用於提取資訊，不應用於驗證token有效性
     *
     * @param string $token JWT token
     * @return JwtPayload payload資訊
     *
     * @throws InvalidTokenException 當token格式無效時
     */
    public function extractPayload(string $token): JwtPayload;

    /**
     * 使用refresh token產生新的access token.
     *
     * @param string $refreshToken 有效的refresh token
     * @param DeviceInfo $deviceInfo 目前裝置資訊
     * @return TokenPair 新的token對（包含新的access token和可能輪轉的refresh token）
     *
     * @throws InvalidTokenException 當refresh token無效時
     * @throws TokenExpiredException 當refresh token已過期時
     * @throws TokenGenerationException 當新token產生失敗時
     */
    public function refreshTokens(string $refreshToken, DeviceInfo $deviceInfo): TokenPair;

    /**
     * 撤銷token（加入黑名單）.
     *
     * @param string $token 要撤銷的token（access token或refresh token）
     * @param string $reason 撤銷原因
     * @return bool 撤銷成功時回傳true
     */
    public function revokeToken(string $token, string $reason = 'manual_revocation'): bool;

    /**
     * 撤銷使用者的所有token.
     *
     * @param int $userId 使用者ID
     * @param string $reason 撤銷原因
     * @return int 撤銷的token數量
     */
    public function revokeAllUserTokens(int $userId, string $reason = 'revoke_all_sessions'): int;

    /**
     * 檢查token是否已被撤銷（是否在黑名單中）.
     *
     * @param string $token 要檢查的token
     * @return bool 在黑名單中時回傳true
     */
    public function isTokenRevoked(string $token): bool;

    /**
     * 取得token的剩餘有效時間（秒）.
     *
     * @param string $token JWT token
     * @return int 剩餘秒數，已過期時回傳0
     *
     * @throws InvalidTokenException 當token格式無效時
     */
    public function getTokenRemainingTime(string $token): int;

    /**
     * 檢查token是否即將過期
     *
     * @param string $token JWT token
     * @param int $thresholdSeconds 臨界值（秒），預設為300秒（5分鐘）
     * @return bool 即將過期時回傳true
     *
     * @throws InvalidTokenException 當token格式無效時
     */
    public function isTokenNearExpiry(string $token, int $thresholdSeconds = 300): bool;

    /**
     * 驗證token是否屬於特定使用者.
     *
     * @param string $token JWT token
     * @param int $userId 使用者ID
     * @return bool 屬於該使用者時回傳true
     *
     * @throws InvalidTokenException 當token格式無效時
     */
    public function isTokenOwnedBy(string $token, int $userId): bool;

    /**
     * 驗證token是否來自特定裝置.
     *
     * @param string $token JWT token
     * @param DeviceInfo $deviceInfo 裝置資訊
     * @return bool 來自該裝置時回傳true
     *
     * @throws InvalidTokenException 當token格式無效時
     */
    public function isTokenFromDevice(string $token, DeviceInfo $deviceInfo): bool;

    /**
     * 取得JWT演算法名稱.
     *
     * @return string 演算法名稱（例如：'RS256'）
     */
    public function getAlgorithm(): string;

    /**
     * 取得access token的TTL（存活時間）.
     *
     * @return int TTL秒數
     */
    public function getAccessTokenTtl(): int;

    /**
     * 取得refresh token的TTL（存活時間）.
     *
     * @return int TTL秒數
     */
    public function getRefreshTokenTtl(): int;
}
