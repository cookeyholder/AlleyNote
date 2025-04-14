<?php

declare(strict_types=1);

namespace App\Exceptions;

class CsrfTokenException extends \Exception
{
    public function __construct(string $message = 'CSRF token 驗證失敗', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
