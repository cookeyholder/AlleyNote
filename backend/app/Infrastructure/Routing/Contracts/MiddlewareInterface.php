<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 路由中介軟體介面.
 *
 * 定義路由系統中介軟體的標準行為，遵循 PSR-15 中介軟體介面概念
 */
interface MiddlewareInterface
{
    /**
     * 處理請求
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @param RequestHandlerInterface $handler 請求處理器
     * @return ResponseInterface HTTP 回應物件
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface;

    /**
     * 取得中介軟體優先順序.
     *
     * 數值越小優先級越高，預設為 0
     *
     * @return int 優先順序
     */
    public function getPriority(): int;

    /**
     * 取得中介軟體名稱.
     *
     * @return string 中介軟體名稱
     */
    public function getName(): string;

    /**
     * 檢查中介軟體是否應該執行.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return bool 是否應該執行
     */
    public function shouldProcess(ServerRequestInterface $request): bool;
}
