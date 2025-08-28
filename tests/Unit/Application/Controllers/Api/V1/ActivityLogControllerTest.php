<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\ActivityLogController;
use App\Application\Controllers\BaseController;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Validation\ValidationResult;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

#[CoversClass(ActivityLogController::class)]
class ActivityLogControllerTest extends TestCase
{
    private ActivityLoggingServiceInterface&MockInterface $loggingService;

    private ActivityLogRepositoryInterface&MockInterface $repository;

    private ValidatorInterface&MockInterface $validator;

    private ActivityLogController $controller;

    protected function setUp(): void
    {
        /** @var ActivityLoggingServiceInterface&MockInterface $loggingService */
        $loggingService = Mockery::mock(ActivityLoggingServiceInterface::class);
        $this->loggingService = $loggingService;

        /** @var ActivityLogRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(ActivityLogRepositoryInterface::class);
        $this->repository = $repository;

        /** @var ValidatorInterface&MockInterface $validator */
        $validator = Mockery::mock(ValidatorInterface::class);
        $this->validator = $validator;

        $this->controller = new ActivityLogController(
            $this->loggingService,
            $this->repository,
            $this->validator,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    #[Test]
    public function it_extends_base_controller(): void
    {
        $this->assertInstanceOf(BaseController::class, $this->controller);
    }

    #[Test]
    public function it_can_create_single_activity_log(): void
    {
        // Arrange
        $requestBody = [
            'action_type' => 'auth.login.success',
            'user_id' => 1,
            'description' => 'User login successful',
            'metadata' => ['ip' => '127.0.0.1'],
        ];

        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        /** @var ResponseInterface&MockInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        $request->shouldReceive('getParsedBody')->andReturn($requestBody);

        $validationResult = ValidationResult::success($requestBody);
        $this->validator->shouldReceive('validate')->andReturn($validationResult);

        $this->loggingService->shouldReceive('log')->andReturn(true);

        // Act
        $result = $this->controller->store($request, $response);

        // Assert
        $responseData = json_decode($result, true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Activity logged successfully', $responseData['message']);
    }

    #[Test]
    public function it_can_create_batch_activity_logs(): void
    {
        // Arrange
        $requestBody = [
            'logs' => [
                [
                    'action_type' => 'auth.login.success',
                    'user_id' => 1,
                    'description' => 'User login successful',
                ],
                [
                    'action_type' => 'post.created',
                    'user_id' => 1,
                    'target_type' => 'post',
                    'target_id' => '123',
                ],
            ],
        ];

        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        /** @var ResponseInterface&MockInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        $request->shouldReceive('getParsedBody')->andReturn($requestBody);

        $validationResult = ValidationResult::success($requestBody);
        $this->validator->shouldReceive('validate')->andReturn($validationResult);

        $this->loggingService->shouldReceive('logBatch')->andReturn(2);

        // Act
        $result = $this->controller->storeBatch($request, $response);

        // Assert
        $responseData = json_decode($result, true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('2 activities logged successfully', $responseData['message']);
        $this->assertEquals(2, $responseData['data']['logged_count']);
    }

    #[Test]
    public function it_can_get_activity_logs_with_pagination(): void
    {
        // Arrange
        $queryParams = [
            'page' => '1',
            'limit' => '10',
            'category' => 'authentication',
        ];

        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        /** @var ResponseInterface&MockInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        $request->shouldReceive('getQueryParams')->andReturn($queryParams);

        $mockLogs = [
            [
                'id' => 1,
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'action_type' => 'auth.login.success',
                'user_id' => 1,
                'occurred_at' => '2024-01-01 12:00:00',
            ],
        ];

        $this->repository->shouldReceive('search')->andReturn($mockLogs);
        $this->repository->shouldReceive('getSearchCount')->andReturn(1);

        // Act
        $result = $this->controller->index($request, $response);

        // Assert
        $responseData = json_decode($result, true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('pagination', $responseData);
        $this->assertEquals(1, $responseData['pagination']['total']);
    }

    #[Test]
    public function it_can_get_user_activity_logs(): void
    {
        // Arrange
        $userId = 1;
        $queryParams = ['limit' => '20'];
        $args = ['id' => (string) $userId];

        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        /** @var ResponseInterface&MockInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        $request->shouldReceive('getQueryParams')->andReturn($queryParams);

        $mockLogs = [
            [
                'id' => 1,
                'user_id' => $userId,
                'action_type' => 'auth.login.success',
                'occurred_at' => '2024-01-01 12:00:00',
            ],
        ];

        $this->repository->shouldReceive('findByUser')->andReturn($mockLogs);

        // Act
        $result = $this->controller->getUserActivities($request, $response, $args);

        // Assert
        $responseData = json_decode($result, true);
        $this->assertTrue($responseData['success']);
        $this->assertCount(1, $responseData['data']);
        $this->assertEquals($userId, $responseData['data'][0]['user_id']);
    }

    #[Test]
    public function it_can_search_activity_logs(): void
    {
        // Arrange
        $queryParams = [
            'q' => 'login',
            'start_time' => '2024-01-01T00:00:00Z',
            'end_time' => '2024-01-31T23:59:59Z',
            'limit' => '50',
        ];

        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        /** @var ResponseInterface&MockInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        $request->shouldReceive('getQueryParams')->andReturn($queryParams);

        $mockLogs = [
            [
                'id' => 1,
                'action_type' => 'auth.login.success',
                'description' => 'User login successful',
                'occurred_at' => '2024-01-15 12:00:00',
            ],
        ];

        $this->repository->shouldReceive('search')->andReturn($mockLogs);
        $this->repository->shouldReceive('getSearchCount')->andReturn(1);

        // Act
        $result = $this->controller->search($request, $response);

        // Assert
        $responseData = json_decode($result, true);
        $this->assertTrue($responseData['success']);
        $this->assertCount(1, $responseData['data']['logs']);
        $this->assertStringContainsString('login', $responseData['data']['logs'][0]['description']);
    }

    #[Test]
    public function it_can_get_activity_statistics(): void
    {
        // Arrange
        $queryParams = [
            'start_time' => '2024-01-01T00:00:00Z',
            'end_time' => '2024-01-31T23:59:59Z',
        ];

        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        /** @var ResponseInterface&MockInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        $request->shouldReceive('getQueryParams')->andReturn($queryParams);

        $mockStats = [
            ['action_category' => 'authentication', 'action_type' => 'auth.login.success', 'count' => 150],
            ['action_category' => 'content', 'action_type' => 'post.created', 'count' => 75],
        ];

        $this->repository->shouldReceive('getActivityStatistics')->andReturn($mockStats);

        // Act
        $result = $this->controller->statistics($request, $response);

        // Assert
        $responseData = json_decode($result, true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('statistics', $responseData['data']);
        $this->assertCount(2, $responseData['data']['statistics']);
    }

    #[Test]
    public function it_handles_validation_errors(): void
    {
        // Arrange
        $requestBody = [
            'action_type' => 'invalid_type',
            'user_id' => 'not_a_number',
        ];

        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        /** @var ResponseInterface&MockInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        $request->shouldReceive('getParsedBody')->andReturn($requestBody);

        $validationErrors = [
            'action_type' => ['Invalid action type'],
            'user_id' => ['User ID must be a number'],
        ];

        $validationResult = ValidationResult::failure($validationErrors);
        $this->validator->shouldReceive('validate')->andReturn($validationResult);

        // Act
        $result = $this->controller->store($request, $response);

        // Assert
        $responseData = json_decode($result, true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals(422, $responseData['error_code']);
        $this->assertArrayHasKey('errors', $responseData);
    }

    #[Test]
    public function it_handles_service_exceptions(): void
    {
        // Arrange
        $requestBody = [
            'action_type' => 'auth.login.success',
            'user_id' => 1,
        ];

        /** @var ServerRequestInterface&MockInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);
        /** @var ResponseInterface&MockInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        $request->shouldReceive('getParsedBody')->andReturn($requestBody);

        $validationResult = ValidationResult::success($requestBody);
        $this->validator->shouldReceive('validate')->andReturn($validationResult);

        $this->loggingService->shouldReceive('log')->andThrow(new RuntimeException('Database connection failed'));

        // Act
        $result = $this->controller->store($request, $response);

        // Assert
        $responseData = json_decode($result, true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals(500, $responseData['error_code']);
    }
}
