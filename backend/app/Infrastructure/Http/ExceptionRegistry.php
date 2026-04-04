<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domains\Auth\Exceptions\AuthenticationException;
use App\Domains\Auth\Exceptions\ForbiddenException;
use App\Domains\Auth\Exceptions\InvalidTokenException;
use App\Domains\Auth\Exceptions\TokenExpiredException;
use App\Domains\Auth\Exceptions\UnauthorizedException;
use App\Domains\Post\Exceptions\PostNotFoundException;
use App\Domains\Post\Exceptions\PostStatusException;
use App\Shared\Enums\HttpStatusCode;
use App\Shared\Exceptions\ApiExceptionInterface;
use App\Shared\Exceptions\CsrfTokenException;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\StateTransitionException;
use App\Shared\Exceptions\Validation\RequestValidationException;
use App\Shared\Exceptions\ValidationException;
use Throwable;

class ExceptionRegistry
{
    /** @var array<class-string, HttpStatusCode> */
    private array $classMappings = [];

    /** @var array<class-string, HttpStatusCode> */
    private array $interfaceMappings = [];

    /**
     * @param class-string<Throwable> $exceptionClass
     */
    public function register(string $exceptionClass, HttpStatusCode $status): self
    {
        $this->classMappings[$exceptionClass] = $status;

        return $this;
    }

    /**
     * @param class-string $interfaceClass
     */
    public function registerInterface(string $interfaceClass, HttpStatusCode $status): self
    {
        $this->interfaceMappings[$interfaceClass] = $status;

        return $this;
    }

    public function resolve(Throwable $exception): ?HttpStatusCode
    {
        if ($exception instanceof ApiExceptionInterface) {
            $status = $exception->getHttpStatusCode();

            return $status instanceof HttpStatusCode
                ? $status
                : HttpStatusCode::tryFrom((int) $status) ?? HttpStatusCode::INTERNAL_SERVER_ERROR;
        }

        $exceptionClass = get_class($exception);

        if (isset($this->classMappings[$exceptionClass])) {
            return $this->classMappings[$exceptionClass];
        }

        foreach ($this->interfaceMappings as $interface => $status) {
            if ($exception instanceof $interface) {
                return $status;
            }
        }

        foreach (class_parents($exceptionClass) ?: [] as $parentClass) {
            if (isset($this->classMappings[$parentClass])) {
                return $this->classMappings[$parentClass];
            }
        }

        return null;
    }

    public static function createDefault(): self
    {
        return new self()
            ->register(PostNotFoundException::class, HttpStatusCode::NOT_FOUND)
            ->register(PostStatusException::class, HttpStatusCode::UNPROCESSABLE_ENTITY)
            ->register(NotFoundException::class, HttpStatusCode::NOT_FOUND)
            ->register(StateTransitionException::class, HttpStatusCode::UNPROCESSABLE_ENTITY)
            ->register(ValidationException::class, HttpStatusCode::UNPROCESSABLE_ENTITY)
            ->register(RequestValidationException::class, HttpStatusCode::UNPROCESSABLE_ENTITY)
            ->register(UnauthorizedException::class, HttpStatusCode::UNAUTHORIZED)
            ->register(ForbiddenException::class, HttpStatusCode::FORBIDDEN)
            ->register(TokenExpiredException::class, HttpStatusCode::UNAUTHORIZED)
            ->register(InvalidTokenException::class, HttpStatusCode::UNAUTHORIZED)
            ->register(AuthenticationException::class, HttpStatusCode::UNAUTHORIZED)
            ->register(CsrfTokenException::class, HttpStatusCode::FORBIDDEN);
    }
}
