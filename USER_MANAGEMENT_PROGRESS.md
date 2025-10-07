# 使用者管理模組開發進度

## 開發日期
2025-10-07

---

## 第一階段：資料庫層 ✅ 已完成

### ✅ 資料庫表格
- `roles` 表：角色管理
- `permissions` 表：權限管理
- `user_roles` 表：使用者角色關聯
- `role_permissions` 表：角色權限關聯

### ✅ 初始資料

**角色 (5個)**：
1. super_admin - 超級管理員
2. admin - 管理員
3. editor - 編輯
4. author - 作者
5. user - 一般使用者

**權限 (21個)**：
- users.*: 使用者管理（create, read, update, delete）
- posts.*: 文章管理（create, read, update, delete, publish）
- roles.*: 角色管理（create, read, update, delete, assign）
- tags.*: 標籤管理（create, read, update, delete）
- statistics.read: 統計查看
- settings.*: 系統設定（read, update）

**角色權限分配**：
- super_admin: 所有權限（21個）
- admin: 除 settings.update 外的所有權限（20個）
- editor: 文章、標籤完整管理（10個）
- author: 文章建立與編輯（4個）
- user: 文章和標籤查看（2個）

**使用者角色**：
- admin 使用者已分配 super_admin 角色

---

## 第二階段：後端 Domain Layer ⏳ 進行中

### ✅ 已存在的模型
- `Role.php` - 角色實體
- `Permission.php` - 權限實體

### ⏳ 待開發
1. **Repositories**
   - [ ] UserRepository 擴充（查詢、更新使用者）
   - [ ] RoleRepository
   - [ ] PermissionRepository

2. **Services**
   - [ ] UserManagementService
   - [ ] RoleManagementService
   - [ ] PermissionService

3. **DTOs**
   - [ ] CreateUserDTO
   - [ ] UpdateUserDTO
   - [ ] AssignRoleDTO
   - [ ] UserListResponseDTO

---

## 第三階段：後端 Application Layer ⏳ 待開發

### 待開發內容
1. **Controllers**
   - [ ] UserController (CRUD)
   - [ ] RoleController (CRUD)
   - [ ] UserRoleController (分配角色)

2. **API 端點設計**
```
GET    /api/users              - 使用者列表（分頁、搜尋）
GET    /api/users/{id}         - 取得單一使用者
POST   /api/users              - 新增使用者
PUT    /api/users/{id}         - 更新使用者
DELETE /api/users/{id}         - 刪除使用者
POST   /api/users/{id}/roles   - 分配角色給使用者
DELETE /api/users/{id}/roles/{roleId} - 移除使用者角色

GET    /api/roles              - 角色列表
GET    /api/roles/{id}         - 取得單一角色
POST   /api/roles              - 新增角色
PUT    /api/roles/{id}         - 更新角色
DELETE /api/roles/{id}         - 刪除角色
GET    /api/roles/{id}/permissions - 取得角色的權限列表
PUT    /api/roles/{id}/permissions - 更新角色權限

GET    /api/permissions        - 權限列表
```

---

## 第四階段：前端 ⏳ 待開發

### 待開發內容
1. **API 模組** (`frontend/src/api/modules/users.js`)
   - [ ] 使用者 CRUD API 封裝
   - [ ] 角色管理 API 封裝
   - [ ] 權限 API 封裝

2. **使用者列表頁面** (`frontend/src/pages/admin/users.js`)
   - [ ] 使用者表格
   - [ ] 搜尋功能
   - [ ] 分頁
   - [ ] 操作按鈕（新增、編輯、刪除）

3. **使用者編輯頁面** (`frontend/src/pages/admin/userEditor.js`)
   - [ ] 使用者表單
   - [ ] 角色選擇（多選）
   - [ ] 密碼設定
   - [ ] 狀態設定

4. **角色管理頁面** (`frontend/src/pages/admin/roles.js`)
   - [ ] 角色列表
   - [ ] 權限分配介面
   - [ ] 新增/編輯角色

5. **路由配置**
   - [ ] 更新 `frontend/src/router/index.js`
   - [ ] 新增使用者管理路由

---

## 下一步行動

1. ⏳ **立即開始**：開發 UserRepository 和相關 Services
2. ⏳ **今日完成**：後端 API Controllers
3. ⏳ **明日完成**：前端介面開發

---

**開發人員**：AI Assistant (Claude)  
**狀態**：資料庫層已完成，後端開發中  
**預估完成時間**：2-3 天
