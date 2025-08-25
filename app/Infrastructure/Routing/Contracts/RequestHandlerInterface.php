<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 請求處理器介面.
 *
 * 定義請求處理器的標準行為，用於中介軟體鏈中的下一個處理器
 */
interface RequestHandlerInterface
{
    /**
     * 處理 HTTP 請求
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return ResponseInterface HTTP 回應物件
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;
}
