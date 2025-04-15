<?php

declare(strict_types=1);

namespace App\Services\Security\Contracts;

interface XssProtectionServiceInterface
{
    /**
     * 清理單一字串中的 XSS 內容
     */
    public function sanitize(string $input): string;

    /**
     * 清理陣列中所有值的 XSS 內容
     */
    public function sanitizeArray(array $input): array;

    /**
     * 清理陣列中指定欄位的 XSS 內容
     *
     * @param array $input 要清理的陣列
     * @param array $fields 要清理的欄位名稱列表
     * @return array 清理後的陣列
     */
    public function cleanArray(array $input, array $fields): array;
}
