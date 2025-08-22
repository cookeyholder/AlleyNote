-- 建立角色表
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 建立權限表
CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    resource VARCHAR(255) NOT NULL,
    action VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 建立角色權限關聯表
CREATE TABLE IF NOT EXISTS role_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE(role_id, permission_id)
);

-- 建立使用者角色關聯表
CREATE TABLE IF NOT EXISTS user_roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    role_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE(user_id, role_id)
);

-- 建立使用者權限表（直接權限分配）
CREATE TABLE IF NOT EXISTS user_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE(user_id, permission_id)
);

-- 插入預設角色
INSERT OR IGNORE INTO roles (name, description) VALUES
('admin', '系統管理員 - 擁有所有權限'),
('editor', '編輯者 - 可以建立、編輯和刪除文章'),
('moderator', '版主 - 可以管理文章狀態和 IP'),
('user', '一般使用者 - 基本瀏覽權限');

-- 插入預設權限
INSERT OR IGNORE INTO permissions (name, description, resource, action) VALUES
-- 文章相關權限
('post:create', '建立文章', 'post', 'create'),
('post:read', '讀取文章', 'post', 'read'),
('post:update', '更新文章', 'post', 'update'),
('post:delete', '刪除文章', 'post', 'delete'),
('post:pin', '置頂文章', 'post', 'pin'),
('post:publish', '發布文章', 'post', 'publish'),

-- 附件相關權限
('attachment:create', '上傳附件', 'attachment', 'create'),
('attachment:read', '讀取附件', 'attachment', 'read'),
('attachment:delete', '刪除附件', 'attachment', 'delete'),

-- IP 管理權限
('ip:create', '新增 IP 規則', 'ip', 'create'),
('ip:read', '讀取 IP 規則', 'ip', 'read'),
('ip:update', '更新 IP 規則', 'ip', 'update'),
('ip:delete', '刪除 IP 規則', 'ip', 'delete'),

-- 使用者管理權限
('user:read', '讀取使用者資料', 'user', 'read'),
('user:update', '更新使用者資料', 'user', 'update'),
('user:delete', '刪除使用者', 'user', 'delete'),
('user:manage_roles', '管理使用者角色', 'user', 'manage_roles'),

-- 系統管理權限
('system:config', '系統設定', 'system', 'config'),
('system:logs', '查看系統日誌', 'system', 'logs'),
('system:backup', '系統備份', 'system', 'backup');

-- 為角色分配權限
-- admin 角色獲得所有權限
INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r, permissions p 
WHERE r.name = 'admin';

-- editor 角色的權限
INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r, permissions p 
WHERE r.name = 'editor' 
AND p.name IN (
    'post:create', 'post:read', 'post:update', 'post:delete', 'post:publish',
    'attachment:create', 'attachment:read', 'attachment:delete'
);

-- moderator 角色的權限
INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r, permissions p 
WHERE r.name = 'moderator' 
AND p.name IN (
    'post:read', 'post:update', 'post:pin', 'post:publish',
    'attachment:read', 'attachment:delete',
    'ip:create', 'ip:read', 'ip:update', 'ip:delete'
);

-- user 角色的權限
INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r, permissions p 
WHERE r.name = 'user' 
AND p.name IN (
    'post:read', 'attachment:read'
);
