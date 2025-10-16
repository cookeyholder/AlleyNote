<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\PasswordResetTokenRepositoryInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\DTOs\ResetPasswordDTO;
use App\Domains\Auth\Entities\PasswordResetToken;
use App\Domains\Auth\ValueObjects\PasswordResetResult;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityType;
use App\Shared\Exceptions\ValidationException;
use DateInterval;
use DateTimeImmutable;
use RuntimeException;

/**
 * 密碼重設服務.
 */
final class PasswordResetService
{
    private const TOKEN_TTL_SECONDS = 3600;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordResetTokenRepositoryInterface $tokenRepository,
        private readonly PasswordManagementService $passwordManagementService,
        private readonly ActivityLoggingServiceInterface $activityLoggingService,
    ) {}

    public function requestReset(string $email, ?string $ipAddress = null, ?string $userAgent = null): PasswordResetResult
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            return PasswordResetResult::userNotFound();
        }

        if (!array_key_exists('id', $user)) {
            throw new RuntimeException('使用者資料缺少 ID 欄位');
        }

        $userIdValue = $user['id'];
        if (!is_int($userIdValue) && !is_numeric($userIdValue)) {
            throw new RuntimeException('使用者 ID 格式錯誤');
        }

        $userId = (int) $userIdValue;

        $this->tokenRepository->invalidateForUser($userId);
        $this->tokenRepository->cleanupExpired(new DateTimeImmutable());

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = new DateTimeImmutable()->add(new DateInterval('PT' . self::TOKEN_TTL_SECONDS . 'S'));

        $tokenEntity = PasswordResetToken::issue(
            userId: $userId,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
            requestedIp: $ipAddress,
            requestedUserAgent: $userAgent,
        );

        $this->tokenRepository->create($tokenEntity);

        $this->activityLoggingService->log(
            CreateActivityLogDTO::success(
                actionType: ActivityType::PASSWORD_RESET_REQUESTED,
                userId: $userId,
                description: '使用者請求密碼重設',
                metadata: [
                    'email' => $email,
                    'expires_at' => $expiresAt->format(DATE_ATOM),
                ],
            )->withNetworkInfo($ipAddress ?? '0.0.0.0', $userAgent ?? 'unknown'),
        );

        return PasswordResetResult::success($plainToken, $expiresAt);
    }

    /**
     * @throws ValidationException
     */
    public function resetPassword(ResetPasswordDTO $dto, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        if ($dto->password !== $dto->passwordConfirmation) {
            throw ValidationException::fromSingleError('password_confirmation', '密碼確認不一致');
        }

        $tokenHash = hash('sha256', $dto->token);
        $now = new DateTimeImmutable();
        $token = $this->tokenRepository->findValidByHash($tokenHash, $now);

        if (!$token) {
            throw ValidationException::fromSingleError('token', '密碼重設連結無效或已過期');
        }

        $userId = $token->getUserId();

        $this->passwordManagementService->resetPassword($userId, $dto->password);

        $usedToken = $token->markAsUsed(
            usedAt: $now,
            usedIp: $ipAddress,
            usedUserAgent: $userAgent,
        );
        $this->tokenRepository->markAsUsed($usedToken);

        $this->activityLoggingService->log(
            CreateActivityLogDTO::success(
                actionType: ActivityType::PASSWORD_RESET_COMPLETED,
                userId: $userId,
                description: '使用者完成密碼重設',
                metadata: [
                    'completed_at' => $now->format(DATE_ATOM),
                ],
            )->withNetworkInfo($ipAddress ?? '0.0.0.0', $userAgent ?? 'unknown'),
        );
    }
}
