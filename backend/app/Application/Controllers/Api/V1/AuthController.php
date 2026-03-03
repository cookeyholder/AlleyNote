<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\DTOs\LoginRequestDTO;
use App\Domains\Auth\DTOs\LogoutRequestDTO;
use App\Domains\Auth\DTOs\RefreshRequestDTO;
use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Domains\Auth\Services\AuthService;
use App\Domains\Auth\Services\UserManagementService;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityType;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Helpers\NetworkHelper;
use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * JWT 認證 Controller.
 *
 * 處理 JWT 認證相關的 API 端點，包含登入、登出、token 刷新、使用者資訊等功能。
 * 整合 DTO 驗證、例外處理、HTTP 回應格式。
 */
class AuthController extends BaseController
{
    public function __construct(
        private AuthService $authService,
        private AuthenticationServiceInterface $authenticationService,
        private JwtTokenServiceInterface $jwtTokenService,
        private ValidatorInterface $validator,
        private ActivityLoggingServiceInterface $activityLoggingService,
        private UserRepositoryInterface $userRepository,
        private UserManagementService $userManagementService,
    ) {}

    #[OA\Post(
        path: '/api/auth/register',
        summary: '使用者註冊',
        description: '建立新的使用者帳號，需要提供使用者名稱、電子郵件和密碼',
        operationId: 'registerUser',
        tags: ['auth'],
        requestBody: new OA\RequestBody(
            description: '註冊資料',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                ],
                required: ['username', 'email', 'password', 'password_confirmation'],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: '註冊成功'),
            new OA\Response(response: 400, description: '註冊資料驗證失敗'),
        ],
    )]
    public function register(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            if (!is_array($data)) {
                return $this->json($response, [
                    'success' => false,
                    'message' => 'Invalid request data format',
                ], 400);
            }

            $dto = new RegisterUserDTO($this->validator, $data);
            $user = $this->authService->register($dto);

            // 記錄成功註冊活動
            $activityDto = CreateActivityLogDTO::success(
                actionType: ActivityType::USER_REGISTERED,
                userId: $user['id'],
                metadata: [
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'registration_timestamp' => date('c'),
                ],
            )->withNetworkInfo(
                NetworkHelper::getClientIp($request),
                $request->getHeaderLine('User-Agent') ?: 'Unknown',
            );

            $this->activityLoggingService->log($activityDto);

            return $this->json($response, [
                'success' => true,
                'message' => '註冊成功',
                'data' => $user,
            ], 201);
        } catch (Exception $e) {
            $responseData = json_decode($this->handleException($e), true);

            return $this->json($response, $responseData, $responseData['error']['code'] ?? 500);
        }
    }

    #[OA\Post(
        path: '/api/auth/login',
        summary: '使用者登入',
        description: '使用帳號密碼或電子郵件密碼進行登入驗證',
        operationId: 'loginUser',
        tags: ['auth'],
        requestBody: new OA\RequestBody(
            description: '登入憑證',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest'),
        ),
        responses: [
            new OA\Response(response: 200, description: '登入成功'),
            new OA\Response(response: 401, description: '登入失敗'),
        ],
    )]
    public function login(Request $request, Response $response): Response
    {
        try {
            $credentials = $request->getParsedBody();

            if (!is_array($credentials) || !isset($credentials['email'], $credentials['password'])) {
                return $this->json($response, [
                    'success' => false,
                    'error' => '缺少必要的登入資料',
                ], 400);
            }

            $loginRequest = LoginRequestDTO::fromArray($credentials);

            $deviceInfo = DeviceInfo::fromUserAgent(
                userAgent: $request->getHeaderLine('User-Agent') ?: 'Unknown',
                ipAddress: NetworkHelper::getClientIp($request),
                deviceName: $credentials['device_name'] ?? null,
            );

            $loginResponse = $this->authenticationService->login($loginRequest, $deviceInfo);

            // 記錄成功登入活動
            $activityDto = CreateActivityLogDTO::success(
                actionType: ActivityType::LOGIN_SUCCESS,
                userId: $loginResponse->userId,
                metadata: [
                    'email' => $loginRequest->email,
                    'device_info' => $deviceInfo->toArray(),
                    'login_timestamp' => date('c'),
                ],
            )->withNetworkInfo($deviceInfo->getIpAddress(), $deviceInfo->getUserAgent());

            $this->activityLoggingService->log($activityDto);

            return $this->json($response, [
                'success' => true,
                'message' => '登入成功',
                ...$loginResponse->toArray(),
            ], 200);
        } catch (Exception $e) {
            if (isset($credentials['email'])) {
                $this->logLoginFailure($request, $credentials['email'], $e->getMessage());
            }
            $responseData = json_decode($this->handleException($e), true);

            return $this->json($response, $responseData, $responseData['error']['code'] ?? 500);
        }
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: '使用者登出',
        tags: ['auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '登出成功'),
        ],
    )]
    public function logout(Request $request, Response $response): Response
    {
        try {
            $requestData = $request->getParsedBody();
            $accessToken = $request->getAttribute('access_token');

            if (is_array($requestData)) {
                $refreshToken = $requestData['refresh_token'] ?? null;
            }

            $logoutRequest = LogoutRequestDTO::fromArray([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken ?? null,
                'revoke_all_tokens' => $requestData['logout_all_devices'] ?? false,
            ]);

            $this->authenticationService->logout($logoutRequest);

            $activityDto = CreateActivityLogDTO::success(
                actionType: ActivityType::LOGOUT,
                userId: $request->getAttribute('user_id'),
                description: '使用者登出',
                metadata: [
                    'logout_timestamp' => date('c'),
                ],
            )->withNetworkInfo(
                NetworkHelper::getClientIp($request),
                $request->getHeaderLine('User-Agent') ?: 'Unknown',
            );

            $this->activityLoggingService->log($activityDto);

            return $this->json($response, [
                'success' => true,
                'message' => '登出成功',
            ], 200);
        } catch (Exception $e) {
            $responseData = json_decode($this->handleException($e), true);

            return $this->json($response, $responseData, $responseData['error']['code'] ?? 500);
        }
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: '取得當前使用者資訊',
        tags: ['auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '成功取得使用者資訊'),
        ],
    )]
    public function me(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            $payload = $request->getAttribute('jwt_payload');

            if (!$userId) {
                return $this->json($response, ['success' => false, 'error' => '未授權存取'], 401);
            }

            $userWithRoles = $this->userRepository->findByIdWithRoles($userId);

            if (!$userWithRoles) {
                return $this->json($response, ['success' => false, 'error' => '使用者不存在'], 404);
            }

            return $this->json($response, [
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $userWithRoles['id'],
                        'email' => $userWithRoles['email'],
                        'name' => $userWithRoles['name'] ?? null,
                        'username' => $userWithRoles['username'] ?? null,
                        'roles' => $userWithRoles['roles'] ?? [],
                    ],
                    'token_info' => [
                        'issued_at' => $payload->getIssuedAt()->getTimestamp(),
                        'expires_at' => $payload->getExpiresAt()->getTimestamp(),
                    ],
                ],
            ], 200);
        } catch (Exception $e) {
            $responseData = json_decode($this->handleException($e), true);

            return $this->json($response, $responseData, $responseData['error']['code'] ?? 500);
        }
    }

    #[OA\Post(
        path: '/api/auth/refresh',
        summary: '刷新認證 Token',
        tags: ['auth'],
        responses: [
            new OA\Response(response: 200, description: 'Token 刷新成功'),
        ],
    )]
    public function refresh(Request $request, Response $response): Response
    {
        try {
            $requestData = $request->getParsedBody();

            if (!is_array($requestData) || !isset($requestData['refresh_token'])) {
                return $this->json($response, ['success' => false, 'error' => '缺少必要的 refresh_token'], 400);
            }

            $refreshRequest = RefreshRequestDTO::fromArray($requestData);
            $deviceInfo = DeviceInfo::fromUserAgent(
                userAgent: $request->getHeaderLine('User-Agent') ?: 'Unknown',
                ipAddress: NetworkHelper::getClientIp($request),
            );

            $refreshResponse = $this->authenticationService->refresh($refreshRequest, $deviceInfo);

            return $this->json($response, [
                'success' => true,
                'message' => 'Token 刷新成功',
                ...$refreshResponse->toArray(),
            ], 200);
        } catch (Exception $e) {
            $responseData = json_decode($this->handleException($e), true);

            return $this->json($response, $responseData, $responseData['error']['code'] ?? 500);
        }
    }

    private function logLoginFailure(Request $request, string $email, string $errorMessage): void
    {
        try {
            $activityDto = CreateActivityLogDTO::failure(
                actionType: ActivityType::LOGIN_FAILED,
                description: $errorMessage,
                metadata: [
                    'email' => $email,
                    'timestamp' => date('c'),
                ],
            )->withNetworkInfo(
                NetworkHelper::getClientIp($request),
                $request->getHeaderLine('User-Agent') ?: 'Unknown',
            );

            $this->activityLoggingService->log($activityDto);
        } catch (Exception) {
            // Ignore logging errors
        }
    }

    #[OA\Put(
        path: '/api/auth/profile',
        summary: '更新個人資料',
        tags: ['auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '更新成功'),
        ],
    )]
    public function updateProfile(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            if (!$userId) {
                return $this->json($response, ['success' => false, 'error' => '未授權存取'], 401);
            }

            $data = $request->getParsedBody();
            if (!is_array($data)) {
                return $this->json($response, ['success' => false, 'error' => '無效的請求資料格式'], 400);
            }

            $this->userRepository->update($userId, array_intersect_key($data, array_flip(['username', 'email', 'name'])));
            $user = $this->userRepository->findByIdWithRoles($userId);

            return $this->json($response, [
                'success' => true,
                'message' => '個人資料更新成功',
                'data' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'name' => $user['name'] ?? null,
                    'roles' => $user['roles'] ?? [],
                ],
            ], 200);
        } catch (Exception $e) {
            $responseData = json_decode($this->handleException($e), true);

            return $this->json($response, $responseData, $responseData['error']['code'] ?? 500);
        }
    }

    #[OA\Post(
        path: '/api/auth/change-password',
        summary: '變更密碼',
        tags: ['auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '密碼變更成功'),
        ],
    )]
    public function changePassword(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            if (!$userId) {
                return $this->json($response, ['success' => false, 'error' => '未授權存取'], 401);
            }

            $data = $request->getParsedBody();
            if (!is_array($data) || empty($data['current_password']) || empty($data['new_password'])) {
                return $this->json($response, ['success' => false, 'error' => '缺少必要的密碼資料'], 400);
            }

            if (($data['new_password'] ?? '') !== ($data['new_password_confirmation'] ?? '')) {
                return $this->json($response, ['success' => false, 'error' => '新密碼與確認密碼不符'], 422);
            }

            $this->userManagementService->changePassword($userId, $data['current_password'], $data['new_password']);

            $activityDto = CreateActivityLogDTO::success(
                actionType: ActivityType::PASSWORD_CHANGED,
                userId: $userId,
                description: '密碼變更成功',
            )->withNetworkInfo(
                NetworkHelper::getClientIp($request),
                $request->getHeaderLine('User-Agent') ?: 'Unknown',
            );

            $this->activityLoggingService->log($activityDto);

            return $this->json($response, [
                'success' => true,
                'message' => '密碼變更成功',
            ], 200);
        } catch (Exception $e) {
            $responseData = json_decode($this->handleException($e), true);

            return $this->json($response, $responseData, $responseData['error']['code'] ?? 500);
        }
    }
}
