# 待修復問題清單

## 問題 1：編輯文章時不會帶入原來的文章內容 ✅ 已修復

**檔案**: `frontend/src/pages/admin/postEditor.js`

**問題**：
- API 回傳 `{success, data: {...}}` 格式
- 前端直接賦值 `post = await postsAPI.get(postId)`
- 應該是 `post = result.data`

**修復**：
```javascript
const result = await postsAPI.get(postId);
post = result.data;
```

**狀態**: ✅ 已修復

---

## 問題 2：首頁顯示尚未到發布時間的文章 ⏳ 待修復

**檔案**: `backend/app/Domains/Post/Repositories/PostRepository.php`

**問題**：
- `paginate()` 方法沒有檢查 `publish_date`
- 對於 `status='published'` 的文章，應該只顯示 `publish_date <= NOW()` 的文章

**需要修復的位置**：
1. PostRepository::paginate() - 第 57-58 行的 SQL 查詢
2. PostRepository::getPinnedPosts() - 第 91 行的 SQL 查詢
3. PostRepository::getPostsByTag() - 第 110 行的 SQL 查詢

**修復方案**：
在 WHERE 條件中加入：
```sql
AND (status != 'published' OR publish_date IS NULL OR publish_date <= datetime('now'))
```

**狀態**: ⏳ 需要修復

---

## 問題 3：建立使用者管理介面 ⏳ 待開發

**需求**：
主管理員需要管理：
1. 使用者列表（查看、新增、編輯、刪除）
2. 使用者權限管理
3. 使用者群組管理
4. 角色與權限分配

**開發計劃**：

### 3.1 後端 API

#### 檔案結構：
```
backend/app/
├── Application/Controllers/Api/V1/
│   └── UserController.php (需新增)
├── Domains/User/
│   ├── Entities/
│   │   ├── User.php (已存在)
│   │   ├── Role.php (需新增)
│   │   └── Permission.php (需新增)
│   ├── Services/
│   │   ├── UserService.php (需檢查)
│   │   ├── RoleService.php (需新增)
│   │   └── PermissionService.php (需新增)
│   ├── Repositories/
│   │   ├── UserRepository.php (需檢查)
│   │   ├── RoleRepository.php (需新增)
│   │   └── PermissionRepository.php (需新增)
│   └── DTOs/
│       ├── CreateUserDTO.php (需新增)
│       ├── UpdateUserDTO.php (需新增)
│       └── AssignRoleDTO.php (需新增)
```

#### API 端點：
```
GET    /api/users              - 使用者列表（分頁、搜尋、篩選）
GET    /api/users/{id}         - 取得單一使用者
POST   /api/users              - 新增使用者
PUT    /api/users/{id}         - 更新使用者
DELETE /api/users/{id}         - 刪除使用者
POST   /api/users/{id}/roles   - 分配角色
GET    /api/roles              - 角色列表
GET    /api/permissions        - 權限列表
```

### 3.2 資料庫結構

#### users 表（已存在，需檢查）
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    last_login DATETIME NULL
);
```

#### roles 表（需新增）
```sql
CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL
);
```

#### permissions 表（需新增）
```sql
CREATE TABLE permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    resource VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

#### user_roles 表（需新增）
```sql
CREATE TABLE user_roles (
    user_id INTEGER NOT NULL,
    role_id INTEGER NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assigned_by INTEGER NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### role_permissions 表（需新增）
```sql
CREATE TABLE role_permissions (
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);
```

### 3.3 前端介面

#### 檔案結構：
```
frontend/src/
├── api/modules/
│   └── users.js (需新增)
├── pages/admin/
│   ├── users.js (需新增) - 使用者列表
│   ├── userEditor.js (需新增) - 使用者編輯器
│   └── roles.js (需新增) - 角色管理
└── router/
    └── index.js (需更新路由)
```

#### 介面功能：

**使用者列表頁面** (`/admin/users`)：
- 表格顯示所有使用者
- 欄位：ID、使用者名稱、Email、角色、最後登入、狀態
- 功能：搜尋、篩選、分頁
- 操作：新增、編輯、刪除、分配角色

**使用者編輯頁面** (`/admin/users/{id}/edit`)：
- 表單欄位：使用者名稱、Email、密碼（選填）
- 角色選擇（多選）
- 狀態設定（啟用/停用）

**角色管理頁面** (`/admin/roles`)：
- 角色列表
- 權限分配
- 新增/編輯/刪除角色

### 3.4 開發順序

1. ✅ 檢查現有的 User 相關程式碼
2. ⏳ 建立資料庫遷移（roles, permissions, user_roles, role_permissions）
3. ⏳ 實作後端 Domain Layer（Entities, Services, Repositories）
4. ⏳ 實作後端 API Controller
5. ⏳ 實作前端 API 模組
6. ⏳ 實作前端使用者列表頁面
7. ⏳ 實作前端使用者編輯頁面
8. ⏳ 實作前端角色管理頁面
9. ⏳ 測試與驗證

**狀態**: ⏳ 待開發（大型功能）

---

## 優先順序

1. 🔥 **問題 1** - 已修復
2. 🔥 **問題 2** - 高優先（影響使用者體驗）
3. 📋 **問題 3** - 中優先（新功能開發）

---

## 下一步行動

1. 立即修復問題 2（publish_date 過濾）
2. 重新建置前端
3. 測試修復結果
4. 規劃問題 3 的開發時程
