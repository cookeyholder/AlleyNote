<?php

declare(strict_types=1);

namespace App\Domains\Auth\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Device Info Value Object.
 *
 * 表示使用者裝置的資訊，用於追蹤和管理不同裝置的 JWT Token。
 * 此類別是不可變的，確保裝置資訊的完整性和一致性。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
final readonly class DeviceInfo implements JsonSerializable
{
    /**
     * 建構裝置資訊.
     *
     * @param string $deviceId 裝置唯一識別符
     * @param string $deviceName 裝置名稱（用戶自訂或系統生成）
     * @param string $userAgent 使用者代理字串
     * @param string $ipAddress IP 位址
     * @param string|null $platform 平台資訊 (Windows, macOS, Linux, iOS, Android 等)
     * @param string|null $browser 瀏覽器資訊 (Chrome, Firefox, Safari 等)
     * @param string|null $browserVersion 瀏覽器版本
     * @param string|null $osVersion 作業系統版本
     * @param bool $isMobile 是否為行動裝置
     * @param bool $isTablet 是否為平板裝置
     * @param bool $isDesktop 是否為桌面裝置
     *
     * @throws InvalidArgumentException 當參數無效時
     */
    public function __construct(
        private string $deviceId,
        private string $deviceName,
        private string $userAgent,
        private string $ipAddress,
        private ?string $platform = null,
        private ?string $browser = null,
        private ?string $browserVersion = null,
        private ?string $osVersion = null,
        private bool $isMobile = false,
        private bool $isTablet = false,
        private bool $isDesktop = true,
    ) {
        $this->validateDeviceId($deviceId);
        $this->validateDeviceName($deviceName);
        $this->validateUserAgent($userAgent);
        $this->validateIpAddress($ipAddress);
        $this->validateDeviceType($isMobile, $isTablet, $isDesktop);

        if ($platform !== null) {
            $this->validatePlatform($platform);
        }
        if ($browser !== null) {
            $this->validateBrowser($browser);
        }
    }

    /**
     * 從使用者代理和 IP 建立裝置資訊.
     *
     * @param string $userAgent 使用者代理字串
     * @param string $ipAddress IP 位址
     * @param string|null $deviceName 裝置名稱，若為 null 則自動生成
     */
    public static function fromUserAgent(string $userAgent, string $ipAddress, ?string $deviceName = null): self
    {
        $parsedInfo = self::parseUserAgent($userAgent);

        $deviceId = self::generateDeviceId($userAgent, $ipAddress);
        $deviceName ??= self::generateDeviceName($parsedInfo);

        return new self(
            deviceId: $deviceId,
            deviceName: $deviceName,
            userAgent: $userAgent,
            ipAddress: $ipAddress,
            platform: $parsedInfo['platform'],
            browser: $parsedInfo['browser'],
            browserVersion: $parsedInfo['browserVersion'],
            osVersion: $parsedInfo['osVersion'],
            isMobile: $parsedInfo['isMobile'],
            isTablet: $parsedInfo['isTablet'],
            isDesktop: $parsedInfo['isDesktop'],
        );
    }

    /**
     * 從陣列建立裝置資訊.
     *
     * @param array<string, mixed> $data 裝置資料
     * @throws InvalidArgumentException 當資料格式無效時
     */
    public static function fromArray(array $data): self
    {
        $requiredFields = ['device_id', 'device_name', 'user_agent', 'ip_address'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        return new self(
            deviceId: $data['device_id'],
            deviceName: $data['device_name'],
            userAgent: $data['user_agent'],
            ipAddress: $data['ip_address'],
            platform: $data['platform'] ?? null,
            browser: $data['browser'] ?? null,
            browserVersion: $data['browser_version'] ?? null,
            osVersion: $data['os_version'] ?? null,
            isMobile: $data['is_mobile'] ?? false,
            isTablet: $data['is_tablet'] ?? false,
            isDesktop: $data['is_desktop'] ?? true,
        );
    }

    /**
     * 取得裝置 ID.
     */
    public function getDeviceId(): string
    {
        return $this->deviceId;
    }

    /**
     * 取得裝置名稱.
     */
    public function getDeviceName(): string
    {
        return $this->deviceName;
    }

    /**
     * 取得使用者代理.
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * 取得 IP 位址
     */
    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * 取得平台資訊.
     */
    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    /**
     * 取得瀏覽器資訊.
     */
    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    /**
     * 取得瀏覽器版本.
     */
    public function getBrowserVersion(): ?string
    {
        return $this->browserVersion;
    }

    /**
     * 取得作業系統版本.
     */
    public function getOsVersion(): ?string
    {
        return $this->osVersion;
    }

    /**
     * 檢查是否為行動裝置.
     */
    public function isMobile(): bool
    {
        return $this->isMobile;
    }

    /**
     * 檢查是否為平板裝置.
     */
    public function isTablet(): bool
    {
        return $this->isTablet;
    }

    /**
     * 檢查是否為桌面裝置.
     */
    public function isDesktop(): bool
    {
        return $this->isDesktop;
    }

    /**
     * 取得裝置類型描述.
     */
    public function getDeviceType(): string
    {
        if ($this->isMobile) {
            return 'mobile';
        }

        if ($this->isTablet) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * 取得裝置指紋（用於識別相同裝置）.
     */
    public function getFingerprint(): string
    {
        $components = [
            $this->platform ?? 'unknown',
            $this->browser ?? 'unknown',
            $this->getDeviceType(),
            substr(md5($this->userAgent), 0, 8),
        ];

        return implode('-', $components);
    }

    /**
     * 取得完整的瀏覽器資訊.
     */
    public function getFullBrowserInfo(): string
    {
        if ($this->browser === null) {
            return 'Unknown Browser';
        }

        $browserInfo = $this->browser;
        if ($this->browserVersion !== null) {
            $browserInfo .= ' ' . $this->browserVersion;
        }

        return $browserInfo;
    }

    /**
     * 取得完整的平台資訊.
     */
    public function getFullPlatformInfo(): string
    {
        if ($this->platform === null) {
            return 'Unknown Platform';
        }

        $platformInfo = $this->platform;
        if ($this->osVersion !== null) {
            $platformInfo .= ' ' . $this->osVersion;
        }

        return $platformInfo;
    }

    /**
     * 檢查是否與另一個裝置資訊匹配.
     *
     * @param DeviceInfo $other 另一個裝置資訊
     */
    public function matches(DeviceInfo $other): bool
    {
        return $this->getFingerprint() === $other->getFingerprint();
    }

    /**
     * 轉換為陣列格式.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'device_id' => $this->deviceId,
            'device_name' => $this->deviceName,
            'user_agent' => $this->userAgent,
            'ip_address' => $this->ipAddress,
            'platform' => $this->platform,
            'browser' => $this->browser,
            'browser_version' => $this->browserVersion,
            'os_version' => $this->osVersion,
            'is_mobile' => $this->isMobile,
            'is_tablet' => $this->isTablet,
            'is_desktop' => $this->isDesktop,
            'device_type' => $this->getDeviceType(),
            'fingerprint' => $this->getFingerprint(),
        ];
    }

    /**
     * 轉換為摘要格式（隱藏敏感資訊）.
     *
     * @return array<string, mixed>
     */
    public function toSummary(): array
    {
        return [
            'device_id' => $this->deviceId,
            'device_name' => $this->deviceName,
            'platform' => $this->getFullPlatformInfo(),
            'browser' => $this->getFullBrowserInfo(),
            'device_type' => $this->getDeviceType(),
            'ip_address_masked' => $this->maskIpAddress(),
        ];
    }

    /**
     * JsonSerializable 實作.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 檢查與另一個 DeviceInfo 是否相等.
     *
     * @param DeviceInfo $other 另一個 DeviceInfo
     */
    public function equals(DeviceInfo $other): bool
    {
        return $this->deviceId === $other->deviceId
            && $this->deviceName === $other->deviceName
            && $this->userAgent === $other->userAgent
            && $this->ipAddress === $other->ipAddress
            && $this->platform === $other->platform
            && $this->browser === $other->browser
            && $this->browserVersion === $other->browserVersion
            && $this->osVersion === $other->osVersion
            && $this->isMobile === $other->isMobile
            && $this->isTablet === $other->isTablet
            && $this->isDesktop === $other->isDesktop;
    }

    /**
     * 轉換為字串表示.
     */
    public function toString(): string
    {
        return sprintf(
            'DeviceInfo(id=%s, name=%s, type=%s, platform=%s, browser=%s, ip=%s)',
            $this->deviceId,
            $this->deviceName,
            $this->getDeviceType(),
            $this->platform ?? 'unknown',
            $this->browser ?? 'unknown',
            $this->maskIpAddress(),
        );
    }

    /**
     * __toString 魔術方法.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * 遮罩 IP 位址以保護隱私
     */
    private function maskIpAddress(): string
    {
        if (filter_var($this->ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4: 隱藏最後一段
            $parts = explode('.', $this->ipAddress);
            $parts[3] = 'xxx';

            return implode('.', $parts);
        }

        if (filter_var($this->ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6: 簡單處理，保留前面部分，後面用 xxxx 替換
            // 對於 2001:db8::1，我們要得到 2001:db8::xxxx
            if (strpos($this->ipAddress, '::') !== false) {
                // 處理簡寫格式
                $parts = explode('::', $this->ipAddress);

                return $parts[0] . '::xxxx';
            } else {
                // 處理完整格式
                $parts = explode(':', $this->ipAddress);
                if (count($parts) >= 4) {
                    return implode(':', array_slice($parts, 0, 4)) . '::xxxx';
                }
            }
        }

        return substr($this->ipAddress, 0, -4) . 'xxxx';
    }

    /**
     * 生成裝置 ID.
     *
     * @param string $userAgent 使用者代理
     * @param string $ipAddress IP 位址
     */
    private static function generateDeviceId(string $userAgent, string $ipAddress): string
    {
        $data = $userAgent . $ipAddress . date('Y-m-d');

        return 'dev_' . substr(hash('sha256', $data), 0, 32);
    }

    /**
     * 生成裝置名稱.
     *
     * @param array<string, mixed> $parsedInfo 解析後的裝置資訊
     */
    private static function generateDeviceName(array $parsedInfo): string
    {
        $platform = $parsedInfo['platform'] ?? 'Unknown';
        $browser = $parsedInfo['browser'] ?? 'Browser';
        $deviceType = $parsedInfo['isMobile'] ? 'Mobile' : ($parsedInfo['isTablet'] ? 'Tablet' : 'Desktop');

        return "{$platform} {$deviceType} ({$browser})";
    }

    /**
     * 解析使用者代理字串.
     *
     * @param string $userAgent 使用者代理字串
     * @return array<string, mixed>
     */
    private static function parseUserAgent(string $userAgent): array
    {
        $info = [
            'platform' => null,
            'browser' => null,
            'browserVersion' => null,
            'osVersion' => null,
            'isMobile' => false,
            'isTablet' => false,
            'isDesktop' => true,
        ];

        // 平板檢測（先檢測平板，因為某些平板也會匹配行動裝置）
        $tabletPattern = '/iPad|Android.*Tablet|Windows.*Touch/i';
        $info['isTablet'] = preg_match($tabletPattern, $userAgent) === 1;

        // 行動裝置檢測（排除平板）
        $mobilePattern = '/Mobile|Android|iPhone|iPod|Windows Phone|BlackBerry/i';
        $info['isMobile'] = !$info['isTablet'] && preg_match($mobilePattern, $userAgent) === 1;

        // 如果是行動裝置或平板，則不是桌面
        $info['isDesktop'] = !$info['isMobile'] && !$info['isTablet'];

        // 平台檢測 (先檢測更具體的模式)
        if (preg_match('/iPad.*CPU OS ([0-9_]+)/i', $userAgent, $matches)) {
            // iPad 專用檢測
            $info['platform'] = 'iOS';
            $info['osVersion'] = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/iPhone.*OS ([0-9_]+)/i', $userAgent, $matches)) {
            // iPhone 專用檢測
            $info['platform'] = 'iOS';
            $info['osVersion'] = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Android ([0-9.]+)/i', $userAgent, $matches)) {
            $info['platform'] = 'Android';
            $info['osVersion'] = $matches[1];
        } elseif (preg_match('/Windows NT ([0-9.]+)/i', $userAgent, $matches)) {
            $info['platform'] = 'Windows';
            $info['osVersion'] = $matches[1];
        } elseif (preg_match('/Mac OS X ([0-9_.]+)/i', $userAgent, $matches)) {
            $info['platform'] = 'macOS';
            $info['osVersion'] = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $info['platform'] = 'Linux';
        }

        // 瀏覽器檢測
        if (preg_match('/Chrome\/([0-9.]+)/i', $userAgent, $matches)) {
            $info['browser'] = 'Chrome';
            $info['browserVersion'] = $matches[1];
        } elseif (preg_match('/Firefox\/([0-9.]+)/i', $userAgent, $matches)) {
            $info['browser'] = 'Firefox';
            $info['browserVersion'] = $matches[1];
        } elseif (preg_match('/Safari\/([0-9.]+)/i', $userAgent, $matches)) {
            if (!str_contains($userAgent, 'Chrome')) { // 避免誤判 Chrome
                $info['browser'] = 'Safari';
                $info['browserVersion'] = $matches[1];
            }
        } elseif (preg_match('/Edge\/([0-9.]+)/i', $userAgent, $matches)) {
            $info['browser'] = 'Edge';
            $info['browserVersion'] = $matches[1];
        }

        return $info;
    }

    /**
     * 驗證裝置 ID.
     *
     * @param string $deviceId 裝置 ID
     * @throws InvalidArgumentException 當裝置 ID 無效時
     */
    private function validateDeviceId(string $deviceId): void
    {
        if (empty($deviceId)) {
            throw new InvalidArgumentException('Device ID cannot be empty');
        }

        if (mb_strlen($deviceId) > 255) {
            throw new InvalidArgumentException('Device ID cannot exceed 255 characters');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $deviceId)) {
            throw new InvalidArgumentException('Device ID can only contain letters, numbers, underscores and hyphens');
        }
    }

    /**
     * 驗證裝置名稱.
     *
     * @param string $deviceName 裝置名稱
     * @throws InvalidArgumentException 當裝置名稱無效時
     */
    private function validateDeviceName(string $deviceName): void
    {
        if (empty($deviceName)) {
            throw new InvalidArgumentException('Device name cannot be empty');
        }

        if (mb_strlen($deviceName) > 255) {
            throw new InvalidArgumentException('Device name cannot exceed 255 characters');
        }
    }

    /**
     * 驗證使用者代理.
     *
     * @param string $userAgent 使用者代理
     * @throws InvalidArgumentException 當使用者代理無效時
     */
    private function validateUserAgent(string $userAgent): void
    {
        if (empty($userAgent)) {
            throw new InvalidArgumentException('User agent cannot be empty');
        }

        if (mb_strlen($userAgent) > 1000) {
            throw new InvalidArgumentException('User agent cannot exceed 1000 characters');
        }
    }

    /**
     * 驗證 IP 位址
     *
     * @param string $ipAddress IP 位址
     * @throws InvalidArgumentException 當 IP 位址無效時
     */
    private function validateIpAddress(string $ipAddress): void
    {
        if (empty($ipAddress)) {
            throw new InvalidArgumentException('IP address cannot be empty');
        }

        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('IP address format is invalid');
        }
    }

    /**
     * 驗證平台.
     *
     * @param string $platform 平台
     * @throws InvalidArgumentException 當平台無效時
     */
    private function validatePlatform(string $platform): void
    {
        $validPlatforms = ['Windows', 'macOS', 'Linux', 'Android', 'iOS', 'Unix', 'Other'];
        if (!in_array($platform, $validPlatforms, true)) {
            throw new InvalidArgumentException(
                'Platform must be one of: ' . implode(', ', $validPlatforms),
            );
        }
    }

    /**
     * 驗證瀏覽器.
     *
     * @param string $browser 瀏覽器
     * @throws InvalidArgumentException 當瀏覽器無效時
     */
    private function validateBrowser(string $browser): void
    {
        $validBrowsers = [
            'Chrome',
            'Firefox',
            'Safari',
            'Edge',
            'Opera',
            'Internet Explorer',
            'Chromium',
            'Other',
        ];

        if (!in_array($browser, $validBrowsers, true)) {
            throw new InvalidArgumentException(
                'Browser must be one of: ' . implode(', ', $validBrowsers),
            );
        }
    }

    /**
     * 驗證裝置類型.
     *
     * @param bool $isMobile 是否為行動裝置
     * @param bool $isTablet 是否為平板裝置
     * @param bool $isDesktop 是否為桌面裝置
     * @throws InvalidArgumentException 當裝置類型設定無效時
     */
    private function validateDeviceType(bool $isMobile, bool $isTablet, bool $isDesktop): void
    {
        $trueCount = ($isMobile ? 1 : 0) + ($isTablet ? 1 : 0) + ($isDesktop ? 1 : 0);

        if ($trueCount !== 1) {
            throw new InvalidArgumentException('Exactly one device type (mobile, tablet, desktop) must be true');
        }
    }
}
