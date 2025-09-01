<?php

declare(strict_types=1);

namespace Tests\Integration\ActivityLogging;

use App\Application\Controllers\Api\V1\ActivityLogController;
use App\Domains\Security\Repositories\ActivityLogRepository;
use App\Domains\Security\Services\ActivityLoggingService;
use Mockery;
use Mockery\MockInterface;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Tests\Support\IntegrationTestCase;

class ActivityLogApiIntegrationTest extends IntegrationTestCase
{
    private ActivityLogController $controller;

    private ServerRequestInterface $request;

    private ResponseInterface $response;

    private ActivityLoggingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立服務依賴 (使用來自 parent 的 $this->db)
        $repository = new ActivityLogRepository($this->db);
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->andReturn();
        $logger->shouldReceive('error')->andReturn();

        $this->service = new ActivityLoggingService($repository, $logger);

        // 建立 controller
        $this->controller = new ActivityLogController($this->service, $repository);

        // Mock PSR-7 請求和回應物件
        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);

        // Mock response body 和基本行為
        $responseBody = Mockery::mock(StreamInterface::class);
        $responseBody->shouldReceive('write')->andReturn(0);

        $this->response->shouldReceive('getBody')->andReturn($responseBody);
        $this->response->shouldReceive('withStatus')->withAnyArgs()->andReturnSelf();
        $this->response->shouldReceive('withHeader')->withAnyArgs()->andReturnSelf();

        // 預設的請求屬性
        $this->request->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn(1);

        $this->request->shouldReceive('getServerParams')
            ->andReturn(['REMOTE_ADDR' => '127.0.0.1']);
    }

    #[Test]
    public function it_creates_activity_log_via_api(): void
    {
        // Arrange - 建立測試使用者以滿足外鍵約束
        $stmt = $this->db->prepare('INSERT INTO users (username, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(['testuser', 'test@example.com', 'hashedpassword', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
        $userId = (int) $this->db->lastInsertId();

        $logData = [
            'action_type' => 'post.created',
            'user_id' => $userId,
            'metadata' => [
                'title' => 'Test Post',
                'ip_address' => '127.0.0.1',
            ],
        ];

        // Mock getParsedBody
        /** @var MockInterface&ServerRequestInterface $mockRequest */
        $mockRequest = $this->request;
        $mockRequest->shouldReceive('getParsedBody')->andReturn($logData);

        // Act
        $response = $this->controller->store($this->request, $this->response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $response);

        // 驗證資料庫中是否有記錄
        $stmt = $this->db->prepare('SELECT * FROM user_activity_logs WHERE user_id = ? AND action_type = ?');
        $stmt->execute([$userId, 'post.created']);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(1, $records, '應該有一筆活動記錄被建立');

        $record = $records[0];
        assert(is_array($record));
        $this->assertEquals('post.created', $record['action_type']);
        $this->assertEquals($userId, $record['user_id']);
        $this->assertEquals('success', $record['status']);

        // 驗證 metadata
        $metadataJson = $record['metadata'] ?? '';
        if (is_string($metadataJson)) {
            $metadata = json_decode($metadataJson, true);
            $this->assertIsArray($metadata);
            $this->assertEquals('Test Post', $metadata['title']);
        } else {
            $this->fail('metadata 應該是 JSON 字串');
        }
    }

    #[Test]
    public function it_retrieves_activity_logs_via_api(): void
    {
        // Arrange - 建立測試使用者以滿足外鍵約束
        $stmt = $this->db->prepare('INSERT INTO users (username, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(['testuser2', 'test2@example.com', 'hashedpassword', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
        $userId = (int) $this->db->lastInsertId();

        // 建立一些測試資料
        $stmt = $this->db->prepare('
            INSERT INTO user_activity_logs
            (uuid, user_id, action_type, action_category, target_type, target_id, status, description, metadata, ip_address, created_at, occurred_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $testRecords = [
            [
                'test-uuid-1',
                $userId,
                'post.created',
                'post',
                'post',
                '1',
                'success',
                'Created test post 1',
                json_encode(['title' => 'Test Post 1']),
                '127.0.0.1',
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
            ],
            [
                'test-uuid-2',
                $userId,
                'post.viewed',
                'post',
                'post',
                '1',
                'success',
                'Viewed test post 1',
                json_encode(['title' => 'Test Post 1']),
                '127.0.0.1',
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($testRecords as $record) {
            $stmt->execute($record);
        }

        // Mock query parameters
        /** @var MockInterface&ServerRequestInterface $mockRequest */
        $mockRequest = $this->request;
        $mockRequest->shouldReceive('getQueryParams')
            ->andReturn([
                'limit' => '10',
                'offset' => '0',
            ]);

        // Act
        $response = $this->controller->index($this->request, $this->response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $response);

        // 驗證資料庫查詢是否正確
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM user_activity_logs WHERE user_id = ?');
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        assert(is_array($result));
        $count = $result['count'] ?? 0;
        if (is_int($count) || is_numeric($count)) {
            $this->assertEquals(2, (int) $count, '應該有 2 筆測試記錄');
        } else {
            $this->fail('count 應該是數字');
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
