<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Listeners;

use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Events\StatisticsSnapshotCreated;
use App\Infrastructure\Statistics\Services\StatisticsMonitoringService;
use App\Shared\Events\Contracts\DomainEventInterface;
use App\Shared\Events\Contracts\EventListenerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 統計快照已建立事件監聽器.
 *
 * 處理統計快照建立事件，觸發快取失效和預熱操作
 */
class StatisticsSnapshotCreatedListener implements EventListenerInterface
{
    public function __construct(
        private readonly StatisticsCacheServiceInterface $cacheService,
        private readonly StatisticsMonitoringService $monitoringService,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function handle(DomainEventInterface $event): void
    {
        if (!$event instanceof StatisticsSnapshotCreated) {
            $this->logger?->warning('StatisticsSnapshotCreatedListener received non-StatisticsSnapshotCreated event', [
                'event_type' => $event->getEventName(),
                'event_id' => $event->getEventId(),
            ]);

            return;
        }

        try {
            $this->handleSnapshotCreated($event);
        } catch (Throwable $e) {
            $this->logger?->error('Failed to handle StatisticsSnapshotCreated event', [
                'event_id' => $event->getEventId(),
                'snapshot_id' => $event->getSnapshotId(),
                'snapshot_type' => $event->getSnapshotType(),
                'is_update' => $event->isUpdate(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 重新拋出異常，讓事件分派器處理
            throw $e;
        }
    }

    public function getListenedEvents(): array
    {
        return ['statistics.snapshot.created'];
    }

    public function getName(): string
    {
        return 'statistics.snapshot_created_listener';
    }

    private function handleSnapshotCreated(StatisticsSnapshotCreated $event): void
    {
        $snapshot = $event->getSnapshot();
        $snapshotType = $event->getSnapshotType();
        $isUpdate = $event->isUpdate();

        $this->logger?->info('Processing StatisticsSnapshotCreated event', [
            'event_id' => $event->getEventId(),
            'snapshot_id' => $event->getSnapshotId(),
            'snapshot_uuid' => $event->getSnapshotUuid(),
            'snapshot_type' => $snapshotType,
            'is_update' => $isUpdate,
        ]);

        // 1. 記錄統計事件
        $this->recordSnapshotEvent($event);

        // 2. 處理快取失效
        $this->handleCacheInvalidation($event);

        // 3. 預熱相關快取
        $this->handleCachePrewarming($event);

        $this->logger?->info('StatisticsSnapshotCreated event processed successfully', [
            'event_id' => $event->getEventId(),
            'snapshot_id' => $event->getSnapshotId(),
            'snapshot_type' => $snapshotType,
        ]);
    }

    private function recordSnapshotEvent(StatisticsSnapshotCreated $event): void
    {
        $eventType = $event->isUpdate() ? 'snapshot_updated' : 'snapshot_created';

        $context = [
            'snapshot_id' => $event->getSnapshotId(),
            'snapshot_uuid' => $event->getSnapshotUuid(),
            'snapshot_type' => $event->getSnapshotType(),
            'is_update' => $event->isUpdate(),
            'event_id' => $event->getEventId(),
        ];

        try {
            $this->monitoringService->logStatisticsEvent($eventType, $context);
        } catch (Throwable $e) {
            $this->logger?->warning('Failed to record snapshot event in monitoring service', [
                'event_id' => $event->getEventId(),
                'snapshot_id' => $event->getSnapshotId(),
                'error' => $e->getMessage(),
            ]);

            // 不重新拋出異常，因為監控記錄失敗不應該影響主流程
        }
    }

    private function handleCacheInvalidation(StatisticsSnapshotCreated $event): void
    {
        $snapshotType = $event->getSnapshotType();

        try {
            // 根據快照類型決定要失效的快取標籤
            $tagsToInvalidate = $this->getCacheTagsForInvalidation($snapshotType);

            if (!empty($tagsToInvalidate)) {
                $this->cacheService->flushByTags($tagsToInvalidate);

                $this->logger?->info('Cache invalidated for snapshot type', [
                    'event_id' => $event->getEventId(),
                    'snapshot_type' => $snapshotType,
                    'invalidated_tags' => $tagsToInvalidate,
                ]);
            }
        } catch (Throwable $e) {
            $this->logger?->error('Failed to invalidate cache for snapshot', [
                'event_id' => $event->getEventId(),
                'snapshot_type' => $snapshotType,
                'error' => $e->getMessage(),
            ]);

            // 不重新拋出異常，快取失效失敗不應該中斷統計流程
        }
    }

    private function handleCachePrewarming(StatisticsSnapshotCreated $event): void
    {
        $snapshotType = $event->getSnapshotType();

        try {
            // 根據快照類型決定要預熱的快取回調
            $warmupCallbacks = $this->getWarmupCallbacks($snapshotType);

            if (!empty($warmupCallbacks)) {
                $results = $this->cacheService->warmup($warmupCallbacks);

                $this->logger?->info('Cache prewarmed for snapshot type', [
                    'event_id' => $event->getEventId(),
                    'snapshot_type' => $snapshotType,
                    'results' => $results,
                ]);
            }
        } catch (Throwable $e) {
            // 快取預熱失敗不應該影響主流程
            $this->logger?->error('Cache prewarming failed', [
                'event_id' => $event->getEventId(),
                'snapshot_type' => $snapshotType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 根據快照類型獲取需要失效的快取標籤.
     *
     * @return array<string>
     */
    private function getCacheTagsForInvalidation(string $snapshotType): array
    {
        $tagsMap = [
            'overview' => ['statistics', 'overview'],
            'posts' => ['statistics', 'posts'],
            'users' => ['statistics', 'users'],
            'popular' => ['statistics', 'popular', 'trends'],
            'sources' => ['statistics', 'sources'],
        ];

        return $tagsMap[$snapshotType] ?? ['statistics'];
    }

    /**
     * 根據快照類型取得需要預熱的快取回調函式.
     *
     * @return array<string, callable>
     */
    private function getWarmupCallbacks(string $snapshotType): array
    {
        // 由於這是事件監聽器，簡化預熱邏輯，只返回空陣列
        // 實際的預熱邏輯應該由專門的預熱服務處理
        return [];
    }
}
