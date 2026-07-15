<?php

declare(strict_types=1);

namespace App\Domains\Auth\ValueObjects;

use JsonSerializable;

readonly class AuthorizationResult implements JsonSerializable
{
    public function __construct(
        private bool $allowed,
        private string $reason,
        private string $code,
        private array $appliedRules = [],
        private array $metadata = [],
    ) {}

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function isDenied(): bool
    {
        return !$this->allowed;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getAppliedRules(): array
    {
        return $this->appliedRules;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function hasRule(string $rule): bool
    {
        return in_array($rule, $this->appliedRules, true);
    }

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

    public static function allowSuperAdmin(): self
    {
        return self::allow(
            reason: '超級管理員擁有所有權限',
            code: 'SUPER_ADMIN_ACCESS',
            appliedRules: ['super_admin'],
        );
    }

    public static function denyInsufficientPermissions(string $resource, string $action): self
    {
        return self::deny(
            reason: "使用者無權限執行操作：{$action} on {$resource}",
            code: 'INSUFFICIENT_PERMISSIONS',
            appliedRules: ['permission_check'],
        );
    }

    public static function denyNotAuthenticated(): self
    {
        return self::deny(
            reason: '使用者未認證',
            code: 'NOT_AUTHENTICATED',
            appliedRules: ['authentication_check'],
        );
    }

    public static function denyIpRestriction(string $ip): self
    {
        return self::deny(
            reason: "IP 位址 {$ip} 被限制存取",
            code: 'IP_RESTRICTION',
            appliedRules: ['ip_restriction'],
        );
    }

    public static function denyTimeRestriction(string $action): self
    {
        return self::deny(
            reason: "操作 {$action} 在當前時間不被允許",
            code: 'TIME_RESTRICTION',
            appliedRules: ['time_restriction'],
        );
    }

    public function toArray(): array
    {
        return [
            'allowed'       => $this->allowed,
            'reason'        => $this->reason,
            'code'          => $this->code,
            'applied_rules' => $this->appliedRules,
            'metadata'      => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function equals(AuthorizationResult $other): bool
    {
        return $this->allowed === $other->allowed
            && $this->reason === $other->reason
            && $this->code === $other->code
            && $this->appliedRules === $other->appliedRules
            && $this->metadata === $other->metadata;
    }

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

    public function __toString(): string
    {
        return $this->toString();
    }
}
