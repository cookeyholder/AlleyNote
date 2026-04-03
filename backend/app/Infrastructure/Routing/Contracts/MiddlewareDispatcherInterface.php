<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MiddlewareDispatcherInterface
{
    /**
     * 執行中介軟體鏈.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @param MiddlewareInterface[] $middlewares 中介軟體陣列
     * @param RequestHandlerInterface $finalHandler 最終處理器
     * @return ResponseInterface HTTP 回應物件
     */
    public function dispatch(
        ServerRequestInterface $request,
        array $middlewares,
        RequestHandlerInterface $finalHandler,
    ): ResponseInterface;

    /**
     * 建立中介軟體執行鏈.
     *
     * @param MiddlewareInterface[] $middlewares 中介軟體陣列
     * @param RequestHandlerInterface $finalHandler 最終處理器
     * @return RequestHandlerInterface 執行鏈處理器
     */
    public function buildChain(
        array $middlewares,
        RequestHandlerInterface $finalHandler,
    ): RequestHandlerInterface;
}
