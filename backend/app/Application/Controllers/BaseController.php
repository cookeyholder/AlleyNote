<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Infrastructure\Http\ExceptionRegistry;
use App\Shared\Enums\HttpStatusCode;
use App\Shared\Enums\JsonFlag;
use App\Shared\Http\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Throwable;

abstract class BaseController
{
    private static ?ExceptionRegistry $exceptionRegistry = null;

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

    protected function handleException(Throwable $e): string
    {
        // 記錄錯誤日誌
        app_log('error', 'API Error', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
        $httpCode = $this->getHttpCodeFromException($e);

        return $this->errorResponse($e->getMessage(), $httpCode);
    }

    private function getHttpCodeFromException(Throwable $e): HttpStatusCode
    {
        $status = self::getExceptionRegistry()->resolve($e);
        if ($status !== null) {
            return $status;
        }

        return HttpStatusCode::INTERNAL_SERVER_ERROR;
    }

    private function getFallbackJson(): string
    {
        return '{"success":false,"error":{"message":"JSON encoding failed"}}';
    }

    private static function getExceptionRegistry(): ExceptionRegistry
    {
        if (self::$exceptionRegistry === null) {
            self::$exceptionRegistry = ExceptionRegistry::createDefault();
        }

        return self::$exceptionRegistry;
    }
}
