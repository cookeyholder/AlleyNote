<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;

if (!function_exists('generate_uuid')) {
    /**
     * 產生 UUID v4
     */
    function generate_uuid(): string
    {
        return Uuid::uuid4()->toString();
    }
}

if (!function_exists('format_datetime')) {
    /**
     * 格式化日期時間為 RFC 3339 格式
     * @param DateTime|string|null $datetime
     * @param string $timezone 預設為 Asia/Taipei
     */
    function format_datetime($datetime = null, string $timezone = 'Asia/Taipei'): string
    {
        if (is_null($datetime)) {
            $datetime = new DateTime();
        } elseif (is_string($datetime)) {
            $datetime = new DateTime($datetime);
        }

        $datetime->setTimezone(new DateTimeZone($timezone));
        return $datetime->format(DateTime::RFC3339);
    }
}

if (!function_exists('normalize_path')) {
    /**
     * 正規化檔案路徑
     * @param string $path 檔案路徑
     * @return string 正規化後的路徑
     */
    function normalize_path(string $path): string
    {
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        return rtrim($path, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('storage_path')) {
    /**
     * 取得儲存空間路徑
     */
    function storage_path(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2) . '/storage';
        return $path ? $basePath . '/' . $path : $basePath;
    }
}

if (!function_exists('public_path')) {
    /**
     * 取得公開目錄路徑
     */
    function public_path(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2) . '/public';
        return $path ? $basePath . '/' . $path : $basePath;
    }
}

if (!function_exists('is_valid_ip')) {
    /**
     * 驗證 IP 位址格式（支援 IPv4、IPv6 與 CIDR）
     */
    function is_valid_ip(string $ip): bool
    {
        // 檢查是否為 CIDR 格式
        if (str_contains($ip, '/')) {
            list($address, $netmask) = explode('/', $ip, 2);
            if (!filter_var($address, FILTER_VALIDATE_IP)) {
                return false;
            }
            // 驗證網路遮罩
            if (
                !is_numeric($netmask) ||
                $netmask < 0 ||
                ($address && str_contains($address, ':') ? $netmask > 128 : $netmask > 32)
            ) {
                return false;
            }
            return true;
        }

        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}

if (!function_exists('sanitize_filename')) {
    /**
     * 清理檔案名稱，移除非法字元
     */
    function sanitize_filename(string $filename): string
    {
        // 移除非法字元
        $filename = preg_replace('/[^\p{L}\p{N}\s\-\_\.]/u', '', $filename);
        // 將多個空格替換為單一底線
        $filename = preg_replace('/\s+/', '_', $filename);
        return trim($filename, '.-_');
    }
}

if (!function_exists('get_file_mime_type')) {
    /**
     * 取得檔案的 MIME 類型
     */
    function get_file_mime_type(string $path): ?string
    {
        if (!file_exists($path)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mimeType;
    }
}
