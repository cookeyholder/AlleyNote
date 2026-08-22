<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\Entities;

use App\Domains\Security\Entities\ActivityLog;
use App\Domains\Security\Enums\ActivityCategory;
use App\Domains\Security\Enums\ActivitySeverity;
use App\Domains\Security\Enums\ActivityStatus;
use App\Domains\Security\Enums\ActivityType;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * ActivityLog 實體測試.
 */
#[CoversClass(ActivityLog::class)]
class ActivityLogTest extends UnitTestCase
{
    #[Test]
    public function it_initializes_from_constructor_with_defaults(): void
    {
        $occurred = new DateTimeImmutable('2026-02-01 09:00:00');
        $entity = new ActivityLog(
            actionType: ActivityType::LOGIN_SUCCESS,
            userId: 7,
            sessionId: 'sess-7',
            status: ActivityStatus::SUCCESS,
            targetType: 'user',
            targetId: '7',
            description: '使用者登入',
            metadata: ['ip' => '192.168.1.5'],
            ipAddress: '192.168.1.5',
            userAgent: 'Mozilla/5.0',
            requestMethod: 'POST',
            requestPath: '/api/v1/auth/login',
            occurredAt: $occurred,
        );

        $this->assertNull($entity->getId());
        $this->assertNotEmpty($entity->getUuid());
        $this->assertSame(ActivityType::LOGIN_SUCCESS, $entity->getActionType());
        $this->assertSame(ActivityCategory::AUTHENTICATION, $entity->getActionCategory());
        $this->assertSame(ActivitySeverity::LOW, $entity->getSeverity());
        $this->assertSame(7, $entity->getUserId());
        $this->assertSame('sess-7', $entity->getSessionId());
        $this->assertSame(ActivityStatus::SUCCESS, $entity->getStatus());
        $this->assertSame('user', $entity->getTargetType());
        $this->assertSame('7', $entity->getTargetId());
        $this->assertSame('使用者登入', $entity->getDescription());
        $this->assertSame(['ip' => '192.168.1.5'], $entity->getMetadata());
        $this->assertSame('{"ip":"192.168.1.5"}', $entity->getMetadataAsJson());
        $this->assertSame('192.168.1.5', $entity->getIpAddress());
        $this->assertSame('Mozilla/5.0', $entity->getUserAgent());
        $this->assertSame('POST', $entity->getRequestMethod());
        $this->assertSame('/api/v1/auth/login', $entity->getRequestPath());
        $this->assertEquals($occurred, $entity->getOccurredAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $entity->getCreatedAt());
    }

    #[Test]
    public function it_falls_back_to_enum_description_when_none_given(): void
    {
        $entity = new ActivityLog(ActivityType::POST_CREATED);

        $this->assertSame(ActivityType::POST_CREATED->getDescription(), $entity->getDescription());
        $this->assertNull($entity->getMetadata());
        $this->assertNull($entity->getMetadataAsJson());
    }

    #[Test]
    public function it_detects_failure_states(): void
    {
        $failedStatus = new ActivityLog(ActivityType::LOGIN_SUCCESS, status: ActivityStatus::FAILED);
        $failureAction = new ActivityLog(ActivityType::ACCESS_DENIED, userId: 1);
        $normal = new ActivityLog(ActivityType::POST_VIEWED, userId: 1);

        $this->assertTrue($failedStatus->isFailure());
        $this->assertTrue($failureAction->isFailure());
        $this->assertFalse($normal->isFailure());
    }

    #[Test]
    public function it_detects_security_related_activities(): void
    {
        $blockedAttack = new ActivityLog(ActivityType::XSS_ATTACK_BLOCKED);
        $regularContent = new ActivityLog(ActivityType::POST_VIEWED);

        $this->assertTrue($blockedAttack->isSecurityRelated());
        $this->assertFalse($regularContent->isSecurityRelated());
    }

    #[Test]
    public function it_flags_high_severity_actions(): void
    {
        $critical = new ActivityLog(ActivityType::SQL_INJECTION_BLOCKED);
        $high = new ActivityLog(ActivityType::LOGIN_FAILED);
        $low = new ActivityLog(ActivityType::LOGIN_SUCCESS);

        $this->assertTrue($critical->isHighSeverity());
        $this->assertTrue($high->isHighSeverity());
        $this->assertFalse($low->isHighSeverity());
    }

    #[Test]
    public function it_builds_context_array(): void
    {
        $entity = new ActivityLog(
            actionType: ActivityType::LOGIN_FAILED,
            userId: 3,
            sessionId: 'sess-3',
            targetType: 'post',
            targetId: '12',
            metadata: ['attempt' => 2],
            ipAddress: '10.1.1.1',
            userAgent: 'curl/8',
            requestMethod: 'GET',
            requestPath: '/login',
        );

        $context = $entity->getContext();

        $this->assertSame(3, $context['user_id']);
        $this->assertSame('sess-3', $context['session_id']);
        $this->assertSame(['type' => 'post', 'id' => '12'], $context['target']);
        /** @var array<string, mixed> $request */
        $request = $context['request'];
        $this->assertSame('GET', $request['method']);
        $this->assertSame('/login', $request['path']);
        $this->assertSame('10.1.1.1', $request['ip']);
        $this->assertSame(['attempt' => 2], $context['metadata']);

        // 沒有目標時 target 為 null
        $noTarget = new ActivityLog(ActivityType::LOGOUT)->getContext();
        $this->assertNull($noTarget['target']);
    }

    #[Test]
    public function it_converts_to_array_format(): void
    {
        $entity = new ActivityLog(
            actionType: ActivityType::IP_BLOCKED,
            userId: null,
            status: ActivityStatus::BLOCKED,
            ipAddress: '203.0.113.99',
            occurredAt: new DateTimeImmutable('2026-03-05 11:22:33'),
        );

        $array = $entity->toArray();

        $this->assertSame('ip_blocked', $array['action_type']);
        $this->assertSame(ActivityCategory::SECURITY->value, $array['action_category']);
        $this->assertSame(ActivitySeverity::CRITICAL->value, $array['severity']);
        $this->assertNull($array['user_id']);
        $this->assertSame('blocked', $array['status']);
        $this->assertNull($array['metadata']);
        $this->assertSame('2026-03-05 11:22:33', $array['occurred_at']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('uuid', $array);
    }

    #[Test]
    public function it_converts_to_log_format(): void
    {
        $entity = new ActivityLog(
            actionType: ActivityType::ATTACHMENT_PERMISSION_DENIED,
            userId: 8,
            targetType: 'attachment',
            targetId: 'ab-01',
            description: '無權限',
            ipAddress: '198.51.100.2',
            occurredAt: new DateTimeImmutable('2026-04-01 06:30:00'),
        );

        $log = $entity->toLogFormat();

        $this->assertSame($entity->getUuid(), $log['activity_id']);
        $this->assertSame('attachment_permission_denied', $log['action']);
        $this->assertSame('attachment', $log['category']);
        $this->assertSame(ActivitySeverity::HIGH->value, $log['severity']);
        $this->assertSame(8, $log['user']);
        $this->assertSame('attachment:ab-01', $log['target']);
        $this->assertSame('198.51.100.2', $log['ip']);
        $this->assertSame('無權限', $log['description']);
        /** @var int|string $timestamp */
        $timestamp = $log['timestamp'];
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', (string) $timestamp);

        // 沒有目標時 target 為 null
        $noTargetLog = new ActivityLog(ActivityType::LOGOUT)->toLogFormat();
        $this->assertNull($noTargetLog['target']);
    }

    #[Test]
    public function it_hydrates_from_database_row(): void
    {
        $row = [
            'id'              => '55',
            'uuid'            => 'uuid-from-db',
            'user_id'         => '9',
            'session_id'      => 'sess-db',
            'action_type'     => 'post_published',
            'action_category' => 'content',
            'target_type'     => 'post',
            'target_id'       => '77',
            'status'          => 'success',
            'description'     => '發布公告',
            'metadata'        => '{"title":"hello"}',
            'ip_address'      => '192.0.2.50',
            'user_agent'      => 'Firefox',
            'request_method'  => 'PATCH',
            'request_path'    => '/api/v1/posts/77/publish',
            'created_at'      => '2026-05-01 00:00:00',
            'occurred_at'     => '2026-05-01 12:00:00',
        ];

        $entity = ActivityLog::fromDatabaseRow($row);

        $this->assertSame(55, $entity->getId());
        $this->assertSame('uuid-from-db', $entity->getUuid());
        $this->assertSame(9, $entity->getUserId());
        $this->assertSame('sess-db', $entity->getSessionId());
        $this->assertSame(ActivityType::POST_PUBLISHED, $entity->getActionType());
        $this->assertSame(ActivityStatus::SUCCESS, $entity->getStatus());
        $this->assertSame('post', $entity->getTargetType());
        $this->assertSame('77', $entity->getTargetId());
        $this->assertSame(['title' => 'hello'], $entity->getMetadata());
        $this->assertSame('2026-05-01 00:00:00', $entity->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-01 12:00:00', $entity->getOccurredAt()->format('Y-m-d H:i:s'));

        // 建立工廠方法應與建構子等效
        $fromDto = ActivityLog::fromDTO(
            actionType: ActivityType::POST_PUBLISHED,
            userId: 9,
            sessionId: 'sess-db',
            status: ActivityStatus::SUCCESS,
            targetType: 'post',
            targetId: '77',
            description: '發布公告',
            metadata: ['title' => 'hello'],
            ipAddress: '192.0.2.50',
            userAgent: 'Firefox',
            requestMethod: 'PATCH',
            requestPath: '/api/v1/posts/77/publish',
            occurredAt: new DateTimeImmutable('2026-05-01 12:00:00'),
        );
        $this->assertSame(ActivityCategory::CONTENT, $fromDto->getActionCategory());
        $this->assertSame('發布公告', $fromDto->getDescription());
    }
}
