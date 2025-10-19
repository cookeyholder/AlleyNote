<?php

declare(strict_types=1);

namespace App\Domains\Security\Services\Headers;

use App\Domains\Security\Contracts\SecurityHeaderServiceInterface;
use Exception;

class SecurityHeaderService implements SecurityHeaderServiceInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    private ?string $currentNonce = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * 安全地取得 config 值
     *
     * @param string $key
     * @return mixed
     */
    private function getConfig(string $key): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * 檢查 config 布林值
     */
    private function isConfigEnabled(string $key): bool
    {
        $value = $this->getConfig($key);
        return is_bool($value) && $value;
    }

    /**
     * 取得 config 字串值
     */
    private function getConfigString(string $key, string $default = ''): string
    {
        $value = $this->getConfig($key);
        return is_string($value) ? $value : $default;
    }

    /**
     * 取得 config 整數值
     */
    private function getConfigInt(string $key, int $default = 0): int
    {
        $value = $this->getConfig($key);
        return is_int($value) ? $value : $default;
    }

    public function setSecurityHeaders(): void
    {
        // Content Security Policy
        if ($this->isConfigEnabled('csp.enabled')) {
            header('Content-Security-Policy: ' . $this->buildCSP());
        }

        // Strict Transport Security (僅在 HTTPS 下啟用)
        if ($this->isConfigEnabled('hsts.enabled') && $this->isHTTPS()) {
            $hstsValue = sprintf(
                'max-age=%d%s%s',
                $this->getConfigInt('hsts.max_age', 31536000),
                $this->isConfigEnabled('hsts.include_subdomains') ? '; includeSubDomains' : '',
                $this->isConfigEnabled('hsts.preload') ? '; preload' : '',
            );
            header('Strict-Transport-Security: ' . $hstsValue);
        }

        // X-Frame-Options
        if ($this->isConfigEnabled('frame_options.enabled')) {
            header('X-Frame-Options: ' . $this->getConfigString('frame_options.value', 'SAMEORIGIN'));
        }

        // X-Content-Type-Options
        if ($this->isConfigEnabled('content_type_options.enabled')) {
            header('X-Content-Type-Options: nosniff');
        }

        // X-XSS-Protection (雖然現代瀏覽器已棄用，但為了向後相容)
        if ($this->isConfigEnabled('xss_protection.enabled')) {
            header('X-XSS-Protection: 1; mode=block');
        }

        // Referrer Policy
        if ($this->isConfigEnabled('referrer_policy.enabled')) {
            header('Referrer-Policy: ' . $this->getConfigString('referrer_policy.value', 'strict-origin-when-cross-origin'));
        }

        // Permissions Policy
        if ($this->isConfigEnabled('permissions_policy.enabled')) {
            header('Permissions-Policy: ' . $this->buildPermissionsPolicy());
        }

        // Cross-Origin Embedder Policy
        if ($this->isConfigEnabled('coep.enabled')) {
            header('Cross-Origin-Embedder-Policy: ' . $this->getConfigString('coep.value', 'require-corp'));
        }

        // Cross-Origin Opener Policy
        if ($this->isConfigEnabled('coop.enabled')) {
            header('Cross-Origin-Opener-Policy: ' . $this->getConfigString('coop.value', 'same-origin'));
        }

        // Cross-Origin Resource Policy
        if ($this->isConfigEnabled('corp.enabled')) {
            header('Cross-Origin-Resource-Policy: ' . $this->getConfigString('corp.value', 'same-origin'));
        }

        // Cache Control for sensitive pages
        if ($this->isConfigEnabled('cache_control.enabled')) {
            header('Cache-Control: ' . $this->getConfigString('cache_control.value', 'no-store, no-cache, must-revalidate'));
        }
    }

    /**
     * 產生 CSP nonce 值
     */
    public function generateNonce(): string
    {
        if ($this->currentNonce === null) {
            $this->currentNonce = base64_encode(random_bytes(16));
        }

        return $this->currentNonce;
    }

    /**
     * 取得當前的 nonce 值
     */
    public function getCurrentNonce(): ?string
    {
        return $this->currentNonce;
    }

    /**
     * 建立 CSP 違規報告端點.
     */
    public function handleCSPReport(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($requestMethod !== 'POST') {
            http_response_code(405);

            return;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (
            !is_string($contentType)
            || (strpos($contentType, 'application/csp-report') === false
            && strpos($contentType, 'application/json') === false)
        ) {
            http_response_code(400);

            return;
        }

        $input = file_get_contents('php://input');
        if ($input === false) {
            http_response_code(400);
            return;
        }
        $report = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($report)) {
            http_response_code(400);

            return;
        }

        // 記錄 CSP 違規
        $this->logCSPViolation($report);

        http_response_code(204);
    }

    /**
     * 記錄 CSP 違規.
     */
    private function logCSPViolation(array $report): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'report' => $report,
        ];

        // 記錄到日誌檔案
        error_log('CSP Violation: ' . json_encode($logData));

        // 如果設定了監控服務，也可以發送到那裡
        $monitoringEndpoint = $this->getConfigString('csp.monitoring_endpoint');
        if ($monitoringEndpoint !== '') {
            $this->sendToMonitoring($logData);
        }
    }

    /**
     * 發送到監控服務.
     */
    private function sendToMonitoring(array $data): void
    {
        try {
            $monitoringEndpoint = $this->getConfigString('csp.monitoring_endpoint');
            if ($monitoringEndpoint === '') {
                return;
            }

            $content = file_get_contents($monitoringEndpoint, false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($data),
                    'timeout' => 2,
                ],
            ]));

            // 忽略結果，因為它是非同步的
        } catch (Exception $e) {
            error_log('Failed to send CSP violation to monitoring: ' . $e->getMessage());
        }
    }

    public function removeServerSignature(): void
    {
        // 移除可能洩漏伺服器資訊的標頭
        header_remove('Server');
        header_remove('X-Powered-By');

        // 設定通用的伺服器標識（可選）
        if ($this->isConfigEnabled('server_signature.enabled')) {
            $serverValue = $this->getConfigString('server_signature.value', 'AlleyNote/1.0');
            header('Server: ' . $serverValue);
        }
    }

    private function buildCSP(): string
    {
        $directives = [];
        $nonce = $this->generateNonce();

        $cspDirectives = $this->getConfig('csp.directives');
        if (!is_array($cspDirectives)) {
            $cspDirectives = [];
        }

        foreach ($cspDirectives as $directive => $sources) {
            if (!is_string($directive)) {
                continue;
            }

            if (!empty($sources) && is_array($sources)) {
                // 對於 script-src 和 style-src，添加 nonce 支援
                if (($directive === 'script-src' || $directive === 'style-src') && $nonce) {
                    // 移除 unsafe-inline 並添加 nonce
                    $sources = array_diff($sources, ["'unsafe-inline'"]);
                    $sources[] = "'nonce-{$nonce}'";
                }

                $directives[] = $directive . ' ' . implode(' ', $sources);
            } elseif (empty($sources)) {
                $directives[] = $directive;
            }
        }

        // 添加 CSP 違規報告
        $reportUri = $this->getConfigString('csp.report_uri');
        if ($reportUri !== '') {
            $directives[] = 'report-uri ' . $reportUri;
        }

        return implode('; ', $directives);
    }

    private function buildPermissionsPolicy(): string
    {
        $policies = [];

        $permissionsDirectives = $this->getConfig('permissions_policy.directives');
        if (!is_array($permissionsDirectives)) {
            $permissionsDirectives = [];
        }

        foreach ($permissionsDirectives as $feature => $allowlist) {
            if (!is_string($feature)) {
                continue;
            }

            if (is_array($allowlist)) {
                $policies[] = $feature . '=(' . implode(' ', $allowlist) . ')';
            } elseif (is_string($allowlist)) {
                $policies[] = $feature . '=' . $allowlist;
            }
        }

        return implode(', ', $policies);
    }

    private function isHTTPS(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';
        $serverPort = $_SERVER['SERVER_PORT'] ?? '';
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';

        return (!empty($https) && is_string($https) && $https !== 'off')
            || (is_string($serverPort) && $serverPort == '443')
            || (is_int($serverPort) && $serverPort === 443)
            || (!empty($forwardedProto) && is_string($forwardedProto) && $forwardedProto === 'https');
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultConfig(): array
    {
        return [
            'csp' => [
                'enabled' => true,
                'report_uri' => '/api/csp-report', // CSP 違規報告端點
                'monitoring_endpoint' => null, // 可設定外部監控服務端點
                'directives' => [
                    'default-src' => ["'self'"],
                    'script-src' => ["'self'"], // 移除 unsafe-inline，使用 nonce 策略
                    'style-src' => ["'self'"], // 移除 unsafe-inline，使用 nonce 策略
                    'img-src' => ["'self'", 'data:', 'https:'],
                    'font-src' => ["'self'"],
                    'connect-src' => ["'self'"],
                    'media-src' => ["'self'"],
                    'object-src' => ["'none'"],
                    'child-src' => ["'self'"],
                    'frame-ancestors' => ["'none'"],
                    'form-action' => ["'self'"],
                    'base-uri' => ["'self'"],
                    'upgrade-insecure-requests' => [],
                ],
            ],
            'hsts' => [
                'enabled' => true,
                'max_age' => 31536000, // 1 year
                'include_subdomains' => true,
                'preload' => false,
            ],
            'frame_options' => [
                'enabled' => true,
                'value' => 'DENY',
            ],
            'content_type_options' => [
                'enabled' => true,
            ],
            'xss_protection' => [
                'enabled' => true,
            ],
            'referrer_policy' => [
                'enabled' => true,
                'value' => 'strict-origin-when-cross-origin',
            ],
            'permissions_policy' => [
                'enabled' => true,
                'directives' => [
                    'geolocation' => '()',
                    'microphone' => '()',
                    'camera' => '()',
                    'magnetometer' => '()',
                    'gyroscope' => '()',
                    'fullscreen' => '(self)',
                    'payment' => '()',
                ],
            ],
            'coep' => [
                'enabled' => false,
                'value' => 'require-corp',
            ],
            'coop' => [
                'enabled' => true,
                'value' => 'same-origin',
            ],
            'corp' => [
                'enabled' => true,
                'value' => 'same-origin',
            ],
            'cache_control' => [
                'enabled' => true,
                'value' => 'no-cache, no-store, must-revalidate',
            ],
            'server_signature' => [
                'enabled' => false,
                'value' => 'AlleyNote/1.0',
            ],
        ];
    }
}
