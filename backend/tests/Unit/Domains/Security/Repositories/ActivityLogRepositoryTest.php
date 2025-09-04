<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\Repositories;

use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityCategory;
use App\Domains\Security\Enums\ActivityStatus;
use App\Domains\Security\Enums\ActivityType;
use App\Domains\Security\Repositories\ActivityLogRepository;
use DateTimeImmutable;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ActivityLogRepository::class)]
class ActivityLogRepositoryTest extends TestCase
{
    private PDO|MockObject $pdo;

    private PDOStatement|MockObject $statement;

    private ActivityLogRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->statement = $this->createMock(PDOStatement::class);

        // 基本設定期望值
        $this->pdo->expects($this->any())->method('exec');
        $this->pdo->expects($this->any())->method('setAttribute');

        $this->repository = new ActivityLogRepository($this->pdo);
    }

    #[Test]
    public function it_implements_activity_log_repository_interface(): void
    {
        $this->assertInstanceOf(ActivityLogRepositoryInterface::class, $this->repository);
    }

    #[Test]
    public function it_can_create_activity_log(): void
    {
        // Arrange
        $dto = CreateActivityLogDTO::success(
            actionType: ActivityType::LOGIN_SUCCESS,
            userId: 1,
            description: 'User login successful',
        );

        // Mock successful database transaction
        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('lastInsertId')->willReturn('1');
        $this->pdo->expects($this->once())->method('commit');

        // Mock the INSERT statement
        $insertStatement = $this->createMock(PDOStatement::class);
        $insertStatement->expects($this->once())->method('execute');

        // Mock the SELECT statement for findById
        $selectStatement = $this->createMock(PDOStatement::class);
        $selectStatement->expects($this->once())
            ->method('execute')
            ->with([':id' => 1]);
        $selectStatement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'user_id' => 1,
                'session_id' => null,
                'action_type' => ActivityType::LOGIN_SUCCESS->value,
                'action_category' => ActivityType::LOGIN_SUCCESS->getCategory()->value,
                'target_type' => null,
                'target_id' => null,
                'status' => ActivityStatus::SUCCESS->value,
                'description' => 'User login successful',
                'metadata' => null,
                'ip_address' => null,
                'user_agent' => null,
                'request_method' => null,
                'request_path' => null,
                'created_at' => '2024-01-01 12:00:00',
                'occurred_at' => '2024-01-01 12:00:00',
            ]);

        // Setup prepare method to return different statements
        $this->pdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($sql) use ($insertStatement, $selectStatement) {
                if (str_contains($sql, 'INSERT')) {
                    return $insertStatement;
                }
                if (str_contains($sql, 'SELECT')) {
                    return $selectStatement;
                }

                return false;
            });

        // Act
        $result = $this->repository->create($dto);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('uuid', $result);
        $this->assertEquals(ActivityType::LOGIN_SUCCESS->value, $result['action_type']);
        $this->assertEquals(1, $result['user_id']);
    }

    #[Test]
    public function it_can_find_activity_log_by_id(): void
    {
        // Arrange
        $mockData = [
            'id' => 1,
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'user_id' => 1,
            'session_id' => null,
            'action_type' => ActivityType::LOGIN_SUCCESS->value,
            'action_category' => ActivityType::LOGIN_SUCCESS->getCategory()->value,
            'target_type' => null,
            'target_id' => null,
            'status' => ActivityStatus::SUCCESS->value,
            'description' => 'User login successful',
            'metadata' => null,
            'ip_address' => null,
            'user_agent' => null,
            'request_method' => null,
            'request_path' => null,
            'created_at' => '2024-01-01 12:00:00',
            'occurred_at' => '2024-01-01 12:00:00',
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('execute')
            ->with([':id' => 1]);

        $this->statement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($mockData);

        // Act
        $result = $this->repository->findById(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals(ActivityType::LOGIN_SUCCESS->value, $result['action_type']);
    }

    #[Test]
    public function it_returns_null_when_activity_log_not_found_by_id(): void
    {
        // Arrange
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('execute')
            ->with([':id' => 999]);

        $this->statement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        // Act
        $result = $this->repository->findById(999);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_can_find_activity_log_by_uuid(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $mockData = [
            'id' => 1,
            'uuid' => $uuid,
            'user_id' => 1,
            'session_id' => null,
            'action_type' => ActivityType::LOGIN_SUCCESS->value,
            'action_category' => ActivityType::LOGIN_SUCCESS->getCategory()->value,
            'target_type' => null,
            'target_id' => null,
            'status' => ActivityStatus::SUCCESS->value,
            'description' => 'User login successful',
            'metadata' => null,
            'ip_address' => null,
            'user_agent' => null,
            'request_method' => null,
            'request_path' => null,
            'created_at' => '2024-01-01 12:00:00',
            'occurred_at' => '2024-01-01 12:00:00',
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('execute')
            ->with([':uuid' => $uuid]);

        $this->statement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($mockData);

        // Act
        $result = $this->repository->findByUuid($uuid);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals($uuid, $result['uuid']);
    }

    #[Test]
    public function it_can_find_activity_logs_by_user(): void
    {
        // Arrange
        $userId = 1;
        $mockData = [
            'id' => 1,
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'user_id' => 1,
            'session_id' => null,
            'action_type' => ActivityType::LOGIN_SUCCESS->value,
            'action_category' => ActivityType::LOGIN_SUCCESS->getCategory()->value,
            'target_type' => null,
            'target_id' => null,
            'status' => ActivityStatus::SUCCESS->value,
            'description' => 'User login successful',
            'metadata' => null,
            'ip_address' => null,
            'user_agent' => null,
            'request_method' => null,
            'request_path' => null,
            'created_at' => '2024-01-01 12:00:00',
            'occurred_at' => '2024-01-01 12:00:00',
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->exactly(3))
            ->method('bindValue')
            ->willReturnCallback(function ($param, $value, $type = null) {
                $this->assertContains($param, [':user_id', ':limit', ':offset']);

                return true;
            });

        $this->statement->expects($this->once())->method('execute');
        $this->statement->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls($mockData, false);

        // Act
        $result = $this->repository->findByUser($userId);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($userId, $result[0]['user_id']);
    }

    #[Test]
    public function it_can_create_batch_activity_logs(): void
    {
        // Arrange
        $dtos = [
            CreateActivityLogDTO::success(ActivityType::LOGIN_SUCCESS, 1),
            CreateActivityLogDTO::failure(ActivityType::LOGIN_FAILED, 2),
        ];

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('prepare')->willReturn($this->statement);
        $this->pdo->expects($this->once())->method('commit');

        $this->statement->expects($this->exactly(2))->method('execute');

        // Act
        $count = $this->repository->createBatch($dtos);

        // Assert
        $this->assertEquals(2, $count);
    }

    #[Test]
    public function it_can_count_by_category(): void
    {
        // Arrange
        $category = ActivityCategory::AUTHENTICATION;

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('execute')
            ->with([':category' => $category->value]);

        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('5');

        // Act
        $count = $this->repository->countByCategory($category);

        // Assert
        $this->assertEquals(5, $count);
    }

    #[Test]
    public function it_can_delete_old_records(): void
    {
        // Arrange
        $before = new DateTimeImmutable('2023-01-01');

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('execute')
            ->with([':before' => $before->format('Y-m-d H:i:s')]);

        $this->statement->expects($this->once())
            ->method('rowCount')
            ->willReturn(3);

        // Act
        $deletedCount = $this->repository->deleteOldRecords($before);

        // Assert
        $this->assertEquals(3, $deletedCount);
    }

    #[Test]
    public function it_can_find_activity_logs_by_time_range(): void
    {
        // Arrange
        $startTime = new DateTimeImmutable('2024-01-01 00:00:00');
        $endTime = new DateTimeImmutable('2024-01-31 23:59:59');
        $mockData = [
            'id' => 1,
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'user_id' => 1,
            'session_id' => null,
            'action_type' => ActivityType::LOGIN_SUCCESS->value,
            'action_category' => ActivityType::LOGIN_SUCCESS->getCategory()->value,
            'target_type' => null,
            'target_id' => null,
            'status' => ActivityStatus::SUCCESS->value,
            'description' => 'User login successful',
            'metadata' => null,
            'ip_address' => null,
            'user_agent' => null,
            'request_method' => null,
            'request_path' => null,
            'created_at' => '2024-01-15 12:00:00',
            'occurred_at' => '2024-01-15 12:00:00',
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->exactly(4))
            ->method('bindValue')
            ->willReturnCallback(function ($param, $value, $type = null) {
                $this->assertContains($param, [':start_time', ':end_time', ':limit', ':offset']);

                return true;
            });

        $this->statement->expects($this->once())->method('execute');
        $this->statement->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls($mockData, false);

        // Act
        $result = $this->repository->findByTimeRange($startTime, $endTime);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['id']);
    }

    #[Test]
    public function it_can_find_security_events(): void
    {
        // Arrange
        $mockData = [
            'id' => 1,
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'user_id' => 1,
            'session_id' => null,
            'action_type' => ActivityType::LOGIN_FAILED->value,
            'action_category' => ActivityType::LOGIN_FAILED->getCategory()->value,
            'target_type' => null,
            'target_id' => null,
            'status' => ActivityStatus::FAILED->value,
            'description' => 'Login failed',
            'metadata' => null,
            'ip_address' => '192.168.1.100',
            'user_agent' => null,
            'request_method' => null,
            'request_path' => null,
            'created_at' => '2024-01-15 12:00:00',
            'occurred_at' => '2024-01-15 12:00:00',
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->exactly(2))
            ->method('bindValue')
            ->willReturnCallback(function ($param, $value, $type = null) {
                $this->assertContains($param, [':limit', ':offset']);

                return true;
            });

        $this->statement->expects($this->once())->method('execute');
        $this->statement->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls($mockData, false);

        // Act
        $result = $this->repository->findSecurityEvents();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(ActivityStatus::FAILED->value, $result[0]['status']);
    }

    #[Test]
    public function it_can_find_failed_activities(): void
    {
        // Arrange
        $mockData = [
            'id' => 1,
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'user_id' => 1,
            'session_id' => null,
            'action_type' => ActivityType::LOGIN_FAILED->value,
            'action_category' => ActivityType::LOGIN_FAILED->getCategory()->value,
            'target_type' => null,
            'target_id' => null,
            'status' => ActivityStatus::FAILED->value,
            'description' => 'Login failed',
            'metadata' => null,
            'ip_address' => '192.168.1.100',
            'user_agent' => null,
            'request_method' => null,
            'request_path' => null,
            'created_at' => '2024-01-15 12:00:00',
            'occurred_at' => '2024-01-15 12:00:00',
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->exactly(2))
            ->method('bindValue')
            ->willReturnCallback(function ($param, $value, $type = null) {
                $this->assertContains($param, [':limit', ':offset']);

                return true;
            });

        $this->statement->expects($this->once())->method('execute');
        $this->statement->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls($mockData, false);

        // Act
        $result = $this->repository->findFailedActivities();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(ActivityStatus::FAILED->value, $result[0]['status']);
    }

    #[Test]
    public function it_can_count_user_activities_in_time_range(): void
    {
        // Arrange
        $userId = 1;
        $startTime = new DateTimeImmutable('2024-01-01 00:00:00');
        $endTime = new DateTimeImmutable('2024-01-31 23:59:59');

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('execute')
            ->with([
                ':user_id' => $userId,
                ':start_time' => $startTime->format('Y-m-d H:i:s'),
                ':end_time' => $endTime->format('Y-m-d H:i:s'),
            ]);

        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('10');

        // Act
        $count = $this->repository->countUserActivities($userId, $startTime, $endTime);

        // Assert
        $this->assertEquals(10, $count);
    }

    #[Test]
    public function it_can_get_activity_statistics(): void
    {
        // Arrange
        $startTime = new DateTimeImmutable('2024-01-01 00:00:00');
        $endTime = new DateTimeImmutable('2024-01-31 23:59:59');

        $mockStats = [
            ['action_category' => 'authentication', 'action_type' => 'login_success', 'count' => 150],
            ['action_category' => 'authentication', 'action_type' => 'login_failed', 'count' => 25],
            ['action_category' => 'content', 'action_type' => 'post_created', 'count' => 75],
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('execute')
            ->with([
                ':start_time' => $startTime->format('Y-m-d H:i:s'),
                ':end_time' => $endTime->format('Y-m-d H:i:s'),
            ]);

        $this->statement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($mockStats);

        // Act
        $result = $this->repository->getActivityStatistics($startTime, $endTime);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(150, $result[0]['count']);
        $this->assertEquals('authentication', $result[0]['action_category']);
    }

    #[Test]
    public function it_can_get_popular_activity_types(): void
    {
        // Arrange
        $mockTypes = [
            ['action_type' => 'login_success', 'count' => 500],
            ['action_type' => 'post_view', 'count' => 300],
            ['action_type' => 'login_failed', 'count' => 50],
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('bindValue')
            ->with(':limit', 10, PDO::PARAM_INT);

        $this->statement->expects($this->once())->method('execute');
        $this->statement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($mockTypes);

        // Act
        $result = $this->repository->getPopularActivityTypes();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('login_success', $result[0]['action_type']);
        $this->assertEquals(500, $result[0]['count']);
    }

    #[Test]
    public function it_can_get_suspicious_ip_addresses(): void
    {
        // Arrange
        $mockIps = [
            ['ip_address' => '192.168.1.100', 'failure_count' => 15],
            ['ip_address' => '10.0.0.50', 'failure_count' => 12],
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('bindValue')
            ->with(':threshold', 10, PDO::PARAM_INT);

        $this->statement->expects($this->once())->method('execute');
        $this->statement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($mockIps);

        // Act
        $result = $this->repository->getSuspiciousIpAddresses();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('192.168.1.100', $result[0]['ip_address']);
        $this->assertEquals(15, $result[0]['failure_count']);
    }

    #[Test]
    public function it_can_search_activity_logs(): void
    {
        // Arrange
        $searchTerm = 'login';
        $mockData = [
            'id' => 1,
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'user_id' => 1,
            'session_id' => null,
            'action_type' => ActivityType::LOGIN_SUCCESS->value,
            'action_category' => ActivityType::LOGIN_SUCCESS->getCategory()->value,
            'target_type' => null,
            'target_id' => null,
            'status' => ActivityStatus::SUCCESS->value,
            'description' => 'User login successful',
            'metadata' => null,
            'ip_address' => null,
            'user_agent' => null,
            'request_method' => null,
            'request_path' => null,
            'created_at' => '2024-01-15 12:00:00',
            'occurred_at' => '2024-01-15 12:00:00',
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->exactly(3))
            ->method('bindValue')
            ->willReturnCallback(function ($param, $value, $type = null) use ($searchTerm) {
                if ($param === ':search_term') {
                    $this->assertEquals('%' . $searchTerm . '%', $value);
                } else {
                    $this->assertContains($param, [':limit', ':offset']);
                }

                return true;
            });

        $this->statement->expects($this->once())->method('execute');
        $this->statement->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls($mockData, false);

        // Act
        $result = $this->repository->search($searchTerm);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertStringContainsString('login', strtolower($result[0]['description']));
    }

    #[Test]
    public function it_can_get_search_count(): void
    {
        // Arrange
        $searchTerm = 'login';

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->statement);

        $this->statement->expects($this->once())
            ->method('execute')
            ->with([':search_term' => '%' . $searchTerm . '%']);

        $this->statement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('25');

        // Act
        $count = $this->repository->getSearchCount($searchTerm);

        // Assert
        $this->assertEquals(25, $count);
    }
}
