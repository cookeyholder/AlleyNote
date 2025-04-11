<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;

if (!function_exists('generate_uuid')) {
    /**
     * 產生 UUID v4
     * @return string
     */
    function generate_uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}

if (!function_exists('format_datetime')) {
    /**
     * 格式化日期時間
     * @param string|null $datetime 要格式化的日期時間，若為空則使用當前時間
     * @return string 格式化後的日期時間字串
     */
    function format_datetime(?string $datetime = null): string
    {
        $dt = $datetime ? new DateTime($datetime) : new DateTime();
        return $dt->format('Y-m-d H:i:s');
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
