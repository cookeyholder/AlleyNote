<?php

declare(strict_types=1);

namespace App\Application\Controllers\Security;

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
    /**
     * 處理 CSP 違規報告。
     */
    public function handleReport(Request $request, Response $response): Response
    {
        try {
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

            // 驗證並處理 CSP 報告
            if (is_array($data) && isset($data['csp-report']) && is_array($data['csp-report'])) {
                // 記錄違規事件 (簡單實作)
                error_log('CSP Violation: ' . json_encode($data['csp-report']));
            }

            // 返回成功回應
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(204); // No Content
        } catch (Exception $e) {
            return $this->createErrorResponse($response, 500, 'Internal server error');
        }
    }

    /**
     * 建立錯誤回應。
     */
    private function createErrorResponse(Response $response, int $statusCode, string $message): Response
    {
        $errorData = json_encode([
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);

        $response->getBody()->write($errorData ?: '{"error": "Unknown error"}');

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
