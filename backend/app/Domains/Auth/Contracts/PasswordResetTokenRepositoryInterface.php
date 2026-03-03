<?php

declare(strict_types=1);

namespace App\Domains\Auth\Contracts;

use App\Domains\Auth\Entities\PasswordResetToken;
use DateTimeImmutable;

/**
 * 密碼重設憑證儲存庫介面.
 */
interface PasswordResetTokenRepositoryInterface
{
    public function create(PasswordResetToken $token): PasswordResetToken;

    public function findValidByHash(string $tokenHash, DateTimeImmutable $now): ?PasswordResetToken;

    public function invalidateForUser(int $userId): void;

    public function markAsUsed(PasswordResetToken $token): void;

    public function cleanupExpired(DateTimeImmutable $now): int;
}
