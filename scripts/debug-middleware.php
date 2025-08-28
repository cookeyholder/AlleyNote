<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Application;
use App\Infrastructure\Routing\Middleware\MiddlewareResolver;
use Psr\Container\ContainerInterface;

// 取得容器
$application = new Application();
$reflector = new ReflectionClass($application);
$containerProperty = $reflector->getProperty('container');
$containerProperty->setAccessible(true);
$container = $containerProperty->getValue($application);

echo "=== JWT 中介軟體除錯 ===\n";
echo "時間: " . date('Y-m-d H:i:s') . "\n\n";

// 檢查中介軟體解析器是否已註冊
try {
    $middlewareResolver = $container->get(MiddlewareResolver::class);
    echo "✅ MiddlewareResolver 已註冊\n";

    // 測試解析 jwt.auth
    if ($middlewareResolver->canResolve('jwt.auth')) {
        echo "✅ jwt.auth 別名可以解析\n";
        $jwtAuthMiddleware = $middlewareResolver->resolve('jwt.auth');
        echo "✅ jwt.auth 解析結果: " . get_class($jwtAuthMiddleware) . "\n";
    } else {
        echo "❌ jwt.auth 別名無法解析\n";
    }

    // 測試解析 jwt.authorize
    if ($middlewareResolver->canResolve('jwt.authorize')) {
        echo "✅ jwt.authorize 別名可以解析\n";
        $jwtAuthorizeMiddleware = $middlewareResolver->resolve('jwt.authorize');
        echo "✅ jwt.authorize 解析結果: " . get_class($jwtAuthorizeMiddleware) . "\n";
    } else {
        echo "❌ jwt.authorize 別名無法解析\n";
    }
} catch (Exception $e) {
    echo "❌ MiddlewareResolver 錯誤: " . $e->getMessage() . "\n";
}

// 檢查個別中介軟體是否可解析
$middlewareClasses = [
    'jwt.auth' => 'App\\Application\\Middleware\\JwtAuthenticationMiddleware',
    'jwt.authorize' => 'App\\Application\\Middleware\\JwtAuthorizationMiddleware'
];

foreach ($middlewareClasses as $alias => $class) {
    try {
        $middleware = $container->get($class);
        echo "✅ {$alias} ({$class}) 可以直接解析\n";
    } catch (Exception $e) {
        echo "❌ {$alias} ({$class}) 解析錯誤: " . $e->getMessage() . "\n";
    }
}

// 檢查 ResponseInterface 是否已註冊
try {
    $response = $container->get(\Psr\Http\Message\ResponseInterface::class);
    echo "✅ ResponseInterface 可以解析\n";
} catch (Exception $e) {
    echo "❌ ResponseInterface 解析錯誤: " . $e->getMessage() . "\n";
}

// 檢查 ServerRequestInterface 是否已註冊
try {
    $request = $container->get(\Psr\Http\Message\ServerRequestInterface::class);
    echo "✅ ServerRequestInterface 可以解析\n";
} catch (Exception $e) {
    echo "❌ ServerRequestInterface 解析錯誤: " . $e->getMessage() . "\n";
}

echo "\n=== 除錯完成 ===\n";
