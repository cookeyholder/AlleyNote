<?php

declare(strict_types=1);

namespace Tests\Functional;

use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityType;
use App\Domains\Security\Repositories\ActivityLogRepository;
use App\Domains\Security\Services\ActivityLoggingService;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * 附件管理系統活動記錄功能測試.
 *
 * 測試所有附件相關的活動記錄功能
 */
class AttachmentActivityLoggingTest extends TestCase
{
    private PDO $pdo;

    private ActivityLoggingService $activityLogger;

    protected function setUp(): void
    {
        parent::setUp();

        // 使用測試資料庫
        $this->pdo = new PDO('sqlite:' . dirname(__DIR__, 1) . '/../database/alleynote.sqlite3');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 清除測試數據
        $this->pdo->exec("DELETE FROM user_activity_logs WHERE action_type LIKE 'attachment.%' AND user_id IS NULL");

        // 建立服務
        $repository = new ActivityLogRepository($this->pdo);
        $this->activityLogger = new ActivityLoggingService($repository, new NullLogger());

        // 設定記錄等級允許所有活動
        $this->activityLogger->setLogLevel(1);
    }

    #[Test]
    public function it_logs_attachment_upload_activity(): void
    {
        $postId = 123;
        $filename = 'test-upload.jpg';

        // 記錄附件上傳活動 (使用 null user_id 避免外鍵約束問題)
        $dto = CreateActivityLogDTO::success(
            actionType: ActivityType::ATTACHMENT_UPLOADED,
            userId: null,
            targetType: 'post',
            targetId: (string) $postId,
            metadata: [
                'post_id' => $postId,
                'filename' => $filename,
                'file_size' => 2048,
                'mime_type' => 'image/jpeg',
            ],
        );

        $result = $this->activityLogger->log($dto);
        $this->assertTrue($result, 'ActivityLogger::log 應該成功');

        // 驗證記錄
        $logs = $this->pdo->query("
            SELECT * FROM user_activity_logs 
            WHERE action_type = 'attachment.uploaded' 
            AND user_id IS NULL
            ORDER BY created_at DESC 
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($logs, '應該要記錄附件上傳活動');
        $this->assertEquals('attachment.uploaded', (is_array($logs) && isset((is_array($logs) ? $logs['action_type'] : (is_object($logs) ? $logs->action_type : null)))) ? (is_array($logs) ? $logs['action_type'] : (is_object($logs) ? $logs->action_type : null)) : null);
        $this->assertEquals('success', (is_array($logs) && isset((is_array($logs) ? $logs['status'] : (is_object($logs) ? $logs->status : null)))) ? (is_array($logs) ? $logs['status'] : (is_object($logs) ? $logs->status : null)) : null);
        $this->assertNull((is_array($logs) && isset((is_array($logs) ? $logs['user_id'] : (is_object($logs) ? $logs->user_id : null)))) ? (is_array($logs) ? $logs['user_id'] : (is_object($logs) ? $logs->user_id : null)) : null);

        $metadata = json_decode((is_array($logs) && isset((is_array($logs) ? $logs['metadata'] : (is_object($logs) ? $logs->metadata : null)))) ? (is_array($logs) ? $logs['metadata'] : (is_object($logs) ? $logs->metadata : null)) : null, true);
        $this->assertIsArray($metadata);
        $this->assertEquals($postId, (is_array($metadata) && isset((is_array($metadata) ? $metadata['post_id'] : (is_object($metadata) ? $metadata->post_id : null)))) ? (is_array($metadata) ? $metadata['post_id'] : (is_object($metadata) ? $metadata->post_id : null)) : null);
        $this->assertEquals($filename, (is_array($metadata) && isset((is_array($metadata) ? $metadata['filename'] : (is_object($metadata) ? $metadata->filename : null)))) ? (is_array($metadata) ? $metadata['filename'] : (is_object($metadata) ? $metadata->filename : null)) : null);
    }

    #[Test]
    public function it_logs_attachment_download_activity(): void
    {
        $attachmentUuid = 'test-attachment-uuid-12345';
        $filename = 'downloaded-file.pdf';

        // 記錄附件下載活動
        $dto = CreateActivityLogDTO::success(
            actionType: ActivityType::ATTACHMENT_DOWNLOADED,
            userId: null,
            targetType: 'attachment',
            targetId: $attachmentUuid,
            metadata: [
                'attachment_uuid' => $attachmentUuid,
                'filename' => $filename,
                'file_size' => 1024000,
            ],
        );

        $result = $this->activityLogger->log($dto);
        $this->assertTrue($result, 'ActivityLogger::log 應該成功');

        // 驗證記錄
        $logs = $this->pdo->query("
            SELECT * FROM user_activity_logs 
            WHERE action_type = 'attachment.downloaded' 
            AND user_id IS NULL
            ORDER BY created_at DESC 
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($logs, '應該要記錄附件下載活動');
        $this->assertEquals('attachment.downloaded', (is_array($logs) && isset((is_array($logs) ? $logs['action_type'] : (is_object($logs) ? $logs->action_type : null)))) ? (is_array($logs) ? $logs['action_type'] : (is_object($logs) ? $logs->action_type : null)) : null);
        $this->assertNull((is_array($logs) && isset((is_array($logs) ? $logs['user_id'] : (is_object($logs) ? $logs->user_id : null)))) ? (is_array($logs) ? $logs['user_id'] : (is_object($logs) ? $logs->user_id : null)) : null);

        $metadata = json_decode((is_array($logs) && isset((is_array($logs) ? $logs['metadata'] : (is_object($logs) ? $logs->metadata : null)))) ? (is_array($logs) ? $logs['metadata'] : (is_object($logs) ? $logs->metadata : null)) : null, true);
        $this->assertIsArray($metadata);
        $this->assertEquals($attachmentUuid, (is_array($metadata) && isset((is_array($metadata) ? $metadata['attachment_uuid'] : (is_object($metadata) ? $metadata->attachment_uuid : null)))) ? (is_array($metadata) ? $metadata['attachment_uuid'] : (is_object($metadata) ? $metadata->attachment_uuid : null)) : null);
        $this->assertEquals($filename, (is_array($metadata) && isset((is_array($metadata) ? $metadata['filename'] : (is_object($metadata) ? $metadata->filename : null)))) ? (is_array($metadata) ? $metadata['filename'] : (is_object($metadata) ? $metadata->filename : null)) : null);
    }

    #[Test]
    public function it_logs_attachment_delete_activity(): void
    {
        $attachmentUuid = 'test-delete-uuid-12345';
        $filename = 'deleted-file.doc';

        // 記錄附件刪除活動
        $dto = CreateActivityLogDTO::success(
            actionType: ActivityType::ATTACHMENT_DELETED,
            userId: null,
            targetType: 'attachment',
            targetId: $attachmentUuid,
            metadata: [
                'attachment_uuid' => $attachmentUuid,
                'filename' => $filename,
                'post_id' => 456,
            ],
        );

        $result = $this->activityLogger->log($dto);
        $this->assertTrue($result, 'ActivityLogger::log 應該成功');

        // 驗證記錄
        $logs = $this->pdo->query("
            SELECT * FROM user_activity_logs 
            WHERE action_type = 'attachment.deleted' 
            AND user_id IS NULL
            ORDER BY created_at DESC 
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($logs, '應該要記錄附件刪除活動');
        $this->assertEquals('attachment.deleted', (is_array($logs) && isset((is_array($logs) ? $logs['action_type'] : (is_object($logs) ? $logs->action_type : null)))) ? (is_array($logs) ? $logs['action_type'] : (is_object($logs) ? $logs->action_type : null)) : null);
        $this->assertNull((is_array($logs) && isset((is_array($logs) ? $logs['user_id'] : (is_object($logs) ? $logs->user_id : null)))) ? (is_array($logs) ? $logs['user_id'] : (is_object($logs) ? $logs->user_id : null)) : null);

        $metadata = json_decode((is_array($logs) && isset((is_array($logs) ? $logs['metadata'] : (is_object($logs) ? $logs->metadata : null)))) ? (is_array($logs) ? $logs['metadata'] : (is_object($logs) ? $logs->metadata : null)) : null, true);
        $this->assertIsArray($metadata);
        $this->assertEquals($attachmentUuid, (is_array($metadata) && isset((is_array($metadata) ? $metadata['attachment_uuid'] : (is_object($metadata) ? $metadata->attachment_uuid : null)))) ? (is_array($metadata) ? $metadata['attachment_uuid'] : (is_object($metadata) ? $metadata->attachment_uuid : null)) : null);
        $this->assertEquals($filename, (is_array($metadata) && isset((is_array($metadata) ? $metadata['filename'] : (is_object($metadata) ? $metadata->filename : null)))) ? (is_array($metadata) ? $metadata['filename'] : (is_object($metadata) ? $metadata->filename : null)) : null);
    }

    #[Test]
    public function it_logs_attachment_permission_denied_activity(): void
    {
        $postId = 789;
        $filename = 'denied-file.jpg';

        // 記錄附件權限被拒絕活動
        $dto = CreateActivityLogDTO::failure(
            actionType: ActivityType::ATTACHMENT_PERMISSION_DENIED,
            userId: null,
            targetType: 'post',
            targetId: (string) $postId,
            metadata: [
                'post_id' => $postId,
                'filename' => $filename,
                'file_size' => 512000,
                'mime_type' => 'image/jpeg',
                'reason' => 'no_permission_to_post',
            ],
        );

        $result = $this->activityLogger->log($dto);
        $this->assertTrue($result, 'ActivityLogger::log 應該成功');

        // 驗證記錄
        $logs = $this->pdo->query("
            SELECT * FROM user_activity_logs 
            WHERE action_type = 'attachment.permission_denied' 
            AND user_id IS NULL
            ORDER BY created_at DESC 
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($logs, '應該要記錄附件權限被拒絕活動');
        $this->assertEquals('attachment.permission_denied', (is_array($logs) && isset((is_array($logs) ? $logs['action_type'] : (is_object($logs) ? $logs->action_type : null)))) ? (is_array($logs) ? $logs['action_type'] : (is_object($logs) ? $logs->action_type : null)) : null);
        $this->assertEquals('failed', (is_array($logs) && isset((is_array($logs) ? $logs['status'] : (is_object($logs) ? $logs->status : null)))) ? (is_array($logs) ? $logs['status'] : (is_object($logs) ? $logs->status : null)) : null);
        $this->assertNull((is_array($logs) && isset((is_array($logs) ? $logs['user_id'] : (is_object($logs) ? $logs->user_id : null)))) ? (is_array($logs) ? $logs['user_id'] : (is_object($logs) ? $logs->user_id : null)) : null);

        $metadata = json_decode((is_array($logs) && isset((is_array($logs) ? $logs['metadata'] : (is_object($logs) ? $logs->metadata : null)))) ? (is_array($logs) ? $logs['metadata'] : (is_object($logs) ? $logs->metadata : null)) : null, true);
        $this->assertIsArray($metadata);
        $this->assertEquals($postId, (is_array($metadata) && isset((is_array($metadata) ? $metadata['post_id'] : (is_object($metadata) ? $metadata->post_id : null)))) ? (is_array($metadata) ? $metadata['post_id'] : (is_object($metadata) ? $metadata->post_id : null)) : null);
        $this->assertEquals($filename, (is_array($metadata) && isset((is_array($metadata) ? $metadata['filename'] : (is_object($metadata) ? $metadata->filename : null)))) ? (is_array($metadata) ? $metadata['filename'] : (is_object($metadata) ? $metadata->filename : null)) : null);
    }

    #[Test]
    public function it_logs_attachment_size_exceeded_activity(): void
    {
        $filename = 'huge-file.zip';
        $fileSize = 100 * 1024 * 1024; // 100MB

        // 記錄檔案大小超限活動
        $dto = CreateActivityLogDTO::failure(
            actionType: ActivityType::ATTACHMENT_SIZE_EXCEEDED,
            userId: null,
            targetType: 'file',
            targetId: $filename,
            metadata: [
                'filename' => $filename,
                'file_size' => $fileSize,
                'mime_type' => 'application/zip',
                'error' => 'file_size_exceeded',
                'max_size' => 10 * 1024 * 1024, // 10MB limit
            ],
        );

        $result = $this->activityLogger->log($dto);
        $this->assertTrue($result, 'ActivityLogger::log 應該成功');

        // 驗證記錄
        $logs = $this->pdo->query("
            SELECT * FROM user_activity_logs 
            WHERE action_type = 'attachment.size_exceeded' 
            AND user_id IS NULL
            ORDER BY created_at DESC 
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($logs, '應該要記錄檔案大小超限活動');
        $this->assertEquals('attachment.size_exceeded', (is_array($logs) && isset((is_array($logs) ? $logs['action_type'] : (is_object($logs) ? $logs->action_type : null)))) ? (is_array($logs) ? $logs['action_type'] : (is_object($logs) ? $logs->action_type : null)) : null);
        $this->assertEquals('failed', (is_array($logs) && isset((is_array($logs) ? $logs['status'] : (is_object($logs) ? $logs->status : null)))) ? (is_array($logs) ? $logs['status'] : (is_object($logs) ? $logs->status : null)) : null);
        $this->assertNull((is_array($logs) && isset((is_array($logs) ? $logs['user_id'] : (is_object($logs) ? $logs->user_id : null)))) ? (is_array($logs) ? $logs['user_id'] : (is_object($logs) ? $logs->user_id : null)) : null);

        $metadata = json_decode((is_array($logs) && isset((is_array($logs) ? $logs['metadata'] : (is_object($logs) ? $logs->metadata : null)))) ? (is_array($logs) ? $logs['metadata'] : (is_object($logs) ? $logs->metadata : null)) : null, true);
        $this->assertIsArray($metadata);
        $this->assertEquals($filename, (is_array($metadata) && isset((is_array($metadata) ? $metadata['filename'] : (is_object($metadata) ? $metadata->filename : null)))) ? (is_array($metadata) ? $metadata['filename'] : (is_object($metadata) ? $metadata->filename : null)) : null);
        $this->assertEquals($fileSize, (is_array($metadata) && isset((is_array($metadata) ? $metadata['file_size'] : (is_object($metadata) ? $metadata->file_size : null)))) ? (is_array($metadata) ? $metadata['file_size'] : (is_object($metadata) ? $metadata->file_size : null)) : null);
    }

    #[Test]
    public function it_logs_attachment_virus_detected_activity(): void
    {
        $filename = 'suspicious-file.exe';

        // 記錄病毒檢測活動
        $dto = CreateActivityLogDTO::failure(
            actionType: ActivityType::ATTACHMENT_VIRUS_DETECTED,
            userId: null,
            targetType: 'file',
            targetId: $filename,
            metadata: [
                'filename' => $filename,
                'file_size' => 1024,
                'mime_type' => 'application/octet-stream',
                'virus_name' => 'Test.Virus',
                'scanner' => 'ClamAV',
            ],
        );

        $result = $this->activityLogger->log($dto);
        $this->assertTrue($result, 'ActivityLogger::log 應該成功');

        // 驗證記錄
        $logs = $this->pdo->query("
            SELECT * FROM user_activity_logs 
            WHERE action_type = 'attachment.virus_detected' 
            AND user_id IS NULL
            ORDER BY created_at DESC 
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($logs, '應該要記錄病毒檢測活動');
        $this->assertEquals('attachment.virus_detected', (is_array($logs) && isset((is_array($logs) ? $logs['action_type'] : (is_object($logs) ? $logs->action_type : null)))) ? (is_array($logs) ? $logs['action_type'] : (is_object($logs) ? $logs->action_type : null)) : null);
        $this->assertEquals('failed', (is_array($logs) && isset((is_array($logs) ? $logs['status'] : (is_object($logs) ? $logs->status : null)))) ? (is_array($logs) ? $logs['status'] : (is_object($logs) ? $logs->status : null)) : null);
        $this->assertNull((is_array($logs) && isset((is_array($logs) ? $logs['user_id'] : (is_object($logs) ? $logs->user_id : null)))) ? (is_array($logs) ? $logs['user_id'] : (is_object($logs) ? $logs->user_id : null)) : null);

        $metadata = json_decode((is_array($logs) && isset((is_array($logs) ? $logs['metadata'] : (is_object($logs) ? $logs->metadata : null)))) ? (is_array($logs) ? $logs['metadata'] : (is_object($logs) ? $logs->metadata : null)) : null, true);
        $this->assertIsArray($metadata);
        $this->assertEquals($filename, (is_array($metadata) && isset((is_array($metadata) ? $metadata['filename'] : (is_object($metadata) ? $metadata->filename : null)))) ? (is_array($metadata) ? $metadata['filename'] : (is_object($metadata) ? $metadata->filename : null)) : null);
        $this->assertEquals('Test.Virus', (is_array($metadata) && isset((is_array($metadata) ? $metadata['virus_name'] : (is_object($metadata) ? $metadata->virus_name : null)))) ? (is_array($metadata) ? $metadata['virus_name'] : (is_object($metadata) ? $metadata->virus_name : null)) : null);
    }

    protected function tearDown(): void
    {
        // 清除測試數據
        if (isset($this->pdo)) {
            $this->pdo->exec("DELETE FROM user_activity_logs WHERE action_type LIKE 'attachment.%' AND user_id IS NULL");
        }
        parent::tearDown();
    }
}
