<?php

declare(strict_types=1);

namespace App\Domains\Security\Contracts;

interface XssProtectionServiceInterface
{
    /**
     * 清理單一字串中的 XSS 內容.
     */
    public function sanitize(string $input): string;

    /**
     * 清理陣列中所有值的 XSS 內容.
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitizeArray(array $input): array;

    /**
     * 清理陣列中指定欄位的 XSS 內容.
     * @param array<string, mixed> $input 要清理的陣列
     * @return array<string, mixed>
     */
    public function cleanArray(array $input, array $fields): array;
}
