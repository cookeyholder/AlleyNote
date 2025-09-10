<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Core;

use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RouteInterface;
use App\Infrastructure\Routing\Contracts\RouteMatchResult;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 路由實體類別.
 *
 * 實作 RouteInterface，代表單一路由的完整資訊
 */
class Route implements RouteInterface
{
    private ?string $name = null;

    /** @var MiddlewareInterface[]|string[] */
    private array $middlewares = [];

    private ?string $compiledPattern = null;

    /** @var string[] */
    private array $parameterNames = [];

    /**
     * @param array<string> $methods HTTP 方法列表
     * @param callable|string $handler
     */
    public function __construct(
        private readonly array $methods,
        private readonly string $pattern,
        private readonly mixed $handler,
    ) {
        $this->parameterNames = $this->extractParameterNames($pattern);
    }

    /**
     * @return array<string>
     */
    public function getMethods(): array
    {
        return array_keys($this->methods);
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array<mixed>
     */
    public function getHandler(): array
    {
        return is_array($this->handler) ? $this->handler : [$this->handler];
    }

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @param array<MiddlewareInterface> $middlewares
     */
    public function addMiddlewares(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->middlewares[] = $middleware;
        }

        return $this;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function matchesMethod(string $method): bool
    {
        return in_array(strtoupper($method), array_map('strtoupper', $this->methods), true);
    }

    public function matchesPath(string $path): RouteMatchResult
    {
        $compiledPattern = $this->compile();

        if (preg_match($compiledPattern, $path, $matches)) {
            // 移除第一個完整匹配
            array_shift($matches);

            // 建立參數陣列
            $parameters = [];
            foreach ($this->parameterNames as $index => $name) {
                $parameters[$name] = $matches[$index] ?? null;
            }

            return new RouteMatchResult(true, $this, $parameters);
        }

        return new RouteMatchResult(false, null);
    }

    public function matches(ServerRequestInterface $request): RouteMatchResult
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        // 檢查 HTTP 方法
        if (!$this->matchesMethod($method)) {
            return new RouteMatchResult(false, null);
        }

        // 檢查路徑模式
        return $this->matchesPath($path);
    }

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $queryParams
     */
    public function generateUrl(array $parameters = [], array $queryParams = []): string
    {
        $url = $this->pattern;

        // 替換路由參數
        foreach ($parameters as $name => $value) {
            if (!is_scalar($value)) {
                throw new InvalidArgumentException("參數 '{$name}' 必須是純量值");
            }

            $placeholder = '{' . $name . '}';
            if (str_contains($url, $placeholder)) {
                $url = str_replace($placeholder, (string) $value, $url);
            }
        }

        // 檢查是否還有未替換的參數
        if (preg_match('/{([^}]+)}/', $url, $matches)) {
            throw new InvalidArgumentException("缺少必要的路由參數: {$matches[1]}");
        }

        // 加入查詢參數
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function withAttributes(array $attributes): self
    {
        $clone = clone $this;

        if (isset($attributes['name']) && is_string($attributes['name'])) {
            $clone->name = $attributes['name'];
        }

        if (isset($attributes['middlewares']) && is_array($attributes['middlewares'])) {
            $clone->middlewares = [];
            /** @var array<MiddlewareInterface> $middlewares */
            $middlewares = $attributes['middlewares'];
            $clone->addMiddlewares($middlewares);
        }

        return $clone;
    }

    // 保留原有的方法以向後相容
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 添加中介軟體（支援字串別名、實例和陣列）.
     * @param MiddlewareInterface|string $middleware
     */
    public function middleware($middleware): self
    {
        if ($middleware instanceof MiddlewareInterface) {
            $this->middlewares[] = $middleware;
        } elseif (is_string($middleware)) {
            // 支援字串別名
            $this->middlewares[] = $middleware;
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function extractParameters(string $path): array
    {
        $result = $this->matchesPath($path);

        /** @var array<string, mixed> */
        return $result->isMatched() ? $result->getParameters() : [];
    }

    /**
     * 編譯路由模式為正規表達式.
     */
    private function compile(): string
    {
        if ($this->compiledPattern !== null) {
            return $this->compiledPattern;
        }

        $pattern = $this->pattern;

        // 先將參數佔位符替換為特殊標記
        $pattern = preg_replace('/{([^}]+)}/', 'ROUTEPARAM', $pattern);
        if ($pattern === null) {
            $pattern = $this->pattern;
        }

        // 轉義特殊字符
        $pattern = preg_quote($pattern, '/');

        // 將特殊標記轉換為正規表達式群組
        $pattern = str_replace('ROUTEPARAM', '([^\/]+)', $pattern);

        // 確保完整匹配
        $this->compiledPattern = '/^' . $pattern . '$/';

        return $this->compiledPattern;
    }

    /**
     * 從路由模式中提取參數名稱.
     * @param string $pattern 路由模式
     * @return string[] 參數名稱陣列
     */
    private function extractParameterNames(string $pattern): array
    {
        preg_match_all('/{([^}]+)}/', $pattern, $matches);

        return $matches[1];
    }

    // HTTP 方法快捷方法
    /**
     * @param callable|string $handler
     */
    public static function get(string $pattern, $handler): self
    {
        return new self(['GET'], $pattern, $handler);
    }

    /**
     * @param callable|string $handler
     */
    public static function post(string $pattern, $handler): self
    {
        return new self(['POST'], $pattern, $handler);
    }

    /**
     * @param callable|string $handler
     */
    public static function put(string $pattern, $handler): self
    {
        return new self(['PUT'], $pattern, $handler);
    }

    /**
     * @param callable|string $handler
     */
    public static function patch(string $pattern, $handler): self
    {
        return new self(['PATCH'], $pattern, $handler);
    }

    /**
     * @param callable|string $handler
     */
    public static function delete(string $pattern, $handler): self
    {
        return new self(['DELETE'], $pattern, $handler);
    }

    /**
     * @param callable|string $handler
     */
    public static function any(string $pattern, $handler): self
    {
        return new self(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], $pattern, $handler);
    }

    /**
     * @param array<string> $methods
     * @param callable|string $handler
     */
    public static function match(array $methods, string $pattern, $handler): self
    {
        return new self($methods, $pattern, $handler);
    }
}
