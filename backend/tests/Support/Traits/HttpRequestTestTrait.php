<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Stream;
use App\Infrastructure\Http\Uri;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP 請求測試功能 Trait.
 *
 * 提供標準化的 PSR-7 請求建構工具，支援 JWT 與 JSON Body 注入。
 */
trait HttpRequestTestTrait
{
    /**
     * 建立一個標準的 API 請求實體.
     *
     * @param string $method HTTP 方法
     * @param string $path 路徑 (例如 /api/posts)
     * @param array $headers 額外標頭
     * @return ServerRequestInterface
     */
    protected function createRequest(
        string $method = 'GET',
        string $path = '/',
        array $headers = [],
    ): ServerRequestInterface {
        // 確保 path 格式正確並包含 Host
        $normalizedPath = '/' . ltrim($path, '/');
        $uri = new Uri('http://localhost' . $normalizedPath);

        // 預設 API 標頭
        $defaultHeaders = [
            'Accept' => 'application/json',
        ];

        return new ServerRequest(
            $method,
            $uri,
            array_merge($defaultHeaders, $headers)
        );
    }

    /**
     * 對請求注入 JWT Bearer Token.
     *
     * @param ServerRequestInterface $request 原始請求
     * @param string $token JWT Token 字串
     * @return ServerRequestInterface 新的請求實體
     */
    protected function withJwtAuth(ServerRequestInterface $request, string $token): ServerRequestInterface
    {
        return $request->withHeader('Authorization', 'Bearer ' . $token);
    }

    /**
     * 對請求注入 JSON Body.
     *
     * @param ServerRequestInterface $request 原始請求
     * @param array $data 欲編碼的資料
     * @return ServerRequestInterface 新的請求實體
     */
    protected function withJsonBody(ServerRequestInterface $request, array $data): ServerRequestInterface
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write($json ?: '');

        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }
}
