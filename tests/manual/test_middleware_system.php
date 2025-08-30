<?php

declare(strict_types=1);

/**
 * 路由中介軟體系統手動測試.
 *
 * 測試中介軟體管理器、執行器和路由整合
 */
// 自動載入 Composer 依賴
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use App\Infrastructure\Routing\Core\Route;
use App\Infrastructure\Routing\Middleware\AbstractMiddleware;
use App\Infrastructure\Routing\Middleware\MiddlewareDispatcher;
use App\Infrastructure\Routing\Middleware\MiddlewareManager;
use App\Infrastructure\Routing\Middleware\RouteInfoMiddleware;
use App\Infrastructure\Routing\Middleware\RouteParametersMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// 建立模擬的 PSR-7 請求和回應
$request = new class implements ServerRequestInterface {
    private array<mixed> $attributes = [];

    public function getMethod(): string
    {
        return 'GET';
    }

    public function getUri()
    {
        return new class {
            public function getPath(): string
            {
                return '/users/123';
            }
        };
    }

    public function withAttribute($name, $value): self
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function getAttributes(): array<mixed>
    {
        return $this->attributes;
    }

    // 其他 PSR-7 方法的空實作
    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion($version): self
    {
        return $this;
    }

    public function getHeaders(): array<mixed>
    {
        return [];
    }

    public function hasHeader($name): bool
    {
        return false;
    }

    public function getHeader($name): array<mixed>
    {
        return [];
    }

    public function getHeaderLine($name): string
    {
        return '';
    }

    public function withHeader($name, $value): self
    {
        return $this;
    }

    public function withAddedHeader($name, $value): self
    {
        return $this;
    }

    public function withoutHeader($name): self
    {
        return $this;
    }

    public function getBody()
    {
        return new class {
            public function __toString(): string
            {
                return '';
            }
        };
    }

    public function withBody($body): self
    {
        return $this;
    }

    public function getRequestTarget(): string
    {
        return '/';
    }

    public function withRequestTarget($requestTarget): self
    {
        return $this;
    }

    public function withMethod($method): self
    {
        return $this;
    }

    public function withUri($uri, $preserveHost = false): self
    {
        return $this;
    }

    public function getServerParams(): array<mixed>
    {
        return [];
    }

    public function getCookieParams(): array<mixed>
    {
        return [];
    }

    public function withCookieParams(array<mixed> $cookies): self
    {
        return $this;
    }

    public function getQueryParams(): array<mixed>
    {
        return [];
    }

    public function withQueryParams(array<mixed> $query): self
    {
        return $this;
    }

    public function getUploadedFiles(): array<mixed>
    {
        return [];
    }

    public function withUploadedFiles(array<mixed> $uploadedFiles): self
    {
        return $this;
    }

    public function getParsedBody()
    {
        return null;
    }

    public function withParsedBody($data): self
    {
        return $this;
    }

    public function withoutAttribute($name): self
    {
        return $this;
    }
};

$response = new class implements ResponseInterface {
    private string $body = '';

    public function __construct(string $body = '')
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return new class ($this->body) {
            public function __construct(private string $content) {}

            public function __toString(): string
            {
                return $this->content;
            }
        };
    }

    // 其他 PSR-7 方法的空實作
    public function getStatusCode(): int
    {
        return 200;
    }

    public function withStatus($code, $reasonPhrase = ''): self
    {
        return $this;
    }

    public function getReasonPhrase(): string
    {
        return 'OK';
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion($version): self
    {
        return $this;
    }

    public function getHeaders(): array<mixed>
    {
        return [];
    }

    public function hasHeader($name): bool
    {
        return false;
    }

    public function getHeader($name): array<mixed>
    {
        return [];
    }

    public function getHeaderLine($name): string
    {
        return '';
    }

    public function withHeader($name, $value): self
    {
        return $this;
    }

    public function withAddedHeader($name, $value): self
    {
        return $this;
    }

    public function withoutHeader($name): self
    {
        return $this;
    }

    public function withBody($body): self
    {
        return $this;
    }
};

// 建立測試中介軟體
class LoggingMiddleware extends AbstractMiddleware
{
    public function __construct(private string $message, int $priority = 0)
    {
        parent::__construct('logging-' . md5($message), $priority);
    }

    protected function execute(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        echo "執行中介軟體: {$this->message}\n";
        $response = $handler->handle($request);
        echo "完成中介軟體: {$this->message}\n";

        return $response;
    }
}

class AuthMiddleware extends AbstractMiddleware
{
    public function __construct(int $priority = 10)
    {
        parent::__construct('auth', $priority);
    }

    protected function execute(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        echo "檢查認證...\n";
        $request = $request->withAttribute('user_id', 123);

        return $handler->handle($request);
    }
}

// 建立最終處理器
$finalHandler = new class implements RequestHandlerInterface {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        echo "執行最終處理器\n";
        echo '請求屬性: ' . json_encode($request->getAttributes(), JSON_UNESCAPED_UNICODE) . "\n";

        return new class implements ResponseInterface {
            public function getBody()
            {
                return new class {
                    public function __toString(): string
                    {
                        return 'Hello, World!';
                    }
                };
            }

            // 其他 PSR-7 方法的空實作
            public function getStatusCode(): int
            {
                return 200;
            }

            public function withStatus($code, $reasonPhrase = ''): self
            {
                return $this;
            }

            public function getReasonPhrase(): string
            {
                return 'OK';
            }

            public function getProtocolVersion(): string
            {
                return '1.1';
            }

            public function withProtocolVersion($version): self
            {
                return $this;
            }

            public function getHeaders(): array<mixed>
            {
                return [];
            }

            public function hasHeader($name): bool
            {
                return false;
            }

            public function getHeader($name): array<mixed>
            {
                return [];
            }

            public function getHeaderLine($name): string
            {
                return '';
            }

            public function withHeader($name, $value): self
            {
                return $this;
            }

            public function withAddedHeader($name, $value): self
            {
                return $this;
            }

            public function withoutHeader($name): self
            {
                return $this;
            }

            public function withBody($body): self
            {
                return $this;
            }
        };
    }
};

echo "=== 路由中介軟體系統測試 ===\n\n";

// 1. 測試中介軟體基本功能
echo "1. 測試中介軟體基本功能\n";
echo "----------------------------\n";

$dispatcher = new MiddlewareDispatcher();
$manager = new MiddlewareManager($dispatcher);

// 加入測試中介軟體
$manager->add(new LoggingMiddleware('第一個中介軟體', 0));
$manager->add(new AuthMiddleware(10));
$manager->add(new LoggingMiddleware('第二個中介軟體', 5));

echo '中介軟體數量: ' . $manager->count() . "\n";
echo '中介軟體名稱: ' . implode(', ', $manager->getNames()) . "\n";
echo "\n";

// 2. 測試中介軟體執行順序
echo "2. 測試中介軟體執行順序 (按優先順序)\n";
echo "---------------------------------------\n";

$response = $manager->process($request, $finalHandler);
echo "\n";

// 3. 測試路由參數中介軟體
echo "3. 測試路由參數中介軟體\n";
echo "--------------------------\n";

$routeParamsMiddleware = new RouteParametersMiddleware([
    'id' => '123',
    'category' => 'users',
]);

$manager->clear();
$manager->add($routeParamsMiddleware);

$response = $manager->process($request, $finalHandler);
echo "\n";

// 4. 測試路由資訊中介軟體
echo "4. 測試路由資訊中介軟體\n";
echo "--------------------------\n";

$routeInfoMiddleware = new RouteInfoMiddleware(
    'users.show',
    '/users/{id}',
    ['GET'],
    'UserController@show',
);

$manager->clear();
$manager->add($routeInfoMiddleware);

$response = $manager->process($request, $finalHandler);
echo "\n";

// 5. 測試路由與中介軟體整合
echo "5. 測試路由與中介軟體整合\n";
echo "----------------------------\n";

$route = new Route(['GET'], '/users/{id}', function () {
    return 'User handler';
});

$route->addMiddleware(new LoggingMiddleware('路由中介軟體'));
$route->setName('users.show');

echo '路由模式: ' . $route->getPattern() . "\n";
echo '路由名稱: ' . ($route instanceof ReflectionNamedType ? $route->getName() : (string)$route) . "\n";
echo '中介軟體數量: ' . count($route->getMiddlewares()) . "\n";

// 測試路由匹配
$matchResult = $route->matches($request);
echo '路由匹配: ' . ($matchResult->isMatched() ? '成功' : '失敗') . "\n";
if ($matchResult->isMatched()) {
    echo '路由參數: ' . json_encode($matchResult->getParameters(), JSON_UNESCAPED_UNICODE) . "\n";
}
echo "\n";

// 6. 測試 URL 生成
echo "6. 測試 URL 生成\n";
echo "-----------------\n";

$route2 = new Route(['GET'], '/posts/{slug}/comments/{id}', 'handler');

try {
    $url = $route2->generateUrl([
        'slug' => 'hello-world',
        'id' => 456,
    ], ['page' => 2, 'limit' => 10]);
    echo "生成的 URL: {$url}\n";
} catch (Exception $e) {
    echo 'URL 生成錯誤: ' . $e->getMessage() . "\n";
}

try {
    $url = $route2->generateUrl(['slug' => 'hello-world']); // 缺少 id 參數
} catch (Exception $e) {
    echo '預期錯誤 (缺少參數): ' . $e->getMessage() . "\n";
}

echo "\n";

// 7. 測試中介軟體狀態管理
echo "7. 測試中介軟體狀態管理\n";
echo "--------------------------\n";

$middleware1 = new LoggingMiddleware('可停用的中介軟體');
$middleware1->disable();

$middleware2 = new LoggingMiddleware('正常中介軟體');

$manager->clear();
$manager->add($middleware1);
$manager->add($middleware2);

echo "執行前狀態:\n";
echo '- 中介軟體1 啟用: ' . ($middleware1->isEnabled() ? '是' : '否') . "\n";
echo '- 中介軟體2 啟用: ' . ($middleware2->isEnabled() ? '是' : '否') . "\n";
echo "\n執行中介軟體鏈:\n";

$response = $manager->process($request, $finalHandler);

echo "\n=== 測試完成 ===\n";
