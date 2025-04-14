<?php

namespace App\Middleware;

use App\Services\RateLimitService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RateLimitService $rateLimitService
    ) {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'];

        if (!$this->rateLimitService->isAllowed($ip)) {
            $response = new \Slim\Psr7\Response();
            return $response
                ->withStatus(429)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(new \Slim\Psr7\Stream(
                    fopen('php://temp', 'r+')
                ))
                ->getBody()
                ->write(json_encode(['error' => '請求過於頻繁，請稍後再試']));
        }

        return $handler->handle($request);
    }
}
