<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

use Exception;

class UnauthorizedException extends Exception
{
    public function __construct(string $message = '', int $code = 401)
    {
        if (empty($message)) {
            $message = '未授權存取，請先登入';
        }

        parent::__construct($message, $code);
    }

    public static function notLoggedIn(): self
    {
        return new self('請先登入後再進行此操作');
    }

    public static function invalidCredentials(): self
    {
        return new self('帳號或密碼錯誤');
    }

    public static function tokenExpired(): self
    {
        return new self('登入權杖已過期，請重新登入');
    }

    public static function tokenInvalid(): self
    {
        return new self('無效的登入權杖');
    }

    public static function sessionExpired(): self
    {
        return new self('登入會話已過期，請重新登入');
    }

    public static function accountDisabled(): self
    {
        return new self('此帳號已被停用，請聯繫管理員');
    }

    public static function accountLocked(): self
    {
        return new self('此帳號因多次登入失敗已被暫時鎖定');
    }

    public static function emailNotVerified(): self
    {
        return new self('請先驗證您的電子郵件地址');
    }
}
