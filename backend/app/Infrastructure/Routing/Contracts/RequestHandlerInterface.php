<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Contracts;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
