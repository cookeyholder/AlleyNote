<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Middleware;

use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 路由參數中介軟體.
 *
 * 負責處理路由參數，將路由參數注入到請求物件中
 */
class RouteParametersMiddleware extends AbstractMiddleware
{
    /**
     * 路由參數.
     *
     * @var array<mixed>
     */
    private array $parameters = [];

    /**
     * 建構函式.
     *
     * @param array $parameters 路由參數
     * @param int $priority 優先順序
     */
    public function __construct(array $parameters = [], int $priority = -100)
    {
        parent::__construct('route-parameters', $priority);
        $this->parameters = $parameters;
    }

    protected function execute(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // 將路由參數加入到請求屬性中
        foreach ($this->parameters as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        // 加入特殊的路由參數集合屬性
        $request = $request->withAttribute('route_parameters', $this->parameters);

        return $handler->handle($request);
    }

    /**
     * 設定路由參數.
     *
     * @param array $parameters 路由參數
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * 新增單一路由參數.
     *
     * @param string $name 參數名稱
     * @param mixed $value 參數值
     */
    public function addParameter(string $name, mixed $value): self
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    /**
     * 取得路由參數.
     *
     * @return array<mixed>
     */
    public function getParameters(): mixed
    {
        return $this->parameters;
    }

    /**
     * 移除指定的路由參數.
     *
     * @param string $name 參數名稱
     */
    public function removeParameter(string $name): self
    {
        unset($this->parameters[$name]);

        return $this;
    }

    /**
     * 清空所有路由參數.
     */
    public function clearParameters(): self
    {
        $this->parameters = [];

        return $this;
    }
}
