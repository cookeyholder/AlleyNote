<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Enums\SanitizerMode;
use HTMLPurifier;
use HTMLPurifier_Config;

final readonly class OutputSanitizerService implements OutputSanitizerInterface
{
    private HTMLPurifier $richTextPurifier;

    public function __construct(
        private int $defaultTruncateLength = 150,
        private string $encoding = 'UTF-8',
    ) {
        $this->richTextPurifier = $this->initializeRichTextPurifier();
    }

    /**
     * 清理 HTML 內容以防止 XSS 攻擊 (完全轉義).
     */
    public function sanitizeHtml(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES, $this->encoding);
    }

    /**
     * 清理富文本內容，保留安全的 HTML 標籤.
     */
    public function sanitizeRichText(string $content): string
    {
        if (empty($content)) {
            return '';
        }

        return $this->richTextPurifier->purify($content);
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
            SanitizerMode::TRUNCATE => $this->sanitizeAndTruncate($content, $truncateLength),
            // 注意：這裡如果之後有新增模式需要更新
        };
    }

    /**
     * 初始化 HTMLPurifier 配置.
     */
    private function initializeRichTextPurifier(): HTMLPurifier
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', $this->encoding);
        // 允許常用的富文本標籤，對應前端 DOMPurify 的設定
        $config->set('HTML.Allowed', 'h1[class|id],h2[class|id],h3[class|id],h4[class|id],h5[class|id],h6[class|id],p[class|id],br,strong,em,u,s,a[href|target|rel|class|id],img[src|alt|title|class|id],ul[class|id],ol[class|id],li[class|id],blockquote[class|id],pre[class|id],code[class|id],table[class|id],thead[class|id],tbody[class|id],tr[class|id],th[class|id],td[class|id],div[class|id],span[class|id]');
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.TargetBlank', true);
        $config->set('AutoFormat.RemoveEmpty', false); // 保留空標籤（如 CKEditor 產生的）

        return new HTMLPurifier($config);
    }
}
