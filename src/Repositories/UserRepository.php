<?php

namespace App\Repositories;

use PDO;
use DateTime;

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

    public function update(string $id, array $data): array
    {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['username', 'email', 'status', 'password'])) {
                $fields[] = "{$key} = :{$key}";
                $params[$key] = $key === 'password' ?
                    password_hash($value, PASSWORD_ARGON2ID) : $value;
            }
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id);
    }

    public function delete(string $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByUuid(string $uuid): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE uuid = :uuid");
        $stmt->execute(['uuid' => $uuid]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function updateLastLogin(string $id): bool
    {
        $sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
