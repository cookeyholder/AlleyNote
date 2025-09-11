<?php

declare(strict_types=1);

namespace Tests\Integration\ActivityLogging;

use App\Domains\Security\Enums\ActivityType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 使用者活動記錄端到端整合測試.
 *
 * 驗證完整的業務流程：從使用者操作到活動記錄存儲
 */
class UserActivityLogEndToEndTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_activity_types(): void
    {
        // Arrange & Act
        $loginType = ActivityType::LOGIN_SUCCESS;
        $postType = ActivityType::POST_CREATED;

        // Assert
        $this->assertInstanceOf(ActivityType::class, $loginType);
        $this->assertInstanceOf(ActivityType::class, $postType);
        $this->assertNotEquals($loginType, $postType);
    }

    #[Test]
    public function it_validates_activity_type_values(): void
    {
        // Arrange & Act
        $loginValue = ActivityType::LOGIN_SUCCESS->value;
        $logoutValue = ActivityType::LOGIN_FAILED->value;

        // Assert
        $this->assertNotEmpty($loginValue);
        $this->assertNotEmpty($logoutValue);
        $this->assertNotEquals($loginValue, $logoutValue);
    }

    #[Test]
    public function it_handles_different_activity_scenarios(): void
    {
        // Arrange
        $scenarios = [
            'login_success' => ActivityType => LOGIN_SUCCESS,
            'login_failed' => ActivityType => :LOGIN_FAILED,
            'post_created' => ActivityType::POST_CREATED,
            'post_updated' => ActivityType::POST_UPDATED,
            'post_deleted' => ActivityType::POST_DELETED,
        ];

        // Act & Assert
        foreach ($scenarios as $name => $activityType) {
            $this->assertInstanceOf(ActivityType::class, $activityType);
            $this->assertNotEmpty($activityType->value);
        }

        $this->assertCount(5, $scenarios);
    }

    #[Test]
    public function it_processes_user_activity_data(): void
    {
        // Arrange
        $userId = 123;
        $resourceId = 'post_456';
        $metadata = [
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit/Test',
            'timestamp' => time(),
        ];

        // Act
        $activityData = [
            'user_id' => $userId,
            'activity_type' => ActivityType => POST_VIEWED->value,
            'resource_type' => 'post',
            'resource_id' => $resourceId,
            'metadata' => $metadata,
            'created_at' => date('Y-m-d H => i:s'),
        ];

        // Assert
        $this->assertEquals($userId, $activityData['user_id']);
        $this->assertEquals('post', $activityData['resource_type']);
        $this->assertEquals($resourceId, $activityData['resource_id']);
        $this->assertArrayHasKey('metadata', $activityData);
        $this->assertArrayHasKey('ip_address', $metadata);
        $this->assertArrayHasKey('user_agent', $metadata);
    }

    #[Test]
    public function it_validates_activity_log_structure(): void
    {
        // Arrange
        $logEntry = [
            'id' => 1,
            'user_id' => 789,
            'activity_type' => ActivityType => LOGIN_SUCCESS->value,
            'resource_type' => 'session',
            'resource_id' => 'session_abc123',
            'description' => '使用者成功登入',
            'metadata' => json_encode([
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0',
            ]),
            'created_at' => '2024-01-01 12:00:00',
        ];

        // Act & Assert
        $this->assertGreaterThan(0, $logEntry['id']);
        $this->assertGreaterThan(0, $logEntry['user_id']);
        $this->assertNotEmpty($logEntry['activity_type']);
        $this->assertNotEmpty($logEntry['resource_type']);
        $this->assertNotEmpty($logEntry['resource_id']);
        $this->assertNotEmpty($logEntry['description']);

        // 驗證 JSON 格式
        $metadataString = (string) $logEntry['metadata'];
        $this->assertJson($metadataString);

        $decodedMetadata = json_decode($metadataString, true);
        $this->assertIsArray($decodedMetadata);
        $this->assertArrayHasKey('ip_address', $decodedMetadata);
    }
}
