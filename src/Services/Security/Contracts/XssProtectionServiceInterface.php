<?php

declare(strict_types=1);

namespace App\Services\Security\Contracts;

interface XssProtectionServiceInterface
{
    public function sanitize(string $input): string;
    public function sanitizeArray(array $input): array;
}
