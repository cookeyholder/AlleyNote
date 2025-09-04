<?php

declare(strict_types=1);

namespace Tests\Integration\ActivityLogging;

use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityType;
use App\Domains\Security\Repositories\ActivityLogRepository;
use App\Domains\Security\Services\ActivityLoggingService;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Tests\Support\IntegrationTestCase;

/**
 * 使用者活動記錄端到端整合測試.
 *
 * 驗證完整的業務流程：從使用者操作到活動記錄存儲
 */
class UserActivityLogEndToEndTest extends IntegrationTestCase
{
    private ActivityLoggingServiceInterface $activityLoggingService;

    private ActivityLogRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立 Mock Logger
        $logger = $this->createMock(LoggerInterface::class);

        // 使用測試資料庫建立真實的服務實例
        $this->repository = new ActivityLogRepository($this->db);
        $this->activityLoggingService = new ActivityLoggingService($this->repository, $logger);
    }

    #[Test]
    public function it_records_complete_forum_participation_flow(): void
    {
        // Arrange - 先建立測試使用者
        $userId = $this->insertTestUser([
            'username' => 'forum_user',
            'email' => 'forum@example.com',
        ]);

        $topicId = 456;
        $topicData = [
            'title' => '討論主題',
            'category' => 'general',
        ];

        // Act 1: 記錄使用者註冊事件
        $registrationResult = $this->activityLoggingService->logSuccess(
            ActivityType::USER_REGISTERED,
            $userId,
            'user',
            (string) $userId,
            [
                'email' => 'forum@example.com',
                'username' => 'forum_user',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit/Test',
            ],
        );

        // Act 2: 記錄登入事件
        $loginResult = $this->activityLoggingService->logSuccess(
            ActivityType::LOGIN_SUCCESS,
            $userId,
            'user',
            (string) $userId,
            [
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit/Test',
            ],
        );

        // Act 3: 記錄討論主題建立事件
        $topicCreateResult = $this->activityLoggingService->logSuccess(
            ActivityType::POST_CREATED,
            $userId,
            'topic',
            (string) $topicId,
            $topicData,
        );

        // Assert
        $this->assertTrue($registrationResult, '使用者註冊事件應該成功記錄');
        $this->assertTrue($loginResult, '登入事件應該成功記錄');
        $this->assertTrue($topicCreateResult, '討論主題建立事件應該成功記錄');
    }

    #[Test]
    public function it_records_complete_post_creation_and_management_flow(): void
    {
        // Arrange - 先建立測試使用者
        $userId = $this->insertTestUser([
            'username' => 'post_author',
            'email' => 'author@example.com',
        ]);

        $postId = 123;
        $postData = [
            'title' => '整合測試文章',
            'content' => '這是一個端到端整合測試建立的文章內容',
            'status' => 'draft',
        ];

        // Act 1: 記錄文章建立事件
        $createResult = $this->activityLoggingService->logSuccess(
            ActivityType::POST_CREATED,
            $userId,
            'post',
            (string) $postId,
            [
                'title' => $postData['title'],
                'status' => $postData['status'],
                'content_length' => strlen($postData['content']),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit/Test',
            ],
        );

        // Act 2: 記錄文章發布事件
        $publishResult = $this->activityLoggingService->logSuccess(
            ActivityType::POST_PUBLISHED,
            $userId,
            'post',
            (string) $postId,
            [
                'title' => $postData['title'],
                'previous_status' => 'draft',
                'new_status' => 'published',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit/Test',
            ],
        );

        // Act 3: 記錄文章置頂事件
        $pinResult = $this->activityLoggingService->logSuccess(
            ActivityType::POST_PINNED,
            $userId,
            'post',
            (string) $postId,
            [
                'title' => $postData['title'],
                'pinned' => true,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit/Test',
            ],
        );

        // Act 4: 記錄文章查看事件
        $viewResult = $this->activityLoggingService->logSuccess(
            ActivityType::POST_VIEWED,
            $userId,
            'post',
            (string) $postId,
            [
                'title' => $postData['title'],
                'viewer_id' => $userId,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit/Test',
            ],
        );

        // Act 5: 記錄文章刪除事件
        $deleteResult = $this->activityLoggingService->logSuccess(
            ActivityType::POST_DELETED,
            $userId,
            'post',
            (string) $postId,
            [
                'title' => $postData['title'],
                'reason' => 'user_requested',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit/Test',
            ],
        );

        // Assert
        $this->assertTrue($createResult, '文章建立事件應該成功記錄');
        $this->assertTrue($publishResult, '文章發布事件應該成功記錄');
        $this->assertTrue($pinResult, '文章置頂事件應該成功記錄');
        $this->assertTrue($viewResult, '文章查看事件應該成功記錄');
        $this->assertTrue($deleteResult, '文章刪除事件應該成功記錄');
    }

    #[Test]
    public function it_records_complete_security_incident_flow(): void
    {
        // Arrange - 先建立測試使用者
        $userId = $this->insertTestUser([
            'username' => 'security_user',
            'email' => 'security@example.com',
        ]);

        $suspiciousIp = '192.168.1.100';

        // Act 1: 記錄多次登入失敗
        $failedLogins = [];
        for ($i = 1; $i <= 5; $i++) {
            $failedLogins[] = $this->activityLoggingService->logFailure(
                ActivityType::LOGIN_FAILED,
                $userId,
                'Invalid password attempt ' . $i,
                [
                    'ip_address' => $suspiciousIp,
                    'user_agent' => 'SuspiciousBot/1.0',
                    'attempt_number' => $i,
                    'email' => 'test@example.com',
                ],
            );
        }

        // Act 2: 記錄 IP 封鎖事件
        $ipBlockResult = $this->activityLoggingService->logSecurityEvent(
            ActivityType::IP_BLOCKED,
            'IP blocked due to too many failed login attempts',
            [
                'ip_address' => $suspiciousIp,
                'reason' => 'too_many_failed_login_attempts',
                'failed_attempts' => 5,
                'block_duration' => '1 hour',
                'triggered_by_user_id' => $userId,
            ],
        );

        // Act 3: 記錄 CSRF 攻擊攔截
        $csrfBlockResult = $this->activityLoggingService->logSecurityEvent(
            ActivityType::CSRF_ATTACK_BLOCKED,
            'CSRF attack blocked - invalid token',
            [
                'ip_address' => $suspiciousIp,
                'user_agent' => 'SuspiciousBot/1.0',
                'requested_path' => '/api/v1/posts',
                'method' => 'POST',
                'reason' => 'invalid_csrf_token',
            ],
        );

        // Act 4: 記錄 XSS 攻擊攔截
        $xssBlockResult = $this->activityLoggingService->logSecurityEvent(
            ActivityType::XSS_ATTACK_BLOCKED,
            'XSS attack blocked - malicious content detected',
            [
                'ip_address' => $suspiciousIp,
                'user_agent' => 'SuspiciousBot/1.0',
                'malicious_content' => '<script>alert("xss")</script>',
                'sanitized_content' => 'alert("xss")',
                'field_name' => 'post_content',
            ],
        );

        // Assert
        foreach ($failedLogins as $result) {
            $this->assertTrue($result, '登入失敗事件應該成功記錄');
        }
        $this->assertTrue($ipBlockResult, 'IP 封鎖事件應該成功記錄');
        $this->assertTrue($csrfBlockResult, 'CSRF 攻擊攔截事件應該成功記錄');
        $this->assertTrue($xssBlockResult, 'XSS 攻擊攔截事件應該成功記錄');
    }

    #[Test]
    public function it_records_batch_operations_flow(): void
    {
        // Arrange - 先建立測試使用者
        $userId = $this->insertTestUser([
            'username' => 'batch_user',
            'email' => 'batch@example.com',
        ]);

        // 建立 DTO 物件陣列
        $batchOperations = [
            CreateActivityLogDTO::success(
                ActivityType::POST_CREATED,
                $userId,
                'post',
                '1',
                '建立文章 1',
                [
                    'title' => '批次操作文章 1',
                    'batch_id' => 'batch_001',
                    'operation_sequence' => 1,
                ],
            ),
            CreateActivityLogDTO::success(
                ActivityType::POST_CREATED,
                $userId,
                'post',
                '2',
                '建立文章 2',
                [
                    'title' => '批次操作文章 2',
                    'batch_id' => 'batch_001',
                    'operation_sequence' => 2,
                ],
            ),
            CreateActivityLogDTO::success(
                ActivityType::POST_PUBLISHED,
                $userId,
                'post',
                '1',
                '發布文章 1',
                [
                    'title' => '批次操作文章 1',
                    'batch_id' => 'batch_001',
                    'operation_sequence' => 3,
                ],
            ),
            CreateActivityLogDTO::success(
                ActivityType::POST_PUBLISHED,
                $userId,
                'post',
                '2',
                '發布文章 2',
                [
                    'title' => '批次操作文章 2',
                    'batch_id' => 'batch_001',
                    'operation_sequence' => 4,
                ],
            ),
        ];

        // Act
        $batchResult = $this->activityLoggingService->logBatch($batchOperations);

        // Assert
        $this->assertGreaterThan(0, $batchResult, '批次操作應該成功記錄至少一個項目');
        $this->assertEquals(4, $batchResult, '應該成功記錄所有 4 個批次操作');
    }

    #[Test]
    public function it_handles_concurrent_logging_operations(): void
    {
        // Arrange - 先建立測試使用者
        $userId = $this->insertTestUser([
            'username' => 'concurrent_user',
            'email' => 'concurrent@example.com',
        ]);

        $concurrentOperations = [];

        // Act: 模擬同時進行多個操作
        for ($i = 1; $i <= 10; $i++) {
            $concurrentOperations[] = $this->activityLoggingService->logSuccess(
                ActivityType::POST_VIEWED,
                $userId,
                'post',
                (string) $i,
                [
                    'title' => "併發測試文章 {$i}",
                    'concurrent_operation' => true,
                    'operation_id' => $i,
                    'timestamp' => time(),
                ],
            );
        }

        // Assert
        foreach ($concurrentOperations as $i => $result) {
            $this->assertTrue($result, "併發操作 {$i} 應該成功記錄");
        }
    }

    #[Test]
    public function it_maintains_data_consistency_across_operations(): void
    {
        // Arrange - 建立測試使用者
        $userId = $this->insertTestUser([
            'username' => 'consistency_user',
            'email' => 'consistency@example.com',
        ]);

        $viewerUserId = $this->insertTestUser([
            'username' => 'viewer_user',
            'email' => 'viewer@example.com',
        ]);

        $postId = 999;

        // Act 1: 記錄一系列相關操作
        $operations = [
            $this->activityLoggingService->logSuccess(
                ActivityType::POST_CREATED,
                $userId,
                'post',
                (string) $postId,
                ['title' => '數據一致性測試文章', 'status' => 'draft'],
            ),
            $this->activityLoggingService->logSuccess(
                ActivityType::POST_UPDATED,
                $userId,
                'post',
                (string) $postId,
                ['title' => '數據一致性測試文章', 'status' => 'published'],
            ),
            $this->activityLoggingService->logSuccess(
                ActivityType::POST_VIEWED,
                $viewerUserId,
                'post',
                (string) $postId,
                ['title' => '數據一致性測試文章', 'viewer_type' => 'other_user'],
            ),
            $this->activityLoggingService->logSuccess(
                ActivityType::POST_DELETED,
                $userId,
                'post',
                (string) $postId,
                ['title' => '數據一致性測試文章', 'reason' => 'test_cleanup'],
            ),
        ];

        // Assert
        foreach ($operations as $i => $result) {
            $this->assertTrue($result, '操作 ' . ($i + 1) . ' 應該成功記錄，維持數據一致性');
        }
    }
}
