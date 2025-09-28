<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Shared\Http\ApiResponse;
use Exception;
use Psr\Http\Message\ResponseInterface;

enum JsonFlag: int
{
    case DEFAULT = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
    case COMPACT = JSON_UNESCAPED_UNICODE;
    case DEBUG = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR;
}

abstract class BaseController
{
    private const EXCEPTION_HTTP_CODES = [
        'App\Exceptions\Post\PostNotFoundException' => 404,
        'App\Exceptions\Post\PostStatusException' => 400,
        'App\Exceptions\Post\PostValidationException' => 422,
        'App\Exceptions\NotFoundException' => 404,
        'App\Exceptions\StateTransitionException' => 409,
        'App\Exceptions\ValidationException' => 422,
        'App\Exceptions\Validation\RequestValidationException' => 422,
        'App\Exceptions\Auth\UnauthorizedException' => 401,
        'App\Exceptions\Auth\ForbiddenException' => 403,
        'App\Exceptions\CsrfTokenException' => 403,
    ];

    /**
     * 建立JSON回應.
     */
    protected function json(
        ResponseInterface $response,
        array $data,
        int $status = 200,
        JsonFlag $jsonFlag = JsonFlag::DEFAULT,
    ): ResponseInterface {
        $json = json_encode($data, $jsonFlag->value) ?: $this->getFallbackJson();

        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    protected function jsonResponse(array $data, int $httpCode = 200): string
    {
        http_response_code($httpCode);
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
        int $httpCode = 400,
        mixed $errors = null,
    ): string {
        return $this->jsonResponse(
            ApiResponse::error($message, $httpCode, $errors),
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

    private function getHttpCodeFromException(Exception $e): int
    {
        $className = get_class($e);

        return array_key_exists($className, self::EXCEPTION_HTTP_CODES)
            ? self::EXCEPTION_HTTP_CODES[$className]
            : 500;
    }

    private function getFallbackJson(): string
    {
        return '{"success":false,"error":{"message":"JSON encoding failed"}}';
    }
}
