<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Middleware;

use App\Infrastructure\Routing\Contracts\MiddlewareDispatcherInterface;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\MiddlewareManagerInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 中介軟體管理器.
 *
 * 負責管理和執行路由中介軟體
 */
class MiddlewareManager implements MiddlewareManagerInterface
{
    /**
     * 中介軟體存儲.
     *
     * @var MiddlewareInterface[]
     */
    private array $middlewares = [];

    /**
     * 中介軟體執行器.
     */
    private MiddlewareDispatcherInterface $dispatcher;

    /**
     * 建構函式.
     *
     * @param MiddlewareDispatcherInterface $dispatcher 中介軟體執行器
     */
    public function __construct(MiddlewareDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[$middleware->getName()] = $middleware;

        return $this;
    }

    public function addMultiple(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof MiddlewareInterface) {
                $this->add($middleware);
            }
        }

        return $this;
    }

    public function remove(string $name): self
    {
        unset($this->middlewares[$name]);

        return $this;
    }

    public function clear(): self
    {
        $this->middlewares = [];

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->middlewares[$name]);
    }

    public function get(string $name): ?MiddlewareInterface
    {
        return $this->middlewares[$name] ?? null;
    }

    public function getAll(): array
    {
        return array_values($this->middlewares);
    }

    public function getSorted(): array
    {
        $middlewares = $this->getAll();

        // 按優先順序排序（數值越小優先級越高）
        usort($middlewares, function (MiddlewareInterface $a, MiddlewareInterface $b): int {
            return $a->getPriority() <=> $b->getPriority();
        });

        return $middlewares;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $finalHandler,
    ): ResponseInterface {
        $middlewares = $this->getSorted();

        // 過濾出應該執行的中介軟體，並重新索引陣列
        $activeMiddlewares = array_values(array_filter(
            $middlewares,
            fn(MiddlewareInterface $middleware): bool => $middleware->shouldProcess($request),
        ));

        return $this->dispatcher->dispatch($request, $activeMiddlewares, $finalHandler);
    }

    public function count(): int
    {
        return count($this->middlewares);
    }

    /**
     * 取得中介軟體名稱列表.
     *
     * @return string[]
     */
    public function getNames(): array
    {
        return array_keys($this->middlewares);
    }

    /**
     * 批次設定中介軟體優先順序.
     *
     * @param array<string, int> $priorities 中介軟體名稱與優先順序對應表
     */
    public function setPriorities(array $priorities): self
    {
        foreach ($priorities as $name => $priority) {
            $middleware = $this->middlewares[$name] ?? null;
            if ($middleware instanceof AbstractMiddleware) {
                $middleware->setPriority($priority);
            }
        }

        return $this;
    }

    /**
     * 批次啟用/停用中介軟體.
     *
     * @param array<string, bool> $states 中介軟體名稱與狀態對應表
     */
    public function setStates(array $states): self
    {
        foreach ($states as $name => $enabled) {
            $middleware = $this->middlewares[$name] ?? null;
            if ($middleware instanceof AbstractMiddleware) {
                $enabled ? $middleware->enable() : $middleware->disable();
            }
        }

        return $this;
    }
}
