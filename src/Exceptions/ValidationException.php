<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends BaseException
{
    public function __construct(string $message = "", array $errors = [])
    {
        parent::__construct($message, $errors);
    }
}
