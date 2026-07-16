<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Shared\Config\EnvironmentConfig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * CSRF Token 控制器
 *
 * 提供 CSRF Token 的發放與重新整理功能。
 */
class CsrfTokenController extends BaseController
{
    private const COOKIE_NAME = 'csrf_token';

    private const TOKEN_LENGTH = 32;

    public function __construct(
        private EnvironmentConfig $config,
    ) {}

    /**
     * 取得新的 CSRF Token.
     *
     * 產生新的 CSRF Token，設定到 Cookie 中，並回傳 Token 值供前端使用。
     */
    public function getToken(Request $request, Response $response): Response
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));

        $isSecure = $this->config->getEnvironment() === 'production';

        $cookieValue = $this->buildCookieHeader(self::COOKIE_NAME, $token, $isSecure);

        $body = json_encode(['token' => $token], JSON_UNESCAPED_UNICODE);

        $response->getBody()->write($body !== false ? $body : '{"token":""}');

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200)
            ->withAddedHeader('Set-Cookie', $cookieValue);
    }

    /**
     * 建立 CSRF Cookie Header 字串.
     */
    private function buildCookieHeader(string $name, string $value, bool $isSecure): string
    {
        $parts = [
            sprintf('%s=%s', $name, $value),
            'Path=/',
            'SameSite=Strict',
        ];
        if ($isSecure) {
            $parts[] = 'Secure';
        }

        return implode('; ', $parts);
    }
}
