<?php

declare(strict_types=1);

namespace App\Application\Controllers\Security;

use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
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
            $report = json_decode(is_string($body) ? $body : (string) $body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $response->withStatus(400);
            }

            // 確保 $report 是正確的陣列格式，並且key是字串
            if (!is_array($report)) {
                return $response->withStatus(400);
            }

            // 轉換為 array<string, mixed> 格式
            $normalizedReport = [];
            foreach ($report as $key => $value) {
                $normalizedReport[(string) $key] = $value;
            }

            // 驗證報告格式
            if (!$this->isValidCSPReport($normalizedReport)) {
                return $response->withStatus(400);
            }

            // 記錄違規
            $this->logViolation($normalizedReport, $request);

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
     * @param array<string, mixed> $report
     */
    private function isValidCSPReport(array $report): bool
    {
        // 檢查必要的欄位
        if (!isset($report['csp-report'])) {
            return false;
        }

        $cspReport = $report['csp-report'];

        // 確保 csp-report 是陣列
        if (!is_array($cspReport)) {
            return false;
        }

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
     * @param array<string, mixed> $report
     */
    private function logViolation(array $report, Request $request): void
    {
        // 確保 csp-report 是陣列
        $cspReport = $report['csp-report'] ?? [];
        if (!is_array($cspReport)) {
            $cspReport = [];
        }

        // 轉換為 array<string, mixed> 格式
        $normalizedCspReport = [];
        foreach ($cspReport as $key => $value) {
            $normalizedCspReport[(string) $key] = $value;
        }

        $logData = [
            'client_ip' => $this->getClientIP($request),
            'user_agent_hash' => hash('sha256', $request->getHeaderLine('User-Agent')),
            'referer' => $request->getHeaderLine('Referer'),
            'csp_report' => $normalizedCspReport,
            'severity' => $this->calculateSeverity($normalizedCspReport),
        ];

        // 使用安全日誌服務記錄 CSP 違規
        if ($logData['severity'] === 'high') {
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
            if (isset($serverParams[$header]) && !empty($serverParams[$header])) {
                $ip = $serverParams[$header];

                // 確保 IP 是字串類型
                if (!is_string($ip)) {
                    if (is_scalar($ip)) {
                        $ip = (string) $ip;
                    } else {
                        continue;
                    }
                }

                // X-Forwarded-For 可能包含多個 IP
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? 'unknown';

        return is_string($remoteAddr) ? $remoteAddr : 'unknown';
    }

    /**
     * 計算違規嚴重程度.
     * @param array<string, mixed> $cspReport
     */
    private function calculateSeverity(array $cspReport): string
    {
        $blockedUri = $cspReport['blocked-uri'] ?? '';
        $violatedDirective = $cspReport['violated-directive'] ?? '';

        // 確保這些值是字串類型，避免直接cast mixed
        if (!is_string($blockedUri)) {
            $blockedUri = is_scalar($blockedUri) ? (string) $blockedUri : '';
        }
        if (!is_string($violatedDirective)) {
            $violatedDirective = is_scalar($violatedDirective) ? (string) $violatedDirective : '';
        }

        // 高風險情況
        if (strpos($violatedDirective, 'script-src') !== false) {
            // 外部惡意腳本注入
            if (
                strpos($blockedUri, 'eval') !== false
                || strpos($blockedUri, 'data:') !== false
                || preg_match('/[a-z0-9\-]+\.(tk|ml|ga|cf)/', $blockedUri)
            ) {
                return 'high';
            }
        }

        // 中風險情況
        if (
            strpos($violatedDirective, 'frame-ancestors') !== false
            || strpos($violatedDirective, 'form-action') !== false
        ) {
            return 'medium';
        }

        // 低風險情況
        return 'low';
    }

    /**
     * 檢查是否需要發送警報.
     * @param array<string, mixed> $logData
     */
    private function checkForAlert(array $logData): void
    {
        // 確保 IP 是字串類型
        $ip = $logData['ip'] ?? 'unknown';
        if (!is_string($ip)) {
            $ip = 'unknown';
        }

        // 如果在短時間內有大量違規，可能是攻擊
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
     * @return array<string, mixed><int, mixed>
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
            $data = json_decode(is_string($line) ? $line : (string) $line, true);
            if (!is_array($data)) {
                continue;
            }

            $timestamp = isset($data['timestamp']) && is_scalar($data['timestamp'])
                ? strtotime((string) $data['timestamp']) : false;
            if ($timestamp === false || $timestamp < $cutoffTime) {
                if ($timestamp !== false && $timestamp < $cutoffTime) {
                    break; // 已經超過時間範圍
                }
                continue;
            }

            $dataIp = $data['ip'] ?? '';
            if (is_string($dataIp) && $dataIp === $ip) {
                $recentViolations[] = $data;
            }
        }

        return $recentViolations;
    }

    /**
     * 發送警報.
     * @param array<string, mixed> $alertData
     */
    private function sendAlert(array $alertData): void
    {
        // 這裡可以整合不同的警報系統
        // 例如：Email、Slack、Discord、PagerDuty 等

        $count = $alertData['count'] ?? 0;
        $ip = $alertData['ip'] ?? 'unknown';
        $timeframe = $alertData['timeframe'] ?? 'unknown';

        // 確保參數是正確的類型
        $count = is_scalar($count) ? (string) $count : '0';
        $ip = is_scalar($ip) ? (string) $ip : 'unknown';
        $timeframe = is_scalar($timeframe) ? (string) $timeframe : 'unknown';

        $message = sprintf(
            'CSP Alert: %s violations from IP %s in %s',
            $count,
            $ip,
            $timeframe,
        );

        $jsonData = json_encode($alertData);
        $logMessage = 'CSP ALERT: ' . ($jsonData !== false ? $jsonData : 'JSON encoding failed');
        error_log($logMessage);

        // 可以在這裡添加其他警報機制
        // $this->sendSlackAlert($message, $alertData);
        // $this->sendEmailAlert($message, $alertData);
    }
}
