<?php

declare(strict_types=1);

// 自動載入 Composer 依賴
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Application\Controllers\PostController;
use App\Infrastructure\Routing\ControllerResolver;
use App\Infrastructure\Routing\Core\Route;
use App\Infrastructure\Routing\Core\Router;
use DI\ContainerBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

// 簡單的 Mock 實作用於測試
class MockStream implements StreamInterface
{
    private string $content = '';

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void {}

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return true;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void {}

    public function rewind(): void {}

    public function isWritable(): bool
    {
        return true;
    }

    public function write(string $string): int
    {
        $this->content .= $string;

        return strlen($string);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        return $this->content;
    }

    public function getContents(): string
    {
        return $this->content;
    }

    public function getMetadata(?string $key = null)
    {
        return null;
    }
}

class MockUri implements UriInterface
{
    public function __construct(private string $path) {}

    public function getScheme(): string
    {
        return 'http';
    }

    public function getAuthority(): string
    {
        return 'localhost';
    }

    public function getUserInfo(): string
    {
        return '';
    }

    public function getHost(): string
    {
        return 'localhost';
    }

    public function getPort(): ?int
    {
        return 80;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return '';
    }

    public function getFragment(): string
    {
        return '';
    }

    public function withScheme(string $scheme): UriInterface
    {
        return $this;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        return $this;
    }

    public function withHost(string $host): UriInterface
    {
        return $this;
    }

    public function withPort(?int $port): UriInterface
    {
        return $this;
    }

    public function withPath(string $path): UriInterface
    {
        return new self($path);
    }

    public function withQuery(string $query): UriInterface
    {
        return $this;
    }

    public function withFragment(string $fragment): UriInterface
    {
        return $this;
    }

    public function __toString(): string
    {
        return $this->path;
    }
}

class MockResponse implements ResponseInterface
{
    private MockStream $body;

    private int $statusCode = 200;

    private array<mixed> $headers = [];

    public function __construct()
    {
        $this->body = new MockStream();
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): ResponseInterface
    {
        return $this;
    }

    public function getHeaders(): array<mixed>
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getHeader(string $name): array<mixed>
    {
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): ResponseInterface
    {
        $new = clone $this;
        $new->headers[$name] = is_array($value) ? $value : [$value];

        return $new;
    }

    public function withAddedHeader(string $name, $value): ResponseInterface
    {
        return $this;
    }

    public function withoutHeader(string $name): ResponseInterface
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $new = clone $this;
        $new->statusCode = $code;

        return $new;
    }

    public function getReasonPhrase(): string
    {
        return '';
    }
}

class MockRequest implements ServerRequestInterface
{
    private MockUri $uri;

    private string $method;

    private array<mixed> $attributes = [];

    public function __construct(string $method, string $path)
    {
        $this->method = $method;
        $this->uri = new MockUri($path);
    }

    public function getRequestTarget(): string
    {
        return $this->uri->getPath();
    }

    public function withRequestTarget(string $requestTarget): ServerRequestInterface
    {
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): ServerRequestInterface
    {
        return $this;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): ServerRequestInterface
    {
        return $this;
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): ServerRequestInterface
    {
        return $this;
    }

    public function getHeaders(): array<mixed>
    {
        return [];
    }

    public function hasHeader(string $name): bool
    {
        return false;
    }

    public function getHeader(string $name): array<mixed>
    {
        return [];
    }

    public function getHeaderLine(string $name): string
    {
        return '';
    }

    public function withHeader(string $name, $value): ServerRequestInterface
    {
        return $this;
    }

    public function withAddedHeader(string $name, $value): ServerRequestInterface
    {
        return $this;
    }

    public function withoutHeader(string $name): ServerRequestInterface
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return new MockStream();
    }

    public function withBody(StreamInterface $body): ServerRequestInterface
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

    public function withCookieParams(array<mixed> $cookies): ServerRequestInterface
    {
        return $this;
    }

    public function getQueryParams(): array<mixed>
    {
        return [];
    }

    public function withQueryParams(array<mixed> $query): ServerRequestInterface
    {
        return $this;
    }

    public function getUploadedFiles(): array<mixed>
    {
        return [];
    }

    public function withUploadedFiles(array<mixed> $uploadedFiles): ServerRequestInterface
    {
        return $this;
    }

    public function getParsedBody()
    {
        return [];
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        return $this;
    }

    public function getAttributes(): array<mixed>
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    public function withoutAttribute(string $name): ServerRequestInterface
    {
        return $this;
    }
}

echo "=== 控制器整合測試 ===\n\n";

try {
    // 1. 建立 DI 容器
    $containerBuilder = new ContainerBuilder();
    $containerBuilder->addDefinitions([
        ResponseInterface::class => function () {
            return new MockResponse();
        },
        ServerRequestInterface::class => function () {
            return new MockRequest('GET', '/');
        },
        PostController::class => \DI\create(PostController::class),
    ]);
    $container = $containerBuilder->build();

    // 2. 建立路由器
    $router = new Router();

    // 3. 建立控制器解析器
    $controllerResolver = new ControllerResolver($container);

    // 4. 測試控制器解析
    echo "1. 測試控制器解析...\n";

    // 建立測試路由
    $route = Route::get('/api/posts/{id}', 'PostController@show');
    $route->setName('posts.show');

    // 建立測試請求
    $request = new MockRequest('GET', '/api/posts/123');
    $response = new MockResponse();

    // 測試控制器解析
    $result = $controllerResolver->resolve($route, $request, ['id' => '123']);

    echo "   ✓ 控制器解析成功\n";
    echo '   ✓ 回應狀態碼: ' . $result->getStatusCode() . "\n";
    echo '   ✓ 內容類型: ' . $result->getHeaderLine('Content-Type') . "\n";

    $body = $result->getBody()->getContents();
    $data = json_decode($body, true);
    if ($data && isset((is_array($data) ? $data['status'] : (is_object($data) ? $data->status : null))) && (is_array($data) ? $data['status'] : (is_object($data) ? $data->status : null)) === 'success') {
        echo "   ✓ 回應內容正確\n";
        echo '   ✓ 貼文 ID: ' . (is_array($data) ? $data['data'] : (is_object($data) ? $data->data : null))['id'] . "\n";
    } else {
        echo "   ✗ 回應內容格式錯誤\n";
        echo '   回應內容: ' . $body . "\n";
    }

    echo "\n";

    // 6. 測試不同的控制器方法
    echo "2. 測試不同控制器方法...\n";

    $testCases = [
        ['GET', '/api/posts', 'PostController@index', [], '取得貼文列表'],
        ['POST', '/api/posts', 'PostController@store', [], '建立新貼文'],
        ['GET', '/api/posts/456', 'PostController@show', ['id' => '456'], '取得單一貼文'],
    ];

    foreach ($testCases as [$method, $path, $handler, $params, $description]) {
        $route = new Route([$method], $path, $handler);
        $request = new MockRequest($method, $path);

        try {
            $result = $controllerResolver->resolve($route, $request, $params);
            echo "   ✓ {$description}: " . $result->getStatusCode() . "\n";
        } catch (Exception $e) {
            echo "   ✗ {$description}: 錯誤 - " . $e->getMessage() . "\n";
        }
    }

    echo "\n";

    // 7. 測試字串格式處理器
    echo "3. 測試字串格式處理器...\n";

    $stringRoute = Route::get('/api/test', 'PostController@index');

    try {
        $result = $controllerResolver->resolve($stringRoute, $request, []);
        echo "   ✓ 字串格式處理器解析成功\n";
    } catch (Exception $e) {
        echo '   ✗ 字串格式處理器失敗: ' . $e->getMessage() . "\n";
    }

    echo "\n";

    // 8. 測試閉包處理器
    echo "4. 測試閉包處理器...\n";

    $closureRoute = Route::get('/api/closure', function (ServerRequestInterface $request) {
        $response = new MockResponse();
        $response->getBody()->write(json_encode(['message' => '閉包處理器測試成功']));

        return $response->withHeader('Content-Type', 'application/json');
    });

    try {
        $result = $controllerResolver->resolve($closureRoute, $request, []);
        echo "   ✓ 閉包處理器解析成功\n";
        echo '   ✓ 回應狀態碼: ' . $result->getStatusCode() . "\n";
    } catch (Exception $e) {
        echo '   ✗ 閉包處理器失敗: ' . $e->getMessage() . "\n";
    }

    echo "\n=== 測試完成 ===\n";
} catch (Exception $e) {
    echo '測試失敗: ' . $e->getMessage() . "\n";
    echo '檔案: ' . $e->getFile() . ':' . $e->getLine() . "\n";
    echo "堆疊追蹤:\n" . $e->getTraceAsString() . "\n";
}
