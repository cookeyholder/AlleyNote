<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * 使用者活動記錄測試資料 Seeder
 * 建立範例測試資料以供開發和測試使用.
 */
class UserActivityLogsSeeder extends AbstractSeed
{
    /**
     * 執行資料填充.
     */
    public function run(): void
    {
        // 清空現有資料
        $this->table('user_activity_logs')->truncate();

        // 產生範例活動記錄
        $activityLogs = $this->generateSampleActivityLogs();

        // 插入資料
        $this->table('user_activity_logs')->insert($activityLogs)->saveData();
    }

    /**
     * 產生範例活動記錄資料.
     */
    private function generateSampleActivityLogs(): array
    {
        $logs = [];
        $now = new DateTime();

        // 認證相關活動
        $logs = array_merge($logs, $this->generateAuthActivities($now));

        // 文章管理活動
        $logs = array_merge($logs, $this->generatePostActivities($now));

        // 附件管理活動
        $logs = array_merge($logs, $this->generateAttachmentActivities($now));

        // 安全事件
        $logs = array_merge($logs, $this->generateSecurityEvents($now));

        // 失敗操作範例
        $logs = array_merge($logs, $this->generateFailedActivities($now));

        return $logs;
    }

    /**
     * 產生認證相關活動記錄.
     */
    private function generateAuthActivities(DateTime $now): array
    {
        return [
            [
                'uuid' => $this->generateUuid(),
                'user_id' => 1,
                'session_id' => 'sess_' . uniqid(),
                'action_type' => 'auth.login.success',
                'action_category' => 'authentication',
                'target_type' => 'user',
                'target_id' => '1',
                'status' => 'success',
                'description' => '使用者成功登入系統',
                'metadata' => json_encode([
                    'login_method' => 'password',
                    'remember_me' => true,
                ]),
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'request_method' => 'POST',
                'request_path' => '/api/v1/auth/login',
                'created_at' => $now->format('Y-m-d H:i:s'),
                'occurred_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $this->generateUuid(),
                'user_id' => 1,
                'session_id' => 'sess_' . uniqid(),
                'action_type' => 'auth.password.changed',
                'action_category' => 'authentication',
                'target_type' => 'user',
                'target_id' => '1',
                'status' => 'success',
                'description' => '使用者變更密碼',
                'metadata' => json_encode([
                    'password_strength' => 'strong',
                    'forced_logout' => true,
                ]),
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'request_method' => 'PUT',
                'request_path' => '/api/v1/user/password',
                'created_at' => $now->sub(new DateInterval('PT1H'))->format('Y-m-d H:i:s'),
                'occurred_at' => $now->sub(new DateInterval('PT1H'))->format('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $this->generateUuid(),
                'user_id' => 1,
                'session_id' => 'sess_' . uniqid(),
                'action_type' => 'auth.logout',
                'action_category' => 'authentication',
                'target_type' => 'user',
                'target_id' => '1',
                'status' => 'success',
                'description' => '使用者登出系統',
                'metadata' => json_encode([
                    'logout_type' => 'manual',
                ]),
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'request_method' => 'POST',
                'request_path' => '/api/v1/auth/logout',
                'created_at' => $now->sub(new DateInterval('PT30M'))->format('Y-m-d H:i:s'),
                'occurred_at' => $now->sub(new DateInterval('PT30M'))->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * 產生文章管理活動記錄.
     */
    private function generatePostActivities(DateTime $now): array
    {
        return [
            [
                'uuid' => $this->generateUuid(),
                'user_id' => 1,
                'session_id' => 'sess_' . uniqid(),
                'action_type' => 'post.created',
                'action_category' => 'content',
                'target_type' => 'post',
                'target_id' => '1',
                'status' => 'success',
                'description' => '建立新文章',
                'metadata' => json_encode([
                    'title' => '系統維護公告',
                    'category' => 'announcement',
                    'is_pinned' => true,
                ]),
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'request_method' => 'POST',
                'request_path' => '/api/v1/posts',
                'created_at' => $now->sub(new DateInterval('PT2H'))->format('Y-m-d H:i:s'),
                'occurred_at' => $now->sub(new DateInterval('PT2H'))->format('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $this->generateUuid(),
                'user_id' => 2,
                'session_id' => 'sess_' . uniqid(),
                'action_type' => 'post.viewed',
                'action_category' => 'content',
                'target_type' => 'post',
                'target_id' => '1',
                'status' => 'success',
                'description' => '瀏覽文章',
                'metadata' => json_encode([
                    'view_duration' => 120,
                    'referrer' => 'direct',
                ]),
                'ip_address' => '192.168.1.101',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'request_method' => 'GET',
                'request_path' => '/api/v1/posts/1',
                'created_at' => $now->sub(new DateInterval('PT1H30M'))->format('Y-m-d H:i:s'),
                'occurred_at' => $now->sub(new DateInterval('PT1H30M'))->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * 產生附件管理活動記錄.
     */
    private function generateAttachmentActivities(DateTime $now): array
    {
        return [
            [
                'uuid' => $this->generateUuid(),
                'user_id' => 1,
                'session_id' => 'sess_' . uniqid(),
                'action_type' => 'attachment.uploaded',
                'action_category' => 'file_management',
                'target_type' => 'attachment',
                'target_id' => '1',
                'status' => 'success',
                'description' => '上傳附件檔案',
                'metadata' => json_encode([
                    'filename' => 'document.pdf',
                    'file_size' => 1024000,
                    'mime_type' => 'application/pdf',
                    'virus_scan_result' => 'clean',
                ]),
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'request_method' => 'POST',
                'request_path' => '/api/v1/attachments',
                'created_at' => $now->sub(new DateInterval('PT3H'))->format('Y-m-d H:i:s'),
                'occurred_at' => $now->sub(new DateInterval('PT3H'))->format('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $this->generateUuid(),
                'user_id' => 2,
                'session_id' => 'sess_' . uniqid(),
                'action_type' => 'attachment.downloaded',
                'action_category' => 'file_management',
                'target_type' => 'attachment',
                'target_id' => '1',
                'status' => 'success',
                'description' => '下載附件檔案',
                'metadata' => json_encode([
                    'filename' => 'document.pdf',
                    'download_method' => 'direct',
                ]),
                'ip_address' => '192.168.1.101',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'request_method' => 'GET',
                'request_path' => '/api/v1/attachments/1/download',
                'created_at' => $now->sub(new DateInterval('PT2H30M'))->format('Y-m-d H:i:s'),
                'occurred_at' => $now->sub(new DateInterval('PT2H30M'))->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * 產生安全事件記錄.
     */
    private function generateSecurityEvents(DateTime $now): array
    {
        return [
            [
                'uuid' => $this->generateUuid(),
                'user_id' => null,
                'session_id' => null,
                'action_type' => 'security.suspicious_login_attempt',
                'action_category' => 'security',
                'target_type' => 'user',
                'target_id' => '1',
                'status' => 'blocked',
                'description' => '可疑登入嘗試被阻擋',
                'metadata' => json_encode([
                    'reason' => 'multiple_failed_attempts',
                    'attempt_count' => 5,
                    'blocked_duration' => 1800,
                ]),
                'ip_address' => '203.0.113.1',
                'user_agent' => 'curl/7.68.0',
                'request_method' => 'POST',
                'request_path' => '/api/v1/auth/login',
                'created_at' => $now->sub(new DateInterval('PT4H'))->format('Y-m-d H:i:s'),
                'occurred_at' => $now->sub(new DateInterval('PT4H'))->format('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $this->generateUuid(),
                'user_id' => null,
                'session_id' => null,
                'action_type' => 'security.ip_blocked',
                'action_category' => 'security',
                'target_type' => 'ip',
                'target_id' => '203.0.113.1',
                'status' => 'success',
                'description' => 'IP 位址已被封鎖',
                'metadata' => json_encode([
                    'reason' => 'repeated_failed_login',
                    'block_duration' => 86400,
                    'auto_blocked' => true,
                ]),
                'ip_address' => '203.0.113.1',
                'user_agent' => null,
                'request_method' => null,
                'request_path' => null,
                'created_at' => $now->sub(new DateInterval('PT3H30M'))->format('Y-m-d H:i:s'),
                'occurred_at' => $now->sub(new DateInterval('PT3H30M'))->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * 產生失敗操作記錄.
     */
    private function generateFailedActivities(DateTime $now): array
    {
        return [
            [
                'uuid' => $this->generateUuid(),
                'user_id' => null,
                'session_id' => null,
                'action_type' => 'auth.login.failed',
                'action_category' => 'authentication',
                'target_type' => 'user',
                'target_id' => '1',
                'status' => 'failed',
                'description' => '登入失敗 - 密碼錯誤',
                'metadata' => json_encode([
                    'failure_reason' => 'invalid_password',
                    'attempt_count' => 1,
                ]),
                'ip_address' => '192.168.1.102',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'request_method' => 'POST',
                'request_path' => '/api/v1/auth/login',
                'created_at' => $now->sub(new DateInterval('PT5H'))->format('Y-m-d H:i:s'),
                'occurred_at' => $now->sub(new DateInterval('PT5H'))->format('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $this->generateUuid(),
                'user_id' => 2,
                'session_id' => 'sess_' . uniqid(),
                'action_type' => 'attachment.virus_detected',
                'action_category' => 'file_management',
                'target_type' => 'attachment',
                'target_id' => '2',
                'status' => 'error',
                'description' => '上傳檔案檢測到病毒',
                'metadata' => json_encode([
                    'filename' => 'suspicious_file.exe',
                    'virus_name' => 'Trojan.Generic',
                    'file_deleted' => true,
                ]),
                'ip_address' => '192.168.1.101',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'request_method' => 'POST',
                'request_path' => '/api/v1/attachments',
                'created_at' => $now->sub(new DateInterval('PT6H'))->format('Y-m-d H:i:s'),
                'occurred_at' => $now->sub(new DateInterval('PT6H'))->format('Y-m-d H:i:s'),
            ],
            [
                'uuid' => $this->generateUuid(),
                'user_id' => 3,
                'session_id' => 'sess_' . uniqid(),
                'action_type' => 'post.permission_denied',
                'action_category' => 'content',
                'target_type' => 'post',
                'target_id' => '1',
                'status' => 'failed',
                'description' => '權限不足，無法編輯文章',
                'metadata' => json_encode([
                    'required_permission' => 'post.edit',
                    'user_permissions' => ['post.read'],
                ]),
                'ip_address' => '192.168.1.103',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
                'request_method' => 'PUT',
                'request_path' => '/api/v1/posts/1',
                'created_at' => $now->sub(new DateInterval('PT7H'))->format('Y-m-d H:i:s'),
                'occurred_at' => $now->sub(new DateInterval('PT7H'))->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * 產生 UUID.
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
        );
    }
}
