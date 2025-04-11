<?php

namespace App\Repositories;

use PDO;

class UserRepository
{
    public function __construct(private PDO $db) {}

    public function create(array $data): array
    {
        $sql = "INSERT INTO users (uuid, username, email, password) VALUES (:uuid, :username, :email, :password)";
        $stmt = $this->db->prepare($sql);

        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        $stmt->execute([
            'uuid' => $uuid,
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_ARGON2ID)
        ]);

        return $this->findById($this->db->lastInsertId());
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }
}
