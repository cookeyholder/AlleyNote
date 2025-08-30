<?php

declare(strict_types=1);

use App\Application\Controllers\PostController;
use App\Application\Controllers\Api\V1\AuthController;
use App\Application\Controllers\Api\V1\ActivityLogController;
use App\Infrastructure\Routing\Contracts\RouterInterface;

/**
 * 路由定義
 * 
 * 定義所有 API 路由，包含 JWT 認證和授權配置
 * 
 * 路由架構：
 * - 公開路由：不需要認證
 * - 認證路由：需要有效的 JWT token
 * - 授權路由：需要認證且具備特定權限
 * 
 * 中介軟體註冊：
 * - 'jwt.auth': JWT 認證中介軟體
 * - 'jwt.authorize': JWT 授權中介軟體
 */
return function (RouterInterface $router): void {

    // =========================================
    // 公開路由 (不需要認證)
    // =========================================

    // 健康檢查
    $healthCheck = $router->get('/api/health', function ($request, $response) {
        $response->getBody()->write((json_encode([
            'status' => 'ok',
            'timestamp' => date('c'),
            'service' => 'AlleyNote API',
            'version' => '1.0.0',
        ]) ?: '{"error": "JSON encoding failed"}'));
        return $response->withHeader('Content-Type', 'application/json');
    });
    $healthCheck->setName('api.health');

    // API 文檔路由
    $router->get('/docs', function ($request, $response) {
        return $response
            ->withStatus(302)
            ->withHeader('Location', '/api/docs/ui');
    })->setName('docs.redirect');

    $router->get('/api/docs', function ($request, $response) {
        // TODO: 實作 Swagger 文檔生成
        $response->getBody()->write((json_encode(['message' => 'API Documentation']) ?: '{"error": "JSON encoding failed"}'));
        return $response->withHeader('Content-Type', 'application/json');
    })->setName('api.docs');

    $router->get('/api/docs/ui', function ($request, $response) {
        // TODO: 實作 Swagger UI
        $response->getBody()->write('<h1>Swagger UI');
        return $response->withHeader('Content-Type', 'text/html');
    })->setName('api.docs.ui');

    // =========================================
    // JWT 認證相關路由 (公開)
    // =========================================

    // 使用者註冊 (公開)
    $authRegister = $router->post('/api/auth/register', [AuthController::class, 'register']);
    $authRegister->setName('auth.register');

    // 使用者登入 (公開)
    $authLogin = $router->post('/api/auth/login', [AuthController::class, 'login']);
    $authLogin->setName('auth.login');

    // Token 刷新 (半保護 - 需要 refresh token 但不需要完整認證)
    $authRefresh = $router->post('/api/auth/refresh', [AuthController::class, 'refresh']);
    $authRefresh->setName('auth.refresh');

    // =========================================
    // JWT 認證路由 (需要有效的 JWT token)
    // =========================================

    // 使用者登出 (需要認證)
    $authLogout = $router->post('/api/auth/logout', [AuthController::class, 'logout']);
    $authLogout->setName('auth.logout');
    $authLogout->middleware('jwt.auth');

    // 取得目前使用者資訊 (需要認證)
    $authMe = $router->get('/api/auth/me', [AuthController::class, 'me']);
    $authMe->setName('auth.me');
    $authMe->middleware('jwt.auth');

    // =========================================
    // 貼文相關路由 (需要認證和授權)
    // =========================================

    // 瀏覽貼文清單 (公開，但認證使用者可看到更多資訊)
    $postsIndex = $router->get('/api/posts', [PostController::class, 'index']);
    $postsIndex->setName('posts.index');

    // 檢視特定貼文 (公開，但認證使用者可看到更多資訊)
    $postsShow = $router->get('/api/posts/{id}', [PostController::class, 'show']);
    $postsShow->setName('posts.show');

    // 建立新貼文 (需要認證和權限)
    $postsStore = $router->post('/api/posts', [PostController::class, 'store']);
    $postsStore->setName('posts.store');
    $postsStore->middleware(['jwt.auth']);

    // 更新貼文 (需要認證和權限 - 只有作者或管理員)
    $postsUpdate = $router->put('/api/posts/{id}', [PostController::class, 'update']);
    $postsUpdate->setName('posts.update');
    $postsUpdate->middleware(['jwt.auth', 'jwt.authorize']);

    // 刪除貼文 (需要認證和權限 - 只有作者或管理員)
    $postsDestroy = $router->delete('/api/posts/{id}', [PostController::class, 'destroy']);
    $postsDestroy->setName('posts.destroy');
    $postsDestroy->middleware(['jwt.auth', 'jwt.authorize']);

    // =========================================
    // 活動記錄 API 路由 (需要認證)
    // =========================================

    // 記錄使用者活動 (POST /api/v1/activity-logs)
    $activityLogStore = $router->post('/api/v1/activity-logs', [ActivityLogController::class, 'store']);
    $activityLogStore->setName('activity_logs.store');
    $activityLogStore->middleware('jwt.auth');

    // 查詢活動記錄 (GET /api/v1/activity-logs)
    $activityLogIndex = $router->get('/api/v1/activity-logs', [ActivityLogController::class, 'index']);
    $activityLogIndex->setName('activity_logs.index');
    $activityLogIndex->middleware('jwt.auth');

    // 取得活動記錄統計 (GET /api/v1/activity-logs/stats)
    $activityLogStats = $router->get('/api/v1/activity-logs/stats', [ActivityLogController::class, 'getStats']);
    $activityLogStats->setName('activity_logs.stats');
    $activityLogStats->middleware('jwt.auth');

    // 取得目前使用者的活動記錄 (GET /api/v1/activity-logs/me)
    $activityLogMe = $router->get('/api/v1/activity-logs/me', [ActivityLogController::class, 'getCurrentUserLogs']);
    $activityLogMe->setName('activity_logs.me');
    $activityLogMe->middleware('jwt.auth');

    // =========================================
    // 管理員路由 (需要管理員權限)
    // =========================================

    // 這些路由將來可以擴充，包含使用者管理、系統設定等
    // 目前先預留路由結構

    /*
    // 使用者管理 (僅限管理員)
    $adminUsersIndex = $router->get('/api/admin/users', [AdminController::class, 'users']);
    $adminUsersIndex->setName('admin.users.index');
    $adminUsersIndex->middleware(['jwt.auth', 'jwt.authorize:admin']);

    // 系統設定 (僅限超級管理員)
    $adminSettings = $router->get('/api/admin/settings', [AdminController::class, 'settings']);
    $adminSettings->setName('admin.settings');
    $adminSettings->middleware(['jwt.auth', 'jwt.authorize:super_admin']);
    */
};

/*
 * 路由配置說明：
 * 
 * 1. 公開路由：
 *    - /api/health - 健康檢查
 *    - /docs, /api/docs, /api/docs/ui - API 文檔
 *    - /api/auth/register - 使用者註冊
 *    - /api/auth/login - 使用者登入
 *    - /api/auth/refresh - Token 刷新
 *    - /api/posts (GET) - 瀏覽貼文清單
 *    - /api/posts/{id} (GET) - 檢視特定貼文
 * 
 * 2. 需要認證的路由：
 *    - /api/auth/logout - 使用者登出
 *    - /api/auth/me - 取得使用者資訊
 * 
 * 3. 需要認證和授權的路由：
 *    - /api/posts (POST) - 建立新貼文
 *    - /api/posts/{id} (PUT) - 更新貼文
 *    - /api/posts/{id} (DELETE) - 刪除貼文
 * 
 * 中介軟體配置：
 * - jwt.auth: JWT 認證中介軟體，驗證 token 有效性
 * - jwt.authorize: JWT 授權中介軟體，檢查使用者權限
 * 
 * 注意：實際的中介軟體註冊需要在應用程式啟動時透過 DI 容器完成
 */
