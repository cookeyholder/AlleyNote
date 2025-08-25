<?php

declare(strict_types=1);

use App\Application\Controllers\PostController;
use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\Core\Router;

/**
 * 路由定義
 * 
 * 在這裡定義所有的 API 路由
 */
return function (RouterInterface $router): void {
    
    // API 路由群組
    $router->group(['prefix' => '/api', 'middleware' => []], function () use ($router) {
        
        // 貼文相關路由
        $postsIndex = $router->get('/posts', [PostController::class, 'index']);
        $postsIndex->setName('posts.index');
        
        $postsShow = $router->get('/posts/{id}', [PostController::class, 'show']);
        $postsShow->setName('posts.show');
        
        $postsStore = $router->post('/posts', [PostController::class, 'store']);
        $postsStore->setName('posts.store');
        
        $postsUpdate = $router->put('/posts/{id}', [PostController::class, 'update']);
        $postsUpdate->setName('posts.update');
        
        $postsDestroy = $router->delete('/posts/{id}', [PostController::class, 'destroy']);
        $postsDestroy->setName('posts.destroy');
        
        // 健康檢查
        $healthCheck = $router->get('/health', function ($request, $response) {
            $response->getBody()->write(json_encode([
                'status' => 'ok',
                'timestamp' => date('c'),
                'service' => 'AlleyNote API',
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });
        $healthCheck->setName('health.check');
        
    });
    
    // 文件路由
    $router->get('/docs', function ($request, $response) {
        return $response
            ->withStatus(302)
            ->withHeader('Location', '/api/docs/ui');
    })->setName('docs.redirect');
    
    // API 文檔路由
    $router->get('/api/docs', function ($request, $response) {
        // TODO: 實作 Swagger 文檔生成
        $response->getBody()->write(json_encode(['message' => 'API Documentation']));
        return $response->withHeader('Content-Type', 'application/json');
    })->setName('api.docs');
    
    $router->get('/api/docs/ui', function ($request, $response) {
        // TODO: 實作 Swagger UI
        $response->getBody()->write('<h1>Swagger UI</h1>');
        return $response->withHeader('Content-Type', 'text/html');
    })->setName('api.docs.ui');
};