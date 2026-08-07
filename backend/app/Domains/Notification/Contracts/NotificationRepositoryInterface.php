<?php

declare(strict_types=1);

namespace App\Domains\Notification\Contracts;

use App\Domains\Notification\DTOs\CreateNotificationDTO;
use App\Domains\Notification\Models\Notification;

interface NotificationRepositoryInterface
{
    /**
     * @return array<int, Notification>
     */
    public function findForUser(?int $userId, bool $unreadOnly = false, int $limit = 20, int $offset = 0): array;

    public function countUnreadForUser(?int $userId): int;

    public function findById(int $id): ?Notification;

    public function markAsRead(int $id, string $readAt): bool;

    public function markAllAsReadForUser(?int $userId, string $readAt): int;

    public function create(CreateNotificationDTO $dto): Notification;
}
