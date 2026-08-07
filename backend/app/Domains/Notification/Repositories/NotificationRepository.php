<?php

declare(strict_types=1);

namespace App\Domains\Notification\Repositories;

use App\Domains\Notification\Contracts\NotificationRepositoryInterface;
use App\Domains\Notification\DTOs\CreateNotificationDTO;
use App\Domains\Notification\Models\Notification;
use PDO;
use PDOException;
use RuntimeException;

class NotificationRepository implements NotificationRepositoryInterface
{
    private const TABLE_NAME = 'notifications';

    private const SELECT_FIELDS = 'id, uuid, user_id, title, message, type, is_read, read_at, created_at';

    public function __construct(
        private readonly PDO $db,
    ) {
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @return array<int, Notification>
     */
    public function findForUser(?int $userId, bool $unreadOnly = false, int $limit = 20, int $offset = 0): array
    {
        try {
            $sql = 'SELECT ' . self::SELECT_FIELDS . ' FROM ' . self::TABLE_NAME . ' WHERE ';
            if ($userId !== null) {
                $sql .= '(user_id = :user_id OR user_id IS NULL)';
            } else {
                $sql .= 'user_id IS NULL';
            }

            if ($unreadOnly) {
                $sql .= ' AND is_read = 0';
            }

            $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

            $stmt = $this->db->prepare($sql);
            if ($userId !== null) {
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            /** @var array<int, array<string, mixed>> $results */
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_values(array_map(
                static fn(array $row): Notification => Notification::fromDatabaseRow($row),
                $results,
            ));
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to retrieve notifications: ' . $e->getMessage(), 0, $e);
        }
    }

    public function countUnreadForUser(?int $userId): int
    {
        try {
            $sql = 'SELECT COUNT(*) FROM ' . self::TABLE_NAME . ' WHERE is_read = 0 AND ';
            if ($userId !== null) {
                $sql .= '(user_id = :user_id OR user_id IS NULL)';
            } else {
                $sql .= 'user_id IS NULL';
            }

            $stmt = $this->db->prepare($sql);
            if ($userId !== null) {
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            }
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to count unread notifications: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findById(int $id): ?Notification
    {
        try {
            $sql = 'SELECT ' . self::SELECT_FIELDS . ' FROM ' . self::TABLE_NAME . ' WHERE id = :id LIMIT 1';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }

            return Notification::fromDatabaseRow($row);
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to find notification: ' . $e->getMessage(), 0, $e);
        }
    }

    public function markAsRead(int $id, string $readAt): bool
    {
        try {
            $sql = 'UPDATE ' . self::TABLE_NAME . ' SET is_read = 1, read_at = :read_at WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':read_at', $readAt, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to mark notification as read: ' . $e->getMessage(), 0, $e);
        }
    }

    public function markAllAsReadForUser(?int $userId, string $readAt): int
    {
        try {
            $sql = 'UPDATE ' . self::TABLE_NAME . ' SET is_read = 1, read_at = :read_at WHERE is_read = 0 AND ';
            if ($userId !== null) {
                $sql .= '(user_id = :user_id OR user_id IS NULL)';
            } else {
                $sql .= 'user_id IS NULL';
            }

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':read_at', $readAt, PDO::PARAM_STR);
            if ($userId !== null) {
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            }
            $stmt->execute();

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to mark all notifications as read: ' . $e->getMessage(), 0, $e);
        }
    }

    public function create(CreateNotificationDTO $dto): Notification
    {
        try {
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
            $createdAt = date('Y-m-d H:i:s');

            $sql = 'INSERT INTO ' . self::TABLE_NAME . ' (uuid, user_id, title, message, type, is_read, created_at)
                    VALUES (:uuid, :user_id, :title, :message, :type, 0, :created_at)';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            if ($dto->userId !== null) {
                $stmt->bindValue(':user_id', $dto->userId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(':title', $dto->title, PDO::PARAM_STR);
            $stmt->bindValue(':message', $dto->message, PDO::PARAM_STR);
            $stmt->bindValue(':type', $dto->type, PDO::PARAM_STR);
            $stmt->bindValue(':created_at', $createdAt, PDO::PARAM_STR);
            $stmt->execute();

            $id = (int) $this->db->lastInsertId();

            return new Notification(
                id: $id,
                uuid: $uuid,
                userId: $dto->userId,
                title: $dto->title,
                message: $dto->message,
                type: $dto->type,
                isRead: false,
                readAt: null,
                createdAt: $createdAt,
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to create notification: ' . $e->getMessage(), 0, $e);
        }
    }
}
