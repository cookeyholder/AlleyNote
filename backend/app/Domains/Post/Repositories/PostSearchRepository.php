<?php

declare(strict_types=1);

namespace App\Domains\Post\Repositories;

use App\Domains\Post\Models\Post;
use PDO;

class PostSearchRepository extends PostBaseRepository
{
    public function searchByTitle(string $title): mixed
    {
        $sql = 'SELECT ' . self::POST_SELECT_FIELDS . ' FROM posts WHERE title LIKE :title AND deleted_at IS NULL';
        $stmt = $this->db->prepare($sql);
        $title = '%' . $title . '%';
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->execute();

        return array_map(
            fn($row) => Post::fromArray($this->preparePostData($row)),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function findLatestByUserId(int $userId): ?Post
    {
        $sql = $this->buildSelectQuery('p.user_id = :userId') . ' ORDER BY p.created_at DESC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            return null;
        }

        return Post::fromArray($this->preparePostData($result));
    }

    public function search(string $keyword): mixed
    {
        $sql = $this->buildSelectQuery('title LIKE :keyword OR content LIKE :keyword');
        $stmt = $this->db->prepare($sql);
        $keyword = '%' . $keyword . '%';
        $stmt->bindValue(':keyword', $keyword, PDO::PARAM_STR);
        $stmt->execute();

        return array_map(
            fn($row) => Post::fromArray($this->preparePostData($row)),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }
}
