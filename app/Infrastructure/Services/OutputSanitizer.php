<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Shared\Contracts\OutputSanitizerInterface;

/**
 * 向下相容的靜態輸出清理器.
 * @deprecated 請遷移到使用 OutputSanitizerService 的實例方法
 */
class OutputSanitizer
{
    /**
     * 清理 HTML 內容以防止 XSS 攻擊.
     */
    public static function sanitizeHtml(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 清理標題內容.
     */
    public static function sanitizeTitle(string $title): string
    {
        return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 清理陣列中的所有字串值以供顯示.
     */
    public static function sanitizeForDisplay(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * 清理字串，保留換行符號
     */
    public static function sanitizePreserveNewlines(string $content): string
    {
        return nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * 清理並截斷文字，用於摘要顯示.
     */
    public static function sanitizeAndTruncate(string $content, int $length = 150): string
    {
        $sanitized = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        if (mb_strlen($sanitized) > $length) {
            return mb_substr($sanitized, 0, $length) . '...';
        }

        return $sanitized;
    }
}

/**
 * 實作 DDD 合規的輸出清理服務.
 */
class OutputSanitizerService implements OutputSanitizerInterface
{
    /**
     * 清理 HTML 內容以防止 XSS 攻擊.
     */
    public function sanitizeHtml(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 清理標題內容.
     */
    public function sanitizeTitle(string $title): string
    {
        return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 清理陣列中的所有字串值以供顯示.
     */
    public function sanitizeForDisplay(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * 清理字串，保留換行符號.
     */
    public function sanitizePreserveNewlines(string $content): string
    {
        return nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * 清理並截斷文字，用於摘要顯示.
     */
    public function sanitizeAndTruncate(string $content, int $length = 150): string
    {
        $sanitized = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        if (mb_strlen($sanitized) > $length) {
            return mb_substr($sanitized, 0, $length) . '...';
        }

        return $sanitized;
    }
}
