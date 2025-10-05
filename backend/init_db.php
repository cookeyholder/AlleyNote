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
