<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use JsonSerializable;

/**
 * 授權結果 Value Object.
 *
 * 封裝授權檢查的結果，包含是否允許、原因、錯誤代碼和應用的規則。
 * 此類別是不可變的，確保授權結果的完整性。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
final readonly class AuthorizationResult implements JsonSerializable
{
    /**
     * 建構授權結果.
     *
     * @param bool $allowed 是否允許存取
     * @param string $reason 授權原因或拒絕原因
     * @param string $code 結果代碼
     * @param array $appliedRules 應用的授權規則清單
     * @param array $metadata 額外的元資料
     */
    public function __construct(
        private bool $allowed,
        private string $reason,
        private string $code,
        private array $appliedRules = [],
        private array $metadata = [],
    ) {}

    /**
     * 檢查是否允許存取.
     *
     * @return bool 是否允許
     */
    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    /**
     * 檢查是否拒絕存取.
     *
     * @return bool 是否拒絕
     */
    public function isDenied(): bool
    {
        return !$this->allowed;
    }

    /**
     * 取得授權原因.
     *
     * @return string 授權或拒絕的原因
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * 取得結果代碼.
     *
     * @return string 結果代碼
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * 取得應用的授權規則.
     *
     * @return array<string> 規則清單
     */
    public function getAppliedRules(): array
    {
        return $this->appliedRules;
    }

    /**
     * 取得元資料.
     *
     * @return array<string, mixed> 元資料
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * 取得特定的元資料值.
     *
     * @param string $key 元資料鍵
     * @param mixed $default 預設值
     * @return mixed 元資料值
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * 檢查是否包含特定規則.
     *
     * @param string $rule 規則名稱
     * @return bool 是否包含
     */
    public function hasRule(string $rule): bool
    {
        return in_array($rule, $this->appliedRules, true);
    }

    /**
     * 建立允許的授權結果.
     *
     * @param string $reason 允許原因
     * @param string $code 結果代碼
     * @param array $appliedRules 應用的規則
     * @param array $metadata 元資料
     */
    public static function allow(
        string $reason = '存取被允許',
        string $code = 'ALLOWED',
        array $appliedRules = [],
        array $metadata = [],
    ): self {
        return new self(
            allowed: true,
            reason: $reason,
            code: $code,
            appliedRules: $appliedRules,
            metadata: $metadata,
        );
    }

    /**
     * 建立拒絕的授權結果.
     *
     * @param string $reason 拒絕原因
     * @param string $code 結果代碼
     * @param array $appliedRules 應用的規則
     * @param array $metadata 元資料
     */
    public static function deny(
        string $reason = '存取被拒絕',
        string $code = 'DENIED',
        array $appliedRules = [],
        array $metadata = [],
    ): self {
        return new self(
            allowed: false,
            reason: $reason,
            code: $code,
            appliedRules: $appliedRules,
            metadata: $metadata,
        );
    }

    /**
     * 建立超級管理員允許結果.
     */
    public static function allowSuperAdmin(): self
    {
        return self::allow(
            reason: '超級管理員擁有所有權限',
            code: 'SUPER_ADMIN_ACCESS',
            appliedRules: ['super_admin'],
        );
    }

    /**
     * 建立權限不足的拒絕結果.
     *
     * @param string $resource 資源名稱
     * @param string $action 操作名稱
     */
    public static function denyInsufficientPermissions(string $resource, string $action): self
    {
        return self::deny(
            reason: "使用者無權限執行操作：{$action} on {$resource}",
            code: 'INSUFFICIENT_PERMISSIONS',
            appliedRules: ['permission_check'],
        );
    }

    /**
     * 建立未認證的拒絕結果.
     */
    public static function denyNotAuthenticated(): self
    {
        return self::deny(
            reason: '使用者未認證',
            code: 'NOT_AUTHENTICATED',
            appliedRules: ['authentication_check'],
        );
    }

    /**
     * 建立 IP 限制的拒絕結果.
     *
     * @param string $ip IP 位址
     */
    public static function denyIpRestriction(string $ip): self
    {
        return self::deny(
            reason: "IP 位址 {$ip} 被限制存取",
            code: 'IP_RESTRICTION',
            appliedRules: ['ip_restriction'],
        );
    }

    /**
     * 建立時間限制的拒絕結果.
     *
     * @param string $action 操作名稱
     */
    public static function denyTimeRestriction(string $action): self
    {
        return self::deny(
            reason: "操作 {$action} 在當前時間不被允許",
            code: 'TIME_RESTRICTION',
            appliedRules: ['time_restriction'],
        );
    }

    /**
     * 轉換為陣列格式.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'reason' => $this->reason,
            'code' => $this->code,
            'applied_rules' => $this->appliedRules,
            'metadata' => $this->metadata,
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
     * 檢查與另一個 AuthorizationResult 是否相等.
     *
     * @param AuthorizationResult $other 另一個授權結果
     * @return bool 是否相等
     */
    public function equals(AuthorizationResult $other): bool
    {
        return $this->allowed === $other->allowed
            && $this->reason === $other->reason
            && $this->code === $other->code
            && $this->appliedRules === $other->appliedRules
            && $this->metadata === $other->metadata;
    }

    /**
     * 轉換為字串表示.
     */
    public function toString(): string
    {
        $status = $this->allowed ? 'ALLOWED' : 'DENIED';
        $rulesCount = count($this->appliedRules);
        $metadataCount = count($this->metadata);

        return sprintf(
            'AuthorizationResult(%s, code=%s, reason="%s", rules=%d, metadata=%d)',
            $status,
            $this->code,
            $this->reason,
            $rulesCount,
            $metadataCount,
        );
    }

    /**
     * __toString 魔術方法.
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
