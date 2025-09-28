<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Enums\JsonFlag;
use App\Shared\Enums\HttpStatusCode;
use App\Shared\Http\ApiResponse;
use Exception;
use Psr\Http\Message\ResponseInterface;

abstract class BaseController
{
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
        HttpStatusCode $status = HttpStatusCode::OK,
        JsonFlag $jsonFlag = JsonFlag::DEFAULT,
    ): ResponseInterface {
        $json = json_encode($data, $jsonFlag->value) ?: $this->getFallbackJson();

        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status->value);
    }

    protected function jsonResponse(array $data, HttpStatusCode $httpCode = HttpStatusCode::OK): string
    {
        http_response_code($httpCode->value);
        header('Content-Type: application/json; charset=utf-8');

        return json_encode($data, JsonFlag::COMPACT->value) ?: '{}';
    }

    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
    ): string {
        return $this->jsonResponse(ApiResponse::success($data, $message));
    }

    protected function errorResponse(
        string $message,
        HttpStatusCode $httpCode = HttpStatusCode::BAD_REQUEST,
        mixed $errors = null,
    ): string {
        return $this->jsonResponse(
            ApiResponse::error($message, $httpCode->value, $errors),
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

        return this->errorResponse($e->getMessage(), $httpCode);
    }

    private function getHttpCodeFromException(Exception $e): HttpStatusCode
    {
        $className = get_class($e);

        return self::EXCEPTION_HTTP_CODES[$className] ?? HttpStatusCode::INTERNAL_SERVER_ERROR;
    }

    private function getFallbackJson(): string
    {
        return '{"success":false,"error":{"message":"JSON encoding failed"}}';
    }
}
