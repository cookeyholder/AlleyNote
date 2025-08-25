<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use App\Infrastructure\Routing\Contracts\RouteInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use RuntimeException;

/**
 * 控制器解析器.
 *
 * 負責解析路由處理器並呼叫對應的控制器方法
 */
class ControllerResolver
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * 解析並執行控制器方法.
     */
    public function resolve(
        RouteInterface $route,
        ServerRequestInterface $request,
        array $parameters = [],
    ): ResponseInterface {
        $handler = $route->getHandler();

        if (is_array($handler) && count($handler) === 2) {
            // 處理器是陣列格式: [ControllerClass::class, 'method']
            return $this->handleArrayHandler($handler, $request, $parameters);
        }

        if (is_string($handler)) {
            // 處理器是字串格式: "ControllerClass@method"
            return $this->handleStringHandler($handler, $request, $parameters);
        }

        if (is_callable($handler)) {
            // 處理器是閉包函式
            return $this->handleCallable($handler, $request, $parameters);
        }

        throw new RuntimeException('無效的路由處理器格式');
    }

    /**
     * 處理閉包函式處理器.
     */
    private function handleCallable(callable $handler, ServerRequestInterface $request, array $parameters): ResponseInterface
    {
        // 將路由參數注入到請求屬性中
        foreach ($parameters as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        $result = $handler($request);

        // 如果結果已經是 ResponseInterface，直接回傳
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        // 否則將結果轉換為 JSON 回應
        return $this->createJsonResponse($result);
    }

    /**
     * 建立空的 PSR-7 Response 物件.
     */
    private function createResponse(): ResponseInterface
    {
        return new class implements ResponseInterface {
            private array $headers = [];

            private $body;

            private int $statusCode = 200;

            private string $reasonPhrase = 'OK';

            private string $protocolVersion = '1.1';

            public function __construct()
            {
                $this->body = new class {
                    private string $content = '';

                    public function write(string $string): int
                    {
                        $this->content .= $string;

                        return strlen($string);
                    }

                    public function __toString(): string
                    {
                        return $this->content;
                    }
                };
            }

            public function getStatusCode(): int
            {
                return $this->statusCode;
            }

            public function withStatus($code, $reasonPhrase = ''): self
            {
                $new = clone $this;
                $new->statusCode = $code;
                if ($reasonPhrase) {
                    $new->reasonPhrase = $reasonPhrase;
                }

                return $new;
            }

            public function getReasonPhrase(): string
            {
                return $this->reasonPhrase;
            }

            public function getProtocolVersion(): string
            {
                return $this->protocolVersion;
            }

            public function withProtocolVersion($version): self
            {
                $new = clone $this;
                $new->protocolVersion = $version;

                return $new;
            }

            public function getHeaders(): array
            {
                return $this->headers;
            }

            public function hasHeader($name): bool
            {
                return isset($this->headers[$name]);
            }

            public function getHeader($name): array
            {
                return $this->headers[$name] ?? [];
            }

            public function getHeaderLine($name): string
            {
                return implode(', ', $this->getHeader($name));
            }

            public function withHeader($name, $value): self
            {
                $new = clone $this;
                $new->headers[$name] = is_array($value) ? $value : [$value];

                return $new;
            }

            public function withAddedHeader($name, $value): self
            {
                $new = clone $this;
                $new->headers[$name] = array_merge($this->getHeader($name), is_array($value) ? $value : [$value]);

                return $new;
            }

            public function withoutHeader($name): self
            {
                $new = clone $this;
                unset($new->headers[$name]);

                return $new;
            }

            public function getBody()
            {
                return $this->body;
            }

            public function withBody($body): self
            {
                $new = clone $this;
                $new->body = $body;

                return $new;
            }
        };
    }

    /**
     * 建立 JSON 回應.
     */
    private function createJsonResponse(mixed $data, int $status = 200): ResponseInterface
    {
        // 建立簡單的 PSR-7 回應
        $response = new class implements ResponseInterface {
            private array $headers = ['Content-Type' => ['application/json']];

            private $body;

            private int $statusCode = 200;

            private string $reasonPhrase = 'OK';

            private string $protocolVersion = '1.1';

            public function __construct()
            {
                $this->body = new class {
                    private string $content = '';

                    public function write(string $string): int
                    {
                        $this->content .= $string;

                        return strlen($string);
                    }

                    public function __toString(): string
                    {
                        return $this->content;
                    }
                };
            }

            public function getStatusCode(): int
            {
                return $this->statusCode;
            }

            public function withStatus($code, $reasonPhrase = ''): self
            {
                $new = clone $this;
                $new->statusCode = $code;
                if ($reasonPhrase) {
                    $new->reasonPhrase = $reasonPhrase;
                }

                return $new;
            }

            public function getReasonPhrase(): string
            {
                return $this->reasonPhrase;
            }

            public function getProtocolVersion(): string
            {
                return $this->protocolVersion;
            }

            public function withProtocolVersion($version): self
            {
                $new = clone $this;
                $new->protocolVersion = $version;

                return $new;
            }

            public function getHeaders(): array
            {
                return $this->headers;
            }

            public function hasHeader($name): bool
            {
                return isset($this->headers[$name]);
            }

            public function getHeader($name): array
            {
                return $this->headers[$name] ?? [];
            }

            public function getHeaderLine($name): string
            {
                return implode(', ', $this->getHeader($name));
            }

            public function withHeader($name, $value): self
            {
                $new = clone $this;
                $new->headers[$name] = is_array($value) ? $value : [$value];

                return $new;
            }

            public function withAddedHeader($name, $value): self
            {
                $new = clone $this;
                $new->headers[$name] = array_merge($this->getHeader($name), is_array($value) ? $value : [$value]);

                return $new;
            }

            public function withoutHeader($name): self
            {
                $new = clone $this;
                unset($new->headers[$name]);

                return $new;
            }

            public function getBody()
            {
                return $this->body;
            }

            public function withBody($body): self
            {
                $new = clone $this;
                $new->body = $body;

                return $new;
            }
        };

        // 將資料編碼為 JSON
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $response->getBody()->write($json ?: '{}');

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    /**
     * 處理字串格式處理器 "ControllerClass@method".
     */
    private function handleStringHandler(string $handler, ServerRequestInterface $request, array $parameters): ResponseInterface
    {
        if (!str_contains($handler, '@')) {
            throw new RuntimeException("處理器格式錯誤: {$handler}，預期格式: ControllerClass@method");
        }

        [$controllerClass, $method] = explode('@', $handler, 2);

        return $this->handleArrayHandler([$controllerClass, $method], $request, $parameters);
    }

    /**
     * 處理陣列格式處理器 [ControllerClass::class, 'method'].
     */
    private function handleArrayHandler(array $handler, ServerRequestInterface $request, array $parameters): ResponseInterface
    {
        [$controllerClass, $method] = $handler;

        // 解析控制器類別
        $controller = $this->resolveController($controllerClass);

        // 檢查方法是否存在
        if (!method_exists($controller, $method)) {
            throw new RuntimeException("控制器方法不存在: {$controllerClass}::{$method}");
        }

        // 準備方法參數
        $methodArgs = $this->resolveMethodArguments($controller, $method, $request, $parameters);

        // 呼叫控制器方法
        return $controller->{$method}(...$methodArgs);
    }

    /**
     * 解析控制器實例.
     */
    private function resolveController(string $controllerClass): object
    {
        // 確保類別名稱是完整的命名空間
        if (!str_starts_with($controllerClass, 'App\\')) {
            $controllerClass = 'App\\Application\\Controllers\\' . $controllerClass;
        }

        // 檢查類別是否存在
        if (!class_exists($controllerClass)) {
            throw new RuntimeException("控制器類別不存在: {$controllerClass}");
        }

        // 從 DI 容器中取得控制器實例
        if ($this->container->has($controllerClass)) {
            return $this->container->get($controllerClass);
        }

        // 如果容器中沒有，嘗試建立實例
        try {
            $reflection = new ReflectionClass($controllerClass);
            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                // 無參數建構子
                return new $controllerClass();
            }

            // 解析建構子參數
            $args = $this->resolveConstructorArguments($constructor);

            return new $controllerClass(...$args);
        } catch (ReflectionException $e) {
            throw new RuntimeException("無法建立控制器實例: {$controllerClass}", 0, $e);
        }
    }

    /**
     * 解析建構子參數.
     */
    private function resolveConstructorArguments(ReflectionMethod $constructor): array
    {
        $args = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } else {
                    throw new RuntimeException("無法解析參數: {$parameter->getName()}");
                }
                continue;
            }

            if (!$type instanceof ReflectionNamedType) {
                throw new RuntimeException("不支援的參數類型: {$parameter->getName()}");
            }

            $typeName = $type->getName();

            if ($this->container->has($typeName)) {
                $args[] = $this->container->get($typeName);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            } elseif ($parameter->allowsNull()) {
                $args[] = null;
            } else {
                throw new RuntimeException("無法解析參數: {$parameter->getName()}，類型: {$typeName}");
            }
        }

        return $args;
    }

    /**
     * 解析控制器方法參數.
     */
    private function resolveMethodArguments(
        object $controller,
        string $methodName,
        ServerRequestInterface $request,
        array $routeParameters,
    ): array {
        try {
            $reflection = new ReflectionMethod($controller, $methodName);
        } catch (ReflectionException $e) {
            throw new RuntimeException("無法反射方法: {$methodName}", 0, $e);
        }

        $args = [];

        foreach ($reflection->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $type = $parameter->getType();

            // 優先處理 PSR-7 請求物件
            if ($type && $type instanceof ReflectionNamedType && $type->getName() === ServerRequestInterface::class) {
                // 將路由參數注入到請求屬性中
                $requestWithParams = $request;
                foreach ($routeParameters as $key => $value) {
                    $requestWithParams = $requestWithParams->withAttribute($key, $value);
                }
                $args[] = $requestWithParams;
                continue;
            }

            // 處理 PSR-7 回應物件
            if ($type && $type instanceof ReflectionNamedType && $type->getName() === ResponseInterface::class) {
                $args[] = $this->createResponse();
                continue;
            }

            // 處理路由參數
            if (isset($routeParameters[$paramName])) {
                $args[] = $this->convertParameter($routeParameters[$paramName], $type);
                continue;
            }

            // 處理依賴注入
            if ($type && $type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();
                if ($this->container->has($typeName)) {
                    $args[] = $this->container->get($typeName);
                    continue;
                }
            }

            // 處理預設值
            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            // 處理可為 null 的參數
            if ($parameter->allowsNull()) {
                $args[] = null;
                continue;
            }

            throw new RuntimeException("無法解析方法參數: {$paramName}");
        }

        return $args;
    }

    /**
     * 轉換參數類型.
     */
    private function convertParameter(string $value, ?ReflectionType $type): mixed
    {
        if ($type === null || !$type instanceof ReflectionNamedType) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }
}
