<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Shared\Enums\HttpStatusCode;
use App\Shared\Enums\JsonFlag;
use App\Shared\Http\ApiResponse;
use Exception;
use Psr\Http\Message\ResponseInterface;

abstract class BaseController
{
    /** @var array<string, HttpStatusCode> */
    private const EXCEPTION_HTTP_CODES = [
        'App\Exceptions\Post\PostNotFoundException' => HttpStatusCode::NOT_FOUND,
        'App\Exceptions\Post\PostStatusException' => HttpStatusCode::BAD_REQUEST,
        'App\Exceptions\Post\PostValidationException' => HttpStatusCode::UNPROCESSABLE_ENTITY,
        'App\Exceptions\NotFoundException' => HttpStatusCode::NOT_FOUND,
        'App\Exceptions\StateTransitionException' => HttpStatusCode::CONFLICT,
        'App\Exceptions\ValidationException' => HttpStatusCode::UNPROCESSABLE_ENTITY,
        'App\Exceptions\Validation\RequestValidationException' => HttpStatusCode::UNPROCESSABLE_ENTITY,
        'App\Exceptions\Auth\UnauthorizedException' => HttpStatusCode::UNAUTHORIZED,
        'App\Exceptions\Auth\ForbiddenException' => HttpStatusCode::FORBIDDEN,
        'App\Exceptions\CsrfTokenException' => HttpStatusCode::FORBIDDEN,
    ];

    /**
     * 建立JSON回應.
     */
    protected function json(
        ResponseInterface $response,
        array $data,
        HttpStatusCode|int $status = HttpStatusCode::OK,
        JsonFlag $jsonFlag = JsonFlag::DEFAULT,
    ): ResponseInterface {
        $json = json_encode($data, $jsonFlag->value) ?: $this->getFallbackJson();

        $response->getBody()->write($json);

        $statusCode = $status instanceof HttpStatusCode ? $status->value : (int) $status;

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    protected function jsonResponse(array $data, HttpStatusCode|int $httpCode = HttpStatusCode::OK): string
    {
        $code = $httpCode instanceof HttpStatusCode ? $httpCode->value : (int) $httpCode;
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');

    return json_encode($data, JsonFlag::DEFAULT->value) ?: '{}';
    }

    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
    ): string {
        return $this->jsonResponse(ApiResponse::success($data, $message));
    }

    protected function errorResponse(
        string $message,
        HttpStatusCode|int $httpCode = HttpStatusCode::BAD_REQUEST,
        mixed $errors = null,
    ): string {
        $code = $httpCode instanceof HttpStatusCode ? $httpCode->value : (int) $httpCode;

        return $this->jsonResponse(
            ApiResponse::error($message, $code, $errors),
            $httpCode,
        );
    }

    protected function paginatedResponse(
        array $data,
        int $total,
        int $page,
        int $perPage,
    ): string {
        return $this->jsonResponse(
            ApiResponse::paginated($data, $total, $page, $perPage),
        );
    }

    protected function handleException(Exception $e): string
    {
        // 記錄錯誤日誌
        error_log(sprintf(
            'API Error: %s in %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
        ));

        $httpCode = $this->getHttpCodeFromException($e);

        return $this->errorResponse($e->getMessage(), $httpCode);
    }

    private function getHttpCodeFromException(Exception $e): HttpStatusCode
    {
        $className = get_class($e);
        if (array_key_exists($className, self::EXCEPTION_HTTP_CODES)) {
            return self::EXCEPTION_HTTP_CODES[$className];
        }

        return HttpStatusCode::INTERNAL_SERVER_ERROR;
    }

    private function getFallbackJson(): string
    {
        return '{"success":false,"error":{"message":"JSON encoding failed"}}';
    }
}
