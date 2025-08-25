<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Contracts;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 路由介面.
 *
 * 定義單一路由的基本結構和行為
 */
interface RouteInterface
{
    /**
     * 取得路由的 HTTP 方法.
     *
     * @return string[]
     */
    public function getMethods(): array;

    /**
     * 取得路由模式.
     */
    public function getPattern(): string;

    /**
     * 取得路由名稱.
     */
    public function getName(): ?string;

    /**
     * 設定路由名稱.
     */
    public function setName(string $name): self;

    /**
     * 取得路由處理器.
     *
     * @return callable|string 處理器
     */
    public function getHandler(): callable|string;

    /**
     * 新增中介軟體.
     *
     * @param MiddlewareInterface $middleware 中介軟體實例
     */
    public function addMiddleware(MiddlewareInterface $middleware): self;

    /**
     * 新增中介軟體（別名方法）.
     *
     * @param MiddlewareInterface|string $middleware 中介軟體實例或類別名稱
     */
    public function middleware(MiddlewareInterface|string $middleware): self;

    /**
     * 新增多個中介軟體.
     *
     * @param MiddlewareInterface[] $middlewares 中介軟體陣列
     */
    public function addMiddlewares(array $middlewares): self;

    /**
     * 取得路由的中介軟體.
     *
     * @return MiddlewareInterface[]
     */
    public function getMiddlewares(): array;

    /**
     * 檢查路由是否匹配指定的 HTTP 方法.
     *
     * @param string $method HTTP 方法
     */
    public function matchesMethod(string $method): bool;

    /**
     * 檢查路由是否匹配指定的 URI 路徑.
     *
     * @param string $path URI 路徑
     * @return RouteMatchResult 匹配結果
     */
    public function matchesPath(string $path): RouteMatchResult;

    /**
     * 檢查路由是否完全匹配請求.
     *
     * @param ServerRequestInterface $request HTTP 請求
     * @return RouteMatchResult 匹配結果
     */
    public function matches(ServerRequestInterface $request): RouteMatchResult;

    /**
     * 為路由生成 URL.
     *
     * @param array<string, scalar> $parameters 路由參數
     * @param array<string, scalar> $queryParams 查詢參數
     * @return string 生成的 URL
     * @throws InvalidArgumentException 當參數無效時
     */
    public function generateUrl(array $parameters = [], array $queryParams = []): string;

    /**
     * 從路徑中提取參數.
     *
     * @param string $path URI 路徑
     * @return array<string, string> 提取的參數
     */
    public function extractParameters(string $path): array;

    /**
     * 克隆路由並設定新的屬性.
     *
     * @param array<string, mixed> $attributes 新的屬性
     */
    public function withAttributes(array $attributes): self;
}
