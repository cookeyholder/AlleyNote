<?php

namespace Tests\Factory;

use App\Database\DatabaseConnection;
use PDO;
use Tests\Factory\Abstracts\AbstractFactory;

class PostFactory extends AbstractFactory
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseConnection::getInstance();
        $this->defaultAttributes = [
            'uuid' => uniqid('post_'),
            'title' => '測試文章標題',
            'content' => '測試文章內容',
            'user_id' => 1,
            'status' => 1,
            'views' => 0,
            'is_pinned' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // 確保資料表存在
        $this->createTable();
    }

    protected function persist(array $data): array
    {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO posts ($columns) VALUES ($values)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        $data['id'] = $this->db->lastInsertId();
        return $data;
    }

    private function createTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                status INTEGER NOT NULL DEFAULT 1,
                views INTEGER NOT NULL DEFAULT 0,
                is_pinned INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }
}
