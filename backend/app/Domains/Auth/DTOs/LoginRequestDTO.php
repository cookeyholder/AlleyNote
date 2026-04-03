<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

use InvalidArgumentException;

final readonly class LoginRequestDTO
{
    /**
     * @param list<string>|null $scopes
     */
    public function __construct(
        public string $email,
        public string $password,
        public bool $rememberMe = false,
        public ?array $scopes = null,
    ) {
        self::assertScopes($this->scopes);
    }

    /**
     * 從陣列建立 LoginRequestDTO.
     *
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: is_string($data['email'] ?? null) ? $data['email'] : '',
            password: is_string($data['password'] ?? null) ? $data['password'] : '',
            rememberMe: (bool) ($data['remember_me'] ?? false),
            scopes: self::normalizeScopes($data['scopes'] ?? null),
        );
    }

    /**
     * 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => '[REDACTED]', // 不記錄密碼
            'remember_me' => $this->rememberMe,
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
