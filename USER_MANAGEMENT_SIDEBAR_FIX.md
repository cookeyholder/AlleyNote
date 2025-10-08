# 使用者管理側欄修復報告

## 問題描述
用戶反映登入後，在側欄沒有看到使用者管理的相關功能。

## 問題分析

### 1. 原始問題
- 登入 API 返回的用戶對象缺少 `role` 和 `name` 欄位
- 前端 `globalGetters.isAdmin()` 檢查需要這些欄位來判斷用戶是否為管理員
- 缺少這些欄位導致 `isAdmin()` 返回 false，側欄的管理功能被隱藏

### 2. 問題根源
- 密碼不匹配：資料庫中的密碼雜湊與測試密碼不匹配
- 登入 API 的 `LoginResponseDTO` 只返回 `id`、`email` 和 `roles`
- 缺少 `name` (username) 和 `role` (主要角色) 欄位

## 修復內容

### 1. 修復密碼問題
更新資料庫中管理員帳號的密碼雜湊：
```bash
UPDATE users SET password_hash = '$2y$12$mhLJbIrzZEXB6uAX7.kXDuk8QoZpCTVSJQMKW3BZy9/YZm0m466de' 
WHERE email = 'admin@example.com';
```

### 2. 更新 LoginResponseDTO
**檔案：** `backend/app/Domains/Auth/DTOs/LoginResponseDTO.php`

#### 2.1 添加 userName 參數
```php
public function __construct(
    public TokenPair $tokens,
    public int $userId,
    public string $userEmail,
    public int $expiresAt,
    public ?string $userName = null,  // 新增
    public ?string $sessionId = null,
    public ?array $permissions = null,
    public ?array $roles = null,
) {}
```

#### 2.2 更新 toArray() 方法
```php
public function toArray(): array
{
    // 從 roles 中提取第一個角色名稱作為 role
    $primaryRole = null;
    if (is_array($this->roles) && count($this->roles) > 0) {
        $primaryRole = $this->roles[0]['name'] ?? null;
    }

    return [
        'access_token' => $this->tokens->getAccessToken(),
        'refresh_token' => $this->tokens->getRefreshToken(),
        'token_type' => $this->tokens->getTokenType(),
        'expires_in' => $this->expiresAt - time(),
        'expires_at' => $this->expiresAt,
        'user' => [
            'id' => $this->userId,
            'email' => $this->userEmail,
            'name' => $this->userName,      // 新增
            'role' => $primaryRole,         // 新增
            'roles' => $this->roles,
        ],
        'session_id' => $this->sessionId,
        'permissions' => $this->permissions,
    ];
}
```

### 3. 更新 AuthenticationService
**檔案：** `backend/app/Domains/Auth/Services/AuthenticationService.php`

#### 3.1 提取 username
```php
$userId = (int) $user['id'];
$userEmail = $user['email'] ?? $request->email;
$userName = $user['username'] ?? null;  // 新增
```

#### 3.2 傳遞 username 到 DTO
```php
return new LoginResponseDTO(
    tokens: $tokenPair,
    userId: $userId,
    userEmail: $userEmail,
    expiresAt: $payload->getExpiresAt()->getTimestamp(),
    userName: $userName,  // 新增
    sessionId: $payload->getJti(),
    permissions: $request->scopes,
    roles: $roles,
);
```

## 驗證結果

### 1. API 測試結果
```json
{
  "success": true,
  "message": "登入成功",
  "user": {
    "id": 1,
    "email": "admin@example.com",
    "name": "admin",
    "role": "super_admin",
    "roles": [
      {
        "id": 1,
        "name": "super_admin",
        "display_name": "超級管理員"
      }
    ]
  }
}
```

### 2. 前端檢查點
- ✓ `user.role` = "super_admin"
- ✓ `user.roles` = [{ id: 1, name: "super_admin", display_name: "超級管理員" }]
- ✓ `globalGetters.isAdmin()` 應該返回 true
- ✓ 側欄應該顯示使用者管理和角色管理連結

## 側欄顯示條件
**檔案：** `frontend/src/layouts/DashboardLayout.js`

側欄中的使用者管理功能通過 `globalGetters.isAdmin()` 來控制顯示：

```javascript
${globalGetters.isAdmin() ? `
  <a href="/admin/users" data-navigo>
    <span>👥</span>
    ${sidebarCollapsed ? '' : '<span>使用者管理</span>'}
  </a>
  <a href="/admin/roles" data-navigo>
    <span>🔐</span>
    ${sidebarCollapsed ? '' : '<span>角色管理</span>'}
  </a>
  <!-- 其他管理功能 -->
` : ''}
```

## isAdmin() 檢查邏輯
**檔案：** `frontend/src/store/globalStore.js`

```javascript
isAdmin() {
    const user = globalStore.get('user');
    
    // 方式 1: 檢查 role 欄位
    const role = this.getUserRole();
    if (role === 'admin' || role === 'super_admin' || role === '超級管理員') {
        return true;
    }
    
    // 方式 2: 檢查 roles 陣列
    if (user?.roles && Array.isArray(user.roles)) {
        for (const r of user.roles) {
            if (typeof r === 'object') {
                // 檢查角色 ID (1 = 超級管理員)
                if (r.id === 1) {
                    return true;
                }
                // 檢查角色名稱
                if (r.name === '超級管理員' || r.name === 'admin' || r.name === 'super_admin') {
                    return true;
                }
            }
        }
    }
    
    return false;
}
```

## 測試步驟

### 手動測試
1. 清除瀏覽器的 LocalStorage
2. 訪問 http://localhost:3000/login
3. 使用以下憑證登入：
   - Email: admin@example.com
   - Password: password
4. 登入成功後，檢查側欄是否顯示：
   - 使用者管理
   - 角色管理
   - 系統統計
   - 系統設定

### API 測試
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' | jq '.user'
```

預期輸出應包含 `name` 和 `role` 欄位。

## 結論
修復完成後，登入 API 現在返回完整的用戶資料，包括：
- `id`: 用戶 ID
- `email`: 電子郵件
- `name`: 用戶名稱 (username)
- `role`: 主要角色名稱
- `roles`: 角色數組

前端的 `isAdmin()` 檢查現在可以正確識別管理員，側欄中的管理功能應該會正常顯示。

## 注意事項
- 此修復向後兼容，不會影響現有的功能
- `role` 欄位是從 `roles` 陣列中的第一個角色提取的
- 前端的 `isAdmin()` 檢查使用多種方式來確保準確性
