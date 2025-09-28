<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Middleware;

use App\Application\Middleware\PostViewRateLimitMiddleware;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * 中介軟體解析器.
 *
 * 負責解析中介軟體字串別名並從容器中取得實例
 */
class MiddlewareResolver
{
    /**
     * 預定義的中介軟體別名對應.
     */
    private static array $middlewareAliases = [
        'auth' => 'jwt.auth',
        'jwt' => 'jwt.auth',
        'jwt.auth' => 'jwt.auth',
        'authorize' => 'jwt.authorize',
        'jwt.authorize' => 'jwt.authorize',
        'post_view_rate_limit' => PostViewRateLimitMiddleware::class,
    ];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    /**
     * 解析中介軟體（支援字串別名和實例）.
     *
     * @param string|MiddlewareInterface $middleware
     * @throws InvalidArgumentException
     */
    public function resolve($middleware): MiddlewareInterface
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if (is_string($middleware)) {
            // 1. 檢查是否為別名
            $resolvedAlias = $this->resolveAlias($middleware);

            // 2. 嘗試從容器中解析
            if ($this->container->has($resolvedAlias)) {
                $resolved = $this->container->get($resolvedAlias);

                if ($resolved instanceof MiddlewareInterface) {
                    return $resolved;
                }

                throw new InvalidArgumentException(
                    "Container entry '{$resolvedAlias}' does not implement MiddlewareInterface",
                );
            }

            // 3. 如果是類別名稱，嘗試直接從容器解析
            if (class_exists($middleware)) {
                if ($this->container->has($middleware)) {
                    $resolved = $this->container->get($middleware);

                    if ($resolved instanceof MiddlewareInterface) {
                        return $resolved;
                    }
                }
            }

            throw new InvalidArgumentException(
                "Cannot resolve middleware: '{$middleware}' (resolved from '{$resolvedAlias}')",
            );
        }

        throw new InvalidArgumentException(
            'Middleware must be a string or MiddlewareInterface instance',
        );
    }

    /**
     * 解析中介軟體陣列.
     *
     * @param array<string|MiddlewareInterface> $middlewares
     * @return MiddlewareInterface[]
     */
    public function resolveMultiple(array $middlewares): array
    {
        $resolved = [];

        foreach ($middlewares as $middleware) {
            $resolved[] = $this->resolve($middleware);
        }

        return $resolved;
    }

    /**
     * 檢查中介軟體是否可以解析.
     *
     * @param string|MiddlewareInterface $middleware
     */
    public function canResolve($middleware): bool
    {
        if ($middleware instanceof MiddlewareInterface) {
            return true;
        }

        if (is_string($middleware)) {
            $resolvedAlias = $this->resolveAlias($middleware);

            return $this->container->has($resolvedAlias) || class_exists($middleware);
        }

        return false;
    }

    /**
     * 解析中介軟體別名.
     */
    private function resolveAlias(string $alias): string
    {
        return self::$middlewareAliases[$alias] ?? $alias;
    }

    /**
     * 取得所有可用的中介軟體別名.
     *
     * @return array<string, string>
     */
    public function getAliases(): array
    {
        return self::$middlewareAliases;
    }

    /**
     * 註冊新的中介軟體別名.
     */
    public function registerAlias(string $alias, string $target): void
    {
        self::$middlewareAliases[$alias] = $target;
    }
}
