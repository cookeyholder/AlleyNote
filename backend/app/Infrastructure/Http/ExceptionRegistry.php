<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Shared\Enums\HttpStatusCode;
use App\Shared\Exceptions\ApiExceptionInterface;
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

            return $status instanceof HttpStatusCode ? $status : HttpStatusCode::from((int) $status);
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
            ->register('App\Domains\Post\Exceptions\PostNotFoundException', HttpStatusCode::NOT_FOUND)
            ->register('App\Domains\Post\Exceptions\PostStatusException', HttpStatusCode::UNPROCESSABLE_ENTITY)
            ->register('App\Shared\Exceptions\NotFoundException', HttpStatusCode::NOT_FOUND)
            ->register('App\Shared\Exceptions\StateTransitionException', HttpStatusCode::UNPROCESSABLE_ENTITY)
            ->register('App\Shared\Exceptions\ValidationException', HttpStatusCode::UNPROCESSABLE_ENTITY)
            ->register('App\Shared\Exceptions\Validation\RequestValidationException', HttpStatusCode::UNPROCESSABLE_ENTITY)
            ->register('App\Domains\Auth\Exceptions\UnauthorizedException', HttpStatusCode::UNAUTHORIZED)
            ->register('App\Domains\Auth\Exceptions\ForbiddenException', HttpStatusCode::FORBIDDEN)
            ->register('App\Domains\Auth\Exceptions\TokenExpiredException', HttpStatusCode::UNAUTHORIZED)
            ->register('App\Domains\Auth\Exceptions\InvalidTokenException', HttpStatusCode::UNAUTHORIZED)
            ->register('App\Domains\Auth\Exceptions\AuthenticationException', HttpStatusCode::UNAUTHORIZED)
            ->register('App\Shared\Exceptions\CsrfTokenException', HttpStatusCode::FORBIDDEN);
    }
}
