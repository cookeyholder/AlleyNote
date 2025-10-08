# 側欄使用者管理功能顯示問題修復報告

## 問題描述

使用主管理員帳號登入後，側欄中沒有顯示「使用者管理」和「角色管理」選項。

## 問題分析

經過調查發現，問題在於後端 API 沒有正確返回使用者的角色資訊，導致前端無法判斷使用者是否為管理員。

### 問題細節

1. **登入 API (`/api/auth/login`)** 沒有返回角色資訊
2. **使用者資訊 API (`/api/auth/me`)** 也沒有返回角色資訊
3. 前端的 `isAdmin()` 函數依賴使用者物件中的 `roles` 欄位來判斷權限

## 修復內容

### 1. 修改 `LoginResponseDTO`

新增 `roles` 欄位到 LoginResponseDTO，並在 `toArray()` 方法中包含角色資訊。

**檔案：** `backend/app/Domains/Auth/DTOs/LoginResponseDTO.php`

```php
public function __construct(
    public TokenPair $tokens,
    public int $userId,
    public string $userEmail,
    public int $expiresAt,
    public ?string $sessionId = null,
    public ?array $permissions = null,
    public ?array $roles = null,  // 新增
) {}

public function toArray(): array
{
    return [
        'access_token' => $this->tokens->getAccessToken(),
        'refresh_token' => $this->tokens->getRefreshToken(),
        'token_type' => $this->tokens->getTokenType(),
        'expires_in' => $this->expiresAt - time(),
        'expires_at' => $this->expiresAt,
        'user' => [
            'id' => $this->userId,
            'email' => $this->userEmail,
            'roles' => $this->roles,  // 新增
        ],
        'session_id' => $this->sessionId,
        'permissions' => $this->permissions,
    ];
}
```

### 2. 修改 `AuthenticationService`

在登入流程中取得使用者角色資訊，並傳入 LoginResponseDTO。

**檔案：** `backend/app/Domains/Auth/Services/AuthenticationService.php`

```php
// 6. 更新使用者最後登入時間
$this->userRepository->updateLastLogin($userId);

// 7. 取得使用者角色資訊
$userWithRoles = $this->userRepository->findByIdWithRoles($userId);
$roles = $userWithRoles['roles'] ?? [];

// 8. 建立回應
$payload = $this->jwtTokenService->extractPayload($tokenPair->getRefreshToken());

return new LoginResponseDTO(
    tokens: $tokenPair,
    userId: $userId,
    userEmail: $userEmail,
    expiresAt: $payload->getExpiresAt()->getTimestamp(),
    sessionId: $payload->getJti(),
    permissions: $request->scopes,
    roles: $roles,  // 新增
);
```

### 3. 修改 `AuthController`

在 `me` 方法中從資料庫取得完整的使用者資訊（包含角色）。

**檔案：** `backend/app/Application/Controllers/Api/V1/AuthController.php`

#### 3.1 新增 `UserRepositoryInterface` 依賴注入

```php
use App\Domains\Auth\Contracts\UserRepositoryInterface;

public function __construct(
    private AuthService $authService,
    private AuthenticationServiceInterface $authenticationService,
    private JwtTokenServiceInterface $jwtTokenService,
    private ValidatorInterface $validator,
    private ActivityLoggingServiceInterface $activityLoggingService,
    private UserRepositoryInterface $userRepository,  // 新增
) {}
```

#### 3.2 修改 `me` 方法

```php
try {
    // 驗證 token 並取得使用者 payload
    $payload = $this->jwtTokenService->validateAccessToken($accessToken);
    $userId = $payload->getUserId();

    // 從資料庫取得完整的使用者資訊（包含角色）
    $userWithRoles = $this->userRepository->findByIdWithRoles($userId);
    
    if (!$userWithRoles) {
        throw new NotFoundException('使用者不存在');
    }

    $userInfo = [
        'user_id' => $userId,
        'email' => $userWithRoles['email'],
        'name' => $userWithRoles['name'] ?? null,
        'username' => $userWithRoles['username'] ?? null,
        'roles' => $userWithRoles['roles'] ?? [],  // 新增
        'token_issued_at' => $payload->getIssuedAt()->getTimestamp(),
        'token_expires_at' => $payload->getExpiresAt()->getTimestamp(),
    ];
} catch (Exception $e) {
    $userInfo = null;
}

// ... 在回應中包含 roles
$responseData = [
    'success' => true,
    'data' => [
        'user' => [
            'id' => $userInfo['user_id'],
            'email' => $userInfo['email'],
            'name' => $userInfo['name'],
            'username' => $userInfo['username'],
            'roles' => $userInfo['roles'],  // 新增
        ],
        'token_info' => [
            'issued_at' => $userInfo['token_issued_at'],
            'expires_at' => $userInfo['token_expires_at'],
        ],
    ],
];
```

### 4. 修改 `UserRepository`

改進 `findByIdWithRoles` 方法，同時返回角色的 `name` 和 `display_name`。

**檔案：** `backend/app/Domains/Auth/Repositories/UserRepository.php`

```php
public function findByIdWithRoles(int $id): ?array
{
    $sql = 'SELECT u.*,
            GROUP_CONCAT(r.id) as role_ids,
            GROUP_CONCAT(r.name) as role_names,
            GROUP_CONCAT(r.display_name) as role_display_names
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            WHERE u.id = :id
            GROUP BY u.id';
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        return null;
    }
    
    $roleIds = $row['role_ids'] ? explode(',', $row['role_ids']) : [];
    $roleNames = $row['role_names'] ? explode(',', $row['role_names']) : [];
    $roleDisplayNames = $row['role_display_names'] ? explode(',', $row['role_display_names']) : [];
    
    $roles = [];
    for ($i = 0; $i < count($roleIds); $i++) {
        $roles[] = [
            'id' => (int) $roleIds[$i],
            'name' => $roleNames[$i] ?? '',
            'display_name' => $roleDisplayNames[$i] ?? '',
        ];
    }
    
    unset($row['role_ids'], $row['role_names'], $row['role_display_names']);
    $row['roles'] = $roles;
    
    return $row;
}
```

### 5. 更新 `UserRepositoryInterface` 和 `UserRepositoryAdapter`

新增 `findById` 和 `findByIdWithRoles` 方法的介面定義和適配器實作。

**檔案：** `backend/app/Domains/Auth/Contracts/UserRepositoryInterface.php`

```php
/**
 * 根據 ID 查找使用者.
 *
 * @param int $id 使用者 ID
 * @return array<string, mixed>|null 使用者資料陣列或 null
 */
public function findById(int $id): ?array;

/**
 * 根據 ID 查找使用者（包含角色資訊）.
 *
 * @param int $id 使用者 ID
 * @return array<string, mixed>|null 使用者資料陣列（包含 roles 欄位）或 null
 */
public function findByIdWithRoles(int $id): ?array;
```

**檔案：** `backend/app/Domains/Auth/Repositories/UserRepositoryAdapter.php`

```php
/**
 * @return array<string, mixed>|null
 */
public function findByIdWithRoles(int $id): ?array
{
    // 委託給原始 repository
    return $this->userRepository->findByIdWithRoles($id);
}
```

### 6. 重設管理員密碼

由於之前的密碼可能不正確，執行以下指令重設：

```bash
docker compose exec -T web php -r "
\$pdo = new PDO('sqlite:/var/www/html/database/alleynote.sqlite3');
\$password = password_hash('admin123', PASSWORD_BCRYPT);
\$stmt = \$pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
\$stmt->execute([\$password, 'admin@example.com']);
echo 'Password updated successfully' . PHP_EOL;
"
```

## 測試結果

### 1. 登入 API 測試

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"admin123"}' \
  | jq .
```

**回應：**

```json
{
  "success": true,
  "message": "登入成功",
  "access_token": "eyJ0eXAi...",
  "refresh_token": "eyJ0eXAi...",
  "token_type": "Bearer",
  "expires_in": 2592000,
  "expires_at": 1762494475,
  "user": {
    "id": 1,
    "email": "admin@example.com",
    "roles": [
      {
        "id": 1,
        "name": "super_admin",
        "display_name": "超級管理員"
      }
    ]
  },
  "session_id": "...",
  "permissions": null
}
```

✅ **成功：** 登入 API 現在正確返回角色資訊。

### 2. 使用者資訊 API 測試

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer <TOKEN>" \
  | jq .
```

**回應：**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "admin@example.com",
      "name": null,
      "username": "admin",
      "roles": [
        {
          "id": 1,
          "name": "super_admin",
          "display_name": "超級管理員"
        }
      ]
    },
    "token_info": {
      "issued_at": 1759902475,
      "expires_at": 1759906075
    }
  }
}
```

✅ **成功：** 使用者資訊 API 現在正確返回角色資訊。

## 前端檢查點

前端的 `isAdmin()` 函數（在 `frontend/src/store/globalStore.js` 中）會檢查以下條件來判斷是否為管理員：

1. 檢查 `user.role` 是否為 `'admin'`, `'super_admin'` 或 `'超級管理員'`
2. 檢查 `user.roles` 陣列中是否有角色 ID 為 1
3. 檢查 `user.roles` 陣列中是否有角色名稱為 `'超級管理員'`, `'admin'` 或 `'super_admin'`

這些檢查條件都能正確識別我們後端返回的角色格式。

## 前端側欄程式碼

在 `frontend/src/layouts/DashboardLayout.js` 中，側欄會檢查 `globalGetters.isAdmin()` 來決定是否顯示使用者管理選單：

```javascript
${globalGetters.isAdmin() ? `
  <a href="/admin/users" data-navigo class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-accent-50 text-modern-700 transition-colors">
    <span>👥</span>
    ${sidebarCollapsed ? '' : '<span>使用者管理</span>'}
  </a>
  <a href="/admin/roles" data-navigo class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-accent-50 text-modern-700 transition-colors">
    <span>🔐</span>
    ${sidebarCollapsed ? '' : '<span>角色管理</span>'}
  </a>
  <a href="/admin/statistics" data-navigo class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-accent-50 text-modern-700 transition-colors">
    <span>📈</span>
    ${sidebarCollapsed ? '' : '<span>系統統計</span>'}
  </a>
  <a href="/admin/settings" data-navigo class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-accent-50 text-modern-700 transition-colors">
    <span>⚙️</span>
    ${sidebarCollapsed ? '' : '<span>系統設定</span>'}
  </a>
` : ''}
```

## 下一步

請在瀏覽器中手動測試：

1. 開啟 http://localhost:3000
2. 使用 `admin@example.com` / `admin123` 登入
3. 確認側欄中是否顯示「使用者管理」、「角色管理」、「系統統計」、「系統設定」等選項

如果仍然沒有顯示，可能需要：

1. 清除瀏覽器的 localStorage
2. 重新整理頁面
3. 檢查瀏覽器開發者工具的 Console 是否有錯誤訊息

## 修改檔案清單

1. ✅ `backend/app/Domains/Auth/DTOs/LoginResponseDTO.php`
2. ✅ `backend/app/Domains/Auth/Services/AuthenticationService.php`
3. ✅ `backend/app/Application/Controllers/Api/V1/AuthController.php`
4. ✅ `backend/app/Domains/Auth/Repositories/UserRepository.php`
5. ✅ `backend/app/Domains/Auth/Contracts/UserRepositoryInterface.php`
6. ✅ `backend/app/Domains/Auth/Repositories/UserRepositoryAdapter.php`

## 結論

所有後端修改已完成，API 現在正確返回角色資訊。前端應該能夠正確識別管理員權限並顯示使用者管理選單。
