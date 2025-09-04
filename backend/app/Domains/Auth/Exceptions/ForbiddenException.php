<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

use Exception;

class ForbiddenException extends Exception
{
    public function __construct(string $message = '', int $code = 403)
    {
        if (empty($message)) {
            $message = '權限不足，無法執行此操作';
        }

        parent::__construct($message, $code);
    }

    public static function insufficientPermissions(): self
    {
        return new self('您沒有足夠的權限執行此操作');
    }

    public static function notOwner(): self
    {
        return new self('只有資源擁有者才能執行此操作');
    }

    public static function adminRequired(): self
    {
        return new self('此操作需要管理員權限');
    }

    public static function moderatorRequired(): self
    {
        return new self('此操作需要版主或管理員權限');
    }

    public static function csrfTokenMismatch(): self
    {
        return new self('CSRF 權杖驗證失敗，請重新整理頁面後再試');
    }

    public static function ipBlocked(): self
    {
        return new self('您的 IP 位址已被封鎖');
    }

    public static function rateLimitExceeded(): self
    {
        return new self('操作過於頻繁，請稍後再試');
    }

    public static function maintenanceMode(): self
    {
        return new self('系統維護中，暫時無法使用此功能');
    }

    public static function resourceLocked(): self
    {
        return new self('此資源已被鎖定，無法進行操作');
    }

    public static function featureDisabled(): self
    {
        return new self('此功能目前已停用');
    }
}
