<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Stream;
use App\Infrastructure\Http\Uri;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP 請求測試功能 Trait.
 */
trait HttpRequestTestTrait
{
    /**
     * 建立標準測試請求.
     */
    protected function createRequest(
        string $method = 'GET',
        string $path = '/',
        array $headers = [],
    ): ServerRequestInterface {
        $uri = new Uri();
        $uri = $uri->withPath($path);

        $defaultHeaders = [
            'Host' => 'localhost',
            'User-Agent' => 'PHPUnit',
            'Accept' => 'application/json',
        ];

        return new ServerRequest(
            $method,
            $uri,
            array_merge($defaultHeaders, $headers),
        );
    }

    /**
     * 對請求注入 JSON Body.
     */
    protected function withJsonBody(ServerRequestInterface $request, array $data): ServerRequestInterface
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write($json ?: '');
        $stream->rewind();

        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream)
            ->withParsedBody($data);
    }

    /**
     * 對請求注入 JWT 認證標頭.
     */
    protected function withJwtAuth(ServerRequestInterface $request, string $token): ServerRequestInterface
    {
        return $request->withHeader('Authorization', 'Bearer ' . $token);
    }
}
