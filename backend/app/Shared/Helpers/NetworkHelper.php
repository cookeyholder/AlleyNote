<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use Psr\Http\Message\ServerRequestInterface as Request;

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
     * 從環境變數取得信任的代理伺服器清單.
     *
     * @return array<int, string>
     */
    public static function getTrustedProxies(): array
    {
        $rawTrustedProxies = getenv('TRUSTED_PROXIES') ?: ($_ENV['TRUSTED_PROXIES'] ?? '');
        if (!is_string($rawTrustedProxies) || trim($rawTrustedProxies) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn(string $proxy): string => trim($proxy),
            explode(',', $rawTrustedProxies),
        )));
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
        $parts = explode('/', $range, 2);
        if (count($parts) !== 2) {
            return false;
        }
        [$subnet, $bits] = $parts;
        $bits = (int) $bits;
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($bits < 0 || $bits > 32) {
                return false;
            }
            $ipAddr = ip2long($ip);
            $subnetAddr = ip2long($subnet);
            if (!is_int($ipAddr) || !is_int($subnetAddr)) {
                return false;
            }
            if ($bits === 0) {
                return true;
            }
            $mask = -1 << (32 - $bits);
            $subnetAddr &= $mask;

            return ($ipAddr & $mask) === $subnetAddr;
        }

        // 目前僅實作 IPv4 範圍檢查，IPv6 可未來擴充
        return false;
    }
}
