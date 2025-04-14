<?php

declare(strict_types=1);

namespace App\Services\Security\Contracts;

interface CsrfProtectionServiceInterface
{
    public function generateToken(): string;
    public function validateToken(string $token): bool;
    public function getTokenFromRequest(): ?string;
}
