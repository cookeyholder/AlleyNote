<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\Contracts;

use AlleyNote\Domains\Auth\Exceptions\InvalidTokenException;
use AlleyNote\Domains\Auth\Exceptions\TokenExpiredException;
use AlleyNote\Domains\Auth\Exceptions\TokenGenerationException;
use AlleyNote\Domains\Auth\Exceptions\TokenParsingException;
use AlleyNote\Domains\Auth\Exceptions\TokenValidationException;
use DateTimeImmutable;

/**
 * JWT Provider 介面.
 *
 * 定義JWT token操作的核心方法，包含token生成、驗證、解析等功能。
 * 實作類別需要提供完整的JWT token處理能力。
 */
interface JwtProviderInterface
{
    /**
     * 生成 access token.
     *
     * @param array<string, mixed> $payload Token payload
     * @param int|null $ttl Token 有效期（秒）
     * @return string JWT token
     *
     * @throws TokenGenerationException 當token生成失敗時
     */
    public function generateAccessToken(array $payload, ?int $ttl = null): string;

    /**
     * 生成 refresh token.
     *
     * @param array<string, mixed> $payload Token payload
     * @param int|null $ttl Token 有效期（秒）
     * @return string JWT token
     *
     * @throws TokenGenerationException 當token生成失敗時
     */
    public function generateRefreshToken(array $payload, ?int $ttl = null): string;

    /**
     * 驗證 token.
     *
     * @param string $token JWT token
     * @param string|null $expectedType 期望的token類型
     * @return array<string, mixed> 解析後的payload
     *
     * @throws TokenExpiredException 當token過期時
     * @throws InvalidTokenException 當token無效時
     * @throws TokenValidationException 當token驗證失敗時
     */
    public function validateToken(string $token, ?string $expectedType = null): array;

    /**
     * 解析 token payload（不進行驗證）.
     *
     * @param string $token JWT token
     * @return array<string, mixed> 解析後的payload
     *
     * @throws TokenParsingException 當token解析失敗時
     */
    public function parseTokenUnsafe(string $token): array;

    /**
     * 取得 token 過期時間戳記.
     *
     * @param string $token JWT token
     * @return DateTimeImmutable|null 過期時間，如果無法取得則回傳null
     */
    public function getTokenExpiration(string $token): ?DateTimeImmutable;

    /**
     * 檢查 token 是否已過期.
     *
     * @param string $token JWT token
     * @return bool true表示已過期，false表示未過期
     */
    public function isTokenExpired(string $token): bool;
}
