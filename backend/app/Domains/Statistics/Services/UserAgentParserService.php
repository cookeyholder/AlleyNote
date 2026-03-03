<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

/**
 * User-Agent 解析服務
 * 用於從 User-Agent 字符串中提取瀏覽器和裝置資訊.
 */
class UserAgentParserService
{
    /**
     * 解析 User-Agent 字符串.
     *
     * @return array{browser: string, browser_version: string, device_type: string, os: string}
     */
    public function parse(?string $userAgent): array
    {
        if ($userAgent === null || $userAgent === '') {
            return [
                'browser' => 'Unknown',
                'browser_version' => '',
                'device_type' => 'Unknown',
                'os' => 'Unknown',
            ];
        }

        return [
            'browser' => $this->detectBrowser($userAgent),
            'browser_version' => $this->detectBrowserVersion($userAgent),
            'device_type' => $this->detectDeviceType($userAgent),
            'os' => $this->detectOS($userAgent),
        ];
    }

    /**
     * 檢測瀏覽器類型.
     */
    private function detectBrowser(string $userAgent): string
    {
        $browsers = [
            'Edge' => '/Edg\/([\d\.]+)/',
            'Chrome' => '/Chrome\/([\d\.]+)/',
            'Safari' => '/Safari\/([\d\.]+)/',
            'Firefox' => '/Firefox\/([\d\.]+)/',
            'Opera' => '/Opera\/([\d\.]+)/',
            'IE' => '/MSIE ([\d\.]+)/',
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return 'Other';
    }

    /**
     * 檢測瀏覽器版本.
     */
    private function detectBrowserVersion(string $userAgent): string
    {
        $patterns = [
            '/Edg\/([\d\.]+)/',
            '/Chrome\/([\d\.]+)/',
            '/Safari\/([\d\.]+)/',
            '/Firefox\/([\d\.]+)/',
            '/Opera\/([\d\.]+)/',
            '/MSIE ([\d\.]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $userAgent, $matches) === 1) {
                return (string) $matches[1];
            }
        }

        return '';
    }

    /**
     * 檢測裝置類型.
     */
    private function detectDeviceType(string $userAgent): string
    {
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
            if (preg_match('/iPad|Tablet/i', $userAgent)) {
                return 'Tablet';
            }

            return 'Mobile';
        }

        return 'Desktop';
    }

    /**
     * 檢測操作系統.
     */
    private function detectOS(string $userAgent): string
    {
        $osList = [
            'Windows 11' => '/Windows NT 10\.0.*Edg/i',
            'Windows 10' => '/Windows NT 10\.0/i',
            'Windows 8.1' => '/Windows NT 6\.3/i',
            'Windows 8' => '/Windows NT 6\.2/i',
            'Windows 7' => '/Windows NT 6\.1/i',
            'iOS' => '/iPhone|iPad|iPod/i',
            'Mac OS X' => '/Mac OS X/i',
            'macOS' => '/Macintosh/i',
            'Android' => '/Android/i',
            'Linux' => '/Linux/i',
            'Ubuntu' => '/Ubuntu/i',
        ];

        foreach ($osList as $os => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $os;
            }
        }

        return 'Other';
    }

    /**
     * 批量解析多個 User-Agent.
     *
     * @param array<string|null> $userAgents
     * @return array<array{browser: string, browser_version: string, device_type: string, os: string}>
     */
    public function parseBatch(array $userAgents): array
    {
        return array_map(fn(?string $ua) => $this->parse($ua), $userAgents);
    }
}
