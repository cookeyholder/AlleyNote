<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\Contracts;

use AlleyNote\Domains\Auth\DTOs\LoginRequestDTO;
use AlleyNote\Domains\Auth\DTOs\LoginResponseDTO;
use AlleyNote\Domains\Auth\DTOs\LogoutRequestDTO;
use AlleyNote\Domains\Auth\DTOs\RefreshRequestDTO;
use AlleyNote\Domains\Auth\DTOs\RefreshResponseDTO;
use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use DateTime;
use Exception;

/**
 * 認證服務介面.
 *
 * 定義完整的 JWT 認證服務功能，包括：
 * - 使用者登入與登出
 * - 權杖重新整理
 * - 權杖驗證與撤銷
 * - 使用者裝置管理
 *
 * 負責協調各個認證元件（JwtTokenService、RefreshTokenRepository 等）
 * 提供統一的認證服務入口。
 */
interface AuthenticationServiceInterface
{
    /**
     * 使用者登入.
     *
     * 驗證使用者憑證，成功後產生存取權杖和更新權杖。
     * 支援裝置資訊記錄和多裝置登入管理。
     *
     * @param LoginRequestDTO $request 登入請求資料
     * @param DeviceInfo $deviceInfo 裝置資訊
     * @return LoginResponseDTO 登入回應（包含權杖對）
     * @throws Exception 認證失敗時拋出
     */
    public function login(LoginRequestDTO $request, DeviceInfo $deviceInfo): LoginResponseDTO;

    /**
     * 權杖重新整理.
     *
     * 使用有效的更新權杖重新產生存取權杖。
     * 支援權杖家族管理和安全性檢查。
     *
     * @param RefreshRequestDTO $request 重新整理請求
     * @param DeviceInfo $deviceInfo 裝置資訊
     * @return RefreshResponseDTO 重新整理回應（新權杖對）
     * @throws Exception 權杖無效或重新整理失敗時拋出
     */
    public function refresh(RefreshRequestDTO $request, DeviceInfo $deviceInfo): RefreshResponseDTO;

    /**
     * 使用者登出.
     *
     * 撤銷指定的更新權杖，並可選擇是否登出所有裝置。
     *
     * @param LogoutRequestDTO $request 登出請求
     * @return bool 登出是否成功
     * @throws Exception 登出失敗時拋出
     */
    public function logout(LogoutRequestDTO $request): bool;

    /**
     * 驗證存取權杖.
     *
     * 驗證存取權杖的有效性，包括簽章、到期時間、黑名單檢查。
     *
     * @param string $accessToken 存取權杖
     * @return bool 權杖是否有效
     */
    public function validateAccessToken(string $accessToken): bool;

    /**
     * 驗證更新權杖.
     *
     * 驗證更新權杖的有效性，包括存在性、到期時間、撤銷狀態。
     *
     * @param string $refreshToken 更新權杖
     * @return bool 權杖是否有效
     */
    public function validateRefreshToken(string $refreshToken): bool;

    /**
     * 撤銷單一更新權杖.
     *
     * 撤銷指定的更新權杖，使其無效。
     *
     * @param string $refreshToken 要撤銷的更新權杖
     * @param string $reason 撤銷原因
     * @return bool 撤銷是否成功
     */
    public function revokeRefreshToken(string $refreshToken, string $reason = 'manual_revocation'): bool;

    /**
     * 撤銷使用者所有更新權杖.
     *
     * 撤銷指定使用者的所有更新權杖，實現全域登出。
     *
     * @param int $userId 使用者 ID
     * @param string $excludeJti 排除的權杖 JTI（可選）
     * @param string $reason 撤銷原因
     * @return int 撤銷的權杖數量
     */
    public function revokeAllUserTokens(int $userId, ?string $excludeJti = null, string $reason = 'logout_all'): int;

    /**
     * 撤銷指定裝置的所有更新權杖.
     *
     * 撤銷指定使用者在特定裝置上的所有更新權杖。
     *
     * @param int $userId 使用者 ID
     * @param string $deviceId 裝置 ID
     * @param string $reason 撤銷原因
     * @return int 撤銷的權杖數量
     */
    public function revokeDeviceTokens(int $userId, string $deviceId, string $reason = 'device_logout'): int;

    /**
     * 獲取使用者權杖統計.
     *
     * 取得指定使用者的權杖使用統計資訊。
     *
     * @param int $userId 使用者 ID
     * @return array<string, int> 權杖統計資訊
     */
    public function getUserTokenStats(int $userId): array;

    /**
     * 清理過期權杖.
     *
     * 清理系統中過期的更新權杖，釋放儲存空間。
     *
     * @param DateTime|null $beforeDate 清理此日期前的權杖（預設為目前時間）
     * @return int 清理的權杖數量
     */
    public function cleanupExpiredTokens(?DateTime $beforeDate = null): int;

    /**
     * 清理已撤銷權杖.
     *
     * 清理系統中已撤銷的更新權杖記錄。
     *
     * @param int $days 保留天數（預設 30 天）
     * @return int 清理的權杖數量
     */
    public function cleanupRevokedTokens(int $days = 30): int;
}
