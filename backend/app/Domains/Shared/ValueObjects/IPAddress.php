<?php

declare(strict_types=1);

namespace App\Domains\Shared\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * IP Address 值物件.
 *
 * 表示有效的 IPv4 或 IPv6 地址
 */
final readonly class IPAddress implements JsonSerializable, Stringable
{
    private string $value;

    private string $version; // 'ipv4' or 'ipv6'

    public function __construct(string $ipAddress)
    {
        $trimmedIp = trim($ipAddress);

        if (empty($trimmedIp)) {
            throw new InvalidArgumentException('IP 地址不能為空');
        }

        // 檢查 IPv4
        if (filter_var($trimmedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->value = $trimmedIp;
            $this->version = 'ipv4';

            return;
        }

        // 檢查 IPv6
        if (filter_var($trimmedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->value = $trimmedIp;
            $this->version = 'ipv6';

            return;
        }

        throw new InvalidArgumentException("無效的 IP 地址格式: {$trimmedIp}");
    }

    /**
     * 從字串建立 IPAddress.
     */
    public static function fromString(string $ipAddress): self
    {
        return new self($ipAddress);
    }

    /**
     * 取得 IP 地址值
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * 取得 IP 版本.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * 檢查是否為 IPv4.
     */
    public function isIPv4(): bool
    {
        return $this->version === 'ipv4';
    }

    /**
     * 檢查是否為 IPv6.
     */
    public function isIPv6(): bool
    {
        return $this->version === 'ipv6';
    }

    /**
     * 檢查是否為私有 IP.
     */
    public function isPrivate(): bool
    {
        return !filter_var(
            $this->value,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );
    }

    /**
     * 檢查是否為本地 IP (localhost, 127.0.0.1, ::1).
     */
    public function isLocalhost(): bool
    {
        return $this->value === '127.0.0.1'
            || $this->value === 'localhost'
            || $this->value === '::1';
    }

    /**
     * 遮罩 IP 地址用於顯示.
     */
    public function mask(): string
    {
        if ($this->isIPv4()) {
            $parts = explode('.', $this->value);
            $parts[count($parts) - 1] = 'xxx';

            return implode('.', $parts);
        }

        // IPv6 簡單遮罩
        if (str_contains($this->value, '::')) {
            $parts = explode('::', $this->value);

            return $parts[0] . '::xxxx';
        }

        $parts = explode(':', $this->value);
        if (count($parts) >= 4) {
            return implode(':', array_slice($parts, 0, 4)) . '::xxxx';
        }

        return substr($this->value, 0, -4) . 'xxxx';
    }

    /**
     * 檢查是否與另一個 IPAddress 相等.
     */
    public function equals(IPAddress $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * 轉換為字串.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * __toString 魔術方法.
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * JsonSerializable 實作.
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'ip_address' => $this->value,
            'version' => $this->version,
            'is_private' => $this->isPrivate(),
            'is_localhost' => $this->isLocalhost(),
        ];
    }
}
