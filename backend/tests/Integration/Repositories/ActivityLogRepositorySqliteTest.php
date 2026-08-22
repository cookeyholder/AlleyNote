<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Entities\ActivityLog;
use App\Domains\Security\Enums\ActivityCategory;
use App\Domains\Security\Enums\ActivityStatus;
use App\Domains\Security\Enums\ActivityType;
use App\Domains\Security\Repositories\ActivityLogRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\IntegrationTestCase;

/**
 * ActivityLogRepository 整合測試（SQLite 記憶體資料庫）.
 */
#[CoversClass(ActivityLogRepository::class)]
#[Group('integration')]
class ActivityLogRepositorySqliteTest extends IntegrationTestCase
{
    private ActivityLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        // 活動紀錄外鍵指向 users，需預先建立測試中引用的使用者以滿足約束
        foreach ([1, 2, 3, 7, 8, 11, 12, 21, 22, 31] as $userId) {
            $this->insertTestUser(['id' => $userId, 'username' => 'activity_user_' . $userId]);
        }
        $this->repository = new ActivityLogRepository($this->db);
    }

    /**
     * 建立測試用 DTO.
     *
     * @param array<string, mixed> $overrides
     */
    private function makeDto(array $overrides = []): CreateActivityLogDTO
    {
        /** @var array<string, mixed> $args */
        $args = array_merge([
            'actionType' => ActivityType::LOGIN_SUCCESS,
            'userId'     => 1,
            'status'     => ActivityStatus::SUCCESS,
            'description' => '測試活動',
            'metadata'   => null,
            'ipAddress'  => '192.168.1.1',
            'occurredAt' => new DateTimeImmutable('2026-01-01 10:00:00'),
        ], $overrides);

        return new CreateActivityLogDTO(
            actionType: $args['actionType'],
            userId: $args['userId'],
            sessionId: $args['sessionId'] ?? null,
            status: $args['status'],
            targetType: $args['targetType'] ?? null,
            targetId: $args['targetId'] ?? null,
            description: $args['description'],
            metadata: $args['metadata'],
            ipAddress: $args['ipAddress'],
            userAgent: $args['userAgent'] ?? 'PHPUnit',
            requestMethod: $args['requestMethod'] ?? 'GET',
            requestPath: $args['requestPath'] ?? '/test',
            occurredAt: $args['occurredAt'],
        );
    }

    /**
     * 直接寫入一筆記錄並回傳查詢結果.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function seed(array $overrides = []): array
    {
        $created = $this->repository->create($this->makeDto($overrides));
        if ($created === null) {
            throw new RuntimeException('seed 失敗');
        }

        return $created;
    }

    #[Test]
    public function create_persists_row_and_returns_full_entity(): void
    {
        $result = $this->seed([
            'actionType' => ActivityType::LOGIN_FAILED,
            'status'     => ActivityStatus::FAILED,
            'metadata'   => ['reason' => 'bad password'],
            'targetType' => 'user',
            'targetId'   => '1',
        ]);

        $this->assertGreaterThan(0, $result['id']);
        $this->assertNotEmpty($result['uuid']);
        $this->assertSame('login_failed', $result['action_type']);
        $this->assertSame(ActivityCategory::AUTHENTICATION->value, $result['action_category']);
        $this->assertSame('failed', $result['status']);
        $this->assertSame(['reason' => 'bad password'], $result['metadata']);
        $this->assertSame('192.168.1.1', $result['ip_address']);
        $this->assertSame('2026-01-01 10:00:00', $result['occurred_at']);
        $this->assertSame('/test', $result['request_path']);

        // 驗證資料庫內容確實存在
        $count = (int) $this->db->query('SELECT COUNT(*) FROM user_activity_logs')->fetchColumn();
        $this->assertSame(1, $count);
    }

    #[Test]
    public function create_wraps_foreign_key_failures_into_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create activity log');

        $this->repository->create($this->makeDto(['userId' => 999999]));
    }

    #[Test]
    public function create_batch_inserts_every_dto(): void
    {
        $dtos = [
            $this->makeDto(['description' => 'first']),
            $this->makeDto(['description' => 'second']),
            $this->makeDto(['description' => 'third']),
        ];

        $this->assertSame(3, $this->repository->createBatch($dtos));
        $this->assertSame(0, $this->repository->createBatch([]));

        $count = (int) $this->db->query('SELECT COUNT(*) FROM user_activity_logs')->fetchColumn();
        $this->assertSame(3, $count);
    }

    #[Test]
    public function create_batch_rejects_non_dto_items(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All items must be CreateActivityLogDTO instances');

        try {
            $this->repository->createBatch([$this->makeDto(), 'not-a-dto']);
        } finally {
            // 交易仍處於開啟狀態，手動回復以保持連線可用
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
        }
    }

    #[Test]
    public function find_by_id_and_uuid_return_null_when_missing(): void
    {
        $row = $this->seed();

        $foundById = $this->repository->findById((int) $row['id']);
        $this->assertNotNull($foundById);
        $this->assertSame($row['uuid'], $foundById['uuid']);

        $foundByUuid = $this->repository->findByUuid((string) $row['uuid']);
        $this->assertNotNull($foundByUuid);
        $this->assertSame($row['id'], $foundByUuid['id']);

        $this->assertNull($this->repository->findById(987654));
        $this->assertNull($this->repository->findByUuid('missing-uuid'));
    }

    #[Test]
    public function find_all_orders_descending_and_paginates(): void
    {
        $this->seed(['description' => 'oldest', 'occurredAt' => new DateTimeImmutable('2026-02-01 08:00:00')]);
        $this->seed(['description' => 'middle', 'occurredAt' => new DateTimeImmutable('2026-02-02 08:00:00')]);
        $this->seed(['description' => 'newest', 'occurredAt' => new DateTimeImmutable('2026-02-03 08:00:00')]);

        $pageOne = $this->repository->findAll(2, 0);
        $pageTwo = $this->repository->findAll(2, 2);

        $this->assertCount(2, $pageOne);
        $this->assertSame('newest', $pageOne[0]['description']);
        $this->assertCount(1, $pageTwo);
        $this->assertSame('oldest', $pageTwo[0]['description']);
    }

    #[Test]
    public function find_by_user_applies_category_and_action_type_filters(): void
    {
        $this->seed(['actionType' => ActivityType::LOGIN_SUCCESS]);
        $this->seed([
            'actionType' => ActivityType::POST_CREATED,
            'userId'     => 2,
        ]);
        $filtered = $this->repository->findByUser(1, 50, 0, ActivityCategory::CONTENT);

        $this->assertCount(0, $filtered, '分類不符時不應回傳');

        $byCategory = $this->repository->findByUser(1, 50, 0, ActivityCategory::AUTHENTICATION);
        $this->assertCount(1, $byCategory);

        $byType = $this->repository->findByUser(1, 50, 0, null, ActivityType::LOGIN_SUCCESS);
        $this->assertCount(1, $byType);

        $all = $this->repository->findByUser(1);
        $this->assertCount(1, $all);
    }

    #[Test]
    public function find_by_time_range_filters_with_optional_category(): void
    {
        $this->seed(['occurredAt' => new DateTimeImmutable('2026-03-01 09:00:00')]);
        $this->seed([
            'actionType' => ActivityType::XSS_ATTACK_BLOCKED,
            'status'     => ActivityStatus::BLOCKED,
            'occurredAt' => new DateTimeImmutable('2026-03-05 09:00:00'),
        ]);
        $this->seed(['occurredAt' => new DateTimeImmutable('2026-04-01 09:00:00')]);

        $results = $this->repository->findByTimeRange(
            new DateTimeImmutable('2026-03-01 00:00:00'),
            new DateTimeImmutable('2026-03-31 23:59:59'),
        );
        $this->assertCount(2, $results);

        $securityOnly = $this->repository->findByTimeRange(
            new DateTimeImmutable('2026-03-01 00:00:00'),
            new DateTimeImmutable('2026-03-31 23:59:59'),
            100,
            0,
            ActivityCategory::SECURITY,
        );
        $this->assertCount(1, $securityOnly);
    }

    #[Test]
    public function find_security_events_matches_security_or_failure_with_optional_ip(): void
    {
        $this->seed([
            'actionType' => ActivityType::XSS_ATTACK_BLOCKED,
            'status'     => ActivityStatus::BLOCKED,
            'ipAddress'  => '198.51.100.7',
        ]);
        $this->seed([
            'actionType' => ActivityType::POST_VIEWED,
            'ipAddress'  => '203.0.113.5',
        ]); // 一般成功活動，不符合

        $events = $this->repository->findSecurityEvents();
        $this->assertCount(1, $events);

        $byIp = $this->repository->findSecurityEvents(100, 0, '198.51.100.7');
        $this->assertCount(1, $byIp);

        $otherIp = $this->repository->findSecurityEvents(100, 0, '10.9.9.9');
        $this->assertCount(0, $otherIp);
    }

    #[Test]
    public function find_failed_activities_supports_user_and_type_filters(): void
    {
        $this->seed([
            'actionType' => ActivityType::LOGIN_FAILED,
            'status'     => ActivityStatus::FAILED,
            'userId'     => 7,
        ]);
        $this->seed([
            'actionType' => ActivityType::POST_UPDATED,
            'userId'     => 8,
        ]); // 成功活動，不符合

        $failed = $this->repository->findFailedActivities();
        $this->assertCount(1, $failed);

        $forUser = $this->repository->findFailedActivities(100, 0, 7);
        $this->assertCount(1, $forUser);

        $wrongUser = $this->repository->findFailedActivities(100, 0, 99);
        $this->assertCount(0, $wrongUser);

        $byType = $this->repository->findFailedActivities(100, 0, null, ActivityType::LOGIN_FAILED);
        $this->assertCount(1, $byType);
    }

    #[Test]
    public function counts_reflect_category_and_user_time_window(): void
    {
        $this->seed(['actionType' => ActivityType::LOGIN_SUCCESS]);
        $this->seed([
            'actionType' => ActivityType::LOGIN_SUCCESS,
            'occurredAt' => new DateTimeImmutable('2026-05-02 12:00:00'),
        ]);
        $this->seed([
            'actionType' => ActivityType::POST_VIEWED,
            'userId'     => 3,
        ]);

        $this->assertSame(2, $this->repository->countByCategory(ActivityCategory::AUTHENTICATION));

        $windowCount = $this->repository->countUserActivities(
            1,
            new DateTimeImmutable('2026-05-01 00:00:00'),
            new DateTimeImmutable('2026-05-31 23:59:59'),
        );
        $this->assertSame(1, $windowCount);
    }

    #[Test]
    public function activity_statistics_group_rows_by_category_and_type(): void
    {
        $this->seed(['actionType' => ActivityType::LOGIN_SUCCESS]);
        $this->seed(['actionType' => ActivityType::LOGIN_SUCCESS]);
        $this->seed(['actionType' => ActivityType::LOGOUT]);

        $stats = $this->repository->getActivityStatistics(
            new DateTimeImmutable('2026-01-01 00:00:00'),
            new DateTimeImmutable('2026-01-31 23:59:59'),
        );

        $this->assertCount(2, $stats);
        $loginRow = null;
        foreach ($stats as $stat) {
            if ($stat['action_type'] === 'login_success') {
                $loginRow = $stat;
            }
        }
        $this->assertNotNull($loginRow);
        $this->assertSame(2, (int) ($loginRow['count'] ?? 0));
    }

    #[Test]
    public function popular_activity_types_rank_by_frequency(): void
    {
        $this->seed(['actionType' => ActivityType::LOGIN_SUCCESS]);
        $this->seed(['actionType' => ActivityType::LOGIN_SUCCESS]);
        $this->seed(['actionType' => ActivityType::LOGIN_SUCCESS]);
        $this->seed(['actionType' => ActivityType::LOGOUT]);

        $popular = $this->repository->getPopularActivityTypes(1);

        $this->assertCount(1, $popular);
        $this->assertSame('login_success', $popular[0]['action_type']);
        $this->assertSame(3, (int) ($popular[0]['count'] ?? 0));
    }

    #[Test]
    public function suspicious_ip_addresses_require_threshold_within_window(): void
    {
        for ($i = 0; $i < 3; ++$i) {
            $this->seed([
                'actionType' => ActivityType::LOGIN_FAILED,
                'status'     => ActivityStatus::FAILED,
                'ipAddress'  => '203.0.113.77',
                'occurredAt' => new DateTimeImmutable('2026-06-01 10:0' . $i . ':00'),
            ]);
        }
        $this->seed([
            'actionType' => ActivityType::LOGIN_FAILED,
            'status'     => ActivityStatus::FAILED,
            'ipAddress'  => '203.0.113.78',
            'occurredAt' => new DateTimeImmutable('2026-06-01 10:30:00'),
        ]);

        $suspicious = $this->repository->getSuspiciousIpAddresses(3, new DateTimeImmutable('2026-06-01 00:00:00'));

        $this->assertCount(1, $suspicious);
        $this->assertSame('203.0.113.77', $suspicious[0]['ip_address']);
        $this->assertSame(3, (int) ($suspicious[0]['failure_count'] ?? 0));

        // 時間窗口之外的失敗不計入
        $outsideWindow = $this->repository->getSuspiciousIpAddresses(1, new DateTimeImmutable('2026-07-01 00:00:00'));
        $this->assertCount(0, $outsideWindow);
    }

    #[Test]
    public function legacy_suspicious_ip_query_uses_login_failed_actions(): void
    {
        for ($i = 0; $i < 2; ++$i) {
            $this->seed([
                'actionType' => ActivityType::LOGIN_FAILED,
                'status'     => ActivityStatus::FAILED,
                'ipAddress'  => '192.0.2.66',
            ]);
        }

        $results = $this->repository->getSuspiciousIPs(2);

        $this->assertCount(1, $results);
        $this->assertSame('192.0.2.66', $results[0]['ip_address']);
        $this->assertSame(2, (int) ($results[0]['failed_attempts'] ?? 0));
    }

    #[Test]
    public function delete_helpers_remove_expected_rows_only(): void
    {
        $this->seed(['description' => 'keep-me']);
        $failedId = $this->seed([
            'actionType' => ActivityType::ACCESS_DENIED,
            'status'     => ActivityStatus::FAILED,
        ])['id'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conditions cannot be empty for safety');
        try {
            $this->repository->deleteByConditions([]);
        } finally {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
        }

        // 條件刪除僅移除符合的列
        $removed = $this->repository->deleteByConditions(['status' => 'failed']);
        $this->assertSame(1, $removed);
        $this->assertNull($this->repository->findById((int) $failedId));

        // 刪除「未來之前」的所有紀錄
        $remaining = $this->repository->deleteOldRecords(new DateTimeImmutable('+1 day'));
        $this->assertSame(1, $remaining);
    }

    #[Test]
    public function search_combines_filters_and_sanitizes_sort_input(): void
    {
        $match = $this->seed([
            'description' => '特徵字串-目標描述',
            'userId'      => 11,
            'actionType'  => ActivityType::POST_CREATED,
            'occurredAt'  => new DateTimeImmutable('2026-07-01 08:00:00'),
        ]);
        $noise = $this->seed([
            'description' => '其他雜訊',
            'userId'      => 12,
            'actionType'  => ActivityType::LOGOUT,
            'occurredAt'  => new DateTimeImmutable('2026-07-02 08:00:00'),
        ]);

        $results = $this->repository->search(
            searchTerm: '特徵字串',
            userId: 11,
            category: ActivityCategory::CONTENT,
            actionType: ActivityType::POST_CREATED,
            startTime: new DateTimeImmutable('2026-06-30 00:00:00'),
            endTime: new DateTimeImmutable('2026-07-31 23:59:59'),
            limit: 10,
            offset: 0,
            sortBy: 'evil_column', // 不在白名單，應退回 occurred_at
            sortOrder: 'asc',
        );

        $this->assertCount(1, $results);
        $this->assertSame($match['uuid'], $results[0]['uuid']);

        $count = $this->repository->getSearchCount(
            searchTerm: '特徵字串',
            userId: 11,
            category: ActivityCategory::CONTENT,
            actionType: ActivityType::POST_CREATED,
            startTime: new DateTimeImmutable('2026-06-30 00:00:00'),
            endTime: new DateTimeImmutable('2026-07-31 23:59:59'),
        );
        $this->assertSame(1, $count);

        $total = $this->repository->getSearchCount();
        $this->assertSame(2, $total);

        unset($noise);
    }

    #[Test]
    public function find_by_user_id_and_time_window_handles_both_modes(): void
    {
        $this->seed(['userId' => 21, 'occurredAt' => new DateTimeImmutable('2026-08-01 09:00:00')]);
        $this->seed(['userId' => 22, 'occurredAt' => new DateTimeImmutable('2026-08-01 09:00:00')]);

        $allForUser = $this->repository->findByUserIdAndTimeWindow(21);
        $this->assertCount(1, $allForUser);
        $this->assertSame(21, $allForUser[0]['user_id']);

        $withinWindow = $this->repository->findByUserIdAndTimeWindow(
            21,
            new DateTimeImmutable('2026-07-01 00:00:00'),
        );
        $this->assertCount(1, $withinWindow);

        $afterWindow = $this->repository->findByUserIdAndTimeWindow(
            21,
            new DateTimeImmutable('2026-09-01 00:00:00'),
        );
        $this->assertCount(0, $afterWindow);
    }

    #[Test]
    public function range_queries_for_user_and_ip_apply_limits(): void
    {
        for ($i = 0; $i < 3; ++$i) {
            $minute = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
            $this->seed([
                'userId'     => 31,
                'ipAddress'  => '198.51.100.90',
                'occurredAt' => new DateTimeImmutable("2026-09-01 10:{$minute}:00"),
            ]);
        }

        $userRange = $this->repository->findByUserAndTimeRange(
            31,
            new DateTimeImmutable('2026-09-01 00:00:00'),
            new DateTimeImmutable('2026-09-30 23:59:59'),
            2,
            0,
        );
        $this->assertCount(2, $userRange);
        $this->assertSame('10:03:00', substr((string) $userRange[0]['occurred_at'], 11));

        $ipRange = $this->repository->findByIpAddressAndTimeRange(
            '198.51.100.90',
            new DateTimeImmutable('2026-09-01 00:00:00'),
            new DateTimeImmutable('2026-09-30 23:59:59'),
        );
        $this->assertCount(3, $ipRange);
    }

    #[Test]
    public function login_failure_statistics_aggregate_accounts_totals_and_trend(): void
    {
        $failureArgs = static fn(string $email): array => [
            'actionType' => ActivityType::LOGIN_FAILED,
            'status'     => ActivityStatus::FAILED,
            'userId'     => null,
            'ipAddress'  => '203.0.113.200',
            'metadata'   => ['email' => $email, 'username' => 'victim'],
            'occurredAt' => new DateTimeImmutable('2026-10-01 10:00:0' . random_int(0, 9)),
        ];
        $this->seed($failureArgs('victim@example.com'));
        $this->seed($failureArgs('victim@example.com'));
        $this->seed($failureArgs('victim@example.com'));
        $this->seed([
            'actionType' => ActivityType::LOGIN_SUCCESS,
            'userId'     => null,
            'metadata'   => ['email' => 'victim@example.com', 'username' => 'victim'],
            'occurredAt' => new DateTimeImmutable('2026-10-01 10:05:00'),
        ]); // 成功登入不計入失敗統計

        $stats = $this->repository->getLoginFailureStatistics(
            new DateTimeImmutable('2026-10-01 00:00:00'),
            new DateTimeImmutable('2026-10-01 23:59:59'),
        );

        $this->assertSame(3, $stats['total']);
        $this->assertCount(1, $stats['accounts']);
        $account = $stats['accounts'][0];
        $this->assertSame('victim@example.com', $account['username']);
        $this->assertSame('victim@example.com', $account['email']);
        $this->assertSame(3, $account['count']);
        $this->assertNotNull($account['latest_attempt']);

        $this->assertCount(1, $stats['trend']);
        $this->assertSame('2026-10-01', $stats['trend'][0]['date']);
        $this->assertSame(3, $stats['trend'][0]['count']);
    }

    #[Test]
    public function entities_round_trip_through_database(): void
    {
        $dto = $this->makeDto([
            'metadata' => ['round' => 'trip'],
            'targetType' => 'post',
            'targetId'   => '42',
        ]);
        $entity = ActivityLog::fromDTO(
            actionType: $dto->getActionType(),
            userId: $dto->getUserId(),
            sessionId: $dto->getSessionId(),
            status: $dto->getStatus(),
            targetType: $dto->getTargetType(),
            targetId: $dto->getTargetId(),
            description: $dto->getDescription(),
            metadata: $dto->getMetadata(),
            ipAddress: $dto->getIpAddress(),
            userAgent: $dto->getUserAgent(),
            requestMethod: $dto->getRequestMethod(),
            requestPath: $dto->getRequestPath(),
            occurredAt: $dto->getOccurredAt(),
        );

        $this->assertSame(ActivityCategory::AUTHENTICATION, $entity->getActionCategory());
        $this->assertTrue($entity->isSecurityRelated() === false || $entity->isFailure() === false);

        $stored = $this->repository->create($dto);
        $this->assertNotNull($stored);
        $this->assertSame(['round' => 'trip'], $stored['metadata']);
        $this->assertSame('post', $stored['target_type']);
    }
}
