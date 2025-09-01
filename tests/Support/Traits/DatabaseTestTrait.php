<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

use App\Infrastructure\Database\DatabaseConnection;
use PDO;
use PDOException;
use RuntimeException;

/**
 * 資料庫測試功能 Trait.
 *
 * 提供記憶體 SQLite 資料庫的設定和測試資料表建立功能
 */
trait DatabaseTestTrait
{
    protected PDO $db;

    /**
     * 設定測試資料庫.
     */
    protected function setUpDatabase(): void
    {
        // 設定資料庫環境變數
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        // 建立記憶體資料庫連線
        try {
            $this->db = new PDO('sqlite::memory:', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // 啟用外鍵約束
            $this->db->exec('PRAGMA foreign_keys = ON');

            // 建立測試用資料表
            $this->createTestTables();

            // 設定全域資料庫連線實例
            DatabaseConnection::setInstance($this->db);
        } catch (PDOException $e) {
            throw new RuntimeException('無法建立測試資料庫連線：' . $e->getMessage());
        }
    }

    /**
     * 清理資料庫連線.
     */
    protected function tearDownDatabase(): void
    {
        if (isset($this->db)) {
            DatabaseConnection::reset();
            $this->db = new PDO('sqlite::memory:');
        }
    }

    /**
     * 建立測試用資料表.
     */
    protected function createTestTables(): void
    {
        $this->createPostsTable();
        $this->createIpListsTable();
        $this->createAttachmentsTable();
        $this->createUsersTable();
        $this->createRefreshTokensTable();
        $this->createTokenBlacklistTable();
        $this->createUserActivityLogsTable();
        $this->createIndices();
    }

    /**
     * 建立貼文資料表.
     */
    protected function createPostsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                seq_number INTEGER NOT NULL UNIQUE,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                user_ip TEXT,
                views INTEGER NOT NULL DEFAULT 0,
                is_pinned INTEGER NOT NULL DEFAULT 0,
                status INTEGER NOT NULL DEFAULT 1,
                publish_date TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ');
    }

    /**
     * 建立 IP 黑白名單資料表.
     */
    protected function createIpListsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS ip_lists (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                ip_address TEXT NOT NULL,
                type INTEGER NOT NULL DEFAULT 0,
                unit_id INTEGER,
                description TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ');
    }

    /**
     * 建立附件資料表.
     */
    protected function createAttachmentsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS attachments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                post_id INTEGER NOT NULL,
                filename TEXT NOT NULL,
                original_name TEXT NOT NULL,
                mime_type TEXT NOT NULL,
                file_size INTEGER NOT NULL,
                storage_path TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                deleted_at TEXT,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
            )
        ');
    }

    /**
     * 建立使用者資料表.
     */
    protected function createUsersTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                status INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ');
    }

    /**
     * 建立 Refresh Token 資料表.
     */
    protected function createRefreshTokensTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS refresh_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                jti TEXT NOT NULL UNIQUE,
                user_id INTEGER NOT NULL,
                device_id TEXT,
                device_name TEXT,
                device_type TEXT,
                user_agent TEXT,
                ip_address TEXT,
                platform TEXT,
                browser TEXT,
                expires_at TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                token_hash TEXT,
                status TEXT NOT NULL DEFAULT "active",
                revoked_at TEXT,
                revoked_reason TEXT,
                last_used_at TEXT,
                parent_token_jti TEXT,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ');
    }

    /**
     * 建立 Token Blacklist 資料表.
     */
    protected function createTokenBlacklistTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS token_blacklist (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                jti TEXT NOT NULL UNIQUE,
                token_type TEXT NOT NULL,
                user_id INTEGER,
                expires_at TEXT NOT NULL,
                blacklisted_at TEXT NOT NULL,
                reason TEXT,
                device_id TEXT,
                metadata TEXT,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ');
    }

    /**
     * 建立使用者活動記錄資料表.
     */
    protected function createUserActivityLogsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS user_activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                user_id INTEGER,
                session_id TEXT,
                action_type TEXT NOT NULL,
                action_category TEXT NOT NULL,
                target_type TEXT,
                target_id TEXT,
                status TEXT NOT NULL DEFAULT "success",
                description TEXT,
                metadata TEXT,
                ip_address TEXT,
                user_agent TEXT,
                request_method TEXT,
                request_path TEXT,
                created_at TEXT NOT NULL,
                occurred_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ');
    }

    /**
     * 建立資料表索引.
     */
    protected function createIndices(): void
    {
        // Posts 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_posts_uuid ON posts(uuid);
            CREATE INDEX IF NOT EXISTS idx_posts_title ON posts(title);
            CREATE INDEX IF NOT EXISTS idx_posts_publish_date ON posts(publish_date);
            CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id);
            CREATE INDEX IF NOT EXISTS idx_posts_views ON posts(views)
        ');

        // IP Lists 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_ip_lists_uuid ON ip_lists(uuid);
            CREATE INDEX IF NOT EXISTS idx_ip_lists_ip_address ON ip_lists(ip_address);
            CREATE INDEX IF NOT EXISTS idx_ip_lists_type ON ip_lists(type)
        ');

        // Attachments 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_attachments_uuid ON attachments(uuid);
            CREATE INDEX IF NOT EXISTS idx_attachments_post_id ON attachments(post_id);
            CREATE INDEX IF NOT EXISTS idx_attachments_created_at ON attachments(created_at)
        ');

        // User Activity Logs 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_activity_logs_uuid ON user_activity_logs(uuid);
            CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id ON user_activity_logs(user_id);
            CREATE INDEX IF NOT EXISTS idx_activity_logs_session_id ON user_activity_logs(session_id);
            CREATE INDEX IF NOT EXISTS idx_activity_logs_action_type ON user_activity_logs(action_type);
            CREATE INDEX IF NOT EXISTS idx_activity_logs_action_category ON user_activity_logs(action_category);
            CREATE INDEX IF NOT EXISTS idx_activity_logs_target ON user_activity_logs(target_type, target_id);
            CREATE INDEX IF NOT EXISTS idx_activity_logs_status ON user_activity_logs(status);
            CREATE INDEX IF NOT EXISTS idx_activity_logs_ip_address ON user_activity_logs(ip_address);
            CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON user_activity_logs(created_at);
            CREATE INDEX IF NOT EXISTS idx_activity_logs_occurred_at ON user_activity_logs(occurred_at)
        ');
    }

    /**
     * 插入測試用貼文資料.
     *
     * @param array<string, mixed> $data
     */
    protected function insertTestPost(array $data = []): int
    {
        $defaultData = [
            'uuid' => $this->generateTestUuid(),
            'seq_number' => rand(1, 9999),
            'title' => 'Test Post ' . $this->generateRandomString(5),
            'content' => 'Test content for post ' . $this->generateRandomString(10),
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'views' => 0,
            'is_pinned' => 0,
            'status' => 1,
            'publish_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $postData = array_merge($defaultData, $data);

        $stmt = $this->db->prepare('
            INSERT INTO posts (uuid, seq_number, title, content, user_id, user_ip, views, is_pinned, status, publish_date, created_at, updated_at)
            VALUES (:uuid, :seq_number, :title, :content, :user_id, :user_ip, :views, :is_pinned, :status, :publish_date, :created_at, :updated_at)
        ');

        $stmt->execute($postData);

        return (int) $this->db->lastInsertId();
    }

    /**
     * 插入測試用使用者資料.
     *
     * @param array<string, mixed> $data
     */
    protected function insertTestUser(array $data = []): int
    {
        $defaultData = [
            'username' => 'testuser_' . $this->generateRandomString(6),
            'email' => $this->generateTestEmail(),
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $userData = array_merge($defaultData, $data);

        $stmt = $this->db->prepare('
            INSERT INTO users (username, email, password, status, created_at, updated_at)
            VALUES (:username, :email, :password, :status, :created_at, :updated_at)
        ');

        $stmt->execute($userData);

        return (int) $this->db->lastInsertId();
    }
}
