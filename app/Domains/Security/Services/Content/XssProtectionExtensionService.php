<?php

declare(strict_types=1);

namespace App\Domains\Security\Services\Content;

use App\Domains\Security\Services\Core\XssProtectionService;
use App\Domains\Post\Services\RichTextProcessorService;
use App\Domains\Post\Services\ContentModerationService;

/**
 * XSS 防護擴展服務.
 *
 * 提供進階的 XSS 防護功能，包含情境感知的防護和自動修復
 */
class XssProtectionExtensionService
{
    private XssProtectionService $baseXssProtection;

    private RichTextProcessorService $richTextProcessor;

    private ContentModerationService $contentModerator;

    private array $config;

    public function __construct(
        XssProtectionService $baseXssProtection,
        RichTextProcessorService $richTextProcessor,
        ContentModerationService $contentModerator,
        array $config = [],
    ) {
        $this->baseXssProtection = $baseXssProtection;
        $this->richTextProcessor = $richTextProcessor;
        $this->contentModerator = $contentModerator;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * 情境感知的 XSS 防護.
     */
    public function protectByContext(string $input, string $context, array $options = []): array
    {
        $result = [
            'protected_content' => '',
            'context' => $context,
            'protection_level' => 'standard',
            'modifications' => [],
            'warnings' => [],
            'security_score' => 100,
        ];

        switch ($context) {
            case 'rich_text_editor':
                $result = $this->protectRichTextEditor($input, $options);
                break;
            case 'user_bio':
                $result = $this->protectUserBio($input, $options);
                break;
            case 'post_title':
                $result = $this->protectPostTitle($input, $options);
                break;
            case 'post_content':
                $result = $this->protectPostContent($input, $options);
                break;
            case 'comment':
                $result = $this->protectComment($input, $options);
                break;
            case 'search_query':
                $result = $this->protectSearchQuery($input, $options);
                break;
            case 'url_parameter':
                $result = $this->protectUrlParameter($input, $options);
                break;
            case 'json_data':
                $result = $this->protectJsonData($input, $options);
                break;
            case 'file_upload':
                $result = $this->protectFileUpload($input, $options);
                break;
            default:
                $result = $this->protectGeneric($input, $options);
        }

        return $result;
    }

    /**
     * 富文本編輯器防護.
     */
    private function protectRichTextEditor(string $input, array $options): array
    {
        $userLevel = $options['user_level'] ?? 'basic';
        $processResult = $this->richTextProcessor->processCKEditorContent($input, $userLevel);

        $result = [
            'protected_content' => $processResult['content'],
            'context' => 'rich_text_editor',
            'protection_level' => 'enhanced',
            'modifications' => [],
            'warnings' => $processResult['warnings'],
            'security_score' => $this->calculateSecurityScore($input, $processResult['content']),
        ];

        if ($input !== $processResult['content']) {
            $result['modifications'][] = [
                'type' => 'html_sanitization',
                'description' => 'HTML 內容已經過安全過濾',
                'original_length' => strlen($input),
                'filtered_length' => strlen($processResult['content']),
            ];
        }

        return $result;
    }

    /**
     * 使用者簡介防護.
     */
    private function protectUserBio(string $input, array $options): array
    {
        // 使用者簡介只允許基本格式化
        $allowedTags = '<b><strong><i><em><u><br><p>';
        $cleaned = strip_tags($input, $allowedTags);
        $cleaned = $this->baseXssProtection->clean($cleaned);

        return [
            'protected_content' => $cleaned,
            'context' => 'user_bio',
            'protection_level' => 'strict',
            'modifications' => $input !== $cleaned ? [['type' => 'tag_filtering', 'description' => '移除不允許的 HTML 標籤']] : [],
            'warnings' => [],
            'security_score' => $this->calculateSecurityScore($input, $cleaned),
        ];
    }

    /**
     * 文章標題防護.
     */
    private function protectPostTitle(string $input, array $options): array
    {
        // 標題不允許任何 HTML
        $cleaned = $this->baseXssProtection->cleanStrict($input);

        // 長度限制
        if (strlen($cleaned) > $this->config['max_title_length']) {
            $cleaned = mb_substr($cleaned, 0, $this->config['max_title_length']);
        }

        return [
            'protected_content' => $cleaned,
            'context' => 'post_title',
            'protection_level' => 'maximum',
            'modifications' => $input !== $cleaned ? [['type' => 'html_removal', 'description' => '移除所有 HTML 標籤']] : [],
            'warnings' => [],
            'security_score' => 100, // 標題經過最嚴格過濾
        ];
    }

    /**
     * 文章內容防護.
     */
    private function protectPostContent(string $input, array $options): array
    {
        $userLevel = $options['user_level'] ?? 'basic';

        // 先進行內容審核
        $moderationResult = $this->contentModerator->moderateContent($input, $options);

        if ($moderationResult['status'] === 'rejected') {
            return [
                'protected_content' => '',
                'context' => 'post_content',
                'protection_level' => 'blocked',
                'modifications' => [['type' => 'content_blocked', 'description' => '內容被安全系統阻止']],
                'warnings' => [['type' => 'security_block', 'message' => '內容包含不安全的元素']],
                'security_score' => 0,
            ];
        }

        // 進行富文本處理
        $processResult = $this->richTextProcessor->processContent($input, $userLevel);

        return [
            'protected_content' => $processResult['content'],
            'context' => 'post_content',
            'protection_level' => 'enhanced',
            'modifications' => $processResult['warnings'],
            'warnings' => array_merge($moderationResult['issues'], $processResult['warnings']),
            'security_score' => $this->calculateSecurityScore($input, $processResult['content']),
        ];
    }

    /**
     * 評論防護.
     */
    private function protectComment(string $input, array $options): array
    {
        // 評論允許的標籤較少
        $allowedTags = '<b><strong><i><em><u><br><p><a>';
        $cleaned = strip_tags($input, $allowedTags);
        $cleaned = $this->baseXssProtection->cleanHtml($cleaned);

        // 長度限制
        if (strlen($cleaned) > $this->config['max_comment_length']) {
            $cleaned = mb_substr($cleaned, 0, $this->config['max_comment_length']) . '...';
        }

        return [
            'protected_content' => $cleaned,
            'context' => 'comment',
            'protection_level' => 'standard',
            'modifications' => $input !== $cleaned ? [['type' => 'html_filtering', 'description' => '過濾不安全的 HTML 元素']] : [],
            'warnings' => [],
            'security_score' => $this->calculateSecurityScore($input, $cleaned),
        ];
    }

    /**
     * 搜尋查詢防護.
     */
    private function protectSearchQuery(string $input, array $options): array
    {
        // 搜尋查詢完全不允許 HTML
        $cleaned = $this->baseXssProtection->cleanStrict($input);

        // 移除特殊字元
        $cleaned = preg_replace('/[<>"\']/', '', $cleaned);

        // 長度限制
        if (strlen($cleaned) > $this->config['max_search_length']) {
            $cleaned = mb_substr($cleaned, 0, $this->config['max_search_length']);
        }

        return [
            'protected_content' => $cleaned,
            'context' => 'search_query',
            'protection_level' => 'maximum',
            'modifications' => $input !== $cleaned ? [['type' => 'search_sanitization', 'description' => '搜尋查詢已清理']] : [],
            'warnings' => [],
            'security_score' => 100,
        ];
    }

    /**
     * URL 參數防護.
     */
    private function protectUrlParameter(string $input, array $options): array
    {
        $cleaned = $this->baseXssProtection->cleanForUrl($input);

        return [
            'protected_content' => $cleaned,
            'context' => 'url_parameter',
            'protection_level' => 'maximum',
            'modifications' => $input !== $cleaned ? [['type' => 'url_encoding', 'description' => 'URL 參數已編碼']] : [],
            'warnings' => [],
            'security_score' => 100,
        ];
    }

    /**
     * JSON 資料防護.
     */
    private function protectJsonData(string $input, array $options): array
    {
        // 嘗試解析 JSON
        $decoded = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'protected_content' => '',
                'context' => 'json_data',
                'protection_level' => 'blocked',
                'modifications' => [['type' => 'invalid_json', 'description' => '無效的 JSON 格式']],
                'warnings' => [['type' => 'json_error', 'message' => 'JSON 解析錯誤']],
                'security_score' => 0,
            ];
        }

        // 遞迴清理 JSON 資料
        $cleaned = $this->cleanJsonRecursively($decoded);

        return [
            'protected_content' => json_encode($cleaned, JSON_UNESCAPED_UNICODE),
            'context' => 'json_data',
            'protection_level' => 'enhanced',
            'modifications' => $decoded !== $cleaned ? [['type' => 'json_sanitization', 'description' => 'JSON 資料已清理']] : [],
            'warnings' => [],
            'security_score' => $this->calculateSecurityScore($input, json_encode($cleaned)),
        ];
    }

    /**
     * 檔案上傳防護.
     */
    private function protectFileUpload(string $input, array $options): array
    {
        $filename = $options['filename'] ?? 'unknown';
        $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // 檢查檔案擴展名
        if (!in_array($fileExtension, $this->config['allowed_file_extensions'], true)) {
            return [
                'protected_content' => '',
                'context' => 'file_upload',
                'protection_level' => 'blocked',
                'modifications' => [['type' => 'file_type_blocked', 'description' => '不允許的檔案類型']],
                'warnings' => [['type' => 'file_security', 'message' => "檔案類型 {$fileExtension} 不被允許"]],
                'security_score' => 0,
            ];
        }

        // 清理檔案名稱
        $cleanFilename = $this->cleanFilename($filename);

        return [
            'protected_content' => $cleanFilename,
            'context' => 'file_upload',
            'protection_level' => 'standard',
            'modifications' => $filename !== $cleanFilename ? [['type' => 'filename_sanitization', 'description' => '檔案名稱已清理']] : [],
            'warnings' => [],
            'security_score' => 90,
        ];
    }

    /**
     * 通用防護.
     */
    private function protectGeneric(string $input, array $options): array
    {
        $cleaned = $this->baseXssProtection->clean($input);

        return [
            'protected_content' => $cleaned,
            'context' => 'generic',
            'protection_level' => 'standard',
            'modifications' => $input !== $cleaned ? [['type' => 'basic_sanitization', 'description' => '基本清理']] : [],
            'warnings' => [],
            'security_score' => $this->calculateSecurityScore($input, $cleaned),
        ];
    }

    /**
     * 遞迴清理 JSON 資料.
     */
    private function cleanJsonRecursively($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'cleanJsonRecursively'], $data);
        }

        if (is_string($data)) {
            return $this->baseXssProtection->clean($data);
        }

        return $data;
    }

    /**
     * 清理檔案名稱.
     */
    private function cleanFilename(string $filename): string
    {
        // 移除危險字元
        $cleaned = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $filename);

        // 防止目錄遍歷
        $cleaned = str_replace(['../', '..\\', '../'], '', $cleaned);

        // 確保檔名不為空
        if (empty($cleaned)) {
            $cleaned = 'file_' . time();
        }

        return $cleaned;
    }

    /**
     * 計算安全分數.
     */
    private function calculateSecurityScore(string $original, string $filtered): int
    {
        if ($original === $filtered) {
            return 100;
        }

        $originalLength = strlen($original);
        $filteredLength = strlen($filtered);

        if ($originalLength === 0) {
            return 100;
        }

        $reductionRatio = ($originalLength - $filteredLength) / $originalLength;

        // 減少量越大，代表過濾掉越多可能有問題的內容
        if ($reductionRatio > 0.5) {
            return 30; // 大量內容被過濾，安全但可能有問題
        } elseif ($reductionRatio > 0.2) {
            return 60; // 中等過濾
        } elseif ($reductionRatio > 0.05) {
            return 80; // 少量過濾
        } else {
            return 95; // 幾乎沒有變化
        }
    }

    /**
     * 預設設定.
     */
    private function getDefaultConfig(): array
    {
        return [
            'max_title_length' => 200,
            'max_comment_length' => 1000,
            'max_search_length' => 100,
            'allowed_file_extensions' => [
                'jpg',
                'jpeg',
                'png',
                'gif',
                'pdf',
                'doc',
                'docx',
                'txt',
                'zip',
            ],
        ];
    }
}
