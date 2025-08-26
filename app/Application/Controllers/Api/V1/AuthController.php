<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use AlleyNote\Domains\Auth\DTOs\LoginRequestDTO;
use AlleyNote\Domains\Auth\DTOs\LogoutRequestDTO;
use AlleyNote\Domains\Auth\DTOs\RefreshRequestDTO;
use AlleyNote\Domains\Auth\Services\AuthenticationService;
use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use App\Application\Controllers\BaseController;
use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Domains\Auth\Services\AuthService;
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
        private AuthenticationService $authenticationService,
        private ValidatorInterface $validator,
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
        path: '/auth/register',
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
            $dto = new RegisterUserDTO($this->validator, $data);
            $user = $this->authService->register($dto);

            $responseData = [
                'success' => true,
                'message' => '註冊成功',
                'data' => $user,
            ];

            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];

            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤',
            ];

            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: '/auth/login',
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
        try {
            $credentials = $request->getParsedBody();

            // 驗證輸入資料
            if (!is_array($credentials) || !isset($credentials['email'], $credentials['password'])) {
                $responseData = [
                    'success' => false,
                    'error' => '缺少必要的登入資料',
                ];
                $response->getBody()->write(json_encode($responseData));

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            // 建立登入請求 DTO
            $loginRequest = LoginRequestDTO::fromArray($credentials);

            // 建立裝置資訊
            $deviceInfo = DeviceInfo::fromUserAgent(
                userAgent: $request->getHeaderLine('User-Agent') ?: 'Unknown',
                ipAddress: $this->getClientIpAddress($request),
                deviceName: $credentials['device_name'] ?? null,
            );

            // 執行 JWT 認證登入
            $loginResponse = $this->authenticationService->login($loginRequest, $deviceInfo);

            // 使用 DTO 內建的 toArray 方法
            $result = [
                'success' => true,
                'message' => '登入成功',
                ...$loginResponse->toArray(),
            ];

            $response->getBody()->write(json_encode($result));

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤',
            ];

            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: '/auth/logout',
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
            $accessToken = null;
            $refreshToken = null;

            // 先檢查 Authorization header
            $authHeader = $request->getHeaderLine('Authorization');
            if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
                $accessToken = substr($authHeader, 7);
            }

            // 如果在 body 中有提供 tokens，優先使用
            if (is_array($requestData)) {
                $accessToken = $requestData['access_token'] ?? $accessToken;
                $refreshToken = $requestData['refresh_token'] ?? null;
            }

            // 建立登出請求 DTO
            $logoutRequest = LogoutRequestDTO::fromArray([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'revoke_all_tokens' => $requestData['logout_all_devices'] ?? false,
            ]);

            // 建立裝置資訊
            $deviceInfo = DeviceInfo::fromUserAgent(
                userAgent: $request->getHeaderLine('User-Agent') ?: 'Unknown',
                ipAddress: $this->getClientIpAddress($request),
                deviceName: $requestData['device_name'] ?? null,
            );

            // 執行登出
            $this->authenticationService->logout($logoutRequest);

            $responseData = [
                'success' => true,
                'message' => '登出成功',
            ];

            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤',
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Get(
        path: '/auth/me',
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
                $response->getBody()->write(json_encode($responseData));

                return $response
                    ->withStatus(401)
                    ->withHeader('Content-Type', 'application/json');
            }

            $accessToken = substr($authHeader, 7);

            // 從 token 取得使用者資訊
            $userInfo = $this->authenticationService->getUserFromToken($accessToken);

            if (!$userInfo) {
                $responseData = [
                    'success' => false,
                    'error' => 'Token 無效或使用者不存在',
                ];
                $response->getBody()->write(json_encode($responseData));

                return $response
                    ->withStatus(401)
                    ->withHeader('Content-Type', 'application/json');
            }

            $user = $userInfo['user'];
            $tokenInfo = $userInfo['token_info'];

            $responseData = [
                'success' => true,
                'data' => [
                    'id' => $user['id'] ?? null,
                    'uuid' => $user['uuid'] ?? null,
                    'username' => $user['username'] ?? null,
                    'email' => $user['email'] ?? null,
                    'role' => $user['role'] ?? 'user',
                    'created_at' => $user['created_at'] ?? null,
                    'updated_at' => $user['updated_at'] ?? null,
                    'last_login_at' => $user['last_login_at'] ?? null,
                    'token_info' => $tokenInfo,
                ],
            ];

            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤',
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Post(
        path: '/auth/refresh',
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

            // 驗證輸入資料
            if (!is_array($requestData) || !isset($requestData['refresh_token'])) {
                $responseData = [
                    'success' => false,
                    'error' => '缺少必要的 refresh_token',
                ];
                $response->getBody()->write(json_encode($responseData));

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            // 建立刷新請求 DTO
            $refreshRequest = RefreshRequestDTO::fromArray($requestData);

            // 建立裝置資訊
            $deviceInfo = DeviceInfo::fromUserAgent(
                userAgent: $request->getHeaderLine('User-Agent') ?: 'Unknown',
                ipAddress: $this->getClientIpAddress($request),
                deviceName: $requestData['device_name'] ?? null,
            );

            // 執行 JWT token 刷新
            $refreshResponse = $this->authenticationService->refresh($refreshRequest, $deviceInfo);

            $result = [
                'success' => true,
                'message' => 'Token 刷新成功',
                ...$refreshResponse->toArray(),
            ];

            $response->getBody()->write(json_encode($result));

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '系統發生錯誤',
            ];
            $response->getBody()->write(json_encode($responseData));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
