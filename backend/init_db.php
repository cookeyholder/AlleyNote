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
    
    echo "正在初始化資料庫...\n";
    
    // 創建 users 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'user',
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ users 表已創建\n";
    
    // 創建 posts 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content TEXT,
            excerpt TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            author_id INTEGER NOT NULL,
            published_at DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ posts 表已創建\n";
    
    // 創建 tags 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL UNIQUE,
            slug VARCHAR(100) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ tags 表已創建\n";
    
    // 創建 post_tags 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS post_tags (
            post_id INTEGER NOT NULL,
            tag_id INTEGER NOT NULL,
            PRIMARY KEY (post_id, tag_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        )
    ");
    echo "✓ post_tags 表已創建\n";
    
    // 創建 attachments 表
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
    
    // 創建 refresh_tokens 表
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
    
    // 創建 ip_lists 表
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
    
    // 創建 user_activity_logs 表
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
    
    // 創建 comments 表
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
    
    // 創建 post_views 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS post_views (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER NOT NULL,
            user_id INTEGER,
            ip_address VARCHAR(45),
            user_agent TEXT,
            referer TEXT,
            viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "✓ post_views 表已創建\n";
    
    // 創建 statistics_snapshots 表
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
    
    // 創建 token_blacklist 表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS token_blacklist (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            jti VARCHAR(36) NOT NULL UNIQUE,
            user_id INTEGER NOT NULL,
            token_type VARCHAR(20) NOT NULL,
            expires_at DATETIME NOT NULL,
            blacklisted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reason VARCHAR(255),
            ip_address VARCHAR(45),
            user_agent TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ token_blacklist 表已創建\n";
    
    // 創建索引
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_author ON posts(author_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_published ON posts(published_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_attachments_post ON attachments(post_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_refresh_tokens_user ON refresh_tokens(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_refresh_tokens_jti ON refresh_tokens(jti)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_comments_post ON comments(post_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_post_views_post ON post_views(post_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_token_blacklist_jti ON token_blacklist(jti)");
    echo "✓ 索引已創建\n";
    
    // 檢查是否已有使用者
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        echo "\n正在創建測試使用者...\n";
        
        // 插入管理員使用者
        // 密碼: password (使用 password_hash)
        $adminPassword = password_hash('password', PASSWORD_BCRYPT);
        $superAdminPassword = password_hash('admin123', PASSWORD_BCRYPT);
        
        $pdo->exec("
            INSERT INTO users (username, email, password, role, is_active) VALUES
            ('admin', 'admin@example.com', '$adminPassword', 'admin', 1),
            ('superadmin', 'superadmin@example.com', '$superAdminPassword', 'super_admin', 1)
        ");
        
        echo "✓ 已創建以下測試帳號：\n";
        echo "  1. admin@example.com / password (管理員)\n";
        echo "  2. superadmin@example.com / admin123 (主管理員)\n";
    } else {
        echo "\n資料庫已有使用者，跳過創建\n";
    }
    
    echo "\n✅ 資料庫初始化完成！\n";
    echo "\n您現在可以使用以下帳號登入：\n";
    echo "  - admin@example.com / password\n";
    echo "  - superadmin@example.com / admin123\n";
    
} catch (PDOException $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
