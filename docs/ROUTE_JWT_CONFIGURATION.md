# JWT 路由配置指南

## 概述

本文件說明如何在 AlleyNote 專案中配置 JWT 認證和授權相關的路由。

## 路由架構設計

### 路由層級劃分

```
Public Routes (公開路由)
├── /api/health - 健康檢查
├── /api/docs/* - API 文檔
├── /api/auth/register - 使用者註冊
├── /api/auth/login - 使用者登入
├── /api/auth/refresh - Token 刷新
├── /api/posts (GET) - 瀏覽貼文清單
└── /api/posts/{id} (GET) - 檢視特定貼文

Authenticated Routes (需要認證的路由)
├── /api/auth/logout - 使用者登出
└── /api/auth/me - 取得使用者資訊

Authorized Routes (需要認證和授權的路由)
├── /api/posts (POST) - 建立新貼文
├── /api/posts/{id} (PUT) - 更新貼文
└── /api/posts/{id} (DELETE) - 刪除貼文
```

## 中介軟體配置

### JWT 認證中介軟體 (jwt.auth)

負責驗證 JWT token 的有效性，並將使用者資訊注入到請求中。

**配置範例**：
```php
// 在 DI 容器中註冊
$container->singleton('jwt.auth', function () use ($container) {
    return new JwtAuthenticationMiddleware(
        $container->get(JwtTokenServiceInterface::class)
    );
});
```

**功能**：
- 從 Authorization header、query 參數或 cookie 提取 token
- 驗證 token 有效性和到期時間
- 檢查 token 是否被撤銷
- 將使用者資訊注入到請求屬性中

### JWT 授權中介軟體 (jwt.authorize)

檢查已認證使用者的角色和權限，確保有足夠權限執行操作。

**配置範例**：
```php
// 在 DI 容器中註冊
$container->singleton('jwt.authorize', function () {
    return new JwtAuthorizationMiddleware([
        'role_permissions' => [
            'admin' => ['*'],
            'moderator' => ['posts.*', 'comments.*'],
            'user' => ['posts.show', 'posts.create', 'comments.show', 'comments.create'],
            'guest' => ['posts.show', 'comments.show'],
        ],
        // 其他配置...
    ]);
});
```

**功能**：
- RBAC（基於角色的存取控制）
- ABAC（基於屬性的存取控制）
- 資源擁有者檢查
- IP 和時間基礎的存取控制

## 路由註冊方式

### 方法 1：直接註冊中介軟體實例

```php
// 需要認證的路由
$authLogout = $router->post('/api/auth/logout', [AuthController::class, 'logout']);
$authLogout->addMiddleware($container->get('jwt.auth'));

// 需要認證和授權的路由
$postsStore = $router->post('/api/posts', [PostController::class, 'store']);
$postsStore->addMiddleware($container->get('jwt.auth'));
$postsStore->addMiddleware($container->get('jwt.authorize'));
```

### 方法 2：透過中介軟體管理器註冊

```php
// 註冊中介軟體到管理器
$middlewareManager = $router->getMiddlewareManager();
$middlewareManager->add('jwt.auth', $container->get('jwt.auth'));
$middlewareManager->add('jwt.authorize', $container->get('jwt.authorize'));

// 在路由中使用
$authLogout = $router->post('/api/auth/logout', [AuthController::class, 'logout']);
$authLogout->middleware(['jwt.auth']);

$postsStore = $router->post('/api/posts', [PostController::class, 'store']);
$postsStore->middleware(['jwt.auth', 'jwt.authorize']);
```

### 方法 3：使用路由群組

```php
// 需要認證的路由群組
$router->group([
    'prefix' => '/api',
    'middleware' => ['jwt.auth']
], function () use ($router) {
    $router->post('/auth/logout', [AuthController::class, 'logout']);
    $router->get('/auth/me', [AuthController::class, 'me']);
});

// 需要認證和授權的路由群組
$router->group([
    'prefix' => '/api/posts',
    'middleware' => ['jwt.auth', 'jwt.authorize']
], function () use ($router) {
    $router->post('/', [PostController::class, 'store']);
    $router->put('/{id}', [PostController::class, 'update']);
    $router->delete('/{id}', [PostController::class, 'destroy']);
});
```

## 權限配置

### 角色權限對應

```php
'role_permissions' => [
    // 管理員擁有所有權限
    'admin' => ['*'],
    'super_admin' => ['*'],
    
    // 版主權限
    'moderator' => [
        'posts.*',      // 所有貼文操作
        'comments.*',   // 所有評論操作
        'users.show',   // 檢視使用者
    ],
    
    // 一般使用者權限
    'user' => [
        'posts.show',     // 檢視貼文
        'posts.create',   // 建立貼文
        'posts.update',   // 更新自己的貼文（需配合擁有者檢查）
        'posts.delete',   // 刪除自己的貼文（需配合擁有者檢查）
        'comments.show',  // 檢視評論
        'comments.create', // 建立評論
    ],
    
    // 訪客權限
    'guest' => [
        'posts.show',     // 僅能檢視貼文
        'comments.show',  // 僅能檢視評論
    ],
],
```

### 資源擁有者檢查

對於 `update` 和 `delete` 操作，系統會自動檢查使用者是否為資源的擁有者：

```php
'ownership_rules' => [
    'posts' => true,    // 啟用貼文擁有者檢查
    'comments' => true, // 啟用評論擁有者檢查
],
```

## 錯誤處理

### 認證失敗

當使用者未提供有效的 JWT token 時：

```json
{
    "success": false,
    "error": "使用者未認證",
    "code": "NOT_AUTHENTICATED",
    "timestamp": "2025-08-27T10:30:00Z"
}
```

### 授權失敗

當使用者沒有足夠權限時：

```json
{
    "success": false,
    "error": "使用者無權限執行操作：create on posts",
    "code": "INSUFFICIENT_PERMISSIONS",
    "timestamp": "2025-08-27T10:30:00Z"
}
```

## 測試路由配置

### 公開路由測試

```bash
# 健康檢查
curl -X GET http://localhost:8000/api/health

# 檢視貼文清單
curl -X GET http://localhost:8000/api/posts
```

### 認證路由測試

```bash
# 使用者登入
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password"}'

# 取得使用者資訊（需要 token）
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### 授權路由測試

```bash
# 建立新貼文（需要認證和權限）
curl -X POST http://localhost:8000/api/posts \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title": "New Post", "content": "Post content"}'
```

## 最佳實踐

### 1. 安全性

- 使用 HTTPS 傳輸 JWT token
- 設定適當的 token 過期時間
- 定期輪換 JWT 密鑰
- 實作 token 黑名單機制

### 2. 效能

- 快取權限檢查結果
- 使用適當的資料庫索引
- 最小化中介軟體的執行時間

### 3. 可維護性

- 將權限配置外部化
- 使用語意化的權限名稱
- 建立完整的測試覆蓋

### 4. 監控

- 記錄認證和授權失敗
- 監控異常的存取模式
- 設定警告機制

## 參考資料

- [JWT 認證規格書](JWT_AUTHENTICATION_SPECIFICATION.md)
- [JWT 開發待辦清單](JWT_DEVELOPMENT_TODOLIST.md)
- [路由系統使用指南](ROUTING_SYSTEM_GUIDE.md)