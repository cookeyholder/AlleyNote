<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\Services;

use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityStatus;
use App\Domains\Security\Enums\ActivityType;
use App\Domains\Security\Services\ActivityLoggingService;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass(ActivityLoggingService => class)]
class ActivityLoggingServiceTest extends TestCase
{
    private ActivityLogRepositoryInterface|MockObject $repository;

    private LoggerInterface|MockObject $logger;

    private ActivityLoggingServiceInterface $service;

    // Properties for callback validation
    private ActivityType $expectedActionType;

    private int $expectedUserId;

    private ?string $expectedTargetType = null;

    private ?string $expectedTargetId = null;

    private ?string $expectedDescription = null;

    private ?array $expectedMetadata = null;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ActivityLogRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new ActivityLoggingService($this->repository, $this->logger);
    }

    #[Test]
    public function it_implements_activity_logging_service_interface(): void
    {
        $this->assertInstanceOf(ActivityLoggingServiceInterface::class, $this->service);
    }

    #[Test]
    public function it_can_log_activity_with_dto(): void
    {
        // Arrange
        $dto = CreateActivityLogDTO::success(
            actionType: ActivityType::LOGIN_SUCCESS,
            userId: 1,
            description: 'User login successful',
        );

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->equalTo($dto))
            ->willReturn([
                'id' => 1,
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'action_type' => ActivityType => LOGIN_SUCCESS->value,
                'user_id' => 1,
            ]);

        // Act
        $result = $this->service->log($dto);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_can_log_success_operation(): void
    {
        // Arrange
        $actionType = ActivityType::LOGIN_SUCCESS;
        $userId = 1;
        $targetType = 'user';
        $targetId = '123';
        $metadata = ['login_method' => 'password'];

        // Store expected values for callback validation
        $this->expectedActionType = $actionType;
        $this->expectedUserId = $userId;
        $this->expectedTargetType = $targetType;
        $this->expectedTargetId = $targetId;
        $this->expectedMetadata = $metadata;

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback([$this, 'validateSuccessOperationDto']))
            ->willReturn([
                'id' => 1,
                'action_type' => $actionType->value,
            ]);

        // Act
        $result = $this->service->logSuccess($actionType, $userId, $targetType, $targetId, $metadata);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_can_log_failure_operation(): void
    {
        // Arrange
        $actionType = ActivityType::LOGIN_FAILED;
        $userId = 1;
        $reason = 'Invalid password';
        $metadata = ['attempt_count' => 3];

        // Store expected values for callback validation
        $this->expectedActionType = $actionType;
        $this->expectedUserId = $userId;
        $this->expectedDescription = $reason;
        $this->expectedMetadata = $metadata;

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback([$this, 'validateFailureOperationDto']))
            ->willReturn([
                'id' => 1,
                'action_type' => $actionType->value,
            ]);

        // Act
        $result = $this->service->logFailure($actionType, $userId, $reason, $metadata);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_can_log_security_event(): void
    {
        // Arrange
        $actionType = ActivityType::IP_BLOCKED;
        $description = 'IP blocked due to suspicious activity';
        $metadata = ['ip_address' => '192.168.1.100', 'reason' => 'brute_force'];

        // Store expected values for callback validation
        $this->expectedActionType = $actionType;
        $this->expectedDescription = $description;
        $this->expectedMetadata = $metadata;

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback([$this, 'validateSecurityEventDto']))
            ->willReturn([
                'id' => 1,
                'action_type' => $actionType->value,
            ]);

        // Act
        $result = $this->service->logSecurityEvent($actionType, $description, $metadata);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_can_log_batch_activities(): void
    {
        // Arrange
        $dtos = [
            CreateActivityLogDTO => success(ActivityType => :LOGIN_SUCCESS, 1),
            CreateActivityLogDTO::success(ActivityType::POST_CREATED, 1),
        ];

        $this->repository->expects($this->once())
            ->method('createBatch')
            ->with($this->equalTo($dtos))
            ->willReturn(2);

        // Act
        $result = $this->service->logBatch($dtos);

        // Assert
        $this->assertEquals(2, $result);
    }

    #[Test]
    public function it_handles_repository_failure_gracefully(): void
    {
        // Arrange
        $dto = CreateActivityLogDTO::success(ActivityType::LOGIN_SUCCESS, 1);

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->equalTo($dto))
            ->willThrowException(new RuntimeException('Database error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Failed to log activity'),
                $this->callback(function (array $context) {
                    return isset($context['error']);
                }),
            );

        // Act
        $result = $this->service->log($dto);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_enable_logging_for_action_type(): void
    {
        // Arrange
        $actionType = ActivityType::LOGIN_SUCCESS;

        // Act
        $this->service->enableLogging($actionType);

        // Assert
        $this->assertTrue($this->service->isLoggingEnabled($actionType));
    }

    #[Test]
    public function it_can_disable_logging_for_action_type(): void
    {
        // Arrange
        $actionType = ActivityType::LOGIN_SUCCESS;

        // Act
        $this->service->disableLogging($actionType);

        // Assert
        $this->assertFalse($this->service->isLoggingEnabled($actionType));
    }

    #[Test]
    public function it_can_set_and_respect_log_level(): void
    {
        // Arrange
        $this->service->setLogLevel(3); // 只記錄嚴重程度 >= 3 的活動

        $highSeverityDto = CreateActivityLogDTO::success(
            actionType: ActivityType::LOGIN_FAILED, // HIGH severity
            userId: 1,
        );

        $lowSeverityDto = CreateActivityLogDTO::success(
            actionType: ActivityType::LOGIN_SUCCESS, // LOW severity
            userId: 1,
        );

        // High severity should be logged
        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->equalTo($highSeverityDto))
            ->willReturn(['id' => 1]);

        // Act & Assert
        $this->assertTrue($this->service->log($highSeverityDto));
        $this->assertFalse($this->service->log($lowSeverityDto)); // Should be filtered out
    }

    #[Test]
    public function it_can_cleanup_old_records(): void
    {
        // Arrange
        $cleanupDate = new DateTimeImmutable('-30 days');

        $this->repository->expects($this->once())
            ->method('deleteOldRecords')
            ->with($this->callback(function (DateTimeInterface $date) use ($cleanupDate) {
                return $date->format('Y-m-d') === $cleanupDate->format('Y-m-d');
            }))
            ->willReturn(150);

        // Act
        $result = $this->service->cleanup();

        // Assert
        $this->assertEquals(150, $result);
    }

    #[Test]
    public function it_skips_logging_when_disabled_for_action_type(): void
    {
        // Arrange
        $actionType = ActivityType::LOGIN_SUCCESS;
        $dto = CreateActivityLogDTO::success($actionType, 1);

        $this->service->disableLogging($actionType);

        // Repository should not be called
        $this->repository->expects($this->never())->method('create');

        // Act
        $result = $this->service->log($dto);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_logs_warning_when_skipping_disabled_action(): void
    {
        // Arrange
        $actionType = ActivityType::LOGIN_SUCCESS;
        $dto = CreateActivityLogDTO::success($actionType, 1);

        $this->service->disableLogging($actionType);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Logging disabled for action type'),
                $this->callback(function (array $context) use ($actionType) {
                    return $context['action_type'] === $actionType->value;
                }),
            );

        // Act
        $this->service->log($dto);
    }

    #[Test]
    public function it_respects_log_level_in_convenience_methods(): void
    {
        // Arrange
        $this->service->setLogLevel(4); // Only HIGH and CRITICAL

        $lowSeverityAction = ActivityType::LOGIN_SUCCESS; // LOW severity
        $highSeverityAction = ActivityType::LOGIN_FAILED; // HIGH severity

        // Repository should only be called once for high severity
        $this->repository->expects($this->once())
            ->method('create')
            ->willReturn(['id' => 1]);

        // Act & Assert
        $this->assertFalse($this->service->logSuccess($lowSeverityAction, 1));
        $this->assertTrue($this->service->logSecurityEvent($highSeverityAction, 'High severity event'));
    }

    /**
     * Validate DTO for success operation.
     */
    public function validateSuccessOperationDto(CreateActivityLogDTO $dto): bool
    {
        return $dto->getActionType() === $this->expectedActionType
            && $dto->getUserId() === $this->expectedUserId
            && $dto->getTargetType() === $this->expectedTargetType
            && $dto->getTargetId() === $this->expectedTargetId
            && $dto->getMetadata() === $this->expectedMetadata
            && $dto->getStatus() === ActivityStatus::SUCCESS;
    }

    /**
     * Validate DTO for failure operation.
     */
    public function validateFailureOperationDto(CreateActivityLogDTO $dto): bool
    {
        return $dto->getActionType() === $this->expectedActionType
            && $dto->getUserId() === $this->expectedUserId
            && $dto->getDescription() === $this->expectedDescription
            && $dto->getMetadata() === $this->expectedMetadata
            && $dto->getStatus() === ActivityStatus::FAILED;
    }

    /**
     * Validate DTO for security event.
     */
    public function validateSecurityEventDto(CreateActivityLogDTO $dto): bool
    {
        return $dto->getActionType() === $this->expectedActionType
            && $dto->getDescription() === $this->expectedDescription
            && $dto->getMetadata() === $this->expectedMetadata
            && $dto->getStatus() === ActivityStatus::BLOCKED;
    }
}
