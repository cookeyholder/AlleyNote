<?php

declare(strict_types=1);

/**
 * 路由系統簡單測試腳本.
 *
 * 這個腳本用於在沒有 Docker 環境的情況下測試路由系統的基本功能
 */

// 自動載入 Composer 依賴
require_once __DIR__ . '/../../vendor/autoload.php';

// 引入路由系統類別
use App\Infrastructure\Routing\Core\Route;
use App\Infrastructure\Routing\Core\RouteCollection;
use App\Infrastructure\Routing\Core\Router;

// 建立一個模擬的 PSR-7 請求物件
class MockServerRequest
{
    public function __construct(
        private string $method,
        private string $path,
    ) {}

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): MockUri
    {
        return new MockUri($this->path);
    }
}

class MockUri
{
    public function __construct(private string $path) {}

    public function getPath(): string
    {
        return $this->path;
    }
}

echo "=== AlleyNote 路由系統測試 ===\n\n";

try {
    // 1. 測試基本路由建立
    echo "1. 測試基本路由建立...\n";
    $route = Route::get('/api/posts', 'PostController@index');
    $route->setName('posts.index');

    echo "   ✓ 路由建立成功\n";
    echo '   ✓ HTTP 方法: ' . implode(', ', $route->getMethods()) . "\n";
    echo '   ✓ 路由模式: ' . $route->getPattern() . "\n";
    echo '   ✓ 路由名稱: ' . ($route->getName() ?? '無') . "\n\n";

    // 2. 測試參數路由
    echo "2. 測試參數路由...\n";
    $paramRoute = Route::get('/api/posts/{id}', 'PostController@show');
    $paramRoute->setName('posts.show');

    // 測試路由是否匹配
    $methodMatches = $paramRoute->matchesMethod('GET');
    $pathMatch = $paramRoute->matchesPath('/api/posts/123');
    echo '   ✓ HTTP 方法匹配: ' . ($methodMatches ? '成功' : '失敗') . "\n";
    echo '   ✓ 路徑匹配: ' . ($pathMatch->isMatched() ? '成功' : '失敗') . "\n";

    if ($pathMatch->isMatched()) {
        $parameters = $pathMatch->getParameters();
        echo '   ✓ 擷取參數: ' . json_encode($parameters) . "\n";
    }
    echo "\n";

    // 3. 測試 Router
    echo "3. 測試 Router...\n";
    $router = new Router();
    $router->get('/api/posts', 'PostController@index')->setName('posts.index');
    $router->post('/api/posts', 'PostController@store')->setName('posts.store');
    $router->get('/api/posts/{id}', 'PostController@show')->setName('posts.show');

    // 4. 測試路由收集器
    echo "4. 測試路由收集器...\n";
    $collection = new RouteCollection();
    $collection->add($route);
    $collection->add($paramRoute);

    echo '   ✓ 路由總數: ' . $collection->count() . "\n";
    echo '   ✓ 根據名稱查找: ' . ($collection->getByName('posts.index') !== null ? '成功' : '失敗') . "\n";
    echo '   ✓ 根據方法查找: ' . count($collection->getByMethod('GET')) . " 個 GET 路由\n\n";

    // 5. 測試路由器
    echo "5. 測試路由器...\n";
    $router = new Router();

    // 註冊路由
    $router->get('/api/posts', ['PostController', 'index'])->setName('posts.index');
    $router->post('/api/posts', ['PostController', 'store'])->setName('posts.store');
    $router->get('/api/posts/{id}', ['PostController', 'show'])->setName('posts.show');

    echo "   ✓ 註冊了 3 個路由\n";
    echo '   ✓ 路由總數: ' . $router->getRoutes()->count() . "\n\n";

    // 6. 測試路由分派
    echo "6. 測試路由分派...\n";
    $testRequests = [
        new MockServerRequest('GET', '/api/posts'),
        new MockServerRequest('POST', '/api/posts'),
        new MockServerRequest('GET', '/api/posts/123'),
        new MockServerRequest('GET', '/api/users'), // 不存在的路由
    ];

    foreach ($testRequests as $i => $testRequest) {
        $result = $router->dispatch($testRequest);
        $method = $testRequest->getMethod();
        $path = $testRequest->getUri()->getPath();

        if ($result->isMatched()) {
            $routeName = $result->getRoute()?->getName() ?? '無名稱';
            $params = $result->getParameters();
            echo "   ✓ {$method} {$path}: 匹配路由 '{$routeName}'"
                . (!empty($params) ? ', 參數: ' . json_encode($params) : '') . "\n";
        } else {
            echo "   ✗ {$method} {$path}: 未找到匹配的路由\n";
        }
    }

    // 7. 測試 URL 產生
    echo "\n7. 測試 URL 產生...\n";

    try {
        $url = $router->url('posts.show', ['id' => '456']);
        echo "   ✓ 產生 URL: {$url}\n";
    } catch (Exception $e) {
        echo '   ✗ URL 產生失敗: ' . $e->getMessage() . "\n";
    }

    echo "\n=== 所有測試完成 ===\n";
} catch (Exception $e) {
    echo '測試失敗: ' . $e->getMessage() . "\n";
    echo "堆疊追蹤:\n" . $e->getTraceAsString() . "\n";
}
