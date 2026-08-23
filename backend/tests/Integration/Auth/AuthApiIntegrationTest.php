<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AuthApiIntegrationTestCase;

/**
 * Auth 領域 HTTP API 整合測試.
 *
 * 以完整應用程式堆疊（路由、中介軟體、控制器、領域服務、SQLite 資料庫）覆蓋：
 * - 登入（成功、錯誤憑證、缺少欄位）
 * - 個人資料（/api/auth/me、/api/auth/profile）
 * - Token 刷新輪替與重用偵測
 * - 登出（Refresh Token 撤銷與 Access Token 黑名單）
 */
#[Group('integration')]
#[Group('api')]
#[Group('auth')]
class AuthApiIntegrationTest extends AuthApiIntegrationTestCase
{
    private const USER_EMAIL = 'auth-user@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthUser('authuser', self::USER_EMAIL, ['user']);
    }

    /**
     * 測試登入成功回傳 Token 對並寫入 Refresh Token.
     */
    public function testLoginSuccessReturnsTokenPairAndStoresRefreshToken(): void
    {
        $response = $this->request('POST', '/api/auth/login', [
            'email'    => self::USER_EMAIL,
            'password' => $this->testPassword(),
        ], ip: $this->uniqueClientIp());
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) ($data['success'] ?? false));
        $accessToken = $this->accessTokenOf($data);
        $refreshToken = $this->refreshTokenOf($data);
        $this->assertNotSame('', $accessToken, '回應應包含 access_token');
        $this->assertNotSame('', $refreshToken, '回應應包含 refresh_token');
        $this->assertSame('Bearer', $this->getStringValue($data, 'token_type'));
        $this->assertGreaterThan(0, $this->getIntValue($data, 'expires_in'));

        // 資料庫應存在一筆有效的 refresh token 記錄
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM refresh_tokens WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$this->userIdOf($data)]);
        $this->assertSame(1, (int) $stmt->fetchColumn());

        // 回應應附加認證 Cookie
        $setCookies = implode("\n", $response->getHeader('Set-Cookie'));
        $this->assertStringContainsString('access_token=', $setCookies);
        $this->assertStringContainsString('refresh_token=', $setCookies);
        $this->assertStringContainsString('auth_mode=cookie', $setCookies);
    }

    /**
     * 測試密碼錯誤回傳 401.
     */
    public function testLoginWithWrongPasswordReturns401(): void
    {
        $response = $this->request('POST', '/api/auth/login', [
            'email'    => self::USER_EMAIL,
            'password' => 'WrongPassword456!',
        ], ip: $this->uniqueClientIp());
        $data = $this->assertErrorResponse($response, 401);

        $this->assertSame(401, $this->getIntValue($data, 'error', 'code'), '錯誤物件應包含數值狀態碼 401');
    }

    /**
     * 測試不存在的Email登入回傳 401，且不外洩帳號是否存在.
     */
    public function testLoginWithUnknownEmailReturns401(): void
    {
        $response = $this->request('POST', '/api/auth/login', [
            'email'    => 'ghost@example.com',
            'password' => $this->testPassword(),
        ], ip: $this->uniqueClientIp());
        $data = $this->assertErrorResponse($response, 401);

        $this->assertSame(401, $this->getIntValue($data, 'error', 'code'));
    }

    /**
     * 測試缺少登入欄位回傳 400.
     */
    public function testLoginWithMissingFieldsReturns400(): void
    {
        $response = $this->request('POST', '/api/auth/login', [], ip: $this->uniqueClientIp());
        $data = $this->assertErrorResponse($response, 400);

        $this->assertSame('缺少必要的登入資料', $this->getStringValue($data, 'error'));
    }

    /**
     * 測試未攜帶 Token 存取個人資料回傳 401.
     */
    public function testMeWithoutTokenReturns401(): void
    {
        $response = $this->request('GET', '/api/auth/me');
        $data = $this->assertErrorResponse($response, 401);

        $this->assertSame('UNAUTHORIZED', $this->getStringValue($data, 'code'));
        $this->assertStringContainsString('Bearer', $response->getHeaderLine('WWW-Authenticate'));
    }

    /**
     * 測試格式錯誤的 Token 回傳 401 TOKEN_INVALID.
     */
    public function testMeWithMalformedTokenReturns401TokenInvalid(): void
    {
        $response = $this->request('GET', '/api/auth/me', headers: [
            'Authorization' => 'Bearer this.is.not-a-jwt',
        ]);
        $data = $this->assertErrorResponse($response, 401);

        $this->assertSame('TOKEN_INVALID', $this->getStringValue($data, 'code'));
    }

    /**
     * 測試簽章不符的 Token 回傳 401.
     */
    public function testMeWithTamperedSignatureReturns401(): void
    {
        $accessToken = $this->accessTokenOf($this->loginUser(self::USER_EMAIL));
        // 保留 JWT 三段式結構，但替換簽章段落使驗證失敗
        $parts = explode('.', $accessToken);
        $tamperedToken = $parts[0] . '.' . $parts[1] . '.c2lnbmF0dXJlLXRhbXBlcmVk';

        $response = $this->request('GET', '/api/auth/me', headers: [
            'Authorization' => 'Bearer ' . $tamperedToken,
        ]);
        $this->assertErrorResponse($response, 401);
    }

    /**
     * 測試過期 Token 回傳 401 TOKEN_EXPIRED.
     */
    public function testMeWithExpiredTokenReturns401TokenExpired(): void
    {
        $accessToken = $this->accessTokenOf($this->loginUser(self::USER_EMAIL));
        $payload = $this->decodeJwtPayload($accessToken);
        $payload['exp'] = time() - 3600;
        $payload['iat'] = time() - 7200;
        $expiredToken = $this->signJwt($payload);

        $response = $this->request('GET', '/api/auth/me', headers: [
            'Authorization' => 'Bearer ' . $expiredToken,
        ]);
        $data = $this->assertErrorResponse($response, 401);

        $this->assertSame('TOKEN_EXPIRED', $this->getStringValue($data, 'code'));
    }

    /**
     * 測試有效 Token 可取得個人資料與角色.
     */
    public function testMeWithValidTokenReturnsUserWithRoles(): void
    {
        $accessToken = $this->accessTokenOf($this->loginUser(self::USER_EMAIL));

        $response = $this->request('GET', '/api/auth/me', headers: [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) ($data['success'] ?? false));

        $user = $this->getArrayValue($data, 'data', 'user');
        $this->assertSame(self::USER_EMAIL, $this->getStringValue($user, 'email'));

        $roleNames = [];
        foreach ($this->getArrayValue($user, 'roles') as $role) {
            if (is_array($role)) {
                $roleNames[] = $this->getStringValue($role, 'name');
            }
        }
        $this->assertContains('user', $roleNames, '個人資料應包含使用者角色');

        $tokenInfo = $this->getArrayValue($data, 'data', 'token_info');
        $this->assertGreaterThan(0, $this->getIntValue($tokenInfo, 'issued_at'));
        $this->assertGreaterThan(0, $this->getIntValue($tokenInfo, 'expires_at'));
    }

    /**
     * 測試刷新會輪替 Token，且舊 Refresh Token 無法重用.
     */
    public function testRefreshRotatesTokensAndRejectsReuseOfOldRefreshToken(): void
    {
        $login = $this->loginUser(self::USER_EMAIL);
        $oldAccessToken = $this->accessTokenOf($login);
        $oldRefreshToken = $this->refreshTokenOf($login);

        // 第一次刷新：應回傳全新的 Token 對
        $csrf = $this->csrfCredentials();
        $firstRefresh = $this->request('POST', '/api/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $firstData = $this->getJson($firstRefresh);

        $this->assertSame(200, $firstRefresh->getStatusCode(), (string) $firstRefresh->getBody());
        $newAccessToken = $this->accessTokenOf($firstData);
        $newRefreshToken = $this->refreshTokenOf($firstData);
        $this->assertNotSame('', $newAccessToken);
        $this->assertNotSame('', $newRefreshToken);
        $this->assertNotSame($oldAccessToken, $newAccessToken, 'Access token 應該輪替');
        $this->assertNotSame($oldRefreshToken, $newRefreshToken, 'Refresh token 應該輪替');

        // 舊 Refresh Token 已被撤銷，重放應回傳 401
        $replayCsrf = $this->csrfCredentials();
        $replay = $this->request('POST', '/api/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ], headers: $replayCsrf['headers'], cookies: $replayCsrf['cookies']);
        $this->assertErrorResponse($replay, 401);

        // 新 Refresh Token 仍可繼續刷新
        $secondCsrf = $this->csrfCredentials();
        $secondRefresh = $this->request('POST', '/api/auth/refresh', [
            'refresh_token' => $newRefreshToken,
        ], headers: $secondCsrf['headers'], cookies: $secondCsrf['cookies']);
        $this->assertSame(200, $secondRefresh->getStatusCode(), (string) $secondRefresh->getBody());
    }

    /**
     * 測試缺少 Refresh Token 的刷新請求回傳 400.
     */
    public function testRefreshWithoutTokenReturns400(): void
    {
        $csrf = $this->csrfCredentials();
        $response = $this->request('POST', '/api/auth/refresh', [], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $data = $this->assertErrorResponse($response, 400);

        $this->assertSame('缺少必要的 refresh_token', $this->getStringValue($data, 'error'));
    }

    /**
     * 測試登出撤銷 Refresh Token、將 Access Token 加入黑名單並清除 Cookie.
     */
    public function testLogoutRevokesTokensAndBlacklistsAccessToken(): void
    {
        $login = $this->loginUser(self::USER_EMAIL);
        $accessToken = $this->accessTokenOf($login);
        $refreshToken = $this->refreshTokenOf($login);
        $jti = $this->getStringValue($this->decodeJwtPayload($refreshToken), 'jti');

        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $accessToken]);
        $response = $this->request('POST', '/api/auth/logout', [
            'refresh_token' => $refreshToken,
        ], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false), '登出應該成功');

        // Refresh Token 已被撤銷
        $stmt = $this->db->prepare('SELECT status FROM refresh_tokens WHERE jti = ?');
        $stmt->execute([$jti]);
        $this->assertSame('revoked', $stmt->fetchColumn(), '登出後 refresh token 應標記為 revoked');

        // Access Token 已加入黑名單，後續請求回傳 401
        $afterLogout = $this->request('GET', '/api/auth/me', headers: [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
        $afterData = $this->assertErrorResponse($afterLogout, 401);
        $this->assertSame('TOKEN_INVALID', $this->getStringValue($afterData, 'code'));

        // 回應應清除認證 Cookie
        $setCookies = implode("\n", $response->getHeader('Set-Cookie'));
        $this->assertStringContainsString('access_token=;', str_replace(' ', '', $setCookies), 'access_token cookie 應被清空');
        $this->assertStringContainsString('refresh_token=;', str_replace(' ', '', $setCookies), 'refresh_token cookie 應被清空');
    }

    /**
     * 測試未認證更新個人資料回傳 401.
     */
    public function testUpdateProfileWithoutTokenReturns401(): void
    {
        $csrf = $this->csrfCredentials();
        $response = $this->request('PUT', '/api/auth/profile', [
            'name' => '新名字',
        ], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $this->assertErrorResponse($response, 401);
    }

    /**
     * 測試更新個人資料包含不支援欄位回傳 400.
     */
    public function testUpdateProfileWithUnsupportedFieldsReturns400(): void
    {
        $accessToken = $this->accessTokenOf($this->loginUser(self::USER_EMAIL));

        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $accessToken]);
        $response = $this->request('PUT', '/api/auth/profile', [
            'is_admin' => true,
        ], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $data = $this->assertErrorResponse($response, 400);

        $unsupportedFields = [];
        foreach ($this->getArrayValue($data, 'unsupported_fields') as $field) {
            if (is_string($field)) {
                $unsupportedFields[] = $field;
            }
        }
        $this->assertContains('is_admin', $unsupportedFields, '應列出未支援的欄位');
    }

    /**
     * 測試更新個人資料成功.
     */
    public function testUpdateProfileWithValidFieldsSucceeds(): void
    {
        $accessToken = $this->accessTokenOf($this->loginUser(self::USER_EMAIL));

        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $accessToken]);
        $response = $this->request('PUT', '/api/auth/profile', [
            'name' => '更新的名字',
        ], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false), '更新個人資料應該成功');
    }

    /**
     * 測試黑名單查詢：登出後同一 Token 的 jti 存在於 token_blacklist.
     */
    public function testLogoutAddsAccessTokenToBlacklistTable(): void
    {
        $login = $this->loginUser(self::USER_EMAIL);
        $accessToken = $this->accessTokenOf($login);
        $refreshToken = $this->refreshTokenOf($login);
        $accessJti = $this->getStringValue($this->decodeJwtPayload($accessToken), 'jti');

        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $accessToken]);
        $logoutResponse = $this->request('POST', '/api/auth/logout', [
            'refresh_token' => $refreshToken,
        ], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $this->assertSame(200, $logoutResponse->getStatusCode(), (string) $logoutResponse->getBody());

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM token_blacklist WHERE jti = ?');
        $stmt->execute([$accessJti]);
        $this->assertSame(1, (int) $stmt->fetchColumn(), '登出後 access token 的 jti 應存在於黑名單');
    }
}
