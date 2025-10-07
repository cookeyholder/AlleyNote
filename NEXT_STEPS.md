# 使用者管理模組 - 剩餘工作

## 已完成 ✅
1. 資料庫表格與初始資料
2. 後端 DTOs、Repositories、Services
3. 後端 Controllers (UserController, RoleController)

## 待完成工作

### 1. 後端路由註冊（5分鐘）

在 `backend/config/routes/api.php` 末尾加入：

```php
// 使用者管理
'users.index' => [
    'methods' => ['GET'],
    'path' => '/api/users',
    'handler' => [UserController::class, 'index'],
    'middleware' => ['auth'],
],
'users.show' => [
    'methods' => ['GET'],
    'path' => '/api/users/{id}',
    'handler' => [UserController::class, 'show'],
    'middleware' => ['auth'],
],
'users.store' => [
    'methods' => ['POST'],
    'path' => '/api/users',
    'handler' => [UserController::class, 'store'],
    'middleware' => ['auth'],
],
'users.update' => [
    'methods' => ['PUT'],
    'path' => '/api/users/{id}',
    'handler' => [UserController::class, 'update'],
    'middleware' => ['auth'],
],
'users.destroy' => [
    'methods' => ['DELETE'],
    'path' => '/api/users/{id}',
    'handler' => [UserController::class, 'destroy'],
    'middleware' => ['auth'],
],
'users.assign_roles' => [
    'methods' => ['PUT'],
    'path' => '/api/users/{id}/roles',
    'handler' => [UserController::class, 'assignRoles'],
    'middleware' => ['auth'],
],

// 角色管理
'roles.index' => [
    'methods' => ['GET'],
    'path' => '/api/roles',
    'handler' => [RoleController::class, 'index'],
    'middleware' => ['auth'],
],
'roles.show' => [
    'methods' => ['GET'],
    'path' => '/api/roles/{id}',
    'handler' => [RoleController::class, 'show'],
    'middleware' => ['auth'],
],
'roles.update_permissions' => [
    'methods' => ['PUT'],
    'path' => '/api/roles/{id}/permissions',
    'handler' => [RoleController::class, 'updatePermissions'],
    'middleware' => ['auth'],
],
'permissions.index' => [
    'methods' => ['GET'],
    'path' => '/api/permissions',
    'handler' => [RoleController::class, 'permissions'],
    'middleware' => ['auth'],
],
```

### 2. 依賴注入註冊（10分鐘）

在 `backend/bootstrap/dependencies.php` 加入：

```php
// Repositories
$container->set(RoleRepository::class, function(ContainerInterface $c) {
    return new RoleRepository($c->get(PDO::class));
});

$container->set(PermissionRepository::class, function(ContainerInterface $c) {
    return new PermissionRepository($c->get(PDO::class));
});

// Services
$container->set(UserManagementService::class, function(ContainerInterface $c) {
    return new UserManagementService(
        $c->get(UserRepository::class)
    );
});

$container->set(RoleManagementService::class, function(ContainerInterface $c) {
    return new RoleManagementService(
        $c->get(RoleRepository::class),
        $c->get(PermissionRepository::class)
    );
});

// Controllers
$container->set(UserController::class, function(ContainerInterface $c) {
    return new UserController(
        $c->get(UserManagementService::class)
    );
});

$container->set(RoleController::class, function(ContainerInterface $c) {
    return new RoleController(
        $c->get(RoleManagementService::class)
    );
});
```

### 3. 前端 API 模組（已準備好程式碼）

建立 `frontend/src/api/modules/users.js`

### 4. 前端頁面（已準備好程式碼）

建立以下檔案：
- `frontend/src/pages/admin/users.js` - 使用者列表
- `frontend/src/pages/admin/userEditor.js` - 使用者編輯
- `frontend/src/pages/admin/roles.js` - 角色管理

### 5. 路由配置

在 `frontend/src/router/index.js` 加入路由

---

## 快速完成指令

```bash
# 1. 測試後端 API
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8000/api/users
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8000/api/roles
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8000/api/permissions

# 2. 重新建置前端
npm run frontend:build

# 3. 重啟服務
docker compose restart nginx web
```

---

**狀態**：後端 API 已完成 90%，需完成路由註冊和 DI 配置  
**預估剩餘時間**：1-2 小時（含前端開發）
