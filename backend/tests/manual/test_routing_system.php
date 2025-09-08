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

echo '=== AlleyNote 路由系統測試 ===

';

try { /* empty */ }
    // 1. 測試基本路由建立
    echo '1. 測試基本路由建立...
';
    $route = Route::get('/api/posts', 'PostController@index');
    $route->setName('posts.index');

    echo '   ✓ 路由建立成功
';
    echo '   ✓ HTTP 方法: ' . implode(', ', $route->getMethods()) . '
';
    echo '   ✓ 路由模式: ' . $route->getPattern() . '
';
    echo '   ✓ 路由名稱: ' . ($route->getName() ?? '無') . '

';

    // 2. 測試參數路由
    echo '2. 測試參數路由...
';
    $paramRoute = Route::get('/api/posts/{id}', 'PostController@show');
    $paramRoute->setName('posts.show');

    // 測試路由是否匹配
    $methodMatches = $paramRoute->matchesMethod('GET');
    $pathMatch = $paramRoute->matchesPath('/api/posts/123');
    echo '   ✓ HTTP 方法匹配: ' . ($methodMatches ? '成功' : '失敗') . '
';
    echo '   ✓ 路徑匹配: ' . ($pathMatch->isMatched() ? '成功' : '失敗') . '
';

    if ($pathMatch->isMatched()) {
        $parameters = $pathMatch->getParameters();
        echo '   ✓ 擷取參數: ' . json_encode($parameters) . '
';
    }
    echo '
';

    // 3. 測試 Router
    echo '3. 測試 Router...
';
    $router = new Router();
    $router->get('/api/posts', 'PostController@index')->setName('posts.index');
    $router->post('/api/posts', 'PostController@store')->setName('posts.store');
    $router->get('/api/posts/{id}', 'PostController@show')->setName('posts.show');

    // 4. 測試路由收集器
    echo '4. 測試路由收集器...
';
    $collection = new RouteCollection();
    $collection->add($route);
    $collection->add($paramRoute);

    echo '   ✓ 路由總數: ' . $collection->count() . '
';
    echo '   ✓ 根據名稱查找: ' . ($collection->getByName('posts.index') !== null ? '成功' : '失敗') . '
';
    echo '   ✓ 根據方法查找: ' . count($collection->getByMethod('GET')) . ' 個 GET 路由

';

    // 5. 測試路由器
    echo '5. 測試路由器...
';
    $router = new Router();

    // 註冊路由
    $router->get('/api/posts', ['PostController', 'index'])->setName('posts.index');
    $router->post('/api/posts', ['PostController', 'store'])->setName('posts.store');
    $router->get('/api/posts/{id}', ['PostController', 'show'])->setName('posts.show');

    echo '   ✓ 註冊了 3 個路由
';
    echo '   ✓ 路由總數: ' . $router->getRoutes()->count() . '

';

    // 6. 測試路由匹配功能
    echo '6. 測試路由匹配功能...
';

    // 測試路由匹配而不是完整分派
    $testCases = [
        ['GET', '/api/posts', '應該匹配 posts.index'],
        ['POST', '/api/posts', '應該匹配 posts.store'],
        ['GET', '/api/posts/123', '應該匹配 posts.show 並提取參數'],
        ['GET', '/api/users', '應該不匹配任何路由'],
    ];

    foreach ($testCases as [$method, $path, $description]) {
        $matched = false;
        $matchedRoute = null;
        $parameters = [];

        foreach ($router->getRoutes()->all() as $route) {
            if ($route->matchesMethod($method)) {
                $pathMatch = $route->matchesPath($path);
                if ($pathMatch->isMatched()) {
                    $matched = true;
                    $matchedRoute = $route;
                    $parameters = $pathMatch->getParameters();
                    break;
                }
            }
        }

        if ($matched) {
            $routeName = $matchedRoute?->getName() ?? '無名稱';
            echo "   ✓ {(string)method} {(string)path}: 匹配路由 '{(string)routeName}'";
            if (!empty($parameters)) {
                echo ', 參數: ' . json_encode($parameters);
            }
            echo '
';
        } else {
            echo '   ✗ {(string)method} {(string)path}: 未找到匹配的路由
';
        }
    }

    // 7. 測試 URL 產生
    echo '
7. 測試 URL 產生...
';

    try { /* empty */ }
        $url = $router->url('posts.show', ['id' => '456']);
        echo '   ✓ 產生 URL: {(string)url}
';
    }  // catch block commented out due to syntax error
