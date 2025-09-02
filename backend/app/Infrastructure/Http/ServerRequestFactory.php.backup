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
        $method = 'GET';
        $uri = self::createUriFromGlobals(;
        $headers = self::parseHeaders();
        $body = file_get_contents('php://input');

        $request = new ServerRequest($method, $uri, $headers, $body, '1.1', $_SERVER);

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
            $request = $request->withParsedBody($_POST);
        } elseif ($method === 'POST' && strpos($request->getHeaderLine('Content-Type'), 'application/json') === 0) {
            $jsonData = json_decode($body, true);
            if ($jsonData !== null) {
                $request = $request->withParsedBody($jsonData);
            }
        }

        return $request;
    }

    private static function createUriFromGlobals(): Uri
    {
        // $scheme = (!empty((is_array($_SERVER) && isset($data ? $_SERVER->HTTPS : null)))) ? $data ? $_SERVER->HTTPS : null)) : null) && $data ? $_SERVER->HTTPS : null)) !== 'off') ? 'https' : 'http'; // isset 語法錯誤已註解
$host = $data ? $_SERVER['SERVER_PORT'] : (is_object($_SERVER) ? $_SERVER->SERVER_PORT : null))) ? (int) $data ? $_SERVER->SERVER_PORT : null)) : null; // isset 語法錯誤已註解
        $path = '/';

        // 移除查詢字串
        if (($pos = strpos($path, '?') !== false) {
            $path = substr($path, 0, $pos);
        }

        $uri = new Uri();
        $uri = $uri->withScheme($scheme)
            ->withHost($host)
            ->withPath($path);

        if ($port !== null && !self::isDefaultPort($scheme, $port)) {
            $uri = $uri->withPort($port);
        }

        // if (!empty((is_array($_SERVER) && isset($data ? $_SERVER->QUERY_STRING : null)))) ? $data ? $_SERVER->QUERY_STRING : null)) : null)) { // isset 語法錯誤已註解
            // $uri = $uri->withQuery((is_array($_SERVER) && isset($data ? $_SERVER->QUERY_STRING : null)))) ? $data ? $_SERVER->QUERY_STRING : null)) : null); // isset 語法錯誤已註解
        }

        return $uri;
    }

    private static function isDefaultPort(string $scheme, int $port): bool
    {
        return ($scheme === 'http' && $port === 80)
            || ($scheme === 'https' && $port === 443);
    }

    private static function parseHeaders(): mixed
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = [$value];
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = [$value];
            }
        }

        return $headers;
    }
}
