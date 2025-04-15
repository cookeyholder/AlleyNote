<?php

declare(strict_types=1);

use PDO;

class CreateIpListsTable
{
    public function up(PDO $db): void
    {
        // 建立 IP 黑白名單資料表
        $db->exec("
            CREATE TABLE ip_lists (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                ip_address TEXT NOT NULL,
                type INTEGER NOT NULL DEFAULT 0,
                unit_id INTEGER,
                description TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
            )
        ");

        // 建立索引以提升查詢效能
        $db->exec("
            CREATE INDEX idx_ip_lists_ip_address ON ip_lists(ip_address);
            CREATE INDEX idx_ip_lists_type ON ip_lists(type);
            CREATE INDEX idx_ip_lists_unit_id ON ip_lists(unit_id);
            CREATE INDEX idx_ip_lists_created_at ON ip_lists(created_at);
        ");
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS ip_lists');
    }
}
