<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        return ($this->handler)($request);
    }
}
