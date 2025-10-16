<?php

declare(strict_types=1);

use App\Application\Controllers\PostController;
use App\Application\Controllers\Api\V1\AuthController;
use App\Application\Controllers\Api\V1\ActivityLogController;
use App\Application\Controllers\Api\V1\PostController as ApiPostController;
use App\Application\Controllers\Api\V1\UserController;
use App\Application\Controllers\Api\V1\RoleController;
use App\Application\Controllers\Api\V1\PermissionController;
use App\Application\Controllers\Api\V1\TagController;
use App\Application\Controllers\Api\V1\SettingController;
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

    $router->get('/api/docs', [\App\Application\Controllers\Web\SwaggerController::class, 'docs'])->setName('api.docs');

    $router->get('/api/docs/ui', [\App\Application\Controllers\Web\SwaggerController::class, 'ui'])->setName('api.docs.ui');

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

    // 忘記密碼 (公開)
    $authForgotPassword = $router->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    $authForgotPassword->setName('auth.forgot-password');

    // 密碼重設提交 (公開)
    $authResetPassword = $router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);
    $authResetPassword->setName('auth.reset-password');

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

    // 更新個人資料 (需要認證)
    $authUpdateProfile = $router->put('/api/auth/profile', [AuthController::class, 'updateProfile']);
    $authUpdateProfile->setName('auth.profile.update');
    $authUpdateProfile->middleware('jwt.auth');

    // 變更密碼 (需要認證)
    $authChangePassword = $router->post('/api/auth/change-password', [AuthController::class, 'changePassword']);
    $authChangePassword->setName('auth.change-password');
    $authChangePassword->middleware('jwt.auth');

    // =========================================
    // 貼文相關路由 (需要認證和授權)
    // =========================================

    // 瀏覽貼文清單 (公開，但認證使用者可看到更多資訊)
    $postsIndex = $router->get('/api/posts', [ApiPostController::class, 'index']);
    $postsIndex->setName('posts.index');

    // 檢視特定貼文 (公開，但認證使用者可看到更多資訊)
    $postsShow = $router->get('/api/posts/{id}', [ApiPostController::class, 'show']);
    $postsShow->setName('posts.show');

    // 建立新貼文 (需要認證和權限)
    $postsStore = $router->post('/api/posts', [ApiPostController::class, 'store']);
    $postsStore->setName('posts.store');
    $postsStore->middleware(['jwt.auth']);

    // 更新貼文 (需要認證和權限 - 只有作者或管理員)
    $postsUpdate = $router->put('/api/posts/{id}', [ApiPostController::class, 'update']);
    $postsUpdate->setName('posts.update');
    $postsUpdate->middleware(['jwt.auth', 'jwt.authorize']);

    // 刪除貼文 (需要認證和權限 - 只有作者或管理員)
    $postsDestroy = $router->delete('/api/posts/{id}', [ApiPostController::class, 'destroy']);
    $postsDestroy->setName('posts.destroy');
    $postsDestroy->middleware(['jwt.auth', 'jwt.authorize']);

    // 發布貼文 (需要認證和權限)
    $postsPublish = $router->post('/api/posts/{id}/publish', [ApiPostController::class, 'publish']);
    $postsPublish->setName('posts.publish');
    $postsPublish->middleware(['jwt.auth', 'jwt.authorize']);

    // 取消發布貼文 (需要認證和權限)
    $postsUnpublish = $router->post('/api/posts/{id}/unpublish', [ApiPostController::class, 'unpublish']);
    $postsUnpublish->setName('posts.unpublish');
    $postsUnpublish->middleware(['jwt.auth', 'jwt.authorize']);

    // 置頂貼文 (需要認證和權限)
    $postsPin = $router->patch('/api/posts/{id}/pin', [ApiPostController::class, 'togglePin']);
    $postsPin->setName('posts.pin');
    $postsPin->middleware(['jwt.auth', 'jwt.authorize']);

    // 取消置頂貼文 (需要認證和權限)
    $postsUnpin = $router->delete('/api/posts/{id}/pin', [ApiPostController::class, 'unpin']);
    $postsUnpin->setName('posts.unpin');
    $postsUnpin->middleware(['jwt.auth', 'jwt.authorize']);

    // =========================================
    // 使用者管理 API 路由 (需要管理員權限)
    // =========================================

    // 取得使用者列表 (需要管理員權限)
    $usersIndex = $router->get('/api/users', [UserController::class, 'index']);
    $usersIndex->setName('users.index');
    $usersIndex->middleware(['jwt.auth', 'jwt.authorize']);

    // 取得單一使用者 (需要管理員權限)
    $usersShow = $router->get('/api/users/{id}', [UserController::class, 'show']);
    $usersShow->setName('users.show');
    $usersShow->middleware(['jwt.auth', 'jwt.authorize']);

    // 建立使用者 (需要管理員權限)
    $usersStore = $router->post('/api/users', [UserController::class, 'store']);
    $usersStore->setName('users.store');
    $usersStore->middleware(['jwt.auth', 'jwt.authorize']);

    // 更新使用者 (需要管理員權限)
    $usersUpdate = $router->put('/api/users/{id}', [UserController::class, 'update']);
    $usersUpdate->setName('users.update');
    $usersUpdate->middleware(['jwt.auth', 'jwt.authorize']);

    // 刪除使用者 (需要管理員權限)
    $usersDestroy = $router->delete('/api/users/{id}', [UserController::class, 'destroy']);
    $usersDestroy->setName('users.destroy');
    $usersDestroy->middleware(['jwt.auth', 'jwt.authorize']);

    // 啟用使用者 (需要管理員權限)
    $usersActivate = $router->post('/api/users/{id}/activate', [UserController::class, 'activate']);
    $usersActivate->setName('users.activate');
    $usersActivate->middleware(['jwt.auth', 'jwt.authorize']);

    // 停用使用者 (需要管理員權限)
    $usersDeactivate = $router->post('/api/users/{id}/deactivate', [UserController::class, 'deactivate']);
    $usersDeactivate->setName('users.deactivate');
    $usersDeactivate->middleware(['jwt.auth', 'jwt.authorize']);

    // 重設使用者密碼 (需要管理員權限)
    $usersResetPassword = $router->post('/api/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    $usersResetPassword->setName('users.reset-password');
    $usersResetPassword->middleware(['jwt.auth', 'jwt.authorize']);

    // Admin 路由別名 (向後兼容)
    $adminUsersIndex = $router->get('/api/admin/users', [UserController::class, 'index']);
    $adminUsersIndex->setName('admin.users.index');
    $adminUsersIndex->middleware(['jwt.auth', 'jwt.authorize']);

    $adminUsersShow = $router->get('/api/admin/users/{id}', [UserController::class, 'show']);
    $adminUsersShow->setName('admin.users.show');
    $adminUsersShow->middleware(['jwt.auth', 'jwt.authorize']);

    $adminUsersStore = $router->post('/api/admin/users', [UserController::class, 'store']);
    $adminUsersStore->setName('admin.users.store');
    $adminUsersStore->middleware(['jwt.auth', 'jwt.authorize']);

    $adminUsersUpdate = $router->put('/api/admin/users/{id}', [UserController::class, 'update']);
    $adminUsersUpdate->setName('admin.users.update');
    $adminUsersUpdate->middleware(['jwt.auth', 'jwt.authorize']);

    $adminUsersDestroy = $router->delete('/api/admin/users/{id}', [UserController::class, 'destroy']);
    $adminUsersDestroy->setName('admin.users.destroy');
    $adminUsersDestroy->middleware(['jwt.auth', 'jwt.authorize']);

    $adminUsersActivate = $router->post('/api/admin/users/{id}/activate', [UserController::class, 'activate']);
    $adminUsersActivate->setName('admin.users.activate');
    $adminUsersActivate->middleware(['jwt.auth', 'jwt.authorize']);

    $adminUsersDeactivate = $router->post('/api/admin/users/{id}/deactivate', [UserController::class, 'deactivate']);
    $adminUsersDeactivate->setName('admin.users.deactivate');
    $adminUsersDeactivate->middleware(['jwt.auth', 'jwt.authorize']);

    $adminUsersResetPassword = $router->post('/api/admin/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    $adminUsersResetPassword->setName('admin.users.reset-password');
    $adminUsersResetPassword->middleware(['jwt.auth', 'jwt.authorize']);

    // =========================================
    // 角色管理 API 路由 (需要管理員權限)
    // =========================================

    // 取得角色列表
    $rolesIndex = $router->get('/api/roles', [RoleController::class, 'index']);
    $rolesIndex->setName('roles.index');
    $rolesIndex->middleware(['jwt.auth', 'jwt.authorize']);

    // 取得單一角色
    $rolesShow = $router->get('/api/roles/{id}', [RoleController::class, 'show']);
    $rolesShow->setName('roles.show');
    $rolesShow->middleware(['jwt.auth', 'jwt.authorize']);

    // 建立角色
    $rolesStore = $router->post('/api/roles', [RoleController::class, 'store']);
    $rolesStore->setName('roles.store');
    $rolesStore->middleware(['jwt.auth', 'jwt.authorize']);

    // 更新角色
    $rolesUpdate = $router->put('/api/roles/{id}', [RoleController::class, 'update']);
    $rolesUpdate->setName('roles.update');
    $rolesUpdate->middleware(['jwt.auth', 'jwt.authorize']);

    // 刪除角色
    $rolesDestroy = $router->delete('/api/roles/{id}', [RoleController::class, 'destroy']);
    $rolesDestroy->setName('roles.destroy');
    $rolesDestroy->middleware(['jwt.auth', 'jwt.authorize']);

    // 更新角色的權限
    $rolesUpdatePermissions = $router->put('/api/roles/{id}/permissions', [RoleController::class, 'updatePermissions']);
    $rolesUpdatePermissions->setName('roles.update.permissions');
    $rolesUpdatePermissions->middleware(['jwt.auth', 'jwt.authorize']);

    // =========================================
    // 權限管理 API 路由 (需要管理員權限)
    // =========================================

    // 取得權限列表
    $permissionsIndex = $router->get('/api/permissions', [PermissionController::class, 'index']);
    $permissionsIndex->setName('permissions.index');
    $permissionsIndex->middleware(['jwt.auth', 'jwt.authorize']);

    // 取得單一權限
    $permissionsShow = $router->get('/api/permissions/{id}', [PermissionController::class, 'show']);
    $permissionsShow->setName('permissions.show');
    $permissionsShow->middleware(['jwt.auth', 'jwt.authorize']);

    // 取得權限列表（按資源分組）
    $permissionsGrouped = $router->get('/api/permissions/grouped', [RoleController::class, 'permissionsGrouped']);
    $permissionsGrouped->setName('permissions.grouped');
    $permissionsGrouped->middleware(['jwt.auth', 'jwt.authorize']);

    // =========================================
    // 標籤管理 API 路由 (需要認證)
    // =========================================

    // 取得標籤列表 (公開)
    $tagsIndex = $router->get('/api/tags', [TagController::class, 'index']);
    $tagsIndex->setName('tags.index');

    // 取得單一標籤
    $tagsShow = $router->get('/api/tags/{id}', [TagController::class, 'show']);
    $tagsShow->setName('tags.show');

    // 建立標籤 (需要認證)
    $tagsStore = $router->post('/api/tags', [TagController::class, 'store']);
    $tagsStore->setName('tags.store');
    $tagsStore->middleware(['jwt.auth']);

    // 更新標籤 (需要認證和授權)
    $tagsUpdate = $router->put('/api/tags/{id}', [TagController::class, 'update']);
    $tagsUpdate->setName('tags.update');
    $tagsUpdate->middleware(['jwt.auth', 'jwt.authorize']);

    // 刪除標籤 (需要認證和授權)
    $tagsDestroy = $router->delete('/api/tags/{id}', [TagController::class, 'destroy']);
    $tagsDestroy->setName('tags.destroy');
    $tagsDestroy->middleware(['jwt.auth', 'jwt.authorize']);

    // =========================================
    // 系統設定 API 路由 (需要管理員權限)
    // =========================================

    // 取得系統設定
    $settingsIndex = $router->get('/api/settings', [SettingController::class, 'index']);
    $settingsIndex->setName('settings.index');
    $settingsIndex->middleware(['jwt.auth', 'jwt.authorize']);

    // 更新系統設定
    $settingsUpdate = $router->put('/api/settings', [SettingController::class, 'update']);
    $settingsUpdate->setName('settings.update');
    $settingsUpdate->middleware(['jwt.auth', 'jwt.authorize']);

    // 取得單一設定
    $settingsShow = $router->get('/api/settings/{key}', [SettingController::class, 'show']);
    $settingsShow->setName('settings.show');
    $settingsShow->middleware(['jwt.auth', 'jwt.authorize']);

    // 更新單一設定
    $settingsUpdateSingle = $router->put('/api/settings/{key}', [SettingController::class, 'updateSingle']);
    $settingsUpdateSingle->setName('settings.update.single');
    $settingsUpdateSingle->middleware(['jwt.auth', 'jwt.authorize']);

    // 取得時區資訊（公開 API）
    $timezoneInfo = $router->get('/api/settings/timezone/info', [SettingController::class, 'getTimezoneInfo']);
    $timezoneInfo->setName('timezone.info');

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

    // 取得登入失敗統計 (GET /api/v1/activity-logs/login-failures)
    $activityLoginFailures = $router->get('/api/v1/activity-logs/login-failures', [ActivityLogController::class, 'getLoginFailureStats']);
    $activityLoginFailures->setName('activity_logs.login_failures');
    $activityLoginFailures->middleware('jwt.auth');

    // =========================================
    // 貼文瀏覽記錄 API 路由
    // =========================================
    $postViewRecord = $router->post('/api/posts/{id}/view', [\App\Application\Controllers\Api\V1\PostViewController::class, 'recordView']);
    $postViewRecord->setName('posts.view.record');

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

    // 注意：以下路由需要 Slim App 實例，暫時註釋
    // 加載快取監控路由
    // $cacheMonitorRoutes = require __DIR__ . '/routes/cache-monitor.php';
    // $cacheMonitorRoutes($app);

    // 加載標籤管理路由
    // $tagManagementRoutes = require __DIR__ . '/routes/tag-management.php';
    // $tagManagementRoutes($app);

    // 加載統計功能路由
    $statisticsRoutes = require __DIR__ . '/routes/statistics.php';
    foreach ($statisticsRoutes as $routeConfig) {
        $route = $router->map(
            $routeConfig['methods'],
            $routeConfig['path'],
            $routeConfig['handler']
        );
        $route->setName($routeConfig['name']);

        if (isset($routeConfig['middleware'])) {
            foreach ($routeConfig['middleware'] as $middleware) {
                $route->add($middleware);
            }
        }
    }
};
