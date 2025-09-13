<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * PSR-7 ServerRequest 工廠.
 */
class ServerRequestFactory
{
    public static function fromGlobals(): ServerRequestInterface
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = self::createUriFromGlobals();
        $headers = self::parseHeaders();
        $body = file_get_contents('php://input');

        $request = new ServerRequest((string) $method, $uri, $headers, $body, '1.1', $_SERVER);

        // 設定查詢參數
        if (!empty($_GET)) {
            $request = $request->withQueryParams($_GET);
        }

        // 設定 Cookie 參數
        if (!empty($_COOKIE)) {
            $request = $request->withCookieParams($_COOKIE);
        }

        // 解析 POST 資料
        if ($method === 'POST' && !empty($_POST)) {
            $request = $request->withParsedBody((array) $_POST);
        } elseif ($method === 'POST' && strpos($request->getHeaderLine('Content-Type'), 'application/json') === 0) {
            $jsonData = json_decode(is_string($body) ? $body : (string) $body, true);
            if ($jsonData !== null) {
                $request = $request->withParsedBody((array) $jsonData);
            }
        }

        return $request;
    }

    private static function createUriFromGlobals(): Uri
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $port = isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : null;
        $path = $_SERVER['REQUEST_URI'] ?? '/';

        // 移除查詢字串
        if (($pos = strpos((string) $path, '?')) !== false) {
            $path = substr((string) $path, 0, $pos);
        }

        $uri = new Uri();
        $uri = $uri->withScheme($scheme)
            ->withHost((string) $host)
            ->withPath((string) $path);

        if ($port !== null && !self::isDefaultPort($scheme, $port)) {
            $uri = $uri->withPort($port);
        }

        if (!empty($_SERVER['QUERY_STRING'])) {
            $uri = $uri->withQuery((string) $_SERVER['QUERY_STRING']);
        }

        return $uri;
    }

    private static function isDefaultPort(string $scheme, int $port): bool
    {
        return ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
    }

    /**
     * @return array<string, array<string>>
     */
    private static function parseHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = [(string) $value];
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = [(string) $value];
            }
        }

        return $headers;
    }
}
