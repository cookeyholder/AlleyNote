<?php

declare(strict_types=1);

namespace Tests\Integration\Settings;

use App\Shared\Helpers\TimezoneHelper;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\AuthApiIntegrationTestCase;

/**
 * Setting HTTP API 整合測試.
 *
 * 以完整應用程式堆疊（路由、中介軟體、控制器、領域服務、SQLite 資料庫）覆蓋：
 * - 讀取設定（管理員取得全部、單一鍵值、不存在鍵值）
 * - 權限控制（未認證 401、非管理員 403）
 * - 更新設定（批量更新、單鍵更新與 integer/boolean/json 型別轉換）
 * - 驗證失敗（未知鍵值 422、缺少 CSRF Token 403）
 * - 資料庫狀態與回應一致性（更新後直接查驗 settings 資料列）
 */
#[Group('integration')]
#[Group('api')]
#[Group('settings')]
final class SettingApiIntegrationTest extends AuthApiIntegrationTestCase
{
    private const ADMIN_EMAIL = 'settings-admin@example.com';

    private const USER_EMAIL = 'settings-user@example.com';

    private const CLIENT_IP = '203.0.113.21';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthUser('settingsadmin', self::ADMIN_EMAIL, ['admin']);
        $this->createAuthUser('settingsuser', self::USER_EMAIL, ['user']);
    }

    /**
     * 測試管理員可取得全部系統設定且型別已轉換.
     */
    public function testAdminCanListAllSettingsWithTypedValues(): void
    {
        $response = $this->adminRequest('GET', '/api/settings');
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));

        $settings = $this->getArrayValue($data, 'data');
        $this->assertSame(
            ['allowed_file_types', 'enable_comments', 'enable_registration', 'max_upload_size', 'posts_per_page', 'site_description', 'site_name'],
            array_keys($settings),
            '應回傳種子設定的全部七個鍵值（依鍵名排序）',
        );

        // string 型別保持原值
        $siteName = $this->getArrayValue($settings, 'site_name');
        $this->assertSame('AlleyNote', $this->getNestedValue($siteName, 'value'));
        $this->assertSame('string', $this->getNestedValue($siteName, 'type'));

        // integer 型別轉為整數
        $postsPerPage = $this->getArrayValue($settings, 'posts_per_page');
        $this->assertSame(20, $this->getNestedValue($postsPerPage, 'value'));
        $this->assertSame('integer', $this->getNestedValue($postsPerPage, 'type'));

        // boolean 型別轉為布林
        $registration = $this->getArrayValue($settings, 'enable_registration');
        $this->assertTrue((bool) ($registration['value'] ?? false));
        $this->assertSame('boolean', $this->getNestedValue($registration, 'type'));

        // json 型別轉為陣列
        $fileTypes = $this->getArrayValue($settings, 'allowed_file_types');
        $types = $fileTypes['value'] ?? null;
        $this->assertIsArray($types);
        $this->assertContains('pdf', $types);
        $this->assertSame('json', $this->getNestedValue($fileTypes, 'type'));
    }

    /**
     * 測試未認證讀取設定列表回傳 401.
     */
    public function testListWithoutTokenReturns401(): void
    {
        $response = $this->request('GET', '/api/settings');
        $this->assertErrorResponse($response, 401);
    }

    /**
     * 測試一般使用者讀取設定列表回傳 403.
     */
    public function testListAsRegularUserReturns403(): void
    {
        $response = $this->userRequest('GET', '/api/settings');
        $this->assertErrorResponse($response, 403);
    }

    /**
     * 測試管理員可讀取單一設定.
     */
    public function testAdminCanShowSingleSetting(): void
    {
        $response = $this->adminRequest('GET', '/api/settings/site_name');
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));
        $this->assertSame('site_name', $this->getStringValue($data, 'data', 'key'));
        $this->assertSame('AlleyNote', $this->getStringValue($data, 'data', 'value'));
        $this->assertSame('string', $this->getStringValue($data, 'data', 'type'));
        $this->assertSame('網站名稱', $this->getStringValue($data, 'data', 'description'));
    }

    /**
     * 測試讀取不存在的設定回傳 404.
     */
    public function testShowNonExistentSettingReturns404(): void
    {
        $response = $this->adminRequest('GET', '/api/settings/no_such_setting');
        $data = $this->assertErrorResponse($response, 404);

        $this->assertStringContainsString('no_such_setting', $this->getStringValue($data, 'message'));
    }

    /**
     * 測試批量更新設定並同步至資料庫.
     */
    public function testBatchUpdatePersistsValuesToDatabase(): void
    {
        $response = $this->adminRequest('PUT', '/api/settings', [
            'site_name'      => '整合測試站',
            'posts_per_page' => '50',
        ]);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));
        $this->assertSame('系統設定更新成功', $this->getStringValue($data, 'message'));
        $this->assertSame('整合測試站', $this->getStringValue($data, 'data', 'site_name', 'value'));
        $this->assertSame(50, $this->getIntValue($data, 'data', 'posts_per_page', 'value'));

        $this->assertSame('整合測試站', $this->rawSettingValue('site_name'));
        $row = $this->fetchSettingRow('posts_per_page');
        $this->assertNotNull($row);
        $this->assertSame('50', self::strOf($row['value'] ?? null), '資料庫應以字串儲存轉換後的整數');
    }

    /**
     * 測試單鍵更新 integer 設定會執行型別轉換.
     */
    public function testUpdateSingleIntegerSettingCastsType(): void
    {
        $response = $this->adminRequest('PUT', '/api/settings/posts_per_page', ['value' => '35']);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(35, $this->getIntValue($data, 'data', 'value'));
        $this->assertSame('integer', $this->getStringValue($data, 'data', 'type'));

        $row = $this->fetchSettingRow('posts_per_page');
        $this->assertNotNull($row);
        $this->assertSame('35', self::strOf($row['value'] ?? null));
    }

    /**
     * 測試單鍵更新 integer 設定遇到非法字串時歸零.
     */
    public function testUpdateSingleIntegerSettingCoercesGarbageToZero(): void
    {
        $response = $this->adminRequest('PUT', '/api/settings/posts_per_page', ['value' => 'not-a-number']);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(0, $this->getIntValue($data, 'data', 'value'));

        $this->assertSame('0', $this->rawSettingValue('posts_per_page'));
    }

    /**
     * 測試單鍵更新 boolean 設定會執行型別轉換.
     */
    public function testUpdateSingleBooleanSettingCastsType(): void
    {
        $response = $this->adminRequest('PUT', '/api/settings/enable_registration', ['value' => false]);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertFalse((bool) $this->getNestedValue($data, 'data', 'value'), 'false 應回傳布林 false');

        $row = $this->fetchSettingRow('enable_registration');
        $this->assertNotNull($row);
        $this->assertSame('0', self::strOf($row['value'] ?? null), '資料庫應以 0 儲存布林 false');
    }

    /**
     * 測試單鍵更新 json 設定會序列化與還原陣列.
     */
    public function testUpdateSingleJsonSettingRoundTripsArray(): void
    {
        $allowed = ['png', 'pdf'];
        $response = $this->adminRequest('PUT', '/api/settings/allowed_file_types', ['value' => $allowed]);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $value = $this->getNestedValue($data, 'data', 'value');
        $this->assertIsArray($value);
        $this->assertSame($allowed, $value);

        $row = $this->fetchSettingRow('allowed_file_types');
        $this->assertNotNull($row);
        $this->assertSame('["png","pdf"]', self::strOf($row['value'] ?? null), '資料庫應以 JSON 字串儲存');

        // 再次讀取應還原為 PHP 陣列
        $showResponse = $this->adminRequest('GET', '/api/settings/allowed_file_types');
        $showData = $this->getJson($showResponse);
        $shown = $this->getNestedValue($showData, 'data', 'value');
        $this->assertIsArray($shown);
        $this->assertSame($allowed, $shown);
    }

    /**
     * 測試更新不存在的設定鍵回傳 404 且不建立新設定.
     */
    public function testUpdateSingleNonExistentKeyReturns404(): void
    {
        $response = $this->adminRequest('PUT', '/api/settings/brand_new_key', ['value' => 'x']);
        $this->assertErrorResponse($response, 404);

        $this->assertNull($this->fetchSettingRow('brand_new_key'), '不存在的鍵值不應被建立');
    }

    /**
     * 測試批量更新含未知鍵時回傳 422 並附錯誤明細.
     */
    public function testBatchUpdateWithUnknownKeyReturns422(): void
    {
        $response = $this->adminRequest('PUT', '/api/settings', [
            'site_name'          => '部分成功測試',
            'unknown_setting_xx' => 'oops',
        ]);
        $data = $this->assertErrorResponse($response, 422);

        $errors = $this->getArrayValue($data, 'errors');
        $this->assertArrayHasKey('unknown_setting_xx', $errors, '錯誤明細應包含失敗的設定鍵');
    }

    /**
     * 測試更新設定缺少 CSRF Token 回傳 403.
     */
    public function testUpdateWithoutCsrfTokenReturns403(): void
    {
        $token = $this->accessTokenOf($this->loginUser(self::ADMIN_EMAIL, ip: $this->uniqueClientIp()));
        $response = $this->request('PUT', '/api/settings/site_name', ['value' => '無 CSRF'], headers: [
            'Authorization' => 'Bearer ' . $token,
        ], ip: self::CLIENT_IP);
        $data = $this->assertErrorResponse($response, 403);

        $this->assertSame('CSRF_INVALID', $this->getStringValue($data, 'code'));

        $this->assertSame('AlleyNote', $this->rawSettingValue('site_name'), '被攔截的請求不得變更設定');
    }

    /**
     * 測試匿名可取得時區資訊.
     */
    public function testTimezoneInfoIsPubliclyAccessible(): void
    {
        TimezoneHelper::resetTimezoneCache();

        try {
            $response = $this->request('GET', '/api/settings/timezone/info');
            $data = $this->getJson($response);

            $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
            $this->assertTrue((bool) ($data['success'] ?? false));
            $this->assertSame('Asia/Taipei', $this->getStringValue($data, 'data', 'timezone'), '未設定 site_timezone 時應使用預設時區');
            $this->assertNotSame('', $this->getStringValue($data, 'data', 'offset'));
            $this->assertNotSame('', $this->getStringValue($data, 'data', 'current_time'));

            $timezones = $this->getNestedValue($data, 'data', 'common_timezones');
            $this->assertIsArray($timezones);
            $this->assertArrayHasKey('Asia/Taipei', $timezones, '時區列表應以時區名稱為鍵');
        } finally {
            TimezoneHelper::resetTimezoneCache();
        }
    }

    /**
     * 以管理員身分發送帶 CSRF 與 Bearer Token 的請求.
     *
     * @param array<string, mixed>|null $body
     */
    private function adminRequest(string $method, string $path, ?array $body = null): ResponseInterface
    {
        return $this->authedRequest($method, $path, $body, self::ADMIN_EMAIL);
    }

    /**
     * 以一般使用者身分發送帶 CSRF 與 Bearer Token 的請求.
     *
     * @param array<string, mixed>|null $body
     */
    private function userRequest(string $method, string $path, ?array $body = null): ResponseInterface
    {
        return $this->authedRequest($method, $path, $body, self::USER_EMAIL);
    }

    /**
     * 登入指定帳號並發送帶 CSRF 與 Authorization 的請求.
     *
     * @param array<string, mixed>|null $body
     */
    private function authedRequest(string $method, string $path, ?array $body, string $email): ResponseInterface
    {
        $token = $this->accessTokenOf($this->loginUser($email, ip: $this->uniqueClientIp()));
        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $token]);

        return $this->request($method, $path, $body, headers: $csrf['headers'], cookies: $csrf['cookies'], ip: self::CLIENT_IP);
    }

    /**
     * 直接查詢 settings 資料列.
     *
     * @return array<string, mixed>|null
     */
    private function fetchSettingRow(string $key): ?array
    {
        $stmt = $this->db->prepare('SELECT key, value, type FROM settings WHERE key = :key');
        $stmt->execute(['key' => $key]);
        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * 直接讀取設定於資料庫中的原始字串值.
     */
    private function rawSettingValue(string $key): string
    {
        $row = $this->fetchSettingRow($key);
        $this->assertNotNull($row, '設定應存在：' . $key);

        return self::strOf($row['value'] ?? null);
    }

    /**
     * 將資料庫欄位值安全轉為字串.
     */
    private static function strOf(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (string) $value;
        }

        return '';
    }
}
