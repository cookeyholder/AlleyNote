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
     * @param array<string> $trustedProxies 信任的代理伺服器 IP 清單
     * @return string 客戶端 IP 位址
     */
    public static function getClientIp(Request $request, array $trustedProxies = []): string
    {
        $serverParams = $request->getServerParams();
        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!is_string($remoteAddr)) {
            $remoteAddr = '127.0.0.1';
        }

        // 僅在有設定 trusted proxies 且來源為受信代理時才信任轉發標頭
        if (empty($trustedProxies) || !self::isIpInRanges($remoteAddr, $trustedProxies)) {
            return $remoteAddr;
        }

        // 映射：Server Param Key => Header Name
        $headerMap = [
            'HTTP_CF_CONNECTING_IP' => 'CF-Connecting-IP',
            'HTTP_X_REAL_IP'        => 'X-Real-IP',
            'HTTP_X_FORWARDED_FOR'  => 'X-Forwarded-For',
            'HTTP_CLIENT_IP'        => 'Client-IP',
        ];

        foreach ($headerMap as $serverKey => $headerName) {
            $value = $serverParams[$serverKey] ?? $request->getHeaderLine($headerName);

            if (!empty($value)) {
                // 處理多個 IP（通常第一個是真實 IP）
                $ips = explode(',', (string) $value);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $remoteAddr;
    }

    /**
     * 檢查 IP 是否在指定的範圍內 (支援單一 IP 或 CIDR).
     */
    private static function isIpInRanges(string $ip, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if (!is_string($range)) {
                continue;
            }

            if (str_contains($range, '/')) {
                if (self::ipInNetwork($ip, $range)) {
                    return true;
                }
            } elseif ($ip === $range) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查 IP 是否屬於 CIDR 網路.
     */
    private static function ipInNetwork(string $ip, string $range): bool
    {
        [$subnet, $bits] = explode('/', $range);
        $bits = (int) $bits;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipAddr = ip2long($ip);
            $subnetAddr = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnetAddr &= $mask;

            return ($ipAddr & $mask) === $subnetAddr;
        }

        // 目前僅實作 IPv4 範圍檢查，IPv6 可未來擴充
        return false;
    }
}
