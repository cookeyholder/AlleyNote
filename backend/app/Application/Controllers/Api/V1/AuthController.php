<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\DTOs\LoginRequestDTO;
use App\Domains\Auth\DTOs\LogoutRequestDTO;
use App\Domains\Auth\DTOs\RefreshRequestDTO;
use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Domains\Auth\Services\AuthService;
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
use Throwable;

/**
 * JWT 認證 Controller.
 */
class AuthController extends BaseController
{
    public function __construct(
        private AuthenticationServiceInterface $authService,
        private JwtTokenServiceInterface $jwtService,
        private ActivityLoggingServiceInterface $activityLogger,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * 取得客戶端 IP 位址.
     */
    private function getClientIp(Request $request): string
    {
        // 優先從 Header 取得真實 IP
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // 代理
            'HTTP_X_FORWARDED_FOR',      // 負載平衡器
            'HTTP_X_FORWARDED',          // 代理
            'HTTP_X_CLUSTER_CLIENT_IP',  // 集群
            'HTTP_FORWARDED_FOR',        // 代理
            'HTTP_FORWARDED',            // 代理
            'REMOTE_ADDR',               // 標準
        ];

        // 從 Header 取得
        $requestHeaders = $request->getHeaders();
        foreach ($headers as $header) {
            $headerKey = str_replace('HTTP_', '', $header);
            $headerKey = str_replace('_', '-', strtolower($headerKey));

            if (isset($requestHeaders[$headerKey])) {
                $ip = $requestHeaders[$headerKey][0] ?? '';
                if (is_string($ip)) {
                    $ip = trim(explode(',', $ip)[0]);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
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

        // 預設值
        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * 取得裝置資訊.
     */
    private function getDeviceInfo(Request $request): DeviceInfo
    {
        $headers = $request->getHeaders();
        $userAgent = $headers['User-Agent'][0] ?? 'Unknown';
        $acceptLanguage = $headers['Accept-Language'][0] ?? 'en';

        return new DeviceInfo(
            userAgent: $userAgent,
            ipAddress: $this->getClientIp($request),
            acceptLanguage: $acceptLanguage,
        );
    }

    #[OA\Post(
        path: '/api/v1/auth/register',
        summary: '使用者註冊',
        description: '註冊新的使用者帳號',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'display_name'],
                properties: [
                    'email' => new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    'password' => new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'password123'),
                    'display_name' => new OA\Property(property: 'display_name', type: 'string', maxLength: 100, example: 'John Doe'),
                    'bio' => new OA\Property(property: 'bio', type: 'string', maxLength: 500, example: 'Software developer'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: '註冊成功',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'message' => new OA\Property(property: 'message', type: 'string', example: '註冊成功'),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                'user_id' => new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                'email' => new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                                'display_name' => new OA\Property(property: 'display_name', type: 'string', example: 'John Doe'),
                            ],
                        ),
                        'tokens' => new OA\Property(
                            property: 'tokens',
                            type: 'object',
                            properties: [
                                'access_token' => new OA\Property(property: 'access_token', type: 'string'),
                                'refresh_token' => new OA\Property(property: 'refresh_token', type: 'string'),
                                'expires_in' => new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                                'token_type' => new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: '驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: false),
                        'error' => new OA\Property(property: 'error', type: 'string', example: '驗證失敗'),
                        'errors' => new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string'),
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 409,
                description: '電子郵件已存在',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: false),
                        'error' => new OA\Property(property: 'error', type: 'string', example: '電子郵件已存在'),
                    ],
                ),
            ),
        ],
        tags: ['Authentication'],
    )]
    public function register(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            if (!is_array($data)) {
                throw new InvalidArgumentException('Invalid request data');
            }

            // 建立註冊 DTO
            $registerDTO = new RegisterUserDTO(
                email: $data['email'] ?? '',
                password: $data['password'] ?? '',
                displayName: $data['display_name'] ?? '',
                bio: $data['bio'] ?? null,
            );

            // 驗證資料
            $validationResult = $this->validator->validate($registerDTO);
            if (!$validationResult->isValid()) {
                $responseData = [
                    'success' => false,
                    'error' => '驗證失敗',
                    'errors' => $validationResult->getErrors(),
                ];
                $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // 執行註冊
            $deviceInfo = $this->getDeviceInfo($request);
            $authResult = $this->authService->register($registerDTO, $deviceInfo);

            // 記錄活動
            $activityLog = CreateActivityLogDTO::userActivity(
                userId: $authResult->getUser()->getId(),
                actionType: ActivityType::USER_REGISTERED,
                description: '使用者註冊成功',
                ipAddress: $deviceInfo->getIpAddress(),
                userAgent: $deviceInfo->getUserAgent(),
            );
            $this->activityLogger->log($activityLog);

            // 回傳成功回應
            $responseData = [
                'success' => true,
                'message' => '註冊成功',
                'data' => [
                    'user_id' => $authResult->getUser()->getId(),
                    'email' => $authResult->getUser()->getEmail(),
                    'display_name' => $authResult->getUser()->getDisplayName(),
                ],
                'tokens' => [
                    'access_token' => $authResult->getAccessToken(),
                    'refresh_token' => $authResult->getRefreshToken(),
                    'expires_in' => $authResult->getExpiresIn(),
                    'token_type' => 'Bearer',
                ],
            ];

            $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'error' => '驗證失敗',
                'errors' => $e->getErrors(),
            ];
            $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $responseData = [
                'success' => false,
                'error' => '註冊失敗',
            ];
            $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: '使用者登入',
        description: '使用電子郵件和密碼登入',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    'email' => new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    'password' => new OA\Property(property: 'password', type: 'string', example: 'password123'),
                    'remember_me' => new OA\Property(property: 'remember_me', type: 'boolean', example: false),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '登入成功',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'message' => new OA\Property(property: 'message', type: 'string', example: '登入成功'),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                'user_id' => new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                'email' => new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                                'display_name' => new OA\Property(property: 'display_name', type: 'string', example: 'John Doe'),
                            ],
                        ),
                        'tokens' => new OA\Property(
                            property: 'tokens',
                            type: 'object',
                            properties: [
                                'access_token' => new OA\Property(property: 'access_token', type: 'string'),
                                'refresh_token' => new OA\Property(property: 'refresh_token', type: 'string'),
                                'expires_in' => new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                                'token_type' => new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: '驗證失敗或登入資料不完整',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: false),
                        'error' => new OA\Property(property: 'error', type: 'string', example: '缺少必要的登入資料'),
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: '認證失敗',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: false),
                        'error' => new OA\Property(property: 'error', type: 'string', example: '電子郵件或密碼錯誤'),
                    ],
                ),
            ),
        ],
        tags: ['Authentication'],
    )]
    public function login(Request $request, Response $response): Response
    {
        try {
            $credentials = $request->getParsedBody();

            // 驗證輸入資料
            if (!is_array($credentials) || !isset($credentials['email'], $credentials['password'])) {
                $responseData = [
                    'success' => false,
                    'error' => '缺少必要的登入資料',
                ];
                $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // 建立登入 DTO
            $loginDTO = new LoginRequestDTO(
                email: $credentials['email'],
                password: $credentials['password'],
                rememberMe: $credentials['remember_me'] ?? false,
            );

            // 執行登入
            $deviceInfo = $this->getDeviceInfo($request);
            $authResult = $this->authService->login($loginDTO, $deviceInfo);

            // 記錄活動
            $activityLog = CreateActivityLogDTO::userActivity(
                userId: $authResult->getUser()->getId(),
                actionType: ActivityType::USER_LOGIN_SUCCESS,
                description: '使用者登入成功',
                ipAddress: $deviceInfo->getIpAddress(),
                userAgent: $deviceInfo->getUserAgent(),
            );
            $this->activityLogger->log($activityLog);

            // 回傳成功回應
            $responseData = [
                'success' => true,
                'message' => '登入成功',
                'data' => [
                    'user_id' => $authResult->getUser()->getId(),
                    'email' => $authResult->getUser()->getEmail(),
                    'display_name' => $authResult->getUser()->getDisplayName(),
                ],
                'tokens' => [
                    'access_token' => $authResult->getAccessToken(),
                    'refresh_token' => $authResult->getRefreshToken(),
                    'expires_in' => $authResult->getExpiresIn(),
                    'token_type' => 'Bearer',
                ],
            ];

            $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (ValidationException $e) {
            // 記錄失敗的登入嘗試
            if (isset($credentials['email'])) {
                $activityLog = CreateActivityLogDTO::securityEvent(
                    actionType: ActivityType::USER_LOGIN_FAILED,
                    description: '使用者登入失敗: ' . $e->getMessage(),
                    ipAddress: $this->getClientIp($request),
                    userAgent: $request->getHeaders()['User-Agent'][0] ?? 'Unknown',
                    metadata: ['email' => $credentials['email']],
                );
                $this->activityLogger->log($activityLog);
            }

            $responseData = [
                'success' => false,
                'error' => '電子郵件或密碼錯誤',
            ];
            $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $responseData = [
                'success' => false,
                'error' => '登入失敗',
            ];
            $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: '使用者登出',
        description: '登出並撤銷 token',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    'refresh_token' => new OA\Property(property: 'refresh_token', type: 'string', description: '可選的 refresh token'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '登出成功',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'message' => new OA\Property(property: 'message', type: 'string', example: '登出成功'),
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: '未認證',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: false),
                        'error' => new OA\Property(property: 'error', type: 'string', example: '未認證'),
                    ],
                ),
            ),
        ],
        tags: ['Authentication'],
    )]
    public function logout(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $authHeader = $request->getHeaderLine('Authorization');

            // 提取 access token
            $accessToken = null;
            if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
                $accessToken = $matches[1];
            }

            if (!$accessToken) {
                $responseData = [
                    'success' => false,
                    'error' => '未提供有效的 token',
                ];
                $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            // 建立登出 DTO
            $logoutDTO = new LogoutRequestDTO(
                accessToken: $accessToken,
                refreshToken: is_array($data) ? ($data['refresh_token'] ?? null) : null,
            );

            // 執行登出
            $deviceInfo = $this->getDeviceInfo($request);
            $this->authService->logout($logoutDTO, $deviceInfo);

            // 回傳成功回應
            $responseData = [
                'success' => true,
                'message' => '登出成功',
            ];
            $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (Exception $e) {
            error_log('Logout error: ' . $e->getMessage());
            $responseData = [
                'success' => false,
                'error' => '登出失敗',
            ];
            $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/refresh',
        summary: '更新 access token',
        description: '使用 refresh token 更新 access token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [
                    'refresh_token' => new OA\Property(property: 'refresh_token', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token 更新成功',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'tokens' => new OA\Property(
                            property: 'tokens',
                            type: 'object',
                            properties: [
                                'access_token' => new OA\Property(property: 'access_token', type: 'string'),
                                'refresh_token' => new OA\Property(property: 'refresh_token', type: 'string'),
                                'expires_in' => new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                                'token_type' => new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: '缺少 refresh token',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: false),
                        'error' => new OA\Property(property: 'error', type: 'string', example: '缺少 refresh token'),
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Refresh token 無效或已過期',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: false),
                        'error' => new OA\Property(property: 'error', type: 'string', example: 'Refresh token 無效'),
                    ],
                ),
            ),
        ],
        tags: ['Authentication'],
    )]
    public function refresh(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            if (!is_array($data) || !isset($data['refresh_token'])) {
                $responseData = [
                    'success' => false,
                    'error' => '缺少 refresh token',
                ];
                $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // 建立更新 DTO
            $refreshDTO = new RefreshRequestDTO(
                refreshToken: $data['refresh_token'],
            );

            // 執行 token 更新
            $deviceInfo = $this->getDeviceInfo($request);
            $authResult = $this->authService->refresh($refreshDTO, $deviceInfo);

            // 回傳成功回應
            $responseData = [
                'success' => true,
                'tokens' => [
                    'access_token' => $authResult->getAccessToken(),
                    'refresh_token' => $authResult->getRefreshToken(),
                    'expires_in' => $authResult->getExpiresIn(),
                    'token_type' => 'Bearer',
                ],
            ];

            $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (Exception $e) {
            error_log('Token refresh error: ' . $e->getMessage());
            $responseData = [
                'success' => false,
                'error' => 'Refresh token 無效或已過期',
            ];
            $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
}
