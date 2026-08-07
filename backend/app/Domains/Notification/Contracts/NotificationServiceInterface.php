<?php

declare(strict_types=1);

namespace App\Domains\Notification\Contracts;

use App\Domains\Notification\DTOs\CreateNotificationDTO;
use App\Domains\Notification\Models\Notification;

interface NotificationServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getUserNotifications(?int $userId, bool $unreadOnly = false, int $limit = 20, int $offset = 0): array;

    public function getUnreadCount(?int $userId): int;

    public function markAsRead(int $id, ?int $currentUserId): bool;

    public function markAllAsRead(?int $userId): int;

    public function broadcastNotification(CreateNotificationDTO $dto): Notification;
}
