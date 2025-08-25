<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 中介軟體管理器介面.
 *
 * 定義中介軟體管理器的標準行為，負責管理和執行中介軟體鏈
 */
interface MiddlewareManagerInterface
{
    /**
     * 新增中介軟體.
     *
     * @param MiddlewareInterface $middleware 中介軟體實例
     */
    public function add(MiddlewareInterface $middleware): self;

    /**
     * 新增多個中介軟體.
     *
     * @param MiddlewareInterface[] $middlewares 中介軟體陣列
     */
    public function addMultiple(array $middlewares): self;

    /**
     * 移除指定名稱的中介軟體.
     *
     * @param string $name 中介軟體名稱
     */
    public function remove(string $name): self;

    /**
     * 清除所有中介軟體.
     */
    public function clear(): self;

    /**
     * 檢查是否存在指定名稱的中介軟體.
     *
     * @param string $name 中介軟體名稱
     */
    public function has(string $name): bool;

    /**
     * 取得指定名稱的中介軟體.
     *
     * @param string $name 中介軟體名稱
     */
    public function get(string $name): ?MiddlewareInterface;

    /**
     * 取得所有中介軟體.
     *
     * @return MiddlewareInterface[]
     */
    public function getAll(): array;

    /**
     * 取得按優先順序排序的中介軟體.
     *
     * @return MiddlewareInterface[]
     */
    public function getSorted(): array;

    /**
     * 處理請求透過中介軟體鏈.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @param RequestHandlerInterface $finalHandler 最終處理器
     * @return ResponseInterface HTTP 回應物件
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $finalHandler,
    ): ResponseInterface;

    /**
     * 取得中介軟體數量.
     */
    public function count(): int;
}
