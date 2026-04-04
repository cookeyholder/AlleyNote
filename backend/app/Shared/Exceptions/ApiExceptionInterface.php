<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Enums\HttpStatusCode;

interface ApiExceptionInterface
{
    public function getHttpStatusCode(): HttpStatusCode|int;
}
