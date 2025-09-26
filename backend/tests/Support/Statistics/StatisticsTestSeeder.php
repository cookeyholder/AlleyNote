<?php

declare(strict_types=1);

namespace Tests\Support\Statistics;

use PDO;

/**
 * 統計測試資料種子.
 *
 * 為統計相關的整合測試提供一致的基礎資料。
 * 包含測試用的 users, posts, views, comments 等相關資料。
 */
final class StatisticsTestSeeder
{
    public function __construct(
        private readonly PDO $db,
    ) {}

    /**
     * 建立所有統計相關的測試資料.
     */
    public function seedAll(): void
    {
        $this->createTables();
        $this->seedUsers();
        $this->seedPosts();
        $this->seedUserActivityLogs();
        $this->seedComments();
        $this->seedPostViews();
    }

    /**
     * 建立測試資料表結構.
     */
    public function createTables(): void
    {
        $this->createUsersTable();
        $this->createPostsTable();
        $this->createUserActivityLogsTable();
        $this->createCommentsTable();
        $this->createPostViewsTable();
        $this->createStatisticsSnapshotsTable();
    }

    /**
     * 建立使用者資料.
     */
    public function seedUsers(): void
    {
        $users = [
            ['user1', 'user1@example.com', '2024-01-01 09:00:00'],
            ['user2', 'user2@example.com', '2024-01-01 10:00:00'],
            ['user3', 'user3@example.com', '2024-01-01 11:00:00'],
            ['user4', 'user4@example.com', '2024-01-02 09:00:00'],
            ['user5', 'user5@example.com', '2024-01-02 10:00:00'],
        ];

        foreach ($users as $i => $user) {
            $this->db->exec('
                INSERT INTO users
                (id, username, email, password, status, created_at, updated_at)
                VALUES (' . ($i + 1) . ", '{$user[0]}', '{$user[1]}', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '{$user[2]}', '{$user[2]}')
            ");
        }
    }

    /**
     * 建立文章資料.
     */
    public function seedPosts(): void
    {
        $posts = [
            ['技術文章1', '深入探討 PHP 8.4 的新特性，包括改進的型別系統、效能優化和新的語法糖。', 1, 'published', 'web', 120, 5, 10, 0, '2024-01-01 10:00:00'],
            ['生活分享1', '分享日常生活中的美好時刻和感悟，從小事中發現大道理。', 2, 'published', 'mobile', 80, 3, 8, 1, '2024-01-01 14:00:00'],
            ['旅遊心得1', '最近去日本旅遊的心得分享，包含景點推薦、美食攻略、交通指南等實用資訊。', 3, 'published', 'web', 150, 8, 15, 0, '2024-01-01 16:00:00'],
            ['美食推薦1', '推薦幾家台北不錯的餐廳，包含各種價位和料理類型，適合不同場合。', 1, 'draft', 'web', 0, 0, 0, 0, '2024-01-01 18:00:00'],
            ['技術文章2', 'Docker 容器化實戰教學，從基礎概念到進階應用，完整的容器化解決方案。', 2, 'published', 'api', 200, 12, 20, 1, '2024-01-02 10:00:00'],
            ['程式設計心得', '十年程式設計師的職涯分享，包含技術成長、團隊合作、專案管理等經驗談。', 4, 'published', 'web', 180, 15, 25, 0, '2024-01-02 14:00:00'],
            ['學習筆記', '最新的前端框架學習心得，比較不同技術的優缺點和適用場景。', 3, 'published', 'web', 90, 6, 12, 0, '2024-01-02 16:00:00'],
        ];

        foreach ($posts as $i => $post) {
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            $this->db->exec('
                INSERT INTO posts
                (id, uuid, seq_number, title, content, user_id, user_ip, status, views, is_pinned, publish_date, created_at, updated_at, creation_source, creation_source_detail)
                VALUES (' . ($i + 1) . ", '{$uuid}', " . ($i + 1) . ", '{$post[0]}', '{$post[1]}', {$post[2]}, '192.168.1.1', '{$post[3]}', {$post[5]}, {$post[8]}, '{$post[9]}', '{$post[9]}', '{$post[9]}', '{$post[4]}', null)
            ");
        }
    }

    /**
     * 建立使用者活動記錄.
     */
    public function seedUserActivityLogs(): void
    {
        $activities = [
            // 2024-01-01 的活動
            [1, 'login', 1800, '2024-01-01 09:30:00'],
            [1, 'view', 0, '2024-01-01 09:35:00'],
            [1, 'view', 0, '2024-01-01 09:40:00'],
            [1, 'login', 2400, '2024-01-01 14:00:00'],
            [2, 'login', 1200, '2024-01-01 10:30:00'],
            [2, 'view', 0, '2024-01-01 10:35:00'],
            [2, 'view', 0, '2024-01-01 10:40:00'],
            [2, 'view', 0, '2024-01-01 15:00:00'],
            [3, 'login', 3600, '2024-01-01 11:30:00'],
            [3, 'view', 0, '2024-01-01 11:35:00'],
            [3, 'view', 0, '2024-01-01 17:00:00'],

            // 2024-01-02 的活動
            [1, 'login', 2000, '2024-01-02 09:00:00'],
            [1, 'view', 0, '2024-01-02 09:05:00'],
            [2, 'login', 1500, '2024-01-02 10:00:00'],
            [3, 'login', 1800, '2024-01-02 11:00:00'],
            [4, 'login', 2200, '2024-01-02 09:30:00'],
            [4, 'view', 0, '2024-01-02 09:35:00'],
            [4, 'view', 0, '2024-01-02 14:30:00'],
            [5, 'login', 900, '2024-01-02 10:30:00'],
            [5, 'view', 0, '2024-01-02 10:35:00'],
        ];

        foreach ($activities as $i => $activity) {
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            $this->db->exec('
                INSERT INTO user_activity_logs
                (id, uuid, user_id, action_type, action_category, status, created_at, occurred_at)
                VALUES (' . ($i + 1) . ", '{$uuid}', {$activity[0]}, '{$activity[1]}', 'user', 'success', '{$activity[3]}', '{$activity[3]}')
            ");
        }
    }

    /**
     * 建立評論資料.
     */
    public function seedComments(): void
    {
        $comments = [
            [1, 1, '很棒的技術分享！對 PHP 8.4 的新特性講解很詳細。', '2024-01-01 10:30:00'],
            [1, 2, '感謝作者的用心整理，學到很多新知識。', '2024-01-01 11:00:00'],
            [1, 3, '期待更多關於效能優化的內容。', '2024-01-01 11:30:00'],
            [2, 1, '很溫馨的分享，生活中確實有很多值得感悟的小事。', '2024-01-01 15:00:00'],
            [2, 3, '作者的文筆很好，讀起來很有共鳴。', '2024-01-01 15:30:00'],
            [3, 1, '日本旅遊攻略很實用，收藏了！', '2024-01-01 17:30:00'],
            [3, 2, '美食推薦看起來都很不錯，下次去日本一定要試試。', '2024-01-01 18:00:00'],
            [3, 4, '交通指南很詳細，對自由行很有幫助。', '2024-01-02 09:00:00'],
            [5, 1, 'Docker 教學寫得很清楚，適合初學者。', '2024-01-02 11:00:00'],
            [5, 3, '容器化確實是現代開發的重要技能。', '2024-01-02 11:30:00'],
            [5, 4, '實戰案例很有參考價值。', '2024-01-02 12:00:00'],
            [6, 2, '十年經驗的分享很寶貴，給剛入行的人很多啟發。', '2024-01-02 15:00:00'],
            [6, 3, '職涯規劃的部分說得很中肯。', '2024-01-02 15:30:00'],
            [6, 5, '團隊合作的心得很實用。', '2024-01-02 16:00:00'],
            [7, 1, '前端技術變化確實很快，需要持續學習。', '2024-01-02 17:00:00'],
            [7, 2, '框架比較很客觀，幫助選擇合適的技術。', '2024-01-02 17:30:00'],
        ];

        foreach ($comments as $i => $comment) {
            $this->db->exec('
                INSERT INTO comments
                (id, post_id, user_id, content, created_at)
                VALUES (' . ($i + 1) . ", {$comment[0]}, {$comment[1]}, '{$comment[2]}', '{$comment[3]}')
            ");
        }
    }

    /**
     * 建立文章瀏覽記錄.
     */
    public function seedPostViews(): void
    {
        $viewRecords = [
            [1, 1, '192.168.1.1', '2024-01-01 10:15:00'],
            [1, 2, '192.168.1.2', '2024-01-01 10:30:00'],
            [1, 3, '192.168.1.3', '2024-01-01 11:00:00'],
            [1, null, '192.168.1.4', '2024-01-01 11:30:00'], // 匿名瀏覽
            [2, 1, '192.168.1.1', '2024-01-01 15:00:00'],
            [2, 3, '192.168.1.3', '2024-01-01 15:15:00'],
            [2, null, '192.168.1.5', '2024-01-01 15:30:00'],
            [3, 1, '192.168.1.1', '2024-01-01 17:00:00'],
            [3, 2, '192.168.1.2', '2024-01-01 17:15:00'],
            [3, 3, '192.168.1.3', '2024-01-01 17:30:00'],
            [3, 4, '192.168.1.6', '2024-01-02 09:00:00'],
            [3, null, '192.168.1.7', '2024-01-02 09:15:00'],
            [5, 1, '192.168.1.1', '2024-01-02 10:30:00'],
            [5, 2, '192.168.1.2', '2024-01-02 10:45:00'],
            [5, 3, '192.168.1.3', '2024-01-02 11:00:00'],
            [5, 4, '192.168.1.6', '2024-01-02 11:15:00'],
            [5, 5, '192.168.1.8', '2024-01-02 11:30:00'],
            [6, 2, '192.168.1.2', '2024-01-02 15:00:00'],
            [6, 3, '192.168.1.3', '2024-01-02 15:15:00'],
            [6, 4, '192.168.1.6', '2024-01-02 15:30:00'],
            [6, 5, '192.168.1.8', '2024-01-02 15:45:00'],
            [7, 1, '192.168.1.1', '2024-01-02 17:00:00'],
            [7, 2, '192.168.1.2', '2024-01-02 17:15:00'],
            [7, 3, '192.168.1.3', '2024-01-02 17:30:00'],
        ];

        foreach ($viewRecords as $i => $view) {
            $userIdValue = $view[1] !== null ? $view[1] : 'NULL';
            $this->db->exec('
                INSERT INTO post_views
                (id, post_id, user_id, ip_address, viewed_at)
                VALUES (' . ($i + 1) . ", {$view[0]}, {$userIdValue}, '{$view[2]}', '{$view[3]}')
            ");
        }
    }

    /**
     * 建立使用者表.
     */
    private function createUsersTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL DEFAULT "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi", -- default: password
                status INTEGER DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    /**
     * 建立文章表.
     */
    private function createPostsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY,
                uuid VARCHAR(36) NOT NULL UNIQUE,
                seq_number INTEGER NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                user_ip VARCHAR(45) NULL,
                status VARCHAR(20) DEFAULT "published",
                views INTEGER DEFAULT 0,
                is_pinned BOOLEAN DEFAULT 0,
                publish_date DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                creation_source VARCHAR(50) DEFAULT "web",
                creation_source_detail VARCHAR(255) NULL,
                FOREIGN KEY (user_id) REFERENCES users (id)
            )
        ');
    }

    /**
     * 建立使用者活動記錄表.
     */
    private function createUserActivityLogsTable(): void
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
                FOREIGN KEY (user_id) REFERENCES users (id)
            )
        ');
    }

    /**
     * 建立評論表.
     */
    private function createCommentsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS comments (
                id INTEGER PRIMARY KEY,
                post_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts (id),
                FOREIGN KEY (user_id) REFERENCES users (id)
            )
        ');
    }

    /**
     * 建立文章瀏覽記錄表.
     */
    private function createPostViewsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS post_views (
                id INTEGER PRIMARY KEY,
                post_id INTEGER NOT NULL,
                user_id INTEGER,
                ip_address VARCHAR(45),
                viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts (id),
                FOREIGN KEY (user_id) REFERENCES users (id)
            )
        ');
    }

    /**
     * 建立統計快照表.
     */
    private function createStatisticsSnapshotsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS statistics_snapshots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) NOT NULL UNIQUE,
                snapshot_type VARCHAR(100) NOT NULL,
                period_type VARCHAR(20) NOT NULL,
                period_start TIMESTAMP NOT NULL,
                period_end TIMESTAMP NOT NULL,
                statistics_data JSON NOT NULL,
                metadata JSON DEFAULT "{}",
                expires_at TIMESTAMP,
                total_views INTEGER DEFAULT 0,
                total_unique_viewers INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // 建立索引以提升查詢效能
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_snapshots_type_period ON statistics_snapshots (snapshot_type, period_start, period_end)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_snapshots_expires_at ON statistics_snapshots (expires_at)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_snapshots_uuid ON statistics_snapshots (uuid)');
    }

    /**
     * 清除所有測試資料.
     */
    public function clearAll(): void
    {
        $tables = [
            'post_views',
            'comments',
            'user_activity_logs',
            'posts',
            'users',
            'statistics_snapshots',
        ];

        foreach ($tables as $table) {
            $this->db->exec("DELETE FROM {$table}");
        }
    }

    /**
     * 刪除所有測試資料表.
     */
    public function dropTables(): void
    {
        $tables = [
            'post_views',
            'comments',
            'user_activity_logs',
            'posts',
            'users',
            'statistics_snapshots',
        ];

        foreach ($tables as $table) {
            $this->db->exec("DROP TABLE IF EXISTS {$table}");
        }
    }
}
