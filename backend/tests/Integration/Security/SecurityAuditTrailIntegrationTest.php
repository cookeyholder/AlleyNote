<?php

declare(strict_types=1);

namespace Tests\Integration\Security;

use App\Domains\Security\Repositories\ActivityLogRepository;
use App\Domains\Security\Services\ActivityLoggingService;
use App\Domains\Security\Services\SuspiciousActivityDetector;
use JsonException;
use Mockery;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Tests\Support\AuthApiIntegrationTestCase;

/**
 * 安全審計日誌整合測試.
 *
 * 以完整應用程式堆疊驗證安全事件寫入 user_activity_logs 且可透過
 * ActivityLogController 的 API 端點查詢：
 * - 登入成功、登入失敗、登出與密碼變更皆留下審計軌跡
 * - 管理端點（activity-logs 系列）可查詢記錄並統計登入失敗
 * - 同一 IP 連續登入失敗會被 SuspiciousActivityDetector 判定為可疑
 */
#[Group('integration')]
#[Group('api')]
#[Group('security')]
#[Group('activity-log')]
final class SecurityAuditTrailIntegrationTest extends AuthApiIntegrationTestCase
{
    private const ADMIN_EMAIL = 'audit-admin@example.com';

    private const USER_EMAIL = 'audit-user@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        // 測試用 users 表僅有 password 欄位，此處補上生產 schema 的 password_hash，
        // 使密碼變更流程（UserRepository 寫入 password_hash）得以執行
        $this->db->exec('ALTER TABLE users ADD COLUMN password_hash TEXT');
        $this->createAuthUser('auditadmin', self::ADMIN_EMAIL, ['admin']);
        $this->createAuthUser('audituser', self::USER_EMAIL, ['user']);
    }

    /**
     * 測試登入成功會寫入審計日誌（含使用者、IP 與 metadata）.
     */
    public function testLoginSuccessWritesAuditLog(): void
    {
        $ip = '203.0.113.21';

        $loginData = $this->loginUser(self::USER_EMAIL, $ip);
        $userId = $this->userIdOf($loginData);
        $this->assertGreaterThan(0, $userId);

        $row = $this->fetchLatestActivityLog('login_success');
        $this->assertNotNull($row, '登入成功後應存在 login_success 活動記錄');
        $this->assertSame($userId, $this->logUserId($row));
        $this->assertSame('success', $this->getStringValue($row, 'status'));
        $this->assertSame($ip, $this->getStringValue($row, 'ip_address'));

        $metadata = $this->decodeMetadata($row);
        $this->assertSame(self::USER_EMAIL, $this->getStringValue($metadata, 'email'));
    }

    /**
     * 測試登入失敗會以 failed 狀態寫入審計日誌.
     */
    public function testLoginFailureWritesFailedAuditLog(): void
    {
        $ip = '203.0.113.22';

        $response = $this->request('POST', '/api/auth/login', [
            'email'    => self::USER_EMAIL,
            'password' => 'WrongPassword456!',
        ], ip: $ip);

        $this->assertGreaterThanOrEqual(
            400,
            $response->getStatusCode(),
            '錯誤密碼不應登入成功：' . $response->getBody(),
        );

        $row = $this->fetchLatestActivityLog('login_failed');
        $this->assertNotNull($row, '登入失敗後應存在 login_failed 活動記錄');
        $this->assertSame('failed', $this->getStringValue($row, 'status'));
        $this->assertSame($ip, $this->getStringValue($row, 'ip_address'));
        $this->assertNotSame('', $this->getStringValue($row, 'description'), '失敗原因應被記錄');

        $metadata = $this->decodeMetadata($row);
        $this->assertSame(self::USER_EMAIL, $this->getStringValue($metadata, 'email'));
    }

    /**
     * 測試登出會寫入審計日誌.
     */
    public function testLogoutWritesAuditLog(): void
    {
        $loginData = $this->loginUser(self::USER_EMAIL);
        $userId = $this->userIdOf($loginData);
        $accessToken = $this->accessTokenOf($loginData);
        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $accessToken]);

        $response = $this->request('POST', '/api/auth/logout', [], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));

        $row = $this->fetchLatestActivityLog('logout');
        $this->assertNotNull($row, '登出後應存在 logout 活動記錄');
        $this->assertSame($userId, $this->logUserId($row));
        $this->assertSame('success', $this->getStringValue($row, 'status'));
    }

    /**
     * 測試密碼變更等敏感操作會寫入審計日誌.
     */
    public function testPasswordChangeWritesSensitiveOperationAuditLog(): void
    {
        $loginData = $this->loginUser(self::USER_EMAIL);
        $userId = $this->userIdOf($loginData);
        $accessToken = $this->accessTokenOf($loginData);
        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $accessToken]);

        $response = $this->request('POST', '/api/auth/change-password', [
            'current_password'          => $this->testPassword(),
            'new_password'              => 'NewPassword456!x',
            'new_password_confirmation' => 'NewPassword456!x',
        ], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));

        $row = $this->fetchLatestActivityLog('password_changed');
        $this->assertNotNull($row, '密碼變更後應存在 password_changed 活動記錄');
        $this->assertSame($userId, $this->logUserId($row));
    }

    /**
     * 測試管理員可透過 activity-logs API 查詢審計記錄.
     */
    public function testAdminCanQueryActivityLogsThroughApi(): void
    {
        $this->loginUser(self::USER_EMAIL);

        $adminToken = $this->accessTokenOf($this->loginUser(self::ADMIN_EMAIL));
        $response = $this->request('GET', '/api/v1/activity-logs?limit=50&offset=0', headers: [
            'Authorization' => 'Bearer ' . $adminToken,
        ]);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));

        $actionTypes = [];
        foreach ($this->getArrayValue($data, 'data') as $item) {
            if (is_array($item)) {
                $actionTypes[] = $this->getStringValue($item, 'action_type');
            }
        }
        $this->assertContains('login_success', $actionTypes, '查詢結果應包含登入成功的審計記錄');
    }

    /**
     * 測試未認證者無法查詢活動日誌（401）.
     */
    public function testUnauthenticatedActivityLogQueryReturns401(): void
    {
        $response = $this->request('GET', '/api/v1/activity-logs');
        $data = $this->assertErrorResponse($response, 401);

        $this->assertSame('UNAUTHORIZED', $data['code'] ?? '');
    }

    /**
     * 測試 me 端點只回傳當前使用者自己的活動記錄.
     */
    public function testCurrentUserLogsOnlyContainOwnActivities(): void
    {
        $userData = $this->loginUser(self::USER_EMAIL);
        $userId = $this->userIdOf($userData);
        $this->loginUser(self::ADMIN_EMAIL);

        $response = $this->request('GET', '/api/v1/activity-logs/me?limit=50', headers: [
            'Authorization' => 'Bearer ' . $this->accessTokenOf($userData),
        ]);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        $rows = $this->getArrayValue($data, 'data');
        $this->assertNotEmpty($rows, '當前使用者的活動記錄不應為空');
        foreach ($rows as $item) {
            if (is_array($item)) {
                $this->assertSame(
                    $userId,
                    $this->getIntValue($item, 'user_id'),
                    'me 端點不得回傳其他使用者的活動記錄',
                );
            }
        }
    }

    /**
     * 測試登入失敗統計端點能彙總失敗嘗試.
     */
    public function testLoginFailureStatisticsEndpointAggregatesFailures(): void
    {
        $victimEmail = 'bruteforce-victim@example.com';
        $this->createAuthUser('bruteforcevictim', $victimEmail, ['user']);

        for ($i = 0; $i < 3; $i++) {
            $response = $this->request('POST', '/api/auth/login', [
                'email'    => $victimEmail,
                'password' => 'WrongPassword456!',
            ], ip: '203.0.113.31');
            $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        }

        $adminToken = $this->accessTokenOf($this->loginUser(self::ADMIN_EMAIL));
        $response = $this->request('GET', '/api/v1/activity-logs/login-failures', headers: [
            'Authorization' => 'Bearer ' . $adminToken,
        ]);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $stats = $this->getArrayValue($data, 'data');
        $this->assertGreaterThanOrEqual(3, $this->getIntValue($stats, 'total'), '失敗統計總數應至少涵蓋三次失敗');

        $matched = false;
        foreach ($this->getArrayValue($stats, 'accounts') as $account) {
            if (is_array($account)
                && $this->getStringValue($account, 'email') === $victimEmail
                && $this->getIntValue($account, 'count') >= 3) {
                $matched = true;
            }
        }
        $this->assertTrue($matched, '失敗統計的帳號清單應包含被攻擊帳號');
    }

    /**
     * 測試 activity-logs store 端點可建立安全事件並可再查詢.
     */
    public function testActivityStoreEndpointPersistsSecurityEvent(): void
    {
        $userData = $this->loginUser(self::USER_EMAIL);
        $userId = $this->userIdOf($userData);
        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $this->accessTokenOf($userData)]);

        $response = $this->request('POST', '/api/v1/activity-logs', [
            'action_type' => 'post_created',
            'user_id'     => $userId,
            'metadata'    => ['title' => '安全事件整合測試'],
        ], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $data = $this->getJson($response);

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));

        $row = $this->fetchLatestActivityLog('post_created');
        $this->assertNotNull($row, 'store 端點建立的活動應寫入資料庫');
        $this->assertSame($userId, $this->logUserId($row));
        $metadata = $this->decodeMetadata($row);
        $this->assertSame('安全事件整合測試', $this->getStringValue($metadata, 'title'));

        $meResponse = $this->request('GET', '/api/v1/activity-logs/me?limit=50', headers: [
            'Authorization' => 'Bearer ' . $this->accessTokenOf($userData),
        ]);
        $meData = $this->getJson($meResponse);
        $actionTypes = [];
        foreach ($this->getArrayValue($meData, 'data') as $item) {
            if (is_array($item)) {
                $actionTypes[] = $this->getStringValue($item, 'action_type');
            }
        }
        $this->assertContains('post_created', $actionTypes, '建立的活動應出現在 me 端點查詢結果');
    }

    /**
     * 測試同一 IP 連續登入失敗會被可疑活動偵測器標記.
     */
    public function testRepeatedLoginFailuresFromSameIpAreDetectedAsSuspicious(): void
    {
        $victimEmail = 'detector-victim@example.com';
        $this->createAuthUser('detectorvictim', $victimEmail, ['user']);
        $attackerIp = '203.0.113.42';

        for ($i = 0; $i < 5; $i++) {
            $response = $this->request('POST', '/api/auth/login', [
                'email'    => $victimEmail,
                'password' => 'WrongPassword456!',
            ], ip: $attackerIp);
            $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        }

        $detector = new SuspiciousActivityDetector(
            new ActivityLogRepository($this->db),
            new ActivityLoggingService(new ActivityLogRepository($this->db), $this->createSilentLogger()),
            $this->createSilentLogger(),
        );
        $analysis = $detector->detectSuspiciousIpActivity($attackerIp);

        $this->assertTrue($analysis->isSuspicious(), '五次連續登入失敗應判定為可疑活動');
        $this->assertSame(5, $analysis->getFailureCounts()['login_failed'] ?? 0);
        $this->assertSame('temporary_account_lock', $analysis->getRecommendedAction());
        $rules = $analysis->getDetectionRules();
        $types = [];
        foreach ($rules as $rule) {
            if (is_array($rule) && is_string($rule['type'] ?? null)) {
                $types[] = $rule['type'];
            }
        }
        $this->assertContains('failure_rate_threshold', $types);

        // 對照組：沒有失敗紀錄的乾淨 IP 不應被判定為可疑
        $cleanAnalysis = $detector->detectSuspiciousIpActivity('203.0.113.99');
        $this->assertFalse($cleanAnalysis->isSuspicious(), '無失敗紀錄的 IP 不應被判定為可疑');
    }

    /**
     * 建立不輸出任何訊息的測試用記錄器.
     */
    private function createSilentLogger(): LoggerInterface
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('emergency')->andReturn(null);
        $logger->shouldReceive('alert')->andReturn(null);
        $logger->shouldReceive('critical')->andReturn(null);
        $logger->shouldReceive('error')->andReturn(null);
        $logger->shouldReceive('warning')->andReturn(null);
        $logger->shouldReceive('notice')->andReturn(null);
        $logger->shouldReceive('info')->andReturn(null);
        $logger->shouldReceive('debug')->andReturn(null);
        $logger->shouldReceive('log')->andReturn(null);

        return $logger;
    }

    /**
     * 取得最新一筆指定類型的活動記錄.
     *
     * @return array<string, mixed>|null 無記錄時回傳 null
     */
    private function fetchLatestActivityLog(string $actionType): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM user_activity_logs WHERE action_type = :action_type ORDER BY id DESC LIMIT 1',
        );
        $stmt->execute(['action_type' => $actionType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        /** @var array<string, mixed>|false $row */
        return is_array($row) ? $row : null;
    }

    /**
     * 安全取得活動記錄中的 user_id.
     *
     * @param array<string, mixed> $row 活動記錄資料列
     */
    private function logUserId(array $row): int
    {
        $value = $row['user_id'] ?? null;

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);
    }

    /**
     * 解析活動記錄的 metadata JSON 欄位.
     *
     * @param array<string, mixed> $row 活動記錄資料列
     *
     * @return array<string, mixed> 解析失敗時回傳空陣列
     */
    private function decodeMetadata(array $row): array
    {
        $raw = $row['metadata'] ?? null;
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        /** @var array<string, mixed> $decoded */

        return is_array($decoded) ? $decoded : [];
    }
}
