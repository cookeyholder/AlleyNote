<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 閉包請求處理器.
 *
 * 將閉包包裝成 RequestHandlerInterface
 */
class ClosureRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private Closure $handler,
    ) {}

    /**
     * 處理請求
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = ($this->handler)($request);

        if (!$response instanceof ResponseInterface) {
            throw new \RuntimeException('Closure must return an instance of ResponseInterface');
        }

        return $response;
    }
}
