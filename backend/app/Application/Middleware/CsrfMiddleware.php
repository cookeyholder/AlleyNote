<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Infrastructure\Http\Response;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CsrfMiddleware implements MiddlewareInterface
{
    private const DEFAULT_PRIORITY = 15;
    private const MIDDLEWARE_NAME = 'csrf';

    public function __construct(
        private int $priority = self::DEFAULT_PRIORITY,
        private bool $enabled = true,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->getHeaderLine('X-CSRF-TOKEN');
            $sessionToken = $request->getAttribute('csrf_token');

            if (empty($token) || empty($sessionToken) || !hash_equals($sessionToken, $token)) {
                return new Response(
                    statusCode: 403,
                    headers: ['Content-Type' => 'application/json'],
                    body: json_encode(['success' => false, 'error' => '無效的 CSRF Token', 'code' => 'CSRF_INVALID'], JSON_UNESCAPED_UNICODE),
                );
            }
        }

        return $handler->handle($request);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getName(): string
    {
        return self::MIDDLEWARE_NAME;
    }

    public function shouldProcess(ServerRequestInterface $request): bool
    {
        return $this->enabled;
    }
}
