<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Shared\Contracts\OutputSanitizerInterface;

enum SanitizerMode: string
{
    case HTML = 'html';
    case TITLE = 'title';
    case PRESERVE_NEWLINES = 'preserve_newlines';
    case TRUNCATE = 'truncate';
}

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
        return array_map(
            fn($value) => is_string($value)
                ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                : $value,
            $data,
        );
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

        return mb_strlen($sanitized) > $length
            ? mb_substr($sanitized, 0, $length) . '...'
            : $sanitized;
    }
}

/**
 * 實作 DDD 合規的輸出清理服務.
 */
final readonly class OutputSanitizerService implements OutputSanitizerInterface
{
    public function __construct(
        private int $defaultTruncateLength = 150,
        private string $encoding = 'UTF-8',
    ) {}

    /**
     * 清理 HTML 內容以防止 XSS 攻擊.
     */
    public function sanitizeHtml(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES, $this->encoding);
    }

    /**
     * 清理標題內容.
     */
    public function sanitizeTitle(string $title): string
    {
        return htmlspecialchars($title, ENT_QUOTES, $this->encoding);
    }

    /**
     * 清理陣列中的所有字串值以供顯示.
     */
    public function sanitizeForDisplay(array $data): array
    {
        return array_map(
            fn($value) => is_string($value)
                ? htmlspecialchars($value, ENT_QUOTES, $this->encoding)
                : $value,
            $data,
        );
    }

    /**
     * 清理字串，保留換行符號.
     */
    public function sanitizePreserveNewlines(string $content): string
    {
        return nl2br(htmlspecialchars($content, ENT_QUOTES, $this->encoding));
    }

    /**
     * 清理並截斷文字，用於摘要顯示.
     */
    public function sanitizeAndTruncate(
        string $content,
        ?int $length = null,
    ): string {
        $length ??= $this->defaultTruncateLength;
        $sanitized = htmlspecialchars($content, ENT_QUOTES, $this->encoding);

        return mb_strlen($sanitized) > $length
            ? mb_substr($sanitized, 0, $length) . '...'
            : $sanitized;
    }

    /**
     * 通用清理方法，支援多種模式.
     */
    public function sanitizeByMode(
        string $content,
        SanitizerMode $mode,
        ?int $truncateLength = null,
    ): string {
        return match ($mode) {
            SanitizerMode::HTML => $this->sanitizeHtml($content),
            SanitizerMode::TITLE => $this->sanitizeTitle($content),
            SanitizerMode::PRESERVE_NEWLINES => $this->sanitizePreserveNewlines($content),
            SanitizerMode::TRUNCATE => $this->sanitizeAndTruncate($content, $truncateLength)
        };
    }
}
