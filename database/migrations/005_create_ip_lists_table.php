<?php

namespace Database\Migrations;

use PDO;

class CreateIpListsTable
{
    public function up(PDO $db): void
    {
        $db->exec("
            CREATE TABLE IF NOT EXISTS ip_lists (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL,
                ip_address TEXT NOT NULL,
                type INTEGER NOT NULL,
                unit_id INTEGER,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (unit_id) REFERENCES units (id)
            )
        ");

        // 建立索引
        $db->exec("CREATE INDEX idx_ip_lists_uuid ON ip_lists (uuid)");
        $db->exec("CREATE INDEX idx_ip_lists_type_unit ON ip_lists (type, unit_id)");
    }

    public function down(PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS ip_lists");
    }
}
