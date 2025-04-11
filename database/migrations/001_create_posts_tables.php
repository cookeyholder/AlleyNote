<?php

declare(strict_types=1);

class CreatePostsTables
{
    public function up(PDO $db): void
    {
        // 建立文章資料表
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
                updated_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

        // 建立標籤資料表
        $db->exec("
            CREATE TABLE tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                parent_id INTEGER,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (parent_id) REFERENCES tags(id)
            )
        ");

        // 建立文章標籤關聯資料表
        $db->exec("
            CREATE TABLE post_tags (
                post_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                created_at TEXT NOT NULL,
                PRIMARY KEY (post_id, tag_id),
                FOREIGN KEY (post_id) REFERENCES posts(id),
                FOREIGN KEY (tag_id) REFERENCES tags(id)
            )
        ");

        // 建立附件資料表
        $db->exec("
            CREATE TABLE attachments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                post_id INTEGER NOT NULL,
                filename TEXT NOT NULL,
                filepath TEXT NOT NULL,
                filesize INTEGER NOT NULL,
                filetype TEXT NOT NULL,
                downloads INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (post_id) REFERENCES posts(id)
            )
        ");

        // 建立文章觀看記錄資料表
        $db->exec("
            CREATE TABLE post_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                post_id INTEGER NOT NULL,
                user_id INTEGER,
                user_ip TEXT NOT NULL,
                view_date TEXT NOT NULL,
                FOREIGN KEY (post_id) REFERENCES posts(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

        // 建立索引
        $db->exec("CREATE INDEX idx_posts_uuid ON posts(uuid)");
        $db->exec("CREATE INDEX idx_posts_seq_number ON posts(seq_number)");
        $db->exec("CREATE INDEX idx_posts_user_id ON posts(user_id)");
        $db->exec("CREATE INDEX idx_posts_publish_date ON posts(publish_date)");
        $db->exec("CREATE INDEX idx_tags_uuid ON tags(uuid)");
        $db->exec("CREATE INDEX idx_tags_parent_id ON tags(parent_id)");
        $db->exec("CREATE INDEX idx_attachments_uuid ON attachments(uuid)");
        $db->exec("CREATE INDEX idx_attachments_post_id ON attachments(post_id)");
        $db->exec("CREATE INDEX idx_post_views_post_id ON post_views(post_id)");
        $db->exec("CREATE INDEX idx_post_views_user_id ON post_views(user_id)");
    }

    public function down(PDO $db): void
    {
        // 移除資料表 (注意外鍵約束的順序)
        $db->exec("DROP TABLE IF EXISTS post_views");
        $db->exec("DROP TABLE IF EXISTS attachments");
        $db->exec("DROP TABLE IF EXISTS post_tags");
        $db->exec("DROP TABLE IF EXISTS tags");
        $db->exec("DROP TABLE IF EXISTS posts");
    }
}
