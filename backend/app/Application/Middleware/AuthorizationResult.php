<?php

declare(strict_types=1);

namespace App\Application\Middleware;

trigger_error(
    'Class App\Application\Middleware\AuthorizationResult is deprecated, use App\Domains\Auth\ValueObjects\AuthorizationResult instead.',
    E_USER_DEPRECATED,
);

/**
 * @deprecated 移轉至 App\Domains\Auth\ValueObjects\AuthorizationResult
 * @see \App\Domains\Auth\ValueObjects\AuthorizationResult
 */
final readonly class AuthorizationResult extends \App\Domains\Auth\ValueObjects\AuthorizationResult {}
