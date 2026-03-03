<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 網路相關工具類.
 *
 * 提供安全的 IP 位址辨識與網路環境檢查功能。
 */
final class NetworkHelper
{
    /**
     * 取得客戶端真實 IP 位址.
     *
     * @param Request $request PSR-7 請求對象
     * @param array<string> $trustedProxies 信任的代理伺服器 IP 清單（目前預留，可未來擴充至配置檔）
     * @return string 客戶端 IP 位址
     */
    public static function getClientIp(Request $request, array $trustedProxies = []): string
    {
        $serverParams = $request->getServerParams();

        // 映射：Server Param Key => Header Name
        $headerMap = [
            'HTTP_CF_CONNECTING_IP' => 'CF-Connecting-IP',
            'HTTP_X_REAL_IP'        => 'X-Real-IP',
            'HTTP_X_FORWARDED_FOR'  => 'X-Forwarded-For',
            'HTTP_CLIENT_IP'        => 'Client-IP',
        ];

        foreach ($headerMap as $serverKey => $headerName) {
            // 優先從 Server Params 取得（通常由 Web Server 填寫）
            $value = $serverParams[$serverKey] ?? $request->getHeaderLine($headerName);

            if (!empty($value)) {
                // 處理多個 IP（以逗號分隔的情況，通常第一個是真實 IP）
                $ip = trim(explode(',', (string) $value)[0]);

                // 驗證 IP 格式
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        // 預設回傳 REMOTE_ADDR
        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
