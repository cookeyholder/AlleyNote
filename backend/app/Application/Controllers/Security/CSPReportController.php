<?php

declare(strict_types=1);

namespace App\Application\Controllers\Security;

use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Domains\Security\Enums\ActivitySeverity;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CSPReportController
{
    private LoggingSecurityServiceInterface $logger;

    public function __construct(LoggingSecurityServiceInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 處理 CSP 違規報告.
     */
    public function handleReport(Request $request, Response $response): Response
    {
        try {
            // 檢查請求方法
            if ($request->getMethod() !== 'POST') {
                return $response->withStatus(405);
            }

            // 檢查 Content-Type
            $contentType = $request->getHeaderLine('Content-Type');
            if (
                strpos($contentType, 'application/csp-report') === false
                && strpos($contentType, 'application/json') === false
            ) {
                return $response->withStatus(400);
            }

            // 解析報告資料
            $body = $request->getBody()->getContents();
            $report = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($report)) {
                return $response->withStatus(400);
            }

            // 驗證報告格式
            if (!$this->isValidCSPReport($report)) {
                return $response->withStatus(400);
            }

            // 記錄違規
            $this->logViolation($report, $request);

            return $response->withStatus(204);
        } catch (Exception $e) {
            $this->logger->error('CSP Report handling error', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            return $response->withStatus(500);
        }
    }

    /**
     * 驗證 CSP 報告格式.
     */
    private function isValidCSPReport(array $report): bool
    {
        // 檢查必要的欄位
        if (!isset($report['csp-report']) || !is_array($report['csp-report'])) {
            return false;
        }

        $cspReport = $report['csp-report'];

        // 基本欄位檢查
        $requiredFields = ['blocked-uri', 'document-uri', 'violated-directive'];
        foreach ($requiredFields as $field) {
            if (!isset($cspReport[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 記錄 CSP 違規.
     */
    private function logViolation(array $report, Request $request): void
    {
        $cspReport = $report['csp-report'] ?? [];
        if (!is_array($cspReport)) {
            return;
        }

        $logData = [
            'client_ip' => $this->getClientIP($request),
            'user_agent_hash' => hash('sha256', $request->getHeaderLine('User-Agent')),
            'referer' => $request->getHeaderLine('Referer'),
            'csp_report' => $cspReport,
            'severity' => $this->calculateSeverity($cspReport),
        ];

        // 使用安全日誌服務記錄 CSP 違規
        if ($logData['severity'] === ActivitySeverity::HIGH) {
            $this->logger->logCriticalSecurityEvent('CSP Violation (High Severity)', $logData);
        } else {
            $this->logger->logSecurityEvent('CSP Violation', $logData);
        }

        // 檢查是否需要立即警報
        $this->checkForAlert($logData);
    }

    /**
     * 取得客戶端真實 IP.
     */
    private function getClientIP(Request $request): string
    {
        $serverParams = $request->getServerParams();

        // 檢查是否通過代理
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (isset($serverParams[$header]) && is_string($serverParams[$header]) && $serverParams[$header] !== '') {
                $ip = $serverParams[$header];

                // X-Forwarded-For 可能包含多個 IP
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? null;
        return is_string($remoteAddr) ? $remoteAddr : 'unknown';
    }

    /**
     * 計算違規嚴重程度.
     */
    private function calculateSeverity(array $cspReport): ActivitySeverity
    {
        $blockedUriRaw = $cspReport['blocked-uri'] ?? null;
        $violatedDirectiveRaw = $cspReport['violated-directive'] ?? null;

        $blockedUri = is_string($blockedUriRaw) ? $blockedUriRaw : '';
        $violatedDirective = is_string($violatedDirectiveRaw) ? $violatedDirectiveRaw : '';

        // 高風險情況
        if (strpos($violatedDirective, 'script-src') !== false) {
            // 外部惡意腳本注入
            if (
                strpos($blockedUri, 'eval') !== false
                || strpos($blockedUri, 'data:') !== false
                || preg_match('/[a-z0-9\-]+\.(tk|ml|ga|cf)/', $blockedUri)
            ) {
                return ActivitySeverity::HIGH;
            }
        }

        // 中風險情況
        if (
            strpos($violatedDirective, 'frame-ancestors') !== false
            || strpos($violatedDirective, 'form-action') !== false
        ) {
            return ActivitySeverity::MEDIUM;
        }

        // 低風險情況
        return ActivitySeverity::LOW;
    }

    /**
     * 檢查是否需要發送警報.
     */
    private function checkForAlert(array $logData): void
    {
        // 如果在短時間內有大量違規，可能是攻擊
        $ip = $logData['client_ip'] ?? 'unknown';
        if (!is_string($ip)) {
            return;
        }
        $recentViolations = $this->getRecentViolations($ip, 300); // 5分鐘內

        if (count($recentViolations) > 10) {
            $this->sendAlert([
                'type' => 'multiple_csp_violations',
                'ip' => $ip,
                'count' => count($recentViolations),
                'timeframe' => '5 minutes',
                'latest_violation' => $logData,
            ]);
        }
    }

    /**
     * 取得最近的違規記錄.
     */
    private function getRecentViolations(string $ip, int $seconds): array
    {
        $logFile = storage_path('logs/csp_violations.log');
        if (!file_exists($logFile)) {
            return [];
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $recentViolations = [];
        $cutoffTime = time() - $seconds;

        foreach (array_reverse($lines) as $line) {
            if (!is_string($line)) {
                continue;
            }
            $data = json_decode($line, true);
            if (!is_array($data)) {
                continue;
            }

            $timestampRaw = $data['timestamp'] ?? null;
            if (!is_string($timestampRaw)) {
                continue;
            }
            $timestamp = strtotime($timestampRaw);
            if ($timestamp === false || $timestamp < $cutoffTime) {
                break; // 已經超過時間範圍
            }

            $dataIp = $data['ip'] ?? null;
            if (is_string($dataIp) && $dataIp === $ip) {
                $recentViolations[] = $data;
            }
        }

        return $recentViolations;
    }

    /**
     * 發送警報.
     */
    private function sendAlert(array $alertData): void
    {
        // 這裡可以整合不同的警報系統
        // 例如：Email、Slack、Discord、PagerDuty 等

        $count = is_int($alertData['count'] ?? null) ? $alertData['count'] : 0;
        $ip = is_string($alertData['ip'] ?? null) ? $alertData['ip'] : 'unknown';
        $timeframe = is_string($alertData['timeframe'] ?? null) ? $alertData['timeframe'] : 'unknown';

        $message = sprintf(
            'CSP Alert: %d violations from IP %s in %s',
            $count,
            $ip,
            $timeframe,
        );

        $jsonAlert = json_encode($alertData);
        error_log('CSP ALERT: ' . ($jsonAlert !== false ? $jsonAlert : '{}'));

        // 可以在這裡添加其他警報機制
        // $this->sendSlackAlert($message, $alertData);
        // $this->sendEmailAlert($message, $alertData);
    }
}
