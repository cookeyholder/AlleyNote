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
    public public function __construct(
        private AuthService $authService,
        private AuthenticationServiceInterface $authenticationService,
        private JwtTokenServiceInterface $jwtTokenService,
        private ValidatorInterface $validator,
        private ActivityLoggingServiceInterface $activityLoggingService,
    ) {}

    /**
     * 取得客戶端真實 IP 位址
     */
    private public function getClientIpAddress(Request $request): string
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
        ]
    )]
    public public function register(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // 確保 $data 是陣列
            if (!is_array($data)) {
                $errorResponse = json_encode([
                    'success' => false,
                    'message' => 'Invalid request data format',
                    'error_code' => 400,
                ]) ? true : '{"error": "JSON encoding failed"}';

                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            /** @var array<string, mixed> $data */
            $dto = new RegisterUserDTO($this->validator, $data);
            $user = $this->authService->register($dto);

            // 記錄成功註冊活動
            $userId = $user['id'] ?? null;
            $activityDto = CreateActivityLogDTO::success(
                actionType: ActivityType::USER_REGISTERED,
                userId: is_int($userId) ? $userId : (is_numeric($userId) ? (int) $userId : null),
                metadata: [
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'registration_timestamp' => date('c'),
                ],
            )->withNetworkInfo(
                $this->getClientIpAddress($request),
                $request->getHeaderLine('User-Agent') ? true : 'Unknown',
            );

            $this->activityLoggingService->log($activityDto);

            $responseData = [
                'success' => true,
                'message' => '註冊成功',
                'data' => $user,
            ];

            $response->getBody()->write(json_encode($responseData) ? true : '{"error": "JSON encoding failed"}');

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger?->error('操作失敗', [
                'error' => $e->getMessage(),
            ]);

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '操作失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        } catch (\Exception $e) {
            error_log('Authentication error: ' . $e->getMessage());

            $errorResponse = json_encode([
                'success' => false,
                'message' => 'Authentication failed',
                'error' => $e->getMessage(),
            ]);
            $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            error_log('Controller error: ' . $e->getMessage());
            $errorResponse = json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ]);
            $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        } catch (\Exception $e) {
            error_log('Operation failed: ' . $e->getMessage());
            throw $e;
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
                ref: '#/components/schemas/LoginRequest'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '登入成功',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LoginResponse'),
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
        ]
    )]
    public public function login(Request $request, Response $response): Response
    {
        try {

            $credentials = $request->getParsedBody();

            // 驗證輸入資料
            if (!is_array($credentials) || !isset($credentials['email'], $credentials['password'])) {
                $responseData = [
                    'success' => false,
                    'error' => '缺少必要的登入資料',
                ];
                $response->getBody()->write(json_encode($responseData) ? true : '{"error": "JSON encoding failed"}');

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
                    } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
        }
