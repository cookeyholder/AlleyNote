<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Middleware;

use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 路由資訊中介軟體.
 *
 * 負責將路由資訊注入到請求物件中
 */
class RouteInfoMiddleware extends AbstractMiddleware
{
    /**
     * 路由名稱.
     */
    private ?string $routeName = null;

    /**
     * 路由模式.
     */
    private ?string $routePattern = null;

    /**
     * HTTP 方法.
     *
     * @var string[]
     */
    private array $methods = [];

    /**
     * 路由處理器.
     */
    private mixed $handler = null;

    /**
     * 建構函式.
     *
     * @param string|null $routeName 路由名稱
     * @param string|null $routePattern 路由模式
     * @param string[] $methods HTTP 方法
     * @param mixed $handler 路由處理器
     * @param int $priority 優先順序
     */
    public function __construct(
        ?string $routeName = null,
        ?string $routePattern = null,
        array $methods = [],
        mixed $handler = null,
        int $priority = -90,
    ) {
        parent::__construct('route-info', $priority);
        $this->routeName = $routeName;
        $this->routePattern = $routePattern;
        $this->methods = $methods;
        $this->handler = $handler;
    }

    protected function execute(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // 注入路由資訊到請求屬性中
        $routeInfo = [
            'name' => $this->routeName,
            'pattern' => $this->routePattern,
            'methods' => $this->methods,
            'handler' => $this->handler,
        ];

        $request = $request->withAttribute('route_info', $routeInfo);

        // 個別注入每個路由資訊
        if ($this->routeName !== null) {
            $request = $request->withAttribute('route_name', $this->routeName);
        }

        if ($this->routePattern !== null) {
            $request = $request->withAttribute('route_pattern', $this->routePattern);
        }

        if (!empty($this->methods)) {
            $request = $request->withAttribute('route_methods', $this->methods);
        }

        if ($this->handler !== null) {
            $request = $request->withAttribute('route_handler', $this->handler);
        }

        return $handler->handle($request);
    }

    /**
     * 設定路由名稱.
     *
     * @param string $name 路由名稱
     */
    public function setRouteName(string $name): self
    {
        $this->routeName = $name;

        return $this;
    }

    /**
     * 設定路由模式.
     *
     * @param string $pattern 路由模式
     */
    public function setRoutePattern(string $pattern): self
    {
        $this->routePattern = $pattern;

        return $this;
    }

    /**
     * 設定 HTTP 方法.
     *
     * @param string[] $methods HTTP 方法
     */
    public function setMethods(array $methods): self
    {
        $this->methods = $methods;

        return $this;
    }

    /**
     * 設定路由處理器.
     *
     * @param mixed $handler 路由處理器
     */
    public function setHandler(mixed $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * 取得路由名稱.
     */
    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    /**
     * 取得路由模式.
     */
    public function getRoutePattern(): ?string
    {
        return $this->routePattern;
    }

    /**
     * 取得 HTTP 方法.
     *
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * 取得路由處理器.
     */
    public function getHandler(): mixed
    {
        return $this->handler;
    }
}
