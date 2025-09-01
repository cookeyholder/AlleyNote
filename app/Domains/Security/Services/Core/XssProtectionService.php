<?php

declare(strict_types=1);

namespace App\Domains\Security\Services\Core;

use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\XssProtectionServiceInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityType;
use Exception;
use HTMLPurifier;
use HTMLPurifier_Config;

class XssProtectionService implements XssProtectionServiceInterface
{
    private HTMLPurifier $purifier;

    private HTMLPurifier $strictPurifier;

    private ActivityLoggingServiceInterface $activityLogger;

    public function __construct(ActivityLoggingServiceInterface $activityLogger)
    {
        $this->activityLogger = $activityLogger;
        $this->initializePurifiers();
    }

    public function clean(string $input): string
    {
        if (empty($input)) {
            return $input;
        }

        $cleaned = $this->purifier->purify($input);

        if ($cleaned !== $input) {
            $this->logXssAttempt($input, $cleaned);
        }

        return $cleaned;
    }

    public function strictClean(string $input): string
    {
        if (empty($input)) {
            return $input;
        }

        $cleaned = $this->strictPurifier->purify($input);

        if ($cleaned !== $input) {
            $this->logXssAttempt($input, $cleaned);
        }

        return $cleaned;
    }

    public function cleanArray(array $data, array $keys = []): array
    {
        if (empty($keys)) {
            return $this->cleanArrayRecursive($data);
        }

        // 支援兩種格式：索引陣列 ['title', 'content'] 或關聯陣列 ['title' => null, 'content' => null]
        $keysToClean = array_keys($keys) === range(0, count($keys) - 1) ? $keys : array_keys($keys);

        foreach ($keysToClean as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = $this->clean($data[$key]);
            }
        }

        return $data;
    }

    public function containsXss(string $input): bool
    {
        if (empty($input)) {
            return false;
        }

        $cleaned = $this->purifier->purify($input);

        return $cleaned !== $input;
    }

    public function sanitize(string $input): string
    {
        return $this->clean($input);
    }

    public function sanitizeArray(array $data): array
    {
        return $this->cleanArrayRecursive($data);
    }

    /**
     * 檢測 XSS 攻擊.
     */
    public function detectXss(string $input): bool
    {
        return $this->containsXss($input);
    }

    /**
     * 清理 HTML（別名方法，對應舊的 cleanHtml）.
     */
    public function cleanHtml(string $input): string
    {
        return $this->clean($input);
    }

    /**
     * 清理用於 URL 的字串.
     */
    public function cleanForUrl(string $input): string
    {
        return $this->strictClean($input);
    }

    private function initializePurifiers(): void
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Allowed', 'p,br,strong,em,u,ol,ul,li,a[href],blockquote,code,pre');
        $config->set('HTML.ForbiddenElements', 'script,iframe,object,embed,form,input,button');
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.Linkify', false);
        $this->purifier = new HTMLPurifier($config);

        $strictConfig = HTMLPurifier_Config::createDefault();
        $strictConfig->set('Core.Encoding', 'UTF-8');
        $strictConfig->set('HTML.Allowed', '');
        $strictConfig->set('AutoFormat.RemoveEmpty', true);
        $this->strictPurifier = new HTMLPurifier($strictConfig);
    }

    private function cleanArrayRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->clean($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->cleanArrayRecursive($value);
            }
        }

        return $data;
    }

    private function logXssAttempt(string $originalInput, string $cleanedInput): void
    {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            // 確保類型正確
            $ipAddress = is_string($ipAddress) ? $ipAddress : null;
            $userAgent = is_string($userAgent) ? $userAgent : null;

            $dto = CreateActivityLogDTO::securityEvent(
                actionType: ActivityType::XSS_ATTACK_BLOCKED,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                description: 'XSS attack attempt detected and blocked',
                metadata: [
                    'original_length' => strlen($originalInput),
                    'cleaned_length' => strlen($cleanedInput),
                    'original_sample' => substr($originalInput, 0, 100),
                    'referer' => $_SERVER['HTTP_REFERER'] ?? null,
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                ],
            );

            $this->activityLogger->log($dto);
        } catch (Exception) {
            // 記錄失敗不應影響主要功能
        }
    }
}
