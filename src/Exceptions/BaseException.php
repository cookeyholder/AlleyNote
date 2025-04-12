<?php

namespace App\Exceptions;

abstract class BaseException extends \Exception
{
    protected array $errors = [];

    public function __construct(string $message = "", array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
