<?php

declare(strict_types=1);

use \PDO;

class CreatePostsTables
{
    public function up(PDO $db): void
    {
        // 建立文章表
        $db->exec("
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                seq_number INTEGER NOT NULL UNIQUE,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                user_ip TEXT NOT NULL,
                views INTEGER NOT NULL DEFAULT 0,
                is_pinned INTEGER NOT NULL DEFAULT 0,
                status INTEGER NOT NULL DEFAULT 1,
                publish_date TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ");

        // 建立標籤表
        $db->exec("
            CREATE TABLE tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ");

        // 建立文章標籤關聯表
        $db->exec("
            CREATE TABLE post_tags (
                post_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                created_at TEXT NOT NULL,
                PRIMARY KEY (post_id, tag_id),
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
            )
        ");

        // 建立文章觀看記錄表
        $db->exec("
            CREATE TABLE post_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                post_id INTEGER NOT NULL,
                user_id INTEGER,
                user_ip TEXT NOT NULL,
                view_date TEXT NOT NULL,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
            )
        ");

        // 建立索引以提升查詢效能
        $db->exec("
            -- 文章表索引
            CREATE INDEX idx_posts_title ON posts(title);
            CREATE INDEX idx_posts_publish_date ON posts(publish_date);
            CREATE INDEX idx_posts_is_pinned ON posts(is_pinned);
            CREATE INDEX idx_posts_user_id ON posts(user_id);
            CREATE INDEX idx_posts_status ON posts(status);
            CREATE INDEX idx_posts_views ON posts(views);

            -- 標籤表索引
            CREATE INDEX idx_tags_name ON tags(name);

            -- 文章標籤關聯表索引
            CREATE INDEX idx_post_tags_tag_id ON post_tags(tag_id);
            CREATE INDEX idx_post_tags_created_at ON post_tags(created_at);

            -- 文章觀看記錄表索引
            CREATE INDEX idx_post_views_post_id ON post_views(post_id);
            CREATE INDEX idx_post_views_user_id ON post_views(user_id);
            CREATE INDEX idx_post_views_user_ip ON post_views(user_ip);
            CREATE INDEX idx_post_views_view_date ON post_views(view_date);
        ");
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS post_views');
        $db->exec('DROP TABLE IF EXISTS post_tags');
        $db->exec('DROP TABLE IF EXISTS tags');
        $db->exec('DROP TABLE IF EXISTS posts');
    }
}
