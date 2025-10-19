<?php

declare(strict_types=1);

namespace App\Domains\Post\Services;

use App\Domains\Security\Enums\ActivitySeverity;
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
        $defaultConfig = $this->getDefaultConfig();
        $this->config = is_array($defaultConfig) ? array_merge($defaultConfig, $config) : $config;
    }

    /**
     * 審核內容.
     * @return array{status: string, confidence: int, issues: array<mixed>, recommendations: array<string>, requires_human_review: bool, auto_actions: array<string>}
     */
    public function moderateContent(string $content, array $metadata = []): array
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
            $result['issues'] = array_merge($result['issues'], $securityIssues);
            $result['status'] = 'rejected';
            $result['confidence'] = 0;

            return $result;
        }

        // 2. 內容品質檢查
        $qualityIssues = $this->checkQuality($content, $metadata);
        if (!empty($qualityIssues)) {
            $result['issues'] = array_merge($result['issues'], $qualityIssues);
        }

        // 3. 敏感詞檢查
        $sensitiveWordIssues = $this->checkSensitiveWords($content);
        if (!empty($sensitiveWordIssues)) {
            $result['issues'] = array_merge($result['issues'], $sensitiveWordIssues);
        }

        // 4. 垃圾內容檢查
        $spamScore = $this->calculateSpamScore($content, $metadata);
        if ($spamScore > $this->config['spam_threshold']) {
            $result['issues'][] = [
                'type' => 'spam_detected',
                'severity' => ActivitySeverity::HIGH,
                'message' => '內容可能為垃圾訊息',
                'score' => $spamScore,
            ];
        }

        // 5. 決定最終狀態
        $this->determineFinalStatus($result);

        /** @var array{status: string, confidence: int, issues: array<mixed>, recommendations: array<string>, requires_human_review: bool, auto_actions: array<string>} */
        return $result;
    }

    /**
     * 安全檢查.
     * @return array<mixed>
     */
    private function checkSecurity(string $content): array
    {
        $issues = [];

        // XSS 檢查
        $hasXss = $this->xssProtection->detectXss($content);
        if ($hasXss) {
            $issues[] = [
                'type' => 'security_xss',
                'severity' => ActivitySeverity::CRITICAL,
                'message' => '偵測到潛在 XSS 攻擊模式',
                'details' => 'Content contains potentially dangerous XSS patterns',
            ];
        }

        // 富文本安全檢查
        $richTextIssues = $this->richTextProcessor->validateSecurity($content);
        if (is_array($richTextIssues)) {
            foreach ($richTextIssues as $issue) {
                if (is_array($issue)) {
                    $issues[] = [
                        'type' => 'security_richtext',
                        'severity' => ActivitySeverity::HIGH,
                        'message' => isset($issue['message']) && is_string($issue['message']) ? $issue['message'] : 'Security issue detected',
                        'details' => isset($issue['details']) && is_string($issue['details']) ? $issue['details'] : '',
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * 品質檢查.
     * @return array<mixed>
     */
    private function checkQuality(string $content, array $metadata): array
    {
        $issues = [];
        $textContent = strip_tags($content);

        // 長度檢查
        if (strlen($textContent) < $this->config['min_content_length']) {
            $issues[] = [
                'type' => 'quality_too_short',
                'severity' => ActivitySeverity::MEDIUM,
                'message' => '內容過短',
                'current_length' => strlen($textContent),
                'min_required' => $this->config['min_content_length'],
            ];
        }

        if (strlen($textContent) > $this->config['max_content_length']) {
            $issues[] = [
                'type' => 'quality_too_long',
                'severity' => ActivitySeverity::MEDIUM,
                'message' => '內容過長',
                'current_length' => strlen($textContent),
                'max_allowed' => $this->config['max_content_length'],
            ];
        }

        // 重複內容檢查
        if ($this->isRepetitiveContent($textContent)) {
            $issues[] = [
                'type' => 'quality_repetitive',
                'severity' => ActivitySeverity::MEDIUM,
                'message' => '內容過度重複',
            ];
        }

        // 全大寫檢查
        if ($this->isAllCaps($textContent)) {
            $issues[] = [
                'type' => 'quality_all_caps',
                'severity' => ActivitySeverity::LOW,
                'message' => '內容全為大寫字母',
            ];
        }

        return $issues;
    }

    /**
     * 敏感詞檢查.
     * @return array<mixed>
     */
    private function checkSensitiveWords(string $content): array
    {
        $issues = [];
        $textContent = strtolower(strip_tags($content));

        if (!is_array($this->config['sensitive_words'])) {
            return $issues;
        }

        foreach ($this->config['sensitive_words'] as $category => $words) {
            if (!is_array($words) || !is_string($category)) {
                continue;
            }

            foreach ($words as $word) {
                if (!is_string($word)) {
                    continue;
                }

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
        $wordsArray = str_word_count($textContent, 1);
        $wordCount = is_array($wordsArray) ? count($wordsArray) : 0;
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
        $issues = isset($result['issues']) && is_array($result['issues']) ? $result['issues'] : [];

        $criticalIssues = array_filter($issues, fn($issue) =>
            is_array($issue) && isset($issue['severity']) && $issue['severity'] === ActivitySeverity::CRITICAL
        );
        $highIssues = array_filter($issues, fn($issue) =>
            is_array($issue) && isset($issue['severity']) && $issue['severity'] === ActivitySeverity::HIGH
        );
        $mediumIssues = array_filter($issues, fn($issue) =>
            is_array($issue) && isset($issue['severity']) && $issue['severity'] === ActivitySeverity::MEDIUM
        );

        if (count($criticalIssues) > 0) {
            $result['status'] = 'rejected';
            $result['confidence'] = 0;
            if (!isset($result['auto_actions'])) {
                $result['auto_actions'] = [];
            }
            if (is_array($result['auto_actions'])) {
                $result['auto_actions'][] = 'content_blocked';
            }
        } elseif (count($highIssues) >= 2 || count($mediumIssues) >= 3) {
            $result['status'] = 'pending';
            $result['requires_human_review'] = true;
            $result['confidence'] = 30;
            if (!isset($result['auto_actions'])) {
                $result['auto_actions'] = [];
            }
            if (is_array($result['auto_actions'])) {
                $result['auto_actions'][] = 'flag_for_review';
            }
        } elseif (count($highIssues) > 0 || count($mediumIssues) > 0) {
            $result['status'] = 'conditional';
            $result['confidence'] = 70;
            if (!isset($result['recommendations'])) {
                $result['recommendations'] = [];
            }
            if (is_array($result['recommendations'])) {
                $result['recommendations'][] = '建議作者檢查並修正標記的問題';
            }
        }

        // 根據問題數量調整信心度
        $totalIssues = count($issues);
        if ($totalIssues > 0 && isset($result['status']) && is_string($result['status']) && $result['status'] === 'approved') {
            $result['confidence'] = max(50, 100 - ($totalIssues * 10));
        }
    }

    /**
     * 檢查是否為重複內容.
     */
    private function isRepetitiveContent(string $text): bool
    {
        $splitResult = preg_split('/[.!?]+/', $text);
        $sentences = is_array($splitResult) ? $splitResult : [];
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
        $cleanText = preg_replace('/[^a-zA-Z]/', '', $text);
        $alphaChars = is_string($cleanText) ? $cleanText : '';

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
        $cleanText = preg_replace('/[^a-zA-Z]/', '', $text);
        $alphaChars = is_string($cleanText) ? $cleanText : '';

        if (strlen($alphaChars) === 0) {
            return 0;
        }

        $upperText = preg_replace('/[^A-Z]/', '', $alphaChars);
        $upperChars = is_string($upperText) ? $upperText : '';

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
    private function getSensitiveWordSeverity(string $category): ActivitySeverity
    {
        $severityMap = [
            'profanity' => ActivitySeverity::HIGH,
            'violence' => ActivitySeverity::HIGH,
            'hate_speech' => ActivitySeverity::CRITICAL,
            'adult_content' => ActivitySeverity::HIGH,
            'illegal' => ActivitySeverity::CRITICAL,
            'spam' => ActivitySeverity::MEDIUM,
            'political' => ActivitySeverity::MEDIUM,
        ];

        return $severityMap[$category] ?? ActivitySeverity::MEDIUM;
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
