<?php

declare(strict_types=1);

namespace App\Services\Security;

class XssProtectionService
{
    public function clean(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function cleanArray(array $input, array $keys): array
    {
        foreach ($keys as $key) {
            if (isset($input[$key])) {
                $input[$key] = $this->clean($input[$key]);
            }
        }
        return $input;
    }
}
