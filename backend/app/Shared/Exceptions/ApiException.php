<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Enums\HttpStatusCode;
use RuntimeException;
use Throwable;

abstract class ApiException extends RuntimeException implements ApiExceptionInterface
{
    public function __construct(
        string $message,
        private readonly HttpStatusCode|int $httpStatusCode,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getHttpStatusCode(): HttpStatusCode|int
    {
        return $this->httpStatusCode;
    }
}
