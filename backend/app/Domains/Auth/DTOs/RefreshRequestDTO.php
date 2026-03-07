<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

use InvalidArgumentException;

/**
 * 刷新請求 DTO.
 *
 * 封裝使用者 Token 刷新請求的資料。
 */
final readonly class RefreshRequestDTO
{
    /**
     * @param list<string>|null $scopes
     */
    public function __construct(
        public string $refreshToken,
        public ?array $scopes = null,
    ) {
        self::assertScopes($this->scopes);
    }

    /**
     * 從陣列建立 RefreshRequestDTO.
     *
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            refreshToken: is_string($data['refresh_token'] ?? null) ? $data['refresh_token'] : '',
            scopes: self::normalizeScopes($data['scopes'] ?? null),
        );
    }

    /**
     * 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'refresh_token' => '[REDACTED]', // 不記錄 token
            'scopes' => $this->scopes,
        ];
    }

    /**
     * @return list<string>|null
     */
    private static function normalizeScopes(mixed $scopes): ?array
    {
        if (!is_array($scopes)) {
            return null;
        }

        $normalizedScopes = [];
        foreach ($scopes as $scope) {
            if (is_string($scope) && $scope !== '') {
                $normalizedScopes[] = $scope;
            }
        }

        return $normalizedScopes === [] ? null : $normalizedScopes;
    }

    /**
     * @param list<string>|null $scopes
     */
    private static function assertScopes(?array $scopes): void
    {
        if ($scopes === null) {
            return;
        }

        foreach ($scopes as $scope) {
            if (!is_string($scope) || $scope === '') {
                throw new InvalidArgumentException('Scopes must be a non-empty string list');
            }
        }
    }
}
