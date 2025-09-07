<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Shared\Http\ApiResponse;
use Exception;
use Psr\Http\Message\ResponseInterface;

abstract class BaseController
{
    /**
     * 建立JSON回應.
     *
     * @param array<string, mixed> $data
     */
    protected function json(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            // JSON 編碼失敗時的回退處理
            $json = '{"success":false,"error":{"message":"JSON encoding failed"}}';
        }

        $response->getBody()->write($json);

        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    /**
     * JSON響應輔助方法.
     *
     * @param array<string, mixed> $data
     */
    protected function jsonResponse(array $data, int $httpCode = 200): string
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');

        $jsonString = json_encode($data, JSON_UNESCAPED_UNICODE);

        return $jsonString !== false ? $jsonString : '{}';
    }

    protected function successResponse(mixed $data = null, string $message = 'Success'): string
    {
        /** @var array<string, mixed> $responseData */
        $responseData = ApiResponse::success($data, $message);

        return $this->jsonResponse($responseData);
    }

    protected function errorResponse(string $message, int $httpCode = 400, mixed $errors = null): string
    {
        /** @var array<string, mixed> $responseData */
        $responseData = ApiResponse::error($message, $httpCode, $errors);

        return $this->jsonResponse($responseData, $httpCode);
    }

    /**
     * 分頁響應輔助方法.
     *
     * @param array<string, mixed> $data
     */
    protected function paginatedResponse(array $data, int $total, int $page, int $perPage): string
    {
        /** @var array<string, mixed> $responseData */
        $responseData = ApiResponse::paginated($data, $total, $page, $perPage);

        return $this->jsonResponse($responseData);
    }

    protected function handleException(Exception $e): string
    {
        // 記錄錯誤日誌
        error_log('API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        // 根據例外類型回傳適當的 HTTP 狀態碼
        $httpCode = $this->getHttpCodeFromException($e);

        return $this->errorResponse($e->getMessage(), $httpCode);
    }

    private function getHttpCodeFromException(Exception $e): int
    {
        // 根據例外類型映射 HTTP 狀態碼
        $className = get_class($e);

        switch ($className) {
            // Post 相關例外
            case 'App\Exceptions\Post\PostNotFoundException':
                return 404;
            case 'App\Exceptions\Post\PostStatusException':
                return 400;
            case 'App\Exceptions\Post\PostValidationException':
                return 422;
                // 通用例外
            case 'App\Exceptions\NotFoundException':
                return 404;
            case 'App\Exceptions\StateTransitionException':
                return 400;
            case 'App\Exceptions\ValidationException':
                return 422;
                // 驗證相關例外
            case 'App\Exceptions\Validation\RequestValidationException':
                return 422;
                // 認證授權相關例外
            case 'App\Exceptions\Auth\UnauthorizedException':
                return 401;
            case 'App\Exceptions\Auth\ForbiddenException':
                return 403;
            case 'App\Exceptions\CsrfTokenException':
                return 403;
            default:
                return 500;
        }
    }
}
