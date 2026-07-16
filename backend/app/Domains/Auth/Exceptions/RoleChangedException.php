<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

use App\Domains\Auth\Exceptions\JwtException;

/**
 * 角色權限變更例外.
 *
 * 當使用者的角色權限在 JWT 發行後被變更時拋出，
 * 要求使用者重新登入以取得新的 Token。
 */
final class RoleChangedException extends JwtException
{
    protected string $errorType = 'role_changed';

    public const ERROR_CODE = 4003;

    public function __construct(string $message = '')
    {
        parent::__construct(
            $message ?: '角色權限已變更，請重新登入',
            self::ERROR_CODE,
        );
    }
}
