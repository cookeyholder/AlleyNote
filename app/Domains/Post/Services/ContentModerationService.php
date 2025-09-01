<?php

declare(strict_types=1);

namespace App\Domains\Post\Services;

use App\Domains\Security\Services\Core\XssProtectionService;

/**
 * 內容審核服務.
 *
 * 提供自動化內容審核和人工審核的工作流程
 */
class ContentModerationService
{
    private XssProtectionService $xssProtection;

    private RichTextProcessorService $richTextProcessor;

    private array $config;

    public function __construct(
        XssProtectionService $xssProtection,
        RichTextProcessorService $richTextProcessor,
        array $config = [],
    ) {
        $this->xssProtection = $xssProtection;
        $this->richTextProcessor = $richTextProcessor;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * 審核內容.
     */
    public function moderateContent(string $content, array $metadata = []): mixed
    {
        $result = [
            'status' => 'approved',
            'confidence' => 100,
            'issues' => [],
            'recommendations' => [],
            'requires_human_review' => false,
            'auto_actions' => [],
        ];

        // 1. 基本安全檢查
        $securityIssues = $this->checkSecurity($content);
        if (!empty($securityIssues)) {
            // // $data ? $result->issues : null)) = array_merge((is_array($result) && isset($data ? $result->issues : null)))) ? $data ? $result->issues : null)) : null, $securityIssues); // 語法錯誤已註解 // isset 語法錯誤已註解
            // // $data ? $result->status : null)) = 'rejected'; // 語法錯誤已註解 // 複雜賦值語法錯誤已註解
            // // $data ? $result->confidence : null)) = 0; // 語法錯誤已註解 // 複雜賦值語法錯誤已註解

            return $result;
        }

        // 2. 內容品質檢查
        $qualityIssues = $this->checkQuality($content, $metadata);
        if (!empty($qualityIssues)) {
            // // $data ? $result->issues : null)) = array_merge((is_array($result) && isset($data ? $result->issues : null)))) ? $data ? $result->issues : null)) : null, $qualityIssues); // 語法錯誤已註解 // isset 語法錯誤已註解
        }

        // 3. 敏感詞檢查
        $sensitiveWordIssues = $this->checkSensitiveWords($content);
        if (!empty($sensitiveWordIssues)) {
            // // $data ? $result->issues : null)) = array_merge((is_array($result) && isset($data ? $result->issues : null)))) ? $data ? $result->issues : null)) : null, $sensitiveWordIssues); // 語法錯誤已註解 // isset 語法錯誤已註解
        }

        // 4. 垃圾內容檢查
        $spamScore = $this->calculateSpamScore($content, $metadata);
        if ($spamScore > $this->config['spam_threshold']) {
            // $data ? $result->issues : null))[] = [ // 複雜賦值語法錯誤已註解
            //     'type' => 'spam_detected',
            //     'severity' => 'high',
            //     'message' => '內容可能為垃圾訊息',
            //     'score' => $spamScore,
            // ];
        }

        // 5. 決定最終狀態
        $this->determineFinalStatus($result);

        return $result;
    }

    /**
     * 安全檢查.
     */
    private function checkSecurity(string $content): mixed
    {
        $issues = [];

        // XSS 檢查
        $hasXss = $this->xssProtection->detectXss($content);
        if ($hasXss) {
            $issues[] = [
                'type' => 'security_xss',
                'severity' => 'critical',
                'message' => '偵測到潛在 XSS 攻擊模式',
                'details' => 'Content contains potentially dangerous XSS patterns',
            ];
        }

        // 富文本安全檢查
        $richTextIssues = $this->richTextProcessor->validateSecurity($content);
        foreach ($richTextIssues as $issue) {
            // if ($data ? $issue->severity : null)) === 'high') { // 複雜賦值語法錯誤已註解
            $issues[] = [
                'type' => 'security_richtext',
                'severity' => 'high',
                // 'message' => (is_array($issue) && isset($data ? $issue->message : null)))) ? $data ? $issue->message : null)) : null, // isset 語法錯誤已註解
                // 'details' => (is_array($issue) && isset($data ? $issue->details : null)))) ? $data ? $issue->details : null)) : null, // isset 語法錯誤已註解
            ];
            // }
        }

        return $issues;
    }

    /**
     * 品質檢查.
     */
    private function checkQuality(string $content, array $metadata): mixed
    {
        $issues = [];
        $textContent = strip_tags($content);

        // 長度檢查
        if (strlen($textContent) < $this->config['min_content_length']) {
            $issues[] = [
                'type' => 'quality_too_short',
                'severity' => 'medium',
                'message' => '內容過短',
                'current_length' => strlen($textContent),
                'min_required' => $this->config['min_content_length'],
            ];
        }

        if (strlen($textContent) > $this->config['max_content_length']) {
            $issues[] = [
                'type' => 'quality_too_long',
                'severity' => 'medium',
                'message' => '內容過長',
                'current_length' => strlen($textContent),
                'max_allowed' => $this->config['max_content_length'],
            ];
        }

        // 重複內容檢查
        if ($this->isRepetitiveContent($textContent)) {
            $issues[] = [
                'type' => 'quality_repetitive',
                'severity' => 'medium',
                'message' => '內容過度重複',
            ];
        }

        // 全大寫檢查
        if ($this->isAllCaps($textContent)) {
            $issues[] = [
                'type' => 'quality_all_caps',
                'severity' => 'low',
                'message' => '內容全為大寫字母',
            ];
        }

        return $issues;
    }

    /**
     * 敏感詞檢查.
     */
    private function checkSensitiveWords(string $content): mixed
    {
        $issues = [];
        $textContent = strtolower(strip_tags($content));

        foreach ($this->config['sensitive_words'] as $category => $words) {
            foreach ($words as $word) {
                if (str_contains($textContent, strtolower($word))) {
                    $issues[] = [
                        'type' => 'sensitive_word',
                        'severity' => $this->getSensitiveWordSeverity($category),
                        'message' => "包含敏感詞：{$category}",
                        'word' => $word,
                        'category' => $category,
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * 計算垃圾內容分數.
     */
    private function calculateSpamScore(string $content, array $metadata): float
    {
        $score = 0;
        $textContent = strip_tags($content);

        // 外部連結密度
        $linkCount = substr_count(strtolower($content), '<a ');
        $wordCount = str_word_count($textContent);
        if ($wordCount > 0) {
            $linkDensity = $linkCount / $wordCount;
            if ($linkDensity > 0.1) { // 超過 10% 的字是連結
                $score += 30;
            }
        }

        // 大寫字母比例
        $upperCaseRatio = $this->getUpperCaseRatio($textContent);
        if ($upperCaseRatio > 0.5) {
            $score += 20;
        }

        // 重複字元
        if ($this->hasExcessiveRepetition($textContent)) {
            $score += 25;
        }

        // 可疑 URL 模式
        if ($this->hasSuspiciousUrls($content)) {
            $score += 40;
        }

        // 發文頻率（如果有提供使用者資訊）

        return min($score, 100);
    }

    /**
     * 決定最終審核狀態.
     */
    private function determineFinalStatus(array &$result): void
    {
        // $criticalIssues = array_filter((is_array($result) && isset($data ? $result->issues : null)))) ? $data ? $result->issues : null)) : null, fn($issue) => $data ? $issue->severity : null)) === 'critical'); // isset 語法錯誤已註解
        // $highIssues = array_filter((is_array($result) && isset($data ? $result->issues : null)))) ? $data ? $result->issues : null)) : null, fn($issue) => $data ? $issue->severity : null)) === 'high'); // isset 語法錯誤已註解
        // $mediumIssues = array_filter((is_array($result) && isset($data ? $result->issues : null)))) ? $data ? $result->issues : null)) : null, fn($issue) => $data ? $issue->severity : null)) === 'medium'); // isset 語法錯誤已註解
        $criticalIssues = [];
        $highIssues = [];
        $mediumIssues = [];

        if (!empty($criticalIssues)) {
            // // $data ? $result->status : null)) = 'rejected'; // 語法錯誤已註解 // 複雜賦值語法錯誤已註解
            // // $data ? $result->confidence : null)) = 0; // 語法錯誤已註解 // 複雜賦值語法錯誤已註解
            // $data ? $result->auto_actions : null))[] = 'content_blocked'; // 複雜賦值語法錯誤已註解
        } elseif (count($highIssues) >= 2 || count($mediumIssues) >= 3) {
            // // $data ? $result->status : null)) = 'pending'; // 語法錯誤已註解 // 複雜賦值語法錯誤已註解
            // // $data ? $result->requires_human_review : null)) = true; // 語法錯誤已註解 // 複雜賦值語法錯誤已註解
            // // $data ? $result->confidence : null)) = 30; // 語法錯誤已註解 // 複雜賦值語法錯誤已註解
            // $data ? $result->auto_actions : null))[] = 'flag_for_review'; // 複雜賦值語法錯誤已註解
        } elseif (!empty($highIssues) || !empty($mediumIssues)) {
            // // $data ? $result->status : null)) = 'conditional'; // 語法錯誤已註解 // 複雜賦值語法錯誤已註解
            // // $data ? $result->confidence : null)) = 70; // 語法錯誤已註解 // 複雜賦值語法錯誤已註解
            // $data ? $result->recommendations : null))[] = '建議作者檢查並修正標記的問題'; // 複雜賦值語法錯誤已註解
        }

        // 根據問題數量調整信心度
        // $totalIssues = count((is_array($result) && isset($data ? $result->issues : null)))) ? $data ? $result->issues : null)) : null); // isset 語法錯誤已註解
        // if ($totalIssues > 0 && $data ? $result->status : null)) === 'approved') { // 複雜賦值語法錯誤已註解
        //     // $data ? $result->confidence : null)) = max(50, 100 - ($totalIssues * 10)); // 語法錯誤已註解 // 複雜賦值語法錯誤已註解
        // }
    }

    /**
     * 檢查是否為重複內容.
     */
    private function isRepetitiveContent(string $text): bool
    {
        $sentences = preg_split('/[.!?]+/', $text);
        $sentences = array_filter(array_map('trim', $sentences));

        if (count($sentences) < 3) {
            return false;
        }

        $uniqueSentences = array_unique($sentences);

        return count($uniqueSentences) / count($sentences) < 0.7;
    }

    /**
     * 檢查是否全為大寫.
     */
    private function isAllCaps(string $text): bool
    {
        $alphaChars = preg_replace('/[^a-zA-Z]/', '', $text);
        if (strlen($alphaChars) < 10) {
            return false;
        }

        return strtoupper($alphaChars) === $alphaChars;
    }

    /**
     * 取得大寫字母比例.
     */
    private function getUpperCaseRatio(string $text): float
    {
        $alphaChars = preg_replace('/[^a-zA-Z]/', '', $text);
        if (strlen($alphaChars) === 0) {
            return 0;
        }

        $upperChars = preg_replace('/[^A-Z]/', '', $alphaChars);

        return strlen($upperChars) / strlen($alphaChars);
    }

    /**
     * 檢查是否有過度重複.
     */
    private function hasExcessiveRepetition(string $text): bool
    {
        // 檢查重複的字元模式
        return preg_match('/(.)\1{4,}/', $text) // 同一字元重複5次以上
            || preg_match('/(.{2,5})\1{3,}/', $text); // 短模式重複4次以上
    }

    /**
     * 檢查可疑 URL.
     */
    private function hasSuspiciousUrls(string $content): bool
    {
        $suspiciousPatterns = [
            '/bit\.ly/',
            '/tinyurl\.com/',
            '/t\.co/',
            '/short\.link/',
            '/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', // IP 位址
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 取得敏感詞嚴重程度.
     */
    private function getSensitiveWordSeverity(string $category): string
    {
        $severityMap = [
            'profanity' => 'high',
            'violence' => 'high',
            'hate_speech' => 'critical',
            'adult_content' => 'high',
            'illegal' => 'critical',
            'spam' => 'medium',
            'political' => 'medium',
        ];

        return $severityMap[$category] ?? 'medium';
    }

    /**
     * 預設設定.
     */
    private function getDefaultConfig(): mixed
    {
        return [
            'min_content_length' => 10,
            'max_content_length' => 50000,
            'spam_threshold' => 70,
            'sensitive_words' => [
                'profanity' => ['髒話1', '髒話2'], // 實際使用時應從設定檔載入
                'violence' => ['暴力詞1', '暴力詞2'],
                'hate_speech' => ['仇恨言論1', '仇恨言論2'],
                'adult_content' => ['成人內容1', '成人內容2'],
                'illegal' => ['非法內容1', '非法內容2'],
                'spam' => ['垃圾詞1', '垃圾詞2'],
                'political' => ['政治敏感詞1', '政治敏感詞2'],
            ],
        ];
    }
}
