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
        // No longer needed, tables are created by DatabaseTestTrait
    }

    /**
     * 建立使用者資料.
     */
    public function seedUsers(): void
    {
        $users = [
            [1, 'user1', 'user1@example.com', 'password', 1, '2024-01-01 09:00:00', '2024-01-01 09:00:00'],
            [2, 'user2', 'user2@example.com', 'password', 1, '2024-01-01 10:00:00', '2024-01-01 10:00:00'],
            [3, 'user3', 'user3@example.com', 'password', 1, '2024-01-01 11:00:00', '2024-01-01 11:00:00'],
            [4, 'user4', 'user4@example.com', 'password', 1, '2024-01-02 09:00:00', '2024-01-02 09:00:00'],
            [5, 'user5', 'user5@example.com', 'password', 1, '2024-01-02 10:00:00', '2024-01-02 10:00:00'],
        ];

        $stmt = $this->db->prepare('
            INSERT INTO users
            (id, username, email, password, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');

        foreach ($users as $user) {
            $user[3] = password_hash($user[3], PASSWORD_DEFAULT);
            $stmt->execute($user);
        }
    }

    /**
     * 建立文章資料.
     */
    public function seedPosts(): void
    {
        $posts = [
            // 2024-01-01
            [1, '技術文章1', '深入探討 PHP 8.4 的新特性...', 1, '1', '2024-01-01 10:00:00', 120, 5, 10, 0, 'web'],
            [2, '生活分享1', '分享日常生活中的美好時刻...', 2, '1', '2024-01-01 14:00:00', 80, 3, 8, 1, 'mobile'],
            [3, '旅遊心得1', '最近去日本旅遊的心得分享...', 3, '1', '2024-01-01 16:00:00', 150, 8, 15, 0, 'web'],
            [4, '美食推薦1', '推薦幾家台北不錯的餐廳...', 1, '0', '2024-01-01 18:00:00', 0, 0, 0, 0, 'web'], // status '0' for draft

            // 2024-01-02
            [5, '技術文章2', 'Docker 容器化實戰教學...', 2, '1', '2024-01-02 10:00:00', 200, 12, 20, 1, 'api'],
            [6, '程式設計心得', '十年程式設計師的職涯分享...', 4, '1', '2024-01-02 14:00:00', 180, 15, 25, 0, 'web'],
            [7, '學習筆記', '最新的前端框架學習心得...', 3, '1', '2024-01-02 16:00:00', 90, 6, 12, 0, 'web'],
        ];

        $stmt = $this->db->prepare('
            INSERT INTO posts
            (id, uuid, seq_number, title, content, user_id, user_ip, status, views, comments_count, likes_count, is_pinned, publish_date, created_at, updated_at, creation_source)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        foreach ($posts as $i => $post) {
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
            );
            $now = $post[5];
            $stmt->execute([
                $post[0], $uuid, $i + 1, $post[1], $post[2], $post[3], '192.168.1.1',
                $post[4], $post[6], $post[7], $post[8], $post[9], $now, $now, $now, $post[10],
            ]);
        }
    }

    /**
     * 建立使用者活動記錄.
     */
    public function seedUserActivityLogs(): void
    {
        $activities = [
            [1, 1, 'login', 'user', 'success', '2024-01-01 09:30:00'],
            [2, 1, 'view', 'post', 'success', '2024-01-01 09:35:00'],
            [3, 2, 'login', 'user', 'success', '2024-01-01 10:30:00'],
            [4, 3, 'login', 'user', 'success', '2024-01-01 11:30:00'],
            [5, 4, 'login', 'user', 'success', '2024-01-02 09:30:00'],
            [6, 5, 'login', 'user', 'success', '2024-01-02 10:30:00'],
        ];

        $stmt = $this->db->prepare('
            INSERT INTO user_activity_logs
            (id, uuid, user_id, action_type, action_category, status, created_at, occurred_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');

        foreach ($activities as $i => $activity) {
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
            );
            $now = $activity[5];
            $stmt->execute([$activity[0], $uuid, $activity[1], $activity[2], $activity[3], $activity[4], $now, $now]);
        }
    }

    /**
     * 建立評論資料.
     */
    public function seedComments(): void
    {
        // Data is seeded by default in DatabaseTestTrait if needed
    }

    /**
     * 建立文章瀏覽記錄.
     */
    public function seedPostViews(): void
    {
        // Data is seeded by default in DatabaseTestTrait if needed
    }

    /**
     * 清除所有測試資料.
     */
    public function clearAll(): void
    {
        $tables = [
            'user_activity_logs',
            'posts',
            'users',
        ];

        foreach ($tables as $table) {
            $this->db->exec("DELETE FROM {$table}");
            $this->db->exec("DELETE FROM sqlite_sequence WHERE name='{$table}'");
        }
    }

    /**
     * 刪除所有測試資料表.
     */
    public function dropTables(): void
    {
        $tables = [
            'user_activity_logs',
            'posts',
            'users',
        ];

        foreach ($tables as $table) {
            $this->db->exec("DROP TABLE IF EXISTS {$table}");
        }
    }
}
