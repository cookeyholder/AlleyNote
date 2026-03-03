<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\DTOs\ForgotPasswordRequestDTO;
use App\Domains\Auth\DTOs\LoginRequestDTO;
use App\Domains\Auth\DTOs\LogoutRequestDTO;
use App\Domains\Auth\DTOs\RefreshRequestDTO;
use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Domains\Auth\DTOs\ResetPasswordDTO;
use App\Domains\Auth\Services\AuthService;
use App\Domains\Auth\Services\PasswordResetService;
use App\Domains\Auth\Services\UserManagementService;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityType;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Exception;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * JWT 認證 Controller.
 *
 * 處理 JWT 認證相關的 API 端點，包含登入、登出、token 刷新、使用者資訊等功能。
 * 整合 DTO 驗證、例外處理、HTTP 回應格式。
 *
 * @author GitHub Copilot
 * @since 1.0.0
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
        private PasswordResetService $passwordResetService,
    ) {}

    /**
     * 取得客戶端真實 IP 位址
     */
    private function getClientIpAddress(Request $request): string
    {
        // 檢查各種可能包含真實 IP 的標頭
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load Balancer/Proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR',               // Standard
        ];

        foreach ($headers as $header) {
            if ($request->hasHeader($header)) {
                $ip = $request->getHeaderLine($header);
                // 處理多個 IP（以逗號分隔的情況）
                $ip = trim(explode(',', $ip)[0]);

                // 驗證 IP 格式
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // 從伺服器參數取得
        $serverParams = $request->getServerParams();
        foreach ($headers as $header) {
            if (isset($serverParams[$header])) {
                $ip = $serverParams[$header];
                if (is_string($ip)) {
                    $ip = trim(explode(',', $ip)[0]);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        // 預設回傳 localhost（適用於開發環境）
        return '127.0.0.1';
    }

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
                    new OA\Property(
                        property: 'username',
                        type: 'string',
                        description: '使用者名稱',
                        minLength: 3,
                        maxLength: 50,
                        example: 'johndoe',
                    ),
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        description: '電子郵件地址',
                        example: 'john@example.com',
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        description: '密碼，至少8個字元',
                        minLength: 8,
                        example: 'password123',
                    ),
                    new OA\Property(
                        property: 'password_confirmation',
                        type: 'string',
                        format: 'password',
                        description: '確認密碼，必須與密碼相同',
                        example: 'password123',
                    ),
                ],
                required: ['username', 'email', 'password', 'password_confirmation'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: '註冊成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '註冊成功'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                                new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                                new OA\Property(property: 'role', type: 'string', example: 'user'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00Z'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: '註冊資料驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '資料驗證失敗'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string'),
                            ),
                            example: [
                                'username' => ['使用者名稱已存在'],
                                'email' => ['電子郵件格式不正確'],
                                'password' => ['密碼長度不足8個字元'],
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 409,
                description: '使用者名稱或電子郵件已存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '使用者名稱或電子郵件已存在'),
                    ],
                ),
            ),
            new OA\Response(
                response: 500,
                description: '伺服器內部錯誤',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '系統發生錯誤'),
                    ],
                ),
            ),
        ],
    )]
    public function register(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // 確保 $data 是陣列
            if ($data === null || !is_array($data)) {
                $errorResponse = json_encode([
                    'success' => false,
                    'message' => 'Invalid request data format',
                    'error_code' => 400,
                ]) ?: '{"error": "JSON encoding failed"}';

                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $payload = $this->filterStringKeys($data);

            $dto = new RegisterUserDTO($this->validator, $payload);
            $registrationResult = $this->authService->register($dto);

            $normalizedResult = $this->filterStringKeys($registrationResult);
            $userData = $this->extractUserData($registrationResult);
            $userId = $this->toIntOrNull($userData['id'] ?? ($normalizedResult['id'] ?? null));
            $metadata = $this->buildRegistrationMetadata($userData);

            // 記錄成功註冊活動
            $activityDto = CreateActivityLogDTO::success(
                actionType: ActivityType::USER_REGISTERED,
                userId: $userId,
                metadata: $metadata,
            )->withNetworkInfo(
                $this->getClientIpAddress($request),
                $request->getHeaderLine('User-Agent') ?: 'Unknown',
            );

            $this->activityLoggingService->log($activityDto);

            $responseData = [
                'success' => (bool) ($normalizedResult['success'] ?? true),
                'message' => $this->toStringOrNull($normalizedResult['message'] ?? null) ?? '註冊成功',
                'data' => $userData,
            ];

            if (isset($normalizedResult['tokens']) && is_array($normalizedResult['tokens'])) {
                $responseData['tokens'] = $normalizedResult['tokens'];
            }

            $response->getBody()->write(json_encode($responseData) ?: '{}');

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤: ' . $e->getMessage(),
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
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
            content: new OA\JsonContent(
                ref: '#/components/schemas/LoginRequest',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '登入成功',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LoginResponse',
                ),
            ),
            new OA\Response(
                response: 400,
                description: '登入資料格式錯誤',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '缺少必要的登入資料'),
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: '登入失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '使用者名稱或密碼錯誤'),
                    ],
                ),
            ),
            new OA\Response(
                response: 423,
                description: '帳號被鎖定',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '帳號暫時被鎖定，請稍後再試'),
                        new OA\Property(property: 'retry_after', type: 'integer', description: '解鎖剩餘秒數', example: 300),
                    ],
                ),
            ),
            new OA\Response(
                response: 500,
                description: '伺服器內部錯誤',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '系統發生錯誤'),
                    ],
                ),
            ),
        ],
    )]
    public function login(Request $request, Response $response): Response
    {
        $credentials = $request->getParsedBody();

        try {
            if (!is_array($credentials)) {
                $responseData = [
                    'success' => false,
                    'error' => '缺少必要的登入資料',
                ];
                $response->getBody()->write(json_encode($responseData) ?: '{}');

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            /** @var array<mixed, mixed> $credentials */

            $payload = $this->filterStringKeys($credentials);
            $email = $this->toStringOrNull($payload['email'] ?? null);
            $password = $this->toStringOrNull($payload['password'] ?? null);

            if ($email === null || $password === null || $email === '' || $password === '') {
                $responseData = [
                    'success' => false,
                    'error' => '缺少必要的登入資料',
                ];
                $response->getBody()->write(json_encode($responseData) ?: '{}');

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            // 此時 $email 保證是 non-empty-string

            // 建立登入請求 DTO
            $loginRequest = LoginRequestDTO::fromArray([
                'email' => $email,
                'password' => $password,
                'remember_me' => $this->toBool($payload['remember_me'] ?? false),
                'scopes' => $this->extractScopes($payload['scopes'] ?? null),
            ]);

            // 建立裝置資訊
            $deviceInfo = DeviceInfo::fromUserAgent(
                userAgent: $request->getHeaderLine('User-Agent') ?: 'Unknown',
                ipAddress: $this->getClientIpAddress($request),
                deviceName: $this->toStringOrNull($payload['device_name'] ?? null),
            );

            // 執行 JWT 認證登入
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

            // 使用 DTO 內建的 toArray 方法
            $result = [
                'success' => true,
                'message' => '登入成功',
                ...$loginResponse->toArray(),
            ];

            $response->getBody()->write(json_encode($result) ?: '{}');

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            // 記錄登入失敗 - 驗證錯誤
            // $credentials 在此必定為 array，因為前面已驗證，PHPStan 自動推斷型別
            $this->logLoginFailureIfPossible($request, $credentials, $e->getMessage());

            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            // 記錄登入失敗 - 使用者不存在
            // $credentials 在此必定為 array，因為前面已驗證，PHPStan 自動推斷型別
            $this->logLoginFailureIfPossible($request, $credentials, '使用者名稱或密碼錯誤');

            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            // 記錄登入失敗 - 密碼錯誤或其他驗證失敗
            // $credentials 在此必定為 array，因為前面已驗證，PHPStan 自動推斷型別
            $this->logLoginFailureIfPossible($request, $credentials, '使用者名稱或密碼錯誤');

            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            // 記錄登入失敗 - 系統錯誤
            // $credentials 在此必定為 array，因為前面已驗證，PHPStan 自動推斷型別
            $this->logLoginFailureIfPossible($request, $credentials, '系統發生錯誤');

            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤: ' . $e->getMessage(),
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: '使用者登出',
        description: '登出當前使用者，清除認證狀態和會話',
        operationId: 'logoutUser',
        tags: ['auth'],
        security: [
            ['bearerAuth' => []],
            ['sessionAuth' => []],
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '登出成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '登出成功'),
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: '未授權存取或 Token 無效',
                content: new OA\JsonContent(
                    ref: '#/components/responses/Unauthorized',
                ),
            ),
        ],
    )]
    public function logout(Request $request, Response $response): Response
    {
        try {
            $requestData = $request->getParsedBody();

            // 從 Authorization header 或 request body 取得 access token
            $authHeader = $request->getHeaderLine('Authorization');
            $accessToken = null;
            if ($authHeader !== '') {
                if (str_starts_with($authHeader, 'Bearer ')) {
                    $accessToken = substr($authHeader, 7);
                }
            }

            $payload = is_array($requestData) ? $this->filterStringKeys($requestData) : [];
            $bodyAccessToken = $this->toStringOrNull($payload['access_token'] ?? null);
            if ($bodyAccessToken !== null) {
                $accessToken = $bodyAccessToken;
            }

            $refreshToken = $this->toStringOrNull($payload['refresh_token'] ?? null);
            $logoutAllDevices = $this->toBool($payload['logout_all_devices'] ?? false);

            // 建立登出請求 DTO
            $logoutRequest = LogoutRequestDTO::fromArray([
                'access_token' => $accessToken ?? '',
                'refresh_token' => $refreshToken,
                'revoke_all_tokens' => $logoutAllDevices,
            ]);

            // 建立裝置資訊
            $deviceInfo = DeviceInfo::fromUserAgent(
                userAgent: $request->getHeaderLine('User-Agent') ?: 'Unknown',
                ipAddress: $this->getClientIpAddress($request),
                deviceName: $this->toStringOrNull($payload['device_name'] ?? null),
            );

            // 執行登出
            $this->authenticationService->logout($logoutRequest);

            // 記錄成功登出活動
            // 注意：此時可能無法取得使用者ID，因為token可能已失效
            $activityDto = CreateActivityLogDTO::success(
                actionType: ActivityType::LOGOUT,
                userId: null, // 登出時通常無法確定使用者ID
                description: '使用者登出',
                metadata: [
                    'logout_timestamp' => date('c'),
                    'logout_all_devices' => $logoutAllDevices,
                ],
            )->withNetworkInfo(
                $this->getClientIpAddress($request),
                $request->getHeaderLine('User-Agent') ?: 'Unknown',
            );

            $this->activityLoggingService->log($activityDto);

            $responseData = [
                'success' => true,
                'message' => '登出成功',
            ];

            $response->getBody()->write(json_encode($responseData) ?: '{}');

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤: ' . $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: '取得當前使用者資訊',
        description: '取得目前登入使用者的詳細資訊',
        operationId: 'getCurrentUser',
        tags: ['auth'],
        security: [
            ['bearerAuth' => []],
            ['sessionAuth' => []],
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得使用者資訊',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: '未授權存取或 Token 無效',
                content: new OA\JsonContent(
                    ref: '#/components/responses/Unauthorized',
                ),
            ),
        ],
    )]
    public function me(Request $request, Response $response): Response
    {
        try {
            // 從 Authorization header 取得 access token
            $authHeader = $request->getHeaderLine('Authorization');
            if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
                $responseData = [
                    'success' => false,
                    'error' => '缺少有效的 Authorization header',
                ];
                $response->getBody()->write((json_encode($responseData) ?: '{}'));

                return $response
                    ->withStatus(401)
                    ->withHeader('Content-Type', 'application/json');
            }

            $accessToken = substr($authHeader, 7);

            try {
                // 驗證 token 並取得使用者 payload
                $payload = $this->jwtTokenService->validateAccessToken($accessToken);
                $userId = $payload->getUserId();

                // 從資料庫取得完整的使用者資訊（包含角色）
                $userWithRoles = $this->userRepository->findByIdWithRoles($userId);

                if (!$userWithRoles) {
                    throw new NotFoundException('使用者不存在');
                }

                $userInfo = [
                    'user_id' => $userId,
                    'email' => $userWithRoles['email'],
                    'name' => $userWithRoles['name'] ?? null,
                    'username' => $userWithRoles['username'] ?? null,
                    'roles' => $userWithRoles['roles'] ?? [],
                    'token_issued_at' => $payload->getIssuedAt()->getTimestamp(),
                    'token_expires_at' => $payload->getExpiresAt()->getTimestamp(),
                ];
            } catch (Exception $e) {
                $userInfo = null;
            }

            if (!$userInfo) {
                $responseData = [
                    'success' => false,
                    'error' => 'Token 無效或使用者不存在',
                ];
                $response->getBody()->write((json_encode($responseData) ?: '{}'));

                return $response
                    ->withStatus(401)
                    ->withHeader('Content-Type', 'application/json');
            }

            $responseData = [
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $userInfo['user_id'],
                        'email' => $userInfo['email'],
                        'name' => $userInfo['name'],
                        'username' => $userInfo['username'],
                        'roles' => $userInfo['roles'],
                    ],
                    'token_info' => [
                        'issued_at' => $userInfo['token_issued_at'],
                        'expires_at' => $userInfo['token_expires_at'],
                    ],
                ],
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤: ' . $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: '/api/auth/refresh',
        summary: '刷新認證 Token',
        description: '使用 Refresh Token 取得新的 Access Token',
        operationId: 'refreshToken',
        tags: ['auth'],
        requestBody: new OA\RequestBody(
            description: 'Refresh Token',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'refresh_token',
                        type: 'string',
                        description: '有效的 Refresh Token',
                        example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...',
                    ),
                ],
                required: ['refresh_token'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token 刷新成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Token 刷新成功'),
                        new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', description: 'Token 有效期（秒）', example: 3600),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', example: '2025-01-15T11:30:00Z'),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: '缺少或格式錯誤的 Refresh Token',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '無效的 refresh_token 格式'),
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Refresh Token 無效或已過期',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: 'Refresh token 無效或已過期'),
                    ],
                ),
            ),
        ],
    )]
    public function refresh(Request $request, Response $response): Response
    {
        try {
            $requestData = $request->getParsedBody();

            if (!is_array($requestData)) {
                $responseData = [
                    'success' => false,
                    'error' => '缺少必要的 refresh_token',
                ];
                $response->getBody()->write(json_encode($responseData) ?: '{}');

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            $payload = $this->filterStringKeys($requestData);
            $refreshToken = $this->toStringOrNull($payload['refresh_token'] ?? null);

            if ($refreshToken === null || $refreshToken === '') {
                $responseData = [
                    'success' => false,
                    'error' => '缺少必要的 refresh_token',
                ];
                $response->getBody()->write(json_encode($responseData) ?: '{}');

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            // 建立刷新請求 DTO
            $refreshRequest = RefreshRequestDTO::fromArray([
                'refresh_token' => $refreshToken,
                'scopes' => $this->extractScopes($payload['scopes'] ?? null),
            ]);

            // 建立裝置資訊
            $deviceInfo = DeviceInfo::fromUserAgent(
                userAgent: $request->getHeaderLine('User-Agent') ?: 'Unknown',
                ipAddress: $this->getClientIpAddress($request),
                deviceName: $this->toStringOrNull($payload['device_name'] ?? null),
            );

            // 執行 JWT token 刷新
            $refreshResponse = $this->authenticationService->refresh($refreshRequest, $deviceInfo);

            $result = [
                'success' => true,
                'message' => 'Token 刷新成功',
                ...$refreshResponse->toArray(),
            ];

            $response->getBody()->write(json_encode($result) ?: '{}');

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤: ' . $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: '/api/auth/forgot-password',
        summary: '請求密碼重設',
        description: '根據電子郵件產生密碼重設憑證並傳送通知。無論電子郵件是否存在都會回傳成功訊息。',
        operationId: 'forgotPassword',
        tags: ['auth'],
        requestBody: new OA\RequestBody(
            description: '忘記密碼請求資料',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        description: '註冊時使用的電子郵件',
                        example: 'user@example.com',
                    ),
                ],
                required: ['email'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '已回應密碼重設請求',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '如果帳號存在，我們已寄送密碼重設資訊。'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', example: '2025-01-15T11:30:00Z'),
                        new OA\Property(property: 'debug_token', type: 'string', example: 'abcdef0123456789'),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: '請求資料格式錯誤',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '無效的請求資料格式'),
                    ],
                ),
            ),
        ],
    )]
    public function forgotPassword(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                $responseData = [
                    'success' => false,
                    'error' => '無效的請求資料格式',
                ];
                $response->getBody()->write((json_encode($responseData) ?: '{}'));

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            /** @var array<string, mixed> $data */

            $dto = new ForgotPasswordRequestDTO($this->validator, $data);

            $clientIp = $this->getClientIpAddress($request);
            $userAgent = $request->getHeaderLine('User-Agent') ?: 'Unknown';

            $result = $this->passwordResetService->requestReset(
                $dto->email,
                $clientIp,
                $userAgent,
            );

            $responseData = [
                'success' => true,
                'message' => '如果帳號存在，我們已寄送密碼重設資訊。',
            ];

            $plainToken = $result->getPlainToken();
            $expiresAt = $result->getExpiresAt();
            if ($plainToken !== null && $expiresAt !== null) {
                $responseData['expires_at'] = $expiresAt->format(DATE_ATOM);

                $appEnv = getenv('APP_ENV') ?: 'development';
                if (strtolower($appEnv) !== 'production') {
                    $responseData['debug_token'] = $plainToken;
                }
            }

            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤: ' . $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: '/api/auth/reset-password',
        summary: '提交密碼重設',
        description: '使用有效的密碼重設憑證設定新的密碼。',
        operationId: 'resetPassword',
        tags: ['auth'],
        requestBody: new OA\RequestBody(
            description: '密碼重設資料',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'token', type: 'string', description: '密碼重設憑證', example: 'abcdef0123456789'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', description: '新密碼，至少 8 個字元', example: 'NewPassw0rd!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', description: '再次確認新密碼', example: 'NewPassw0rd!'),
                ],
                required: ['token', 'password', 'password_confirmation'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '密碼重設成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '密碼已成功重設。'),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: '資料驗證失敗或憑證無效',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '密碼重設連結無效或已過期'),
                    ],
                ),
            ),
        ],
    )]
    public function resetPassword(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                $responseData = [
                    'success' => false,
                    'error' => '無效的請求資料格式',
                ];
                $response->getBody()->write((json_encode($responseData) ?: '{}'));

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            /** @var array<string, mixed> $data */

            $dto = new ResetPasswordDTO($this->validator, $data);

            $clientIp = $this->getClientIpAddress($request);
            $userAgent = $request->getHeaderLine('User-Agent') ?: 'Unknown';

            $this->passwordResetService->resetPassword($dto, $clientIp, $userAgent);

            $responseData = [
                'success' => true,
                'message' => '密碼已成功重設。',
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤: ' . $e->getMessage(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 記錄登入失敗活動.
     */
    private function logLoginFailure(Request $request, string $email, string $errorMessage): void
    {
        try {
            $activityDto = CreateActivityLogDTO::failure(
                actionType: ActivityType::LOGIN_FAILED,
                description: $errorMessage,
                metadata: [
                    'email' => $email,
                    'error_message' => $errorMessage,
                    'timestamp' => date('c'),
                ],
            )->withNetworkInfo(
                $this->getClientIpAddress($request),
                $request->getHeaderLine('User-Agent') ?: 'Unknown',
            );

            $this->activityLoggingService->log($activityDto);
        } catch (Exception $e) {
            // 記錄活動失敗不應該影響主要流程，只記錄錯誤
            error_log('Failed to log login failure activity: ' . $e->getMessage());
        }
    }

    /**
     * 從 credentials 陣列提取 email 並記錄登入失敗活動。
     *
     * @param array<mixed, mixed>|object|null $credentials
     */
    private function logLoginFailureIfPossible(Request $request, array|object|null $credentials, string $errorMessage): void
    {
        if (!is_array($credentials)) {
            return;
        }

        $payload = $this->filterStringKeys($credentials);
        $email = $this->toStringOrNull($payload['email'] ?? null);

        if ($email !== null && $email !== '') {
            $this->logLoginFailure($request, $email, $errorMessage);
        }
    }

    /**
     * 更新個人資料.
     *
     * PUT /auth/profile
     */
    #[OA\Put(
        path: '/api/auth/profile',
        summary: '更新個人資料',
        description: '更新當前登入使用者的個人資料',
        operationId: 'updateProfile',
        tags: ['auth'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            description: '個人資料',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'username',
                        type: 'string',
                        description: '使用者名稱',
                        example: 'johndoe',
                    ),
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        description: '電子郵件地址',
                        example: 'john@example.com',
                    ),
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        description: '顯示名稱',
                        example: 'John Doe',
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '更新成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '個人資料更新成功'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                                new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                                new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: '未授權',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '未授權存取'),
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: '驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '資料驗證失敗'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ],
                ),
            ),
        ],
    )]
    public function updateProfile(Request $request, Response $response): Response
    {
        try {
            // 從 Authorization header 取得 access token
            $authHeader = $request->getHeaderLine('Authorization');
            if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
                $responseData = [
                    'success' => false,
                    'error' => '缺少有效的 Authorization header',
                ];
                $response->getBody()->write((json_encode($responseData) ?: '{}'));

                return $response
                    ->withStatus(401)
                    ->withHeader('Content-Type', 'application/json');
            }

            $accessToken = substr($authHeader, 7);

            // 驗證 token 並取得使用者 ID
            $payload = $this->jwtTokenService->validateAccessToken($accessToken);
            $userId = $payload->getUserId();

            // 取得更新資料
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                $responseData = [
                    'success' => false,
                    'error' => '無效的請求資料格式',
                ];
                $response->getBody()->write((json_encode($responseData) ?: '{}'));

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            $updateData = [];
            if (isset($data['username'])) {
                $updateData['username'] = $data['username'];
            }
            if (isset($data['email'])) {
                $updateData['email'] = $data['email'];
            }
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            // 更新使用者資料
            $this->userRepository->update($userId, $updateData);

            // 取得更新後的使用者資訊
            $user = $this->userRepository->findByIdWithRoles($userId);

            if ($user === null) {
                throw new NotFoundException('使用者不存在');
            }

            $responseData = [
                'success' => true,
                'message' => '個人資料更新成功',
                'data' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'name' => $user['name'] ?? null,
                    'roles' => $user['roles'] ?? [],
                ],
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(422)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤: ' . $e->getMessage(),
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 變更密碼
     *
     * POST /auth/change-password
     */
    #[OA\Post(
        path: '/api/auth/change-password',
        summary: '變更密碼',
        description: '變更當前登入使用者的密碼',
        operationId: 'changePassword',
        tags: ['auth'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            description: '密碼資料',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'current_password',
                        type: 'string',
                        format: 'password',
                        description: '當前密碼',
                        example: 'oldpassword123',
                    ),
                    new OA\Property(
                        property: 'new_password',
                        type: 'string',
                        format: 'password',
                        description: '新密碼，至少6個字元',
                        minLength: 6,
                        example: 'newpassword123',
                    ),
                    new OA\Property(
                        property: 'new_password_confirmation',
                        type: 'string',
                        format: 'password',
                        description: '確認新密碼，必須與新密碼相同',
                        example: 'newpassword123',
                    ),
                ],
                required: ['current_password', 'new_password', 'new_password_confirmation'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '密碼變更成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '密碼變更成功'),
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: '未授權',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'error', type: 'string', example: '未授權存取'),
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: '驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '資料驗證失敗'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ],
                ),
            ),
        ],
    )]
    public function changePassword(Request $request, Response $response): Response
    {
        try {
            // 從 Authorization header 取得 access token
            $authHeader = $request->getHeaderLine('Authorization');
            if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
                $responseData = [
                    'success' => false,
                    'error' => '缺少有效的 Authorization header',
                ];
                $response->getBody()->write((json_encode($responseData) ?: '{}'));

                return $response
                    ->withStatus(401)
                    ->withHeader('Content-Type', 'application/json');
            }

            $accessToken = substr($authHeader, 7);

            // 驗證 token 並取得使用者 ID
            $payload = $this->jwtTokenService->validateAccessToken($accessToken);
            $userId = $payload->getUserId();

            // 取得密碼資料
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                $responseData = [
                    'success' => false,
                    'error' => '無效的請求資料格式',
                ];
                $response->getBody()->write((json_encode($responseData) ?: '{}'));

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            // 驗證必填欄位
            if (empty($data['current_password']) || empty($data['new_password']) || empty($data['new_password_confirmation'])) {
                throw ValidationException::fromErrors([
                    'current_password' => empty($data['current_password']) ? ['當前密碼為必填'] : [],
                    'new_password' => empty($data['new_password']) ? ['新密碼為必填'] : [],
                    'new_password_confirmation' => empty($data['new_password_confirmation']) ? ['確認新密碼為必填'] : [],
                ]);
            }

            // 驗證新密碼確認
            if ($data['new_password'] !== $data['new_password_confirmation']) {
                throw ValidationException::fromSingleError('new_password_confirmation', '新密碼與確認密碼不符');
            }

            // 使用 UserManagementService 變更密碼
            $this->userManagementService->changePassword(
                $userId,
                $data['current_password'],
                $data['new_password'],
            );

            // 記錄密碼變更活動
            $activityDto = CreateActivityLogDTO::success(
                actionType: ActivityType::PASSWORD_CHANGED,
                userId: $userId,
                description: '密碼變更成功',
                metadata: [
                    'timestamp' => date('c'),
                ],
            )->withNetworkInfo(
                $this->getClientIpAddress($request),
                $request->getHeaderLine('User-Agent') ?: 'Unknown',
            );

            $this->activityLoggingService->log($activityDto);

            $responseData = [
                'success' => true,
                'message' => '密碼變更成功',
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ];
            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(422)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤: ' . $e->getMessage(),
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{}'));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 過濾陣列，只保留字串鍵的元素。
     *
     * @param array<mixed, mixed> $data
     * @return array<string, mixed>
     */
    private function filterStringKeys(array $data): array
    {
        $filtered = [];

        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * 轉換輸入值為字串，若不可轉換則回傳 null。
     */
    private function toStringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    /**
     * 將輸入值轉換為布林值。
     */
    private function toBool(mixed $value, bool $default = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        return $default;
    }

    /**
     * 嘗試將輸入值轉為整數。
     */
    private function toIntOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * 擷取註冊回傳中的使用者資料。
     *
     * @return array<string, mixed>
     */
    private function extractUserData(mixed $registrationResult): array
    {
        if (is_array($registrationResult)) {
            if (isset($registrationResult['user']) && is_array($registrationResult['user'])) {
                return $this->filterStringKeys($registrationResult['user']);
            }

            if (isset($registrationResult['id']) || isset($registrationResult['email']) || isset($registrationResult['username'])) {
                return $this->filterStringKeys($registrationResult);
            }
        }

        if (is_object($registrationResult)) {
            return $this->filterStringKeys(get_object_vars($registrationResult));
        }

        return [];
    }

    /**
     * 建立註冊活動需要的 metadata。
     *
     * @param array<string, mixed> $userData
     * @return array<string, mixed>
     */
    private function buildRegistrationMetadata(array $userData): array
    {
        $metadata = [
            'registration_timestamp' => date('c'),
        ];

        $email = $this->toStringOrNull($userData['email'] ?? null);
        if ($email !== null) {
            $metadata['email'] = $email;
        }

        $username = $this->toStringOrNull($userData['username'] ?? null);
        if ($username !== null) {
            $metadata['username'] = $username;
        }

        return $metadata;
    }

    /**
     * 過濾 scopes 參數為字串陣列。
     *
     * @return array<int, string>|null
     */
    private function extractScopes(mixed $scopes): ?array
    {
        if (!is_array($scopes)) {
            return null;
        }

        $normalized = [];
        foreach ($scopes as $scope) {
            $scopeString = $this->toStringOrNull($scope);
            if ($scopeString !== null) {
                $normalized[] = $scopeString;
            }
        }

        return $normalized === [] ? null : $normalized;
    }
}
