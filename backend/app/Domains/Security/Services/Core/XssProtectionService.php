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

/**
 * XSS 防護服務實作。
 */
final class XssProtectionService implements XssProtectionServiceInterface
{
    private HTMLPurifier $purifier;

    private HTMLPurifier $strictPurifier;

    public function __construct(private ActivityLoggingServiceInterface $activityLogger)
    {
        $this->initializePurifiers();
    }

    public function sanitize(string $input): string
    {
        return $this->clean($input);
    }

    public function sanitizeArray(array $input): array
    {
        return $this->cleanArrayRecursive($input);
    }

    /**
     * @param array<mixed, mixed> $input
     * @param array<int, string>|array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function cleanArray(array $input, array $fields): array
    {
        $fieldsToClean = array_is_list($fields) ? $fields : array_keys($fields);

        /** @var array<string, mixed> $result */
        $result = [];
        foreach ($input as $k => $v) {
            $key = (string) $k;
            if (in_array($key, $fieldsToClean, true) && is_string($v)) {
                $result[$key] = $this->clean($v);
            } else {
                $result[$key] = $v;
            }
        }

        return $result;
    }

    public function clean(string $input): string
    {
        if ($input === '') {
            return $input;
        }

        $cleaned = $this->purifier->purify($input);

        if ($cleaned !== $input) {
            $this->logXssAttempt($input, $cleaned);
        }

        return $cleaned;
    }

    /**
     * Recursively clean array values.
     *
     * @param array<mixed, mixed> $data
     * @return array<string, mixed>
     */
    public function cleanArrayRecursive(array $data): array
    {
        $isList = array_keys($data) === range(0, count($data) - 1);

        if ($isList) {
            $new = [];
            foreach ($data as $i => $v) {
                $new[(string) $i] = $v;
            }
            $data = $new;
        }

        /** @var array<string, mixed> $result */
        $result = [];
        foreach ($data as $key => $value) {
            $sKey = (string) $key;
            if (is_string($value)) {
                $result[$sKey] = $this->clean($value);
            } elseif (is_array($value)) {
                $result[$sKey] = $this->cleanArrayRecursive($value);
            } else {
                $result[$sKey] = $value;
            }
        }

        return $result;
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

    private function logXssAttempt(string $originalInput, string $cleanedInput): void
    {
        try {
            $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : null;
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : null;
            $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : null;
            $method = isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : 'unknown';

            $dto = CreateActivityLogDTO::securityEvent(
                ActivityType::XSS_ATTACK_BLOCKED,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                description: 'XSS attack attempt detected and blocked',
                metadata: [
                    'original_length' => strlen($originalInput),
                    'cleaned_length' => strlen($cleanedInput),
                    'original_sample' => substr($originalInput, 0, 100),
                    'referer' => $referer,
                    'method' => $method,
                ],
            );

            $this->activityLogger->log($dto);
        } catch (Exception $e) {
            error_log('Failed to log XSS attempt: ' . $e->getMessage());
        }
    }

    public function strictClean(string $input): string
    {
        if ($input === '') {
            return $input;
        }

        $cleaned = $this->strictPurifier->purify($input);

        if ($cleaned !== $input) {
            $this->logXssAttempt($input, $cleaned);
        }

        return $cleaned;
    }

    public function cleanHtml(string $input): string
    {
        return $this->clean($input);
    }

    public function cleanForUrl(string $input): string
    {
        return $this->strictClean($input);
    }

    public function containsXss(string $input): bool
    {
        if ($input === '') {
            return false;
        }

        $cleaned = $this->purifier->purify($input);

        return $cleaned !== $input;
    }

    public function detectXss(string $input): bool
    {
        return $this->containsXss($input);
    }

    // @phpstan-ignore-next-line
    private function containsDangerousPatterns(string $input): bool
    {
        $patterns = [
            '/<script\\b[^<]*(?:(?!<\\/script>)<[^<]*)*<\\/script>/i',
            '/javascript:/i',
            '/on\\w+\\s*=/i',
            '/<iframe\\b[^>]*>/i',
            '/<object\\b[^>]*>/i',
            '/<embed\\b[^>]*>/i',
            '/<form\\b[^>]*>/i',
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    public function getStats(): array
    {
        return [
            'purifier_version' => HTMLPurifier::VERSION,
            'allowed_tags' => 'p,br,strong,em,u,ol,ul,li,a[href],blockquote,code,pre',
            'forbidden_elements' => 'script,iframe,object,embed,form,input,button',
            'encoding' => 'UTF-8',
        ];
    }

    public function isHealthy(): bool
    {
        try {
            $testInput = '<p>Test</p><script>alert("xss")</script>';
            $cleaned = $this->clean($testInput);

            return str_contains($cleaned, '<p>Test</p>') && !str_contains($cleaned, 'script');
        } catch (Exception) {
            return false;
        }
    }
}
