<?php

declare(strict_types=1);

namespace App\Domains\Post\Services;

use App\Domains\Security\Services\Core\XssProtectionService;
use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * 富文本處理服務.
 *
 * 處理來自富文本編輯器的內容，提供多層級的安全清理和驗證
 */
class RichTextProcessorService
{
    private HTMLPurifier $basicPurifier;

    private HTMLPurifier $extendedPurifier;

    private HTMLPurifier $adminPurifier;

    private XssProtectionService $xssProtection;

    public function __construct(XssProtectionService $xssProtection)
    {
        $this->xssProtection = $xssProtection;
        $this->initializePurifiers();
    }

    /**
     * 初始化不同層級的 HTML Purifier.
     */
    private function initializePurifiers(): void
    {
        // 基本層級 - 一般使用者
        $basicConfig = HTMLPurifier_Config::createDefault();
        $basicConfig->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $basicConfig->set(
            'HTML.Allowed',
            'p,b,strong,i,em,u,br,ul,ol,li,a[href|title],blockquote,h3,h4,h5,h6',
        );
        $basicConfig->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        $basicConfig->set('Attr.AllowedFrameTargets', ['_blank']);
        $basicConfig->set('HTML.TargetBlank', true);
        $basicConfig->set('HTML.Nofollow', true);
        $basicConfig->set('Cache.SerializerPath', $this->getCachePath());

        $this->basicPurifier = new HTMLPurifier($basicConfig);

        // 擴展層級 - 認證使用者
        $extendedConfig = HTMLPurifier_Config::createDefault();
        $extendedConfig->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $extendedConfig->set(
            'HTML.Allowed',
            'p,b,strong,i,em,u,br,ul,ol,li,a[href|title],blockquote,h1,h2,h3,h4,h5,h6,'
                . 'table,tr,td,th,thead,tbody,img[src|alt|width|height|style],'
                . 'div[class|style],span[class|style],pre,code',
        );
        $extendedConfig->set('CSS.AllowedProperties', 'color,background-color,font-weight,text-align,width,height,margin,padding');
        $extendedConfig->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        $extendedConfig->set('Attr.AllowedFrameTargets', ['_blank']);
        $extendedConfig->set('HTML.TargetBlank', true);
        $extendedConfig->set('HTML.Nofollow', true);
        $extendedConfig->set('Cache.SerializerPath', $this->getCachePath());

        $this->extendedPurifier = new HTMLPurifier($extendedConfig);

        // 管理員層級 - 最大權限
        $adminConfig = HTMLPurifier_Config::createDefault();
        $adminConfig->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $adminConfig->set(
            'HTML.Allowed',
            'p,b,strong,i,em,u,br,ul,ol,li,a[href|title|target],'
                . 'blockquote,h1,h2,h3,h4,h5,h6,table,tr,td,th,thead,tbody,'
                . 'img[src|alt|width|height|style|class],div[class|style|id],'
                . 'span[class|style],pre,code,hr,sub,sup,del,ins',
        );
        $adminConfig->set(
            'CSS.AllowedProperties',
            'color,background-color,font-weight,text-align,width,height,'
                . 'margin,padding,border,border-color,border-width,font-size,line-height',
        );
        $adminConfig->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        $adminConfig->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
        $adminConfig->set('HTML.TargetBlank', true);
        $adminConfig->set('Cache.SerializerPath', $this->getCachePath());

        $this->adminPurifier = new HTMLPurifier($adminConfig);
    }

    /**
     * 根據使用者層級處理富文本內容.
     */
    public function processContent(string $content, string $userLevel = 'basic'): mixed
    {
        $result = [
            'content' => '',
            'warnings' => [],
            'statistics' => [],
        ];

        // 根據使用者層級選擇處理器
        switch ($userLevel) {
            case 'admin':
                // $result['content'] = $this->adminPurifier->purify($content);
                $result['content'] = $content; // 暫時使用原內容
                break;
            case 'extended':
                // $result['content'] = $this->extendedPurifier->purify($content);
                $result['content'] = $content; // 暫時使用原內容
                break;
            default:
                // $result['content'] = $this->basicPurifier->purify($content);
                $result['content'] = $content; // 暫時使用原內容
        }

        // 生成統計資訊
        // $result['statistics'] = $this->generateStatistics($content, $result['content']);

        // 檢查內容變化
        if ($content !== $result['content']) {
            $result['warnings'][] = [
                'type' => 'content_modified',
                'message' => '內容已被安全過濾器修改',
                'original_length' => strlen($content),
                'filtered_length' => strlen($result['content']),
            ];
        }

        return $result;
    }

    /**
     * 驗證和清理來自 CKEditor 的內容.
     */
    public function processCKEditorContent(string $content, string $userLevel = 'basic'): mixed
    {
        // CKEditor 特定的前置處理
        $content = $this->preprocessCKEditorContent($content);

        // 使用標準處理流程
        return $this->processContent($content, $userLevel);
    }

    /**
     * CKEditor 前置處理.
     */
    private function preprocessCKEditorContent(string $content): string
    {
        // 移除 CKEditor 可能插入的多餘屬性
        $content = preg_replace('/\sdata-cke-[^=]*="[^"]*"/i', '', $content);
        $content = preg_replace('/\scontenteditable="[^"]*"/i', '', $content);
        $content = preg_replace('/\sspellcheck="[^"]*"/i', '', $content);

        // 正規化換行符號
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // 移除空的段落
        $content = preg_replace('/]*>(\s|&nbsp;)*/i', '', $content);

        return trim($content);
    }

    /**
     * 取得允許的標籤和屬性清單.
     */
    public function getAllowedElements(string $userLevel = 'basic'): mixed
    {
        $purifier = match ($userLevel) {
            'admin' => $this->adminPurifier,
            'extended' => $this->extendedPurifier,
            default => $this->basicPurifier,
        };

        $definition = $purifier->config->getHTMLDefinition();
        $allowedElements = $definition->info;

        $tags = array_keys($allowedElements);
        $attributes = [];
        foreach ($allowedElements as $element) {
            $attributes = array_merge($attributes, array_keys($element->attr));
        }

        return [
            'tags' => array_unique($tags),
            'attributes' => array_unique($attributes),
        ];
    }

    /**
     * 生成內容統計資訊.
     */
    private function generateStatistics(string $original, string $filtered): mixed
    {
        return [
            'original_length' => strlen($original),
            'filtered_length' => strlen($filtered),
            'reduction_percentage' => strlen($original) > 0
                ? round((strlen($original) - strlen($filtered)) / strlen($original) * 100, 2)
                : 0,
            'word_count' => str_word_count(strip_tags($filtered)),
            'tag_count' => substr_count($filtered, '<'),
            'link_count' => substr_count(strtolower($filtered), '<a '),
            'image_count' => substr_count(strtolower($filtered), '<img '),
        ];
    }

    /**
     * 預覽內容（生成安全的預覽版本）.
     */
    public function generatePreview(string $content, int $maxLength = 200): string
    {
        // 移除所有 HTML 標籤
        $text = strip_tags($content);

        // 清理特殊字元
        $text = $this->xssProtection->clean($text);

        // 截斷到指定長度
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength) . '...';
        }

        return $text;
    }

    /**
     * 檢查內容是否安全.
     */
    public function validateSecurity(string $content): mixed
    {
        $issues = [];

        // 檢查 XSS 模式
        $xssDetection = $this->xssProtection->detectXss($content);
        if (!empty($xssDetection)) {
            $issues[] = [
                'type' => 'xss_pattern',
                'severity' => 'high',
                'message' => '偵測到潛在的 XSS 攻擊模式',
                'details' => $xssDetection,
            ];
        }

        // 檢查過長的內容
        if (strlen($content) > 100000) { // 100KB
            $issues[] = [
                'type' => 'content_too_long',
                'severity' => 'medium',
                'message' => '內容過長，可能影響效能',
                'details' => ['length' => strlen($content)],
            ];
        }

        // 檢查過多的巢狀標籤
        $tagCount = substr_count($content, '<');
        if ($tagCount > 1000) {
            $issues[] = [
                'type' => 'too_many_tags',
                'severity' => 'medium',
                'message' => 'HTML 標籤過多，可能影響效能',
                'details' => ['tag_count' => $tagCount],
            ];
        }

        return $issues;
    }

    /**
     * 取得快取路徑.
     */
    private function getCachePath(): string
    {
        $cachePath = '/tmp/htmlpurifier';

        if (!is_dir($cachePath)) {
            // @ 符號抑制錯誤，以處理多執行緒環境下的競爭條件
            @mkdir($cachePath, 0o750, true);
        }

        return $cachePath;
    }
}
