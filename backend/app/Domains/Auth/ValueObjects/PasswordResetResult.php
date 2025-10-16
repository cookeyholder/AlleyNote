<?php

declare(strict_types=1);

namespace App\Domains\Auth\ValueObjects;

use DateTimeImmutable;

/**
 * 密碼重設請求結果.
 */
final class PasswordResetResult
{
    private function __construct(
        private readonly bool $userFound,
        private readonly ?string $plainToken,
        private readonly ?DateTimeImmutable $expiresAt,
    ) {}

    public static function userNotFound(): self
    {
        return new self(false, null, null);
    }

    public static function success(string $plainToken, DateTimeImmutable $expiresAt): self
    {
        return new self(true, $plainToken, $expiresAt);
    }

    public function userFound(): bool
    {
        return $this->userFound;
    }

    public function getPlainToken(): ?string
    {
        return $this->plainToken;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
