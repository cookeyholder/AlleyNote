<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

/**
 * 輸出清理服務介面.
 *
 * 定義內容清理和防止 XSS 攻擊的標準介面
 */
interface OutputSanitizerInterface
{
    /**
     * 清理 HTML 內容以防止 XSS 攻擊.
     */
    public function sanitizeHtml(string $content): string;

    /**
     * 清理標題內容.
     */
    public function sanitizeTitle(string $title): string;

    /**
     * 清理陣列中的所有字串值以供顯示.
     */
    public function sanitizeForDisplay(array $data): array;

    /**
     * 清理字串，保留換行符號.
     */
    public function sanitizePreserveNewlines(string $content): string;

    /**
     * 清理並截斷文字，用於摘要顯示.
     */
    public function sanitizeAndTruncate(string $content, int $length = 150): string;
}
