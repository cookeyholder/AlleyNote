<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Contracts;

/**
 * 路由匹配結果.
 *
 * 包含路由匹配的所有相關資訊
 */
class RouteMatchResult
{
    public function __construct(
        private readonly bool $isMatched,
        private readonly ?RouteInterface $route = null,
        private readonly array $parameters = [],
        private readonly ?string $error = null,
    ) {}

    /**
     * 檢查是否成功匹配路由.
     */
    public function isMatched(): bool
    {
        return $this->isMatched;
    }

    /**
     * 取得匹配的路由.
     */
    public function getRoute(): ?RouteInterface
    {
        return $this->route;
    }

    /**
     * 取得路由參數.
     *
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * 取得指定參數值
     */
    public function getParameter(string $name, ?string $default = null): ?string
    {
        return $this->parameters[$name] ?? $default;
    }

    /**
     * 取得錯誤訊息.
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * 建立成功匹配的結果.
     */
    public static function success(RouteInterface $route, array $parameters = []): self
    {
        return new self(true, $route, $parameters);
    }

    /**
     * 建立匹配失敗的結果.
     */
    public static function failed(?string $error = null): self
    {
        return new self(false, null, [], $error);
    }
}
