<?php

declare(strict_types=1);

namespace App\Domains\Notification\Services;

use App\Domains\Notification\Contracts\NotificationRepositoryInterface;
use App\Domains\Notification\Contracts\NotificationServiceInterface;
use App\Domains\Notification\DTOs\CreateNotificationDTO;
use App\Domains\Notification\Models\Notification;
use RuntimeException;

class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly NotificationRepositoryInterface $repository,
    ) {}

    public function getUserNotifications(?int $userId, bool $unreadOnly = false, int $limit = 20, int $offset = 0): array
    {
        $notifications = $this->repository->findForUser($userId, $unreadOnly, $limit, $offset);
        $unreadCount = $this->repository->countUnreadForUser($userId);

        return [
            'notifications' => array_map(static fn(Notification $n): array => $n->toArray(), $notifications),
            'unread_count'  => $unreadCount,
            'limit'         => $limit,
            'offset'        => $offset,
        ];
    }

    public function getUnreadCount(?int $userId): int
    {
        return $this->repository->countUnreadForUser($userId);
    }

    public function markAsRead(int $id, ?int $currentUserId): bool
    {
        $notification = $this->repository->findById($id);
        if (!$notification) {
            throw new RuntimeException('找不到指定的站內通知');
        }

        if ($notification->getUserId() !== null && $notification->getUserId() !== $currentUserId) {
            throw new RuntimeException('無權限操作此通知');
        }

        $readAt = date('Y-m-d H:i:s');

        return $this->repository->markAsRead($id, $readAt);
    }

    public function markAllAsRead(?int $userId): int
    {
        $readAt = date('Y-m-d H:i:s');

        return $this->repository->markAllAsReadForUser($userId, $readAt);
    }

    public function broadcastNotification(CreateNotificationDTO $dto): Notification
    {
        if (trim($dto->title) === '' || trim($dto->message) === '') {
            throw new RuntimeException('通知標題與內容不能為空');
        }

        return $this->repository->create($dto);
    }
}
