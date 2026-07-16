<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\UserRepositoryInterface;

/**
 * JWT 角色時效驗證器.
 *
 * 比對 JWT 的發行時間（iat）與使用者的角色更新時間（role_updated_at），
 * 若角色在 Token 發行後曾被更新，則要求使用者重新登入。
 */
final class JwtRoleFreshnessValidator
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * 驗證 JWT iat 是否在使用者角色更新時間之後.
     *
     * @param int $userId 使用者 ID
     * @param int $tokenIat JWT 發行時間戳（秒）
     *
     * @return bool true 表示角色未變更，false 表示角色已變更需重新登入
     */
    public function validate(int $userId, int $tokenIat): bool
    {
        $roleUpdatedAt = $this->getUserRoleUpdatedAt($userId);
        if ($roleUpdatedAt === null) {
            return true;
        }

        return $tokenIat >= $roleUpdatedAt;
    }

    /**
     * 取得使用者角色更新時間戳.
     *
     * @param int $userId 使用者 ID
     *
     * @return int|null 角色更新時間戳（秒），若無該欄位則回傳 null
     */
    public function getUserRoleUpdatedAt(int $userId): ?int
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            return null;
        }

        $roleUpdatedAt = $user['role_updated_at'] ?? null;
        if ($roleUpdatedAt === null) {
            return null;
        }

        if (is_string($roleUpdatedAt)) {
            $timestamp = strtotime($roleUpdatedAt);

            return $timestamp !== false ? $timestamp : null;
        }

        return is_int($roleUpdatedAt) ? $roleUpdatedAt : null;
    }
}
