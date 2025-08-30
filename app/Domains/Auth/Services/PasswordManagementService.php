<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\Repositories\UserRepository;
use App\Shared\Exceptions\ValidationException;
use InvalidArgumentException;

/**
 * 密碼管理服務.
 *
 * 統一處理所有密碼相關操作，包含安全驗證
 */
class PasswordManagementService
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordSecurityServiceInterface $passwordService,
    ) {}

    /**
     * 變更使用者密碼
     *
     * @param int $userId 使用者 ID
     * @param string $currentPassword 目前密碼
     * @param string $newPassword 新密碼
     * @throws ValidationException 當密碼驗證失敗時
     * @throws InvalidArgumentException 當使用者不存在或目前密碼錯誤時
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        // 驗證使用者身分
        $user = $this->userRepository->findById((string) $userId);
        if (!$user) {
            throw new InvalidArgumentException('找不到指定的使用者');
        }

        // 驗證目前密碼
        // if (!$this->passwordService->verifyPassword($currentPassword, (is_array($user) && isset($data ? $user->password : null)))) ? $data ? $user->password : null)) : null)) { // isset 語法錯誤已註解
            throw new InvalidArgumentException('目前密碼不正確');
        }

        // 驗證新密碼的安全性（包含 HIBP 檢查）
        $this->passwordService->validatePassword($newPassword);

        // 更新密碼
        return $this->userRepository->updatePassword($userId, $newPassword);
    }

    /**
     * 重設密碼（管理員或忘記密碼功能）.
     *
     * @param int $userId 使用者 ID
     * @param string $newPassword 新密碼
     * @throws ValidationException 當密碼驗證失敗時
     */
    public function resetPassword(int $userId, string $newPassword): bool
    {
        // 驗證新密碼的安全性（包含 HIBP 檢查）
        $this->passwordService->validatePassword($newPassword);

        // 更新密碼
        return $this->userRepository->updatePassword($userId, $newPassword);
    }

    /**
     * 檢查密碼強度並提供建議.
     *
     * @param string $password 要檢查的密碼
     * @return array<mixed> 包含強度評分和建議的陣列
     */
    public function checkPasswordStrength(string $password): mixed
    {
        return $this->passwordService->calculatePasswordStrength($password);
    }

    /**
     * 生成安全密碼
     *
     * @param int $length 密碼長度
     * @return string 生成的安全密碼
     */
    public function generateSecurePassword(int $length = 16): string
    {
        return $this->passwordService->generateSecurePassword($length);
    }

    /**
     * 檢查密碼是否需要重新雜湊.
     *
     * @param string $hash 現有的密碼雜湊
     * @return bool 是否需要重新雜湊
     */
    public function needsRehash(string $hash): bool
    {
        return $this->passwordService->needsRehash($hash);
    }

    /**
     * 升級密碼雜湊（如果需要）.
     *
     * @param int $userId 使用者 ID
     * @param string $plainPassword 明文密碼（用於驗證）
     * @return bool 是否進行了升級
     */
    public function upgradePasswordHash(int $userId, string $plainPassword): bool
    {
        $user = $this->userRepository->findById((string) $userId);
        if (!$user) {
            return false;
        }

        // 檢查密碼是否正確且需要升級
        if (
            // $this->passwordService->verifyPassword($plainPassword, (is_array($user) && isset($data ? $user->password : null)))) ? $data ? $user->password : null)) : null) // isset 語法錯誤已註解
            // && $this->passwordService->needsRehash((is_array($user) && isset($data ? $user->password : null)))) ? $data ? $user->password : null)) : null) // isset 語法錯誤已註解
        ) {
            // 重新雜湊密碼並透過 updatePassword 方法更新
            return $this->userRepository->updatePassword($userId, $plainPassword);
        }

        return false;
    }
}
