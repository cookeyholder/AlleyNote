<?php

declare(strict_types=1);

/**
 * 快速資料庫初始化腳本
 * 創建基礎表結構並插入測試使用者
 */

try {
    $dbPath = __DIR__ . '/database/alleynote.sqlite3';

    // 確保資料庫目錄存在
    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0755, true);
    }

    // 連接資料庫
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    echo "正在初始化資料庫...\n";

    /**
     * 取得資料表欄位清單.
     *
     * @return array<int, string>
     */
    $getColumns = static function (PDO $pdo, string $table): array {
        $columns = $pdo->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_map(
            static fn(array $column): string => (string) ($column['name'] ?? ''),
            $columns,
        );

        return array_filter($columnNames);
    };

    $ensureColumn = static function (PDO $pdo, string $table, string $column, string $definition) use ($getColumns): void {
        $columns = $getColumns($pdo, $table);
        if (!in_array($column, $columns, true)) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            echo "✓ {$table}.{$column} 欄位已補齊\n";
        }
    };

    $hasColumn = static function (PDO $pdo, string $table, string $column) use ($getColumns): bool {
        return in_array($column, $getColumns($pdo, $table), true);
    };

    // users 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid VARCHAR(36) UNIQUE,
            username VARCHAR(255) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255),
            status INTEGER NOT NULL DEFAULT 1,
            role VARCHAR(50) NOT NULL DEFAULT 'user',
            is_active INTEGER NOT NULL DEFAULT 1,
            last_login DATETIME,
            deleted_at DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ users 表已創建\n";

    $ensureColumn($pdo, 'users', 'uuid', 'VARCHAR(36)');
    $ensureColumn($pdo, 'users', 'password', 'VARCHAR(255)');
    $ensureColumn($pdo, 'users', 'password_hash', 'VARCHAR(255)');
    $ensureColumn($pdo, 'users', 'status', 'INTEGER NOT NULL DEFAULT 1');
    $ensureColumn($pdo, 'users', 'role', "VARCHAR(50) NOT NULL DEFAULT 'user'");
    $ensureColumn($pdo, 'users', 'is_active', 'INTEGER NOT NULL DEFAULT 1');
    $ensureColumn($pdo, 'users', 'last_login', 'DATETIME');
    $ensureColumn($pdo, 'users', 'deleted_at', 'DATETIME');

    // posts 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid VARCHAR(36) NOT NULL UNIQUE,
            seq_number INTEGER NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL DEFAULT '',
            user_id INTEGER NOT NULL,
            user_ip VARCHAR(45),
            views INTEGER NOT NULL DEFAULT 0,
            comments_count INTEGER NOT NULL DEFAULT 0,
            likes_count INTEGER NOT NULL DEFAULT 0,
            is_pinned INTEGER NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            publish_date DATETIME,
            deleted_at DATETIME,
            creation_source VARCHAR(20) DEFAULT 'web',
            creation_source_detail TEXT,
            slug VARCHAR(255),
            excerpt TEXT,
            author_id INTEGER,
            published_at DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ posts 表已創建\n";

    $ensureColumn($pdo, 'posts', 'uuid', 'VARCHAR(36)');
    $ensureColumn($pdo, 'posts', 'seq_number', 'INTEGER');
    $ensureColumn($pdo, 'posts', 'user_id', 'INTEGER');
    $ensureColumn($pdo, 'posts', 'user_ip', 'VARCHAR(45)');
    $ensureColumn($pdo, 'posts', 'views', 'INTEGER NOT NULL DEFAULT 0');
    $ensureColumn($pdo, 'posts', 'comments_count', 'INTEGER NOT NULL DEFAULT 0');
    $ensureColumn($pdo, 'posts', 'likes_count', 'INTEGER NOT NULL DEFAULT 0');
    $ensureColumn($pdo, 'posts', 'is_pinned', 'INTEGER NOT NULL DEFAULT 0');
    $ensureColumn($pdo, 'posts', 'publish_date', 'DATETIME');
    $ensureColumn($pdo, 'posts', 'deleted_at', 'DATETIME');
    $ensureColumn($pdo, 'posts', 'creation_source', "VARCHAR(20) DEFAULT 'web'");
    $ensureColumn($pdo, 'posts', 'creation_source_detail', 'TEXT');
    $ensureColumn($pdo, 'posts', 'slug', 'VARCHAR(255)');
    $ensureColumn($pdo, 'posts', 'excerpt', 'TEXT');
    $ensureColumn($pdo, 'posts', 'author_id', 'INTEGER');
    $ensureColumn($pdo, 'posts', 'published_at', 'DATETIME');

    // 舊資料相容：若 user_id 尚未填，嘗試用 author_id 或預設 admin(1) 補齊
    if ($hasColumn($pdo, 'posts', 'user_id')) {
        if ($hasColumn($pdo, 'posts', 'author_id')) {
            $pdo->exec('UPDATE posts SET user_id = COALESCE(user_id, author_id, 1) WHERE user_id IS NULL OR user_id = 0');
        } else {
            $pdo->exec('UPDATE posts SET user_id = 1 WHERE user_id IS NULL OR user_id = 0');
        }
    }
    if ($hasColumn($pdo, 'posts', 'seq_number')) {
        $pdo->exec('UPDATE posts SET seq_number = id WHERE seq_number IS NULL OR seq_number = 0');
    }

    // tags 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL UNIQUE,
            slug VARCHAR(255) UNIQUE,
            description TEXT,
            color VARCHAR(7),
            usage_count INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ tags 表已創建\n";
    $ensureColumn($pdo, 'tags', 'slug', 'VARCHAR(255)');
    $ensureColumn($pdo, 'tags', 'description', 'TEXT');
    $ensureColumn($pdo, 'tags', 'color', 'VARCHAR(7)');
    $ensureColumn($pdo, 'tags', 'usage_count', 'INTEGER NOT NULL DEFAULT 0');

    // post_tags 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS post_tags (
            post_id INTEGER NOT NULL,
            tag_id INTEGER NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (post_id, tag_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        )
    ");
    echo "✓ post_tags 表已創建\n";
    $ensureColumn($pdo, 'post_tags', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

    // attachments 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attachments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid VARCHAR(36) NOT NULL UNIQUE,
            post_id INTEGER NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size INTEGER NOT NULL,
            storage_path VARCHAR(500) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        )
    ");
    echo "✓ attachments 表已創建\n";

    // refresh_tokens 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS refresh_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            jti VARCHAR(36) NOT NULL UNIQUE,
            user_id INTEGER NOT NULL,
            device_id VARCHAR(255),
            device_name VARCHAR(255),
            device_type VARCHAR(50),
            user_agent TEXT,
            ip_address VARCHAR(45),
            platform VARCHAR(50),
            browser VARCHAR(50),
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            token_hash VARCHAR(255),
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            revoked_at DATETIME,
            revoked_reason TEXT,
            last_used_at DATETIME,
            parent_token_jti VARCHAR(36),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ refresh_tokens 表已創建\n";
    $ensureColumn($pdo, 'refresh_tokens', 'device_id', 'VARCHAR(255)');
    $ensureColumn($pdo, 'refresh_tokens', 'device_name', 'VARCHAR(255)');
    $ensureColumn($pdo, 'refresh_tokens', 'device_type', 'VARCHAR(50)');
    $ensureColumn($pdo, 'refresh_tokens', 'user_agent', 'TEXT');
    $ensureColumn($pdo, 'refresh_tokens', 'ip_address', 'VARCHAR(45)');
    $ensureColumn($pdo, 'refresh_tokens', 'platform', 'VARCHAR(50)');
    $ensureColumn($pdo, 'refresh_tokens', 'browser', 'VARCHAR(50)');
    $ensureColumn($pdo, 'refresh_tokens', 'token_hash', 'VARCHAR(255)');
    $ensureColumn($pdo, 'refresh_tokens', 'status', "VARCHAR(20) NOT NULL DEFAULT 'active'");
    $ensureColumn($pdo, 'refresh_tokens', 'revoked_at', 'DATETIME');
    $ensureColumn($pdo, 'refresh_tokens', 'revoked_reason', 'TEXT');
    $ensureColumn($pdo, 'refresh_tokens', 'last_used_at', 'DATETIME');
    $ensureColumn($pdo, 'refresh_tokens', 'parent_token_jti', 'VARCHAR(36)');

    // ip_lists 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ip_lists (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid VARCHAR(36) NOT NULL UNIQUE,
            ip_address VARCHAR(45) NOT NULL,
            type INTEGER NOT NULL DEFAULT 0,
            unit_id INTEGER,
            description TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ ip_lists 表已創建\n";

    // user_activity_logs 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid VARCHAR(36) NOT NULL UNIQUE,
            user_id INTEGER,
            activity_type VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            request_method VARCHAR(10),
            request_uri TEXT,
            request_data TEXT,
            response_status INTEGER,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "✓ user_activity_logs 表已創建\n";
    $ensureColumn($pdo, 'user_activity_logs', 'action_type', 'VARCHAR(50)');
    $ensureColumn($pdo, 'user_activity_logs', 'action_category', 'VARCHAR(50)');
    $ensureColumn($pdo, 'user_activity_logs', 'status', "VARCHAR(20) NOT NULL DEFAULT 'success'");

    // comments 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid VARCHAR(36) NOT NULL UNIQUE,
            post_id INTEGER NOT NULL,
            user_id INTEGER,
            parent_id INTEGER,
            content TEXT NOT NULL,
            user_ip VARCHAR(45),
            status INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
        )
    ");
    echo "✓ comments 表已創建\n";

    // post_views 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS post_views (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid VARCHAR(36),
            post_id INTEGER NOT NULL,
            user_id INTEGER,
            user_ip VARCHAR(45),
            ip_address VARCHAR(45),
            user_agent TEXT,
            referrer TEXT,
            referer TEXT,
            view_date DATETIME,
            viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "✓ post_views 表已創建\n";
    $ensureColumn($pdo, 'post_views', 'uuid', 'VARCHAR(36)');
    $ensureColumn($pdo, 'post_views', 'user_ip', 'VARCHAR(45)');
    $ensureColumn($pdo, 'post_views', 'referrer', 'TEXT');
    $ensureColumn($pdo, 'post_views', 'view_date', 'DATETIME');

    // statistics_snapshots 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS statistics_snapshots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid VARCHAR(36) NOT NULL UNIQUE,
            period_type VARCHAR(20) NOT NULL,
            period_start DATETIME NOT NULL,
            period_end DATETIME NOT NULL,
            snapshot_data TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ statistics_snapshots 表已創建\n";

    // token_blacklist 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS token_blacklist (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            jti VARCHAR(36) NOT NULL UNIQUE,
            user_id INTEGER,
            token_type VARCHAR(20) NOT NULL,
            expires_at DATETIME NOT NULL,
            blacklisted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reason VARCHAR(255),
            device_id VARCHAR(255),
            metadata TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "✓ token_blacklist 表已創建\n";
    $ensureColumn($pdo, 'token_blacklist', 'device_id', 'VARCHAR(255)');
    $ensureColumn($pdo, 'token_blacklist', 'metadata', 'TEXT');

    // roles / permissions / 關聯表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(50) NOT NULL UNIQUE,
            display_name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME
        )
    ");
    echo "✓ roles 表已創建\n";
    $ensureColumn($pdo, 'roles', 'display_name', "VARCHAR(100) NOT NULL DEFAULT ''");
    $ensureColumn($pdo, 'roles', 'description', 'TEXT');
    $ensureColumn($pdo, 'roles', 'updated_at', 'DATETIME');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL UNIQUE,
            display_name VARCHAR(100) NOT NULL,
            description TEXT,
            resource VARCHAR(50) NOT NULL,
            action VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ permissions 表已創建\n";
    $ensureColumn($pdo, 'permissions', 'display_name', "VARCHAR(100) NOT NULL DEFAULT ''");
    $ensureColumn($pdo, 'permissions', 'description', 'TEXT');
    $ensureColumn($pdo, 'permissions', 'resource', "VARCHAR(50) NOT NULL DEFAULT ''");
    $ensureColumn($pdo, 'permissions', 'action', "VARCHAR(50) NOT NULL DEFAULT ''");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            role_id INTEGER NOT NULL,
            assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, role_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
        )
    ");
    echo "✓ user_roles 表已創建\n";
    $ensureColumn($pdo, 'user_roles', 'assigned_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS role_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role_id INTEGER NOT NULL,
            permission_id INTEGER NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        )
    ");
    echo "✓ role_permissions 表已創建\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            permission_id INTEGER NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, permission_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        )
    ");
    echo "✓ user_permissions 表已創建\n";

    // 創建索引
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status)");
    if ($hasColumn($pdo, 'posts', 'user_id')) {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id)");
    }
    if ($hasColumn($pdo, 'posts', 'author_id')) {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_author ON posts(author_id)");
    }
    if ($hasColumn($pdo, 'posts', 'publish_date')) {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_publish_date ON posts(publish_date)");
    }
    if ($hasColumn($pdo, 'posts', 'published_at')) {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_published ON posts(published_at)");
    }
    if ($hasColumn($pdo, 'posts', 'seq_number')) {
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_posts_seq_number ON posts(seq_number)");
    }
    if ($hasColumn($pdo, 'posts', 'deleted_at')) {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_deleted_at ON posts(deleted_at)");
    }
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_attachments_post ON attachments(post_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_refresh_tokens_user ON refresh_tokens(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_refresh_tokens_jti ON refresh_tokens(jti)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_comments_post ON comments(post_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_post_views_post ON post_views(post_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_token_blacklist_jti ON token_blacklist(jti)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_roles_user_id ON user_roles(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_roles_role_id ON user_roles(role_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_permissions_resource_action ON permissions(resource, action)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_role_permissions_role_id ON role_permissions(role_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_role_permissions_permission_id ON role_permissions(permission_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_permissions_user_id ON user_permissions(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_permissions_permission_id ON user_permissions(permission_id)");
    echo "✓ 索引已創建\n";

    // 檢查是否已有使用者
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();

    if ($userCount == 0) {
        echo "\n正在創建測試使用者...\n";

        // 插入管理員使用者
        // 預設密碼：Admin@123456 / SuperAdmin@123456 (使用 password_hash)
        $adminPassword = password_hash('Admin@123456', PASSWORD_BCRYPT);
        $superAdminPassword = password_hash('SuperAdmin@123456', PASSWORD_BCRYPT);

        $pdo->exec("
            INSERT INTO users (uuid, username, email, password, password_hash, role, is_active, status) VALUES
            (lower(hex(randomblob(16))), 'admin', 'admin@example.com', '$adminPassword', '$adminPassword', 'admin', 1, 1),
            (lower(hex(randomblob(16))), 'superadmin', 'superadmin@example.com', '$superAdminPassword', '$superAdminPassword', 'super_admin', 1, 1)
        ");

        echo "✓ 已創建以下測試帳號：\n";
        echo "  1. admin@example.com / Admin@123456 (管理員)\n";
        echo "  2. superadmin@example.com / SuperAdmin@123456 (主管理員)\n";
    } else {
        echo "\n資料庫已有使用者，跳過創建\n";
    }

    // 建立預設角色
    $pdo->exec("
        INSERT OR IGNORE INTO roles (name, display_name, description) VALUES
        ('admin', '管理員', '系統管理員'),
        ('super_admin', '超級管理員', '最高權限管理員'),
        ('editor', '編輯', '可以建立、編輯和發布文章'),
        ('author', '作者', '可以建立和編輯自己的文章'),
        ('user', '一般使用者', '只能瀏覽公開內容')
    ");

    // 建立基礎權限（供授權服務查詢）
    $pdo->exec("
        INSERT OR IGNORE INTO permissions (name, display_name, resource, action, description) VALUES
        ('posts.create', '建立文章', 'posts', 'create', '可以新增文章'),
        ('posts.read', '查看文章', 'posts', 'read', '可以查看文章'),
        ('posts.update', '更新文章', 'posts', 'update', '可以修改文章'),
        ('posts.delete', '刪除文章', 'posts', 'delete', '可以刪除文章'),
        ('posts.publish', '發布文章', 'posts', 'publish', '可以發布文章'),
        ('tags.create', '建立標籤', 'tags', 'create', '可以新增標籤'),
        ('tags.read', '查看標籤', 'tags', 'read', '可以查看標籤'),
        ('tags.update', '更新標籤', 'tags', 'update', '可以修改標籤'),
        ('tags.delete', '刪除標籤', 'tags', 'delete', '可以刪除標籤')
    ");

    // 指派預設角色給測試帳號
    $pdo->exec("
        INSERT OR IGNORE INTO user_roles (user_id, role_id)
        SELECT u.id, r.id
        FROM users u
        JOIN roles r ON (
            (u.username = 'admin' AND r.name = 'admin') OR
            (u.username = 'superadmin' AND r.name = 'super_admin')
        )
    ");

    // 指派 admin/super_admin 角色預設權限
    $pdo->exec("
        INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
        SELECT r.id, p.id
        FROM roles r
        JOIN permissions p
        WHERE r.name IN ('admin', 'super_admin')
    ");

    echo "\n✅ 資料庫初始化完成！\n";
    echo "\n您現在可以使用以下帳號登入：\n";
    echo "  - admin@example.com / Admin@123456\n";
    echo "  - superadmin@example.com / SuperAdmin@123456\n";

} catch (PDOException $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
