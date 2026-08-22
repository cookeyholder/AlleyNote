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
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 5,
            ]);

            // 啟用外鍵約束與效能設定
            DatabaseConnection::applySqlitePragmas($this->db, true);

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
        $this->createCommentsTable();
        $this->createPostViewsTable();
        $this->createStatisticsSnapshotsTable();
        $this->createTagsTable();
        $this->createPostTagsTable();
        $this->createRolesTable();
        $this->createPermissionsTable();
        $this->createUserRolesTable();
        $this->createRolePermissionsTable();
        $this->createUserPermissionsTable();
        $this->createSettingsTable();
        $this->createNotificationsTable();
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
                comments_count INTEGER DEFAULT 0,
                likes_count INTEGER DEFAULT 0,
                is_pinned INTEGER NOT NULL DEFAULT 0,
                status INTEGER NOT NULL DEFAULT 1,
                publish_date TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                deleted_at TEXT,
                creation_source TEXT DEFAULT "unknown",
                creation_source_detail TEXT
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
     * 建立評論資料表.
     */
    protected function createCommentsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ');
    }

    /**
     * 建立文章瀏覽記錄資料表.
     */
    protected function createPostViewsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS post_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER NOT NULL,
                user_id INTEGER,
                ip_address TEXT,
                viewed_at TEXT NOT NULL,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ');
    }

    /**
     * 建立統計快照資料表.
     */
    protected function createStatisticsSnapshotsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS statistics_snapshots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                snapshot_type TEXT NOT NULL,
                period_type TEXT NOT NULL,
                period_start TEXT NOT NULL,
                period_end TEXT NOT NULL,
                statistics_data TEXT NOT NULL,
                metadata TEXT,
                expires_at TEXT,
                total_views INTEGER DEFAULT 0,
                total_unique_viewers INTEGER DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ');
    }

    /**
     * 建立標籤資料表.
     */
    protected function createTagsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                slug TEXT UNIQUE,
                description TEXT,
                color TEXT,
                usage_count INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT
            )
        ');
    }

    /**
     * 建立貼文標籤關聯資料表.
     */
    protected function createPostTagsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS post_tags (
                post_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (post_id, tag_id),
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
            )
        ');
    }

    /**
     * 建立角色資料表.
     */
    protected function createRolesTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                display_name TEXT NOT NULL,
                description TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT
            )
        ');

        $this->db->exec("
            INSERT OR IGNORE INTO roles (id, name, display_name, description) VALUES
            (1, 'admin', '系統管理員', '擁有系統所有權限'),
            (2, 'user', '一般使用者', '一般使用者權限')
        ");
    }

    /**
     * 建立權限資料表.
     */
    protected function createPermissionsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                display_name TEXT NOT NULL,
                description TEXT,
                resource TEXT NOT NULL,
                action TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    /**
     * 建立使用者角色關聯資料表.
     */
    protected function createUserRolesTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS user_roles (
                user_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL,
                assigned_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                assigned_by INTEGER,
                PRIMARY KEY (user_id, role_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ');
    }

    /**
     * 建立角色權限關聯資料表.
     */
    protected function createRolePermissionsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (role_id, permission_id),
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            )
        ');
    }

    /**
     * 建立使用者直接權限關聯資料表.
     */
    protected function createUserPermissionsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS user_permissions (
                user_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                assigned_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, permission_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            )
        ');
    }

    /**
     * 建立系統設定資料表並填入預設設定.
     */
    protected function createSettingsTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key TEXT NOT NULL UNIQUE,
                value TEXT,
                type TEXT NOT NULL DEFAULT 'string',
                description TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $stmt = $this->db->prepare("
            INSERT OR IGNORE INTO settings (key, value, type, description) VALUES
            (:k1, :v1, 'string', '網站名稱'),
            (:k2, :v2, 'string', '網站描述'),
            (:k3, :v3, 'integer', '每頁文章數量'),
            (:k4, :v4, 'boolean', '允許使用者註冊'),
            (:k5, :v5, 'boolean', '允許留言'),
            (:k6, :v6, 'integer', '最大上傳檔案大小（位元組）'),
            (:k7, :v7, 'json', '允許的檔案類型')
        ");
        $stmt->execute([
            'k1' => 'site_name', 'v1' => 'AlleyNote',
            'k2' => 'site_description', 'v2' => 'AlleyNote 公布欄系統',
            'k3' => 'posts_per_page', 'v3' => '20',
            'k4' => 'enable_registration', 'v4' => '1',
            'k5' => 'enable_comments', 'v5' => '1',
            'k6' => 'max_upload_size', 'v6' => '10485760',
            'k7' => 'allowed_file_types', 'v7' => json_encode(['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']),
        ]);
    }

    /**
     * 建立通知資料表.
     */
    protected function createNotificationsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                user_id INTEGER NOT NULL,
                type TEXT NOT NULL,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                data TEXT,
                is_read INTEGER NOT NULL DEFAULT 0,
                read_at TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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

        // Comments 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_comments_post_id ON comments(post_id);
            CREATE INDEX IF NOT EXISTS idx_comments_user_id ON comments(user_id);
        ');

        // Post Views 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_post_views_post_id ON post_views(post_id);
            CREATE INDEX IF NOT EXISTS idx_post_views_user_id ON post_views(user_id);
        ');

        // Statistics Snapshots 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_snapshots_uuid ON statistics_snapshots(uuid);
            CREATE INDEX IF NOT EXISTS idx_snapshots_type_period ON statistics_snapshots(snapshot_type, period_start, period_end);
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

        // Tags 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_tags_name ON tags(name);
            CREATE INDEX IF NOT EXISTS idx_tags_slug ON tags(slug);
            CREATE INDEX IF NOT EXISTS idx_post_tags_tag_id ON post_tags(tag_id)
        ');

        // Settings 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_settings_key ON settings(key)
        ');

        // Notifications 索引
        $this->db->exec('
            CREATE INDEX IF NOT EXISTS idx_notifications_uuid ON notifications(uuid);
            CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id);
            CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read)
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
            'uuid'                   => $this->generateTestUuid(),
            'seq_number'             => rand(1, 99999),
            'title'                  => 'Test Post ' . $this->generateRandomString(5),
            'content'                => 'Test content for post ' . $this->generateRandomString(10),
            'user_id'                => 1,
            'user_ip'                => '127.0.0.1',
            'views'                  => 0,
            'is_pinned'              => 0,
            'status'                 => 'published',
            'publish_date'           => gmdate('Y-m-d H:i:s', time() - 3600),
            'created_at'             => gmdate('Y-m-d H:i:s', time() - 3600),
            'updated_at'             => gmdate('Y-m-d H:i:s', time() - 3600),
            'creation_source'        => 'unknown',
            'creation_source_detail' => null,
        ];

        $postData = array_merge($defaultData, $data);
        $userId = (int) $postData['user_id'];

        // 確保使用者存在以滿足外鍵約束
        $userCheck = $this->db->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
        $userCheck->execute([$userId]);
        if ((int) $userCheck->fetchColumn() === 0) {
            $this->insertTestUser(['id' => $userId, 'username' => 'user_' . $userId]);
        }

        $stmt = $this->db->prepare('
            INSERT INTO posts (uuid, seq_number, title, content, user_id, user_ip, views, is_pinned, status, publish_date, created_at, updated_at, creation_source, creation_source_detail)
            VALUES (:uuid, :seq_number, :title, :content, :user_id, :user_ip, :views, :is_pinned, :status, :publish_date, :created_at, :updated_at, :creation_source, :creation_source_detail)
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
            'username'   => 'testuser_' . $this->generateRandomString(6),
            'email'      => $this->generateTestEmail(),
            'password'   => password_hash('password123', PASSWORD_BCRYPT),
            'status'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $userData = array_merge($defaultData, $data);

        if (isset($userData['id'])) {
            $stmt = $this->db->prepare('
                INSERT INTO users (id, username, email, password, status, created_at, updated_at)
                VALUES (:id, :username, :email, :password, :status, :created_at, :updated_at)
            ');
        } else {
            $stmt = $this->db->prepare('
                INSERT INTO users (username, email, password, status, created_at, updated_at)
                VALUES (:username, :email, :password, :status, :created_at, :updated_at)
            ');
        }

        $stmt->execute($userData);

        return isset($userData['id']) ? (int) $userData['id'] : (int) $this->db->lastInsertId();
    }

    protected function generateRandomString(int $length = 10): string
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }

    protected function generateTestEmail(): string
    {
        return 'test_' . $this->generateRandomString(8) . '@example.com';
    }

    protected function generateTestUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
