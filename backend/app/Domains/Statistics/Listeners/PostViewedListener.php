<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Listeners;

use App\Domains\Statistics\Contracts\StatisticsMonitoringServiceInterface;
use App\Domains\Statistics\Events\PostViewed;
use App\Shared\Events\Contracts\DomainEventInterface;
use App\Shared\Events\Contracts\EventListenerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 文章瀏覽事件監聽器.
 *
 * 處理文章瀏覽事件，觸發非同步計數更新和統計資料記錄
 */
class PostViewedListener implements EventListenerInterface
{
    public function __construct(
        private readonly StatisticsMonitoringServiceInterface $monitoringService,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function handle(DomainEventInterface $event): void
    {
        if (!$event instanceof PostViewed) {
            $this->logger?->warning('PostViewedListener received non-PostViewed event', [
                'event_type' => $event->getEventName(),
                'event_id' => $event->getEventId(),
            ]);

            return;
        }

        try {
            $this->handlePostViewed($event);
        } catch (Throwable $e) {
            $this->logger?->error('Failed to handle PostViewed event', [
                'event_id' => $event->getEventId(),
                'post_id' => $event->getPostId(),
                'user_id' => $event->getUserId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 重新拋出異常，讓事件分派器處理
            throw $e;
        }
    }

    public function getListenedEvents(): array
    {
        return ['statistics.post.viewed'];
    }

    public function getName(): string
    {
        return 'statistics.post_viewed_listener';
    }

    private function handlePostViewed(PostViewed $event): void
    {
        $postId = $event->getPostId();
        $userId = $event->getUserId();
        $userIp = $event->getUserIp();

        $this->logger?->info('Processing PostViewed event', [
            'event_id' => $event->getEventId(),
            'post_id' => $postId,
            'user_id' => $userId,
            'is_authenticated' => $event->isAuthenticatedUser(),
            'user_agent' => $event->getUserAgent(),
        ]);

        // 記錄統計事件到監控服務
        $this->recordViewEvent($event);

        // 此處可以擴展為非同步處理，例如：
        // - 更新即時瀏覽計數
        // - 記錄使用者行為軌跡
        // - 觸發推薦演算法
        // - 發送到消息佇列進行批量處理

        $this->logger?->info('PostViewed event processed successfully', [
            'event_id' => $event->getEventId(),
            'post_id' => $postId,
        ]);
    }

    private function recordViewEvent(PostViewed $event): void
    {
        $context = [
            'post_id' => $event->getPostId(),
            'user_id' => $event->getUserId(),
            'user_ip' => $event->getUserIp(),
            'user_agent' => $event->getUserAgent(),
            'referrer' => $event->getReferrer(),
            'viewed_at' => $event->getViewedAt()->format('c'),
            'is_authenticated' => $event->isAuthenticatedUser(),
            'event_id' => $event->getEventId(),
        ];

        try {
            $this->monitoringService->logStatisticsEvent('post_viewed', $context);
        } catch (Throwable $e) {
            $this->logger?->warning('Failed to record view event in monitoring service', [
                'event_id' => $event->getEventId(),
                'post_id' => $event->getPostId(),
                'error' => $e->getMessage(),
            ]);

            // 不重新拋出異常，因為監控記錄失敗不應該影響主流程
        }
    }
}
