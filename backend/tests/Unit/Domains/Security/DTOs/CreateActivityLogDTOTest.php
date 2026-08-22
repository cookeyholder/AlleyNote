<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\DTOs;

use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityStatus;
use App\Domains\Security\Enums\ActivityType;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * CreateActivityLogDTO 測試.
 */
#[CoversClass(CreateActivityLogDTO::class)]
class CreateActivityLogDTOTest extends UnitTestCase
{
    #[Test]
    public function it_sets_defaults_on_construction(): void
    {
        $dto = new CreateActivityLogDTO(ActivityType::LOGIN_SUCCESS, 1);

        $this->assertSame(ActivityType::LOGIN_SUCCESS, $dto->getActionType());
        $this->assertSame(1, $dto->getUserId());
        $this->assertNull($dto->getSessionId());
        $this->assertSame(ActivityStatus::SUCCESS, $dto->getStatus());
        $this->assertNull($dto->getTargetType());
        $this->assertNull($dto->getTargetId());
        $this->assertNull($dto->getDescription());
        $this->assertNull($dto->getMetadata());
        $this->assertNull($dto->getIpAddress());
        $this->assertNull($dto->getUserAgent());
        $this->assertNull($dto->getRequestMethod());
        $this->assertNull($dto->getRequestPath());
        $this->assertInstanceOf(DateTimeImmutable::class, $dto->getOccurredAt());
    }

    #[Test]
    public function it_builds_from_array_with_full_data(): void
    {
        $dto = CreateActivityLogDTO::fromArray([
            'action_type'    => 'login_failed',
            'user_id'        => 5,
            'session_id'     => 'sess-1',
            'status'         => 'failed',
            'target_type'    => 'post',
            'target_id'      => '9',
            'description'    => '登入失敗',
            'metadata'       => ['ip' => '10.0.0.1'],
            'ip_address'     => '10.0.0.1',
            'user_agent'     => 'UA',
            'request_method' => 'POST',
            'request_path'   => '/api/v1/auth/login',
            'occurred_at'    => '2026-01-15 12:30:00',
        ]);

        $this->assertSame(ActivityType::LOGIN_FAILED, $dto->getActionType());
        $this->assertSame(5, $dto->getUserId());
        $this->assertSame('sess-1', $dto->getSessionId());
        $this->assertSame(ActivityStatus::FAILED, $dto->getStatus());
        $this->assertSame('post', $dto->getTargetType());
        $this->assertSame('9', $dto->getTargetId());
        $this->assertSame('登入失敗', $dto->getDescription());
        $this->assertSame(['ip' => '10.0.0.1'], $dto->getMetadata());
        $this->assertSame('10.0.0.1', $dto->getIpAddress());
        $this->assertSame('UA', $dto->getUserAgent());
        $this->assertSame('POST', $dto->getRequestMethod());
        $this->assertSame('/api/v1/auth/login', $dto->getRequestPath());
        $this->assertSame('2026-01-15 12:30:00', $dto->getOccurredAt()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_builds_from_array_with_minimal_data(): void
    {
        $dto = CreateActivityLogDTO::fromArray(['action_type' => 'logout']);

        $this->assertSame(ActivityType::LOGOUT, $dto->getActionType());
        $this->assertNull($dto->getUserId());
        $this->assertSame(ActivityStatus::SUCCESS, $dto->getStatus());
        $this->assertNull($dto->getDescription());
    }

    #[Test]
    public function it_creates_success_failure_and_security_event_factories(): void
    {
        $success = CreateActivityLogDTO::success(
            ActivityType::LOGIN_SUCCESS,
            userId: 3,
            targetType: 'user',
            targetId: '3',
            description: 'ok',
            metadata: ['k' => 'v'],
        );
        $failure = CreateActivityLogDTO::failure(
            ActivityType::LOGIN_FAILED,
            userId: 3,
            description: 'bad credentials',
        );
        $security = CreateActivityLogDTO::securityEvent(
            ActivityType::XSS_ATTACK_BLOCKED,
            ipAddress: '8.8.8.8',
            userAgent: 'bot',
            description: 'xss blocked',
            metadata: ['payload' => '<script>'],
        );

        $this->assertSame(ActivityStatus::SUCCESS, $success->getStatus());
        $this->assertSame(ActivityStatus::FAILED, $failure->getStatus());
        $this->assertSame(ActivityStatus::BLOCKED, $security->getStatus());
        $this->assertSame('8.8.8.8', $security->getIpAddress());
        $this->assertSame('bot', $security->getUserAgent());
    }

    #[Test]
    public function it_returns_immutable_copies_from_fluent_setters(): void
    {
        $original = new CreateActivityLogDTO(ActivityType::POST_CREATED, 1);
        $occurred = new DateTimeImmutable('2026-06-01 10:00:00');

        $updated = $original
            ->withUserId(2)
            ->withSessionId('sess-2')
            ->withRequestInfo('PUT', '/api/v1/posts/1')
            ->withNetworkInfo('203.0.113.5', 'Mozilla/5.0')
            ->withMetadata(['a' => 1])
            ->addMetadata('b', 2)
            ->withUserId(null);

        $this->assertNotSame($original, $updated);
        $this->assertSame(1, $original->getUserId());
        $this->assertNull($original->getSessionId());
        $this->assertNull($original->getRequestMethod());
        $this->assertNull($original->getMetadata());
        $this->assertSame('sess-2', $updated->getSessionId());
        $this->assertSame('PUT', $updated->getRequestMethod());
        $this->assertSame('/api/v1/posts/1', $updated->getRequestPath());
        $this->assertSame('203.0.113.5', $updated->getIpAddress());
        $this->assertSame('Mozilla/5.0', $updated->getUserAgent());
        $this->assertSame(['a' => 1, 'b' => 2], $updated->getMetadata());

        $withTime = new CreateActivityLogDTO(ActivityType::POST_UPDATED, occurredAt: $occurred);
        $this->assertSame($occurred, $withTime->getOccurredAt());
    }

    #[Test]
    public function it_converts_to_array_with_derived_fields(): void
    {
        $dto = CreateActivityLogDTO::failure(
            ActivityType::LOGIN_FAILED,
            userId: 11,
            metadata: ['reason' => '密碼錯誤'],
        );

        $array = $dto->toArray();

        $this->assertSame('login_failed', $array['action_type']);
        $this->assertSame('authentication', $array['action_category']);
        $this->assertSame('failed', $array['status']);
        $this->assertSame(11, $array['user_id']);
        // 未提供描述時使用 enum 預設描述
        $this->assertSame(ActivityType::LOGIN_FAILED->getDescription(), $array['description']);
        $this->assertSame('{"reason":"密碼錯誤"}', $array['metadata']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('occurred_at', $array);
    }

    #[Test]
    public function it_serializes_to_json_same_as_to_array(): void
    {
        $dto = CreateActivityLogDTO::success(ActivityType::POST_VIEWED, userId: 4);

        $this->assertSame($dto->toArray(), $dto->jsonSerialize());
        $this->assertJson((string) json_encode($dto));
    }

    #[Test]
    public function it_rejects_non_serializable_metadata(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Metadata must be JSON serializable');

        new CreateActivityLogDTO(ActivityType::LOGIN_SUCCESS, metadata: ['nan' => NAN]);
    }

    #[Test]
    public function it_rejects_oversized_metadata(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds maximum limit');

        new CreateActivityLogDTO(ActivityType::LOGIN_SUCCESS, metadata: ['blob' => str_repeat('a', 70000)]);
    }

    #[Test]
    public function it_validates_metadata_in_fluent_setters(): void
    {
        $dto = CreateActivityLogDTO::success(ActivityType::LOGIN_SUCCESS);

        $this->expectException(InvalidArgumentException::class);

        $dto->withMetadata(['inf' => INF]);
    }
}
