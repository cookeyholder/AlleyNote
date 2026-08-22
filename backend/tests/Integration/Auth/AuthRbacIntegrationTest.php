<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AuthApiIntegrationTestCase;

/**
 * RBAC 授權 HTTP API 整合測試.
 *
 * 以完整應用程式堆疊驗證角色權限控制：
 * - 一般使用者存取 admin 端點必須回 HTTP 403 與正確錯誤碼
 * - 未認證請求必須回 HTTP 401
 * - 管理員可正常存取 admin 端點
 * - 公告發布（admin 路由）的權限邊界
 */
#[Group('integration')]
#[Group('api')]
#[Group('auth')]
#[Group('rbac')]
class AuthRbacIntegrationTest extends AuthApiIntegrationTestCase
{
    private const USER_EMAIL = 'rbac-user@example.com';

    private const ADMIN_EMAIL = 'rbac-admin@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthUser('rbacuser', self::USER_EMAIL, ['user']);
        $this->createAuthUser('rbacadmin', self::ADMIN_EMAIL, ['admin']);
    }

    /**
     * 建立一般使用者與管理員的測試資料並取得兩者的登入回應.
     *
     * @return array{user: array<string, mixed>, admin: array<string, mixed>}
     */
    private function loginBothUsers(): array
    {
        return [
            'user'  => $this->loginUser(self::USER_EMAIL),
            'admin' => $this->loginUser(self::ADMIN_EMAIL),
        ];
    }

    /**
     * 測試一般使用者存取 admin 儀表板被拒絕（403 INSUFFICIENT_PERMISSIONS）.
     */
    public function testNormalUserAccessingAdminDashboardIsForbidden(): void
    {
        $tokens = $this->loginBothUsers();
        $accessToken = $this->accessTokenOf($tokens['user']);

        $response = $this->request('GET', '/api/admin/dashboard', headers: [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
        $data = $this->assertErrorResponse($response, 403);

        $this->assertSame(
            'INSUFFICIENT_PERMISSIONS',
            $data['code'] ?? '',
            '一般使用者存取 admin 端點應回傳 INSUFFICIENT_PERMISSIONS',
        );
    }

    /**
     * 測試一般使用者無法透過 admin 端點列出使用者.
     */
    public function testNormalUserCannotListUsersViaAdminEndpoint(): void
    {
        $tokens = $this->loginBothUsers();
        $accessToken = $this->accessTokenOf($tokens['user']);

        $response = $this->request('GET', '/api/admin/users', headers: [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
        $data = $this->assertErrorResponse($response, 403);

        $this->assertSame('INSUFFICIENT_PERMISSIONS', $data['code'] ?? '');
    }

    /**
     * 測試未認證存取 admin 端點回傳 401 UNAUTHORIZED.
     */
    public function testUnauthenticatedAdminAccessReturns401(): void
    {
        $response = $this->request('GET', '/api/admin/dashboard');
        $data = $this->assertErrorResponse($response, 401);

        $this->assertSame('UNAUTHORIZED', $data['code'] ?? '');
    }

    /**
     * 測試管理員可存取 admin 儀表板.
     */
    public function testAdminCanAccessAdminDashboard(): void
    {
        $tokens = $this->loginBothUsers();
        $accessToken = $this->accessTokenOf($tokens['admin']);

        $response = $this->request('GET', '/api/admin/dashboard', headers: [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
        $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * 測試管理員可透過 admin 端點列出使用者.
     */
    public function testAdminCanListUsersViaAdminEndpoint(): void
    {
        $tokens = $this->loginBothUsers();
        $accessToken = $this->accessTokenOf($tokens['admin']);

        $response = $this->request('GET', '/api/admin/users', headers: [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));
        $emails = [];
        foreach ($this->getArrayValue($data, 'data') as $item) {
            if (is_array($item)) {
                $emails[] = $this->getStringValue($item, 'email');
            }
        }
        $this->assertContains(self::USER_EMAIL, $emails, '使用者清單應包含一般使用者');
    }

    /**
     * 測試一般使用者可發布自己的公告（作者生命週期）.
     */
    public function testNormalUserCanPublishOwnPost(): void
    {
        $tokens = $this->loginBothUsers();
        $accessToken = $this->accessTokenOf($tokens['user']);
        $userId = $this->userIdOf($tokens['user']);
        $this->assertGreaterThan(0, $userId);

        $postId = $this->insertTestPost([
            'title'   => 'RBAC 作者發布測試',
            'content' => '一般使用者發布自己公告的測試內容',
            'user_id' => $userId,
            'status'  => 'draft',
        ]);

        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $accessToken]);
        $response = $this->request('POST', '/api/posts/' . $postId . '/publish', [], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false), '作者應該可以發布自己的公告');
    }

    /**
     * 測試一般使用者無法發布他人的公告（403）.
     */
    public function testNormalUserCannotPublishOthersPost(): void
    {
        $tokens = $this->loginBothUsers();
        $accessToken = $this->accessTokenOf($tokens['user']);
        $adminId = $this->userIdOf($tokens['admin']);
        $this->assertGreaterThan(0, $adminId);

        // 公告歸屬管理員，一般使用者不應能發布
        $postId = $this->insertTestPost([
            'title'   => 'RBAC 越權發布測試',
            'content' => '用於驗證不得發布他人公告的測試內容',
            'user_id' => $adminId,
        ]);

        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $accessToken]);
        $response = $this->request('POST', '/api/posts/' . $postId . '/publish', [], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $data = $this->assertErrorResponse($response, 403);

        $this->assertSame('權限不足', $this->getStringValue($data, 'message'), '越權發布應回傳權限不足訊息');
    }

    /**
     * 測試未認證者無法發布公告（401，先於授權檢查）.
     */
    public function testPublishPostWithoutTokenReturns401(): void
    {
        $postId = $this->insertTestPost(['title' => '未認證發布測試']);
        $csrf = $this->csrfCredentials();

        $response = $this->request('POST', '/api/posts/' . $postId . '/publish', [], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $data = $this->assertErrorResponse($response, 401);

        $this->assertSame('UNAUTHORIZED', $data['code'] ?? '');
    }
}
