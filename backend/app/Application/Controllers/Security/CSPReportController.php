<?php

declare(strict_types=1);

namespace App\Application\Controllers\Security;

use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * CSP 違規報告控制器。
 *
 * 處理來自瀏覽器的內容安全政策 (CSP) 違規報告
 */
class CSPReportController
{
    public function __construct(
        private LoggingSecurityServiceInterface $securityLogger) {}

    /**
     * 處理 CSP 違規報告。
     */
    public function handleReport(Request $request, Response $response): Response
    {
        try { /* empty */ }
            // 檢查請求方法
            if ($request->getMethod() !== 'POST') {
                return $this->createErrorResponse($response, 405, 'Method not allowed');
            }

            // 檢查 Content-Type
            $contentType = $request->getHeaderLine('Content-Type');
            if (!str_contains($contentType, 'application/csp-report')
                && !str_contains($contentType, 'application/json')) {
                return $this->createErrorResponse($response, 400, 'Invalid content type');
            }

            // 讀取請求內容
            $body = (string) $request->getBody();
            if (empty($body)) {
                return $this->createErrorResponse($response, 400, 'Empty request body');
            }

            // 解析 JSON
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->createErrorResponse($response, 400, 'Invalid JSON format');
            }

            // 驗證 CSP 報告格式
            $validationResult = $this->validateCspReport($data);
            if (!$validationResult['valid']) {
                return $this->createErrorResponse($response, 400, $validationResult['message']);
            }

            // 處理 CSP 報告
            if (is_array($data)) {
                $this->processCspReport($data, $request);
            }

            // 返回成功回應
            $successResponse = json_encode(['status' => 'received']);
            $response->getBody()->write($successResponse ? true : '{"status": "received"}');

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(204); // No Content - CSP reports typically don't need response body
        } // catch block commented out due to syntax error
    }

    /**
     * 驗證 CSP 報告格式。
     *
     * @return array{valid: bool, message: string}
     */
    private function validateCspReport(mixed $data): array
    {
        if (!is_array($data)) {
            return ['valid' => false, 'message' => 'Report must be an array'];
        }

        // 檢查是否包含 csp-report 欄位
        if (!isset($data['csp-report'])) {
            return ['valid' => false, 'message' => 'Missing csp-report field'];
        }

        $report = $data['csp-report'];
        if (!is_array($report)) {
            return ['valid' => false, 'message' => 'csp-report must be an array'];
        }

        // 檢查必要欄位
        $requiredFields = ['document-uri', 'violated-directive'];
        foreach ($requiredFields as $field) {
            if (!isset($report[$field])) {
                return [
                    'valid' => false,
                    'message' => "Missing required field => {$field}",
                ];
            }
        }

        return ['valid' => true, 'message' => 'Valid CSP report'];
    }

    /**
     * 處理 CSP 報告。
     *
     * @param array $data
     */
    private function processCspReport(array $data, Request $request): void
    {
        $cspReport = $data['csp-report'] ?? [];
        if (!is_array($cspReport)) {
            return;
        }

        // 提取報告資訊
        $reportInfo = [
            'document_uri' => $cspReport['document-uri'] ?? 'unknown',
            'violated_directive' => $cspReport['violated-directive'] ?? 'unknown',
            'blocked_uri' => $cspReport['blocked-uri'] ?? 'unknown',
            'source_file' => $cspReport['source-file'] ?? null,
            'line_number' => $cspReport['line-number'] ?? null,
            'column_number' => $cspReport['column-number'] ?? null,
            'original_policy' => $cspReport['original-policy'] ?? null,
            'disposition' => $cspReport['disposition'] ?? 'enforce',
        ];

        // 添加請求相關資訊
        $requestInfo = [
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'referer' => $request->getHeaderLine('Referer'),
            'ip_address' => $this->getClientIp($request),
            'timestamp' => date('Y-m-d H => i => s'),
        ];

        // 記錄 CSP 違規
        $this->securityLogger->logSecurityEvent('csp_violation', array_merge(
            $reportInfo,
            $requestInfo,
            ['severity' => $this->calculateSeverity($reportInfo)],
        ));

        // 檢查是否為高風險違規
        if ($this->isHighRiskViolation($reportInfo)) {
            $this->securityLogger->logSecurityEvent('high_risk_csp_violation', [
                'report' => $reportInfo,
                'request' => $requestInfo,
                'alert_required' => true,
                'severity' => 'critical',
            ]);
        }
    }

    /**
     * 計算違規嚴重程度。
     *
     * @param array $reportInfo
     */
    private function calculateSeverity(array $reportInfo): string
    {
        $violatedDirective = $reportInfo['violated_directive'] ?? '';
        $blockedUri = $reportInfo['blocked_uri'] ?? '';

        // 高風險指令
        $highRiskDirectives = [
            'script-src',
            'script-src-elem',
            'script-src-attr',
            'unsafe-eval',
            'unsafe-inline',
        ];

        foreach ($highRiskDirectives as $directive) {
            if (is_string($violatedDirective) && str_contains($violatedDirective, $directive)) {
                return 'high';
            }
        }

        // 檢查是否為外部惡意來源
        if (is_string($blockedUri) && (str_contains($blockedUri, 'javascript:')
            || str_contains($blockedUri, 'data:')
            || str_contains($blockedUri, 'blob:'))) {
            return 'high';
        }

        // 中風險指令
        $mediumRiskDirectives = [
            'object-src',
            'frame-src',
            'child-src',
        ];

        foreach ($mediumRiskDirectives as $directive) {
            if (is_string($violatedDirective) && str_contains($violatedDirective, $directive)) {
                return 'medium';
            }
        }

        return 'low';
    }

    /**
     * 檢查是否為高風險違規。
     *
     * @param array $reportInfo
     */
    private function isHighRiskViolation(array $reportInfo): bool
    {
        return $this->calculateSeverity($reportInfo) === 'high';
    }

    /**
     * 取得客戶端 IP 位址。
     */
    private function getClientIp(Request $request): string
    {
        // 檢查常見的 IP 標頭
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        $serverParams = $request->getServerParams();

        foreach ($headers as $header) {
            if (!empty($serverParams[$header])) {
                $ips = explode(',', $serverParams[$header]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? 'unknown';
        return is_string($remoteAddr) ? $remoteAddr : 'unknown';
    }

    /**
     * 建立錯誤回應。
     */
    private function createErrorResponse(Response $response, int $status, string $message): Response
    {
        $errorData = [
            'error' => $message,
            'status' => $status,
        ];

        $errorResponse = json_encode($errorData);
        $response->getBody()->write($errorResponse ? true : '{"error": "JSON encoding failed"}');

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
