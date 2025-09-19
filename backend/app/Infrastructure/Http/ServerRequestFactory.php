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

        // 正規化 $_SERVER 為 array<string,mixed>
        $serverParams = self::normalizeServerParams($_SERVER);

        $request = new ServerRequest((string) $method, $uri, $headers, $body, '1.1', $serverParams);

        // 設定查詢參數
        if (!empty($_GET)) {
            $request = $request->withQueryParams(self::normalizeStringParams($_GET));
        }

        // 設定 Cookie 參數
        if (!empty($_COOKIE)) {
            $request = $request->withCookieParams(self::normalizeStringParams($_COOKIE));
        }

        // 解析 POST 資料
        if ($method === 'POST' && !empty($_POST)) {
            $request = $request->withParsedBody(self::normalizeParsedBody((array) $_POST));
        } elseif ($method === 'POST' && strpos($request->getHeaderLine('Content-Type'), 'application/json') === 0) {
            $jsonData = json_decode(is_string($body) ? $body : (string) $body, true);
            if ($jsonData !== null) {
                $request = $request->withParsedBody(self::normalizeParsedBody((array) $jsonData));
            }
        }

        return $request;
    }

    /**
     * 正規化 server params 為 array<string,mixed>.
     *
     * @param array<mixed> $server
     * @return array<string,mixed>
     */
    private static function normalizeServerParams(array $server): array
    {
        $out = [];
        foreach ($server as $k => $v) {
            $key = (string) $k;
            if (is_array($v)) {
                $out[$key] = $v; // 保留原陣列（mixed）
                continue;
            }

            if (is_null($v)) {
                $out[$key] = null;
                continue;
            }

            if (is_scalar($v)) {
                $out[$key] = $v;
                continue;
            }

            $out[$key] = (string) $v;
        }

        return $out;
    }

    /**
     * 將參數陣列轉成 array<string,string>（若為陣列值則取第一個值並轉為字串）.
     *
     * @param array<mixed> $params
     * @return array<string,string>
     */
    private static function normalizeStringParams(array $params): array
    {
        $out = [];
        foreach ($params as $k => $v) {
            $key = (string) $k;
            if (is_array($v)) {
                $first = reset($v);
                $out[$key] = is_scalar($first) ? (string) $first : (string) (json_encode($first) ?: '');
                continue;
            }

            $out[$key] = is_scalar($v) ? (string) $v : (string) (json_encode($v) ?: '');
        }

        return $out;
    }

    /**
     * 正規化 parsed body 為 array<string,mixed>.
     *
     * @param array<mixed> $data
     * @return array<string,mixed>
     */
    private static function normalizeParsedBody(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $out[(string) $k] = $v;
        }

        return $out;
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
