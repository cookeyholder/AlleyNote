<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\DTOs\UpdatePostDTO;
use App\Domains\Post\Exceptions\PostNotFoundException;
use App\Domains\Post\Exceptions\PostStatusException;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Enums\ActivityType;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\StateTransitionException;
use App\Shared\Exceptions\Validation\RequestValidationException;
use App\Shared\Exceptions\ValidationException;
use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PostController extends BaseController
{
    public function __construct(
        private readonly PostServiceInterface $postService,
        private readonly ValidatorInterface $validator,
        private readonly OutputSanitizerInterface $sanitizer,
        private readonly ActivityLoggingServiceInterface $activityLogger,
    ) {}

    #[OA\Get(
        path: '/posts',
        summary: '取得所有貼文',
        description: '取得分頁的貼文列表，支援搜尋和篩選',
        operationId: 'getPosts',
        tags: ['posts'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: '頁碼',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1),
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: '每頁筆數',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10, minimum: 1, maximum: 100),
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: '搜尋關鍵字',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'category',
                in: 'query',
                description: '貼文分類篩選',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['general', 'announcement', 'urgent', 'notice'],
                ),
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: '貼文狀態篩選',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['draft', 'published', 'archived'],
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得貼文列表',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/PaginatedResponse',
                ),
            ),
            new OA\Response(
                response: 400,
                description: '請求參數錯誤',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ValidationError',
                ),
            ),
        ],
    )]
    public function index(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();

            $page = max(1, (int) ($queryParams['page'] ?? 1));
            $limit = min(100, max(1, (int) ($queryParams['limit'] ?? 10)));

            $filters = [];
            if (!empty($queryParams['search'])) {
                $filters['search'] = trim($queryParams['search']);
            }
            if (!empty($queryParams['category'])) {
                $filters['category'] = $queryParams['category'];
            }
            if (!empty($queryParams['status'])) {
                $filters['status'] = $queryParams['status'];
            }

            $result = $this->postService->listPosts($page, $limit, $filters);

            // 將 Post 對象正確序列化為數組
            $items = array_map(function($post) {
                return $post->toArray();
            }, $result['items']);

            $responseData = $this->paginatedResponse(
                $items,
                $result['total'],
                $result['page'],
                $result['per_page'],
            );

            // 記錄成功的文章列表查看活動
            $userId = $request->getAttribute('user_id');
            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_VIEWED,
                userId: $userId,
                targetType: 'post_list',
                metadata: [
                    'page' => $page,
                    'limit' => $limit,
                    'filters' => $filters,
                    'total_results' => $result['total'],
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $response->getBody()->write(($responseData ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (RequestValidationException $e) {
            // 記錄驗證失敗活動
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_VIEWED,
                userId: $request->getAttribute('user_id'),
                reason: 'Request validation failed: ' . $e->getMessage(),
                metadata: [
                    'errors' => $e->getErrors(),
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 422, $e->getErrors());
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (Exception $e) {
            // 記錄一般錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_VIEWED,
                userId: $request->getAttribute('user_id'),
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    #[OA\Post(
        path: '/posts',
        summary: '建立新貼文',
        description: '建立一篇新的貼文，需要 CSRF Token 驗證',
        operationId: 'createPost',
        tags: ['posts'],
        security: [
            ['csrfToken' => []],
        ],
        requestBody: new OA\RequestBody(
            description: '貼文資料',
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CreatePostRequest',
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: '貼文建立成功',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ApiResponse',
                ),
            ),
            new OA\Response(
                response: 400,
                description: '輸入資料驗證失敗',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ValidationError',
                ),
            ),
            new OA\Response(
                response: 401,
                description: '未授權存取',
                content: new OA\JsonContent(
                    ref: '#/components/responses/Unauthorized',
                ),
            ),
            new OA\Response(
                response: 403,
                description: 'CSRF Token 驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'CSRF token verification failed'),
                    ],
                ),
            ),
        ],
    )]
    public function store(Request $request, Response $response): Response
    {
        try {
            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // 記錄 JSON 格式錯誤
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_CREATED,
                    userId: $request->getAttribute('user_id'),
                    reason: 'Invalid JSON format',
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid JSON format', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // 添加必需的欄位
            $userId = $request->getAttribute('user_id') ?? 1;
            $data['user_id'] = $userId;
            $data['user_ip'] = $this->getUserIp($request);

            $dto = new CreatePostDTO($this->validator, $data);
            $post = $this->postService->createPost($dto);

            // 記錄成功建立文章的活動
            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_CREATED,
                userId: $userId,
                targetType: 'post',
                targetId: (string) $post->getId(),
                metadata: [
                    'title' => $post->getTitle(),
                    'status' => $post->getStatusValue(),
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $successResponse = $this->successResponse($post->toSafeArray($this->sanitizer), '貼文建立成功');
            $response->getBody()->write(($successResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (ValidationException $e) {
            // 記錄驗證失敗
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_CREATED,
                userId: $request->getAttribute('user_id'),
                reason: 'Validation failed: ' . $e->getMessage(),
                metadata: [
                    'errors' => $e->getErrors(),
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 400, $e->getErrors());
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (Exception $e) {
            // 記錄一般錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_CREATED,
                userId: $request->getAttribute('user_id'),
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: ['ip_address' => $this->getUserIp($request)],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    #[OA\Get(
        path: '/posts/{id}',
        summary: '取得單一貼文',
        description: '根據 ID 取得貼文詳細資訊，並記錄瀏覽次數',
        operationId: 'getPostById',
        tags: ['posts'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '貼文 ID',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得貼文詳細資訊',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Post'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '貼文不存在',
                content: new OA\JsonContent(
                    ref: '#/components/responses/NotFound',
                ),
            ),
        ],
    )]
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];

            if ($id <= 0) {
                // 記錄無效 ID 錯誤
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_VIEWED,
                    userId: $request->getAttribute('user_id'),
                    reason: 'Invalid post ID: ' . $args['id'],
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $post = $this->postService->findById($id);

            // 記錄瀏覽次數
            $userIp = $this->getUserIp($request);
            if ($userIp) {
                $this->postService->recordView($id, $userIp);
            }

            // 記錄文章查看活動
            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_VIEWED,
                userId: $request->getAttribute('user_id'),
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $post->getTitle(),
                    'status' => $post->getStatusValue(),
                    'ip_address' => $userIp,
                ],
            );

            $successResponse = $this->successResponse($post->toSafeArray($this->sanitizer), '成功取得貼文');
            $response->getBody()->write(($successResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PostNotFoundException $e) {
            // 記錄文章未找到錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_VIEWED,
                userId: $request->getAttribute('user_id'),
                reason: 'Post not found: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $args['id'] ?? 'unknown',
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 404);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (Exception $e) {
            // 記錄一般錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_VIEWED,
                userId: $request->getAttribute('user_id'),
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $args['id'] ?? 'unknown',
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    #[OA\Put(
        path: '/posts/{id}',
        summary: '更新貼文',
        description: '更新指定 ID 的貼文，需要 CSRF Token 驗證',
        operationId: 'updatePost',
        tags: ['posts'],
        security: [
            ['csrfToken' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '貼文 ID',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
        ],
        requestBody: new OA\RequestBody(
            description: '更新的貼文資料',
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/UpdatePostRequest',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '貼文更新成功',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ApiResponse',
                ),
            ),
            new OA\Response(
                response: 400,
                description: '輸入資料驗證失敗',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ValidationError',
                ),
            ),
            new OA\Response(
                response: 401,
                description: '未授權存取',
                content: new OA\JsonContent(
                    ref: '#/components/responses/Unauthorized',
                ),
            ),
            new OA\Response(
                response: 403,
                description: 'CSRF Token 驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'CSRF token verification failed'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '貼文不存在',
                content: new OA\JsonContent(
                    ref: '#/components/responses/NotFound',
                ),
            ),
        ],
    )]
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];

            if ($id <= 0) {
                // 記錄無效 ID 錯誤
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_UPDATED,
                    userId: $request->getAttribute('user_id'),
                    reason: 'Invalid post ID: ' . $args['id'],
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // 記錄 JSON 格式錯誤
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_UPDATED,
                    userId: $request->getAttribute('user_id'),
                    reason: 'Invalid JSON format',
                    metadata: [
                        'post_id' => $id,
                        'ip_address' => $this->getUserIp($request),
                    ],
                );

                $errorResponse = $this->errorResponse('Invalid JSON format', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $dto = new UpdatePostDTO($this->validator, $data);
            $post = $this->postService->updatePost($id, $dto);

            // 記錄成功更新文章的活動
            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_UPDATED,
                userId: $request->getAttribute('user_id'),
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $post->getTitle(),
                    'status' => $post->getStatusValue(),
                    'changes' => $data, // 記錄變更的欄位
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $successResponse = $this->successResponse($post->toSafeArray($this->sanitizer), '貼文更新成功');
            $response->getBody()->write(($successResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (ValidationException $e) {
            // 記錄驗證失敗
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UPDATED,
                userId: $request->getAttribute('user_id'),
                reason: 'Validation failed: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $args['id'] ?? 'unknown',
                    'errors' => $e->getErrors(),
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 400, $e->getErrors());
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (PostNotFoundException $e) {
            // 記錄文章未找到錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UPDATED,
                userId: $request->getAttribute('user_id'),
                reason: 'Post not found: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $args['id'] ?? 'unknown',
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 404);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (Exception $e) {
            // 記錄一般錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UPDATED,
                userId: $request->getAttribute('user_id'),
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $args['id'] ?? 'unknown',
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    #[OA\Delete(
        path: '/posts/{id}',
        summary: '刪除貼文',
        description: '刪除指定 ID 的貼文，需要 CSRF Token 驗證',
        operationId: 'deletePost',
        tags: ['posts'],
        security: [
            ['csrfToken' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '貼文 ID',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: '貼文刪除成功',
            ),
            new OA\Response(
                response: 401,
                description: '未授權存取',
                content: new OA\JsonContent(
                    ref: '#/components/responses/Unauthorized',
                ),
            ),
            new OA\Response(
                response: 403,
                description: 'CSRF Token 驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'CSRF token verification failed'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '貼文不存在',
                content: new OA\JsonContent(
                    ref: '#/components/responses/NotFound',
                ),
            ),
        ],
    )]
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];

            if ($id <= 0) {
                // 記錄無效 ID 錯誤
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_DELETED,
                    userId: $request->getAttribute('user_id'),
                    reason: 'Invalid post ID: ' . $args['id'],
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $post = $this->postService->findById($id);
            $postTitle = $post->getTitle();
            $postStatus = $post->getStatusValue();

            $this->postService->deletePost($id);

            // 記錄成功刪除文章的活動
            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_DELETED,
                userId: $request->getAttribute('user_id'),
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $postTitle,
                    'status' => $postStatus,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            // 刪除成功回傳 204 No Content
            return $response->withStatus(204);
        } catch (ValidationException $e) {
            // 記錄驗證失敗
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $request->getAttribute('user_id'),
                reason: 'Validation failed: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $args['id'] ?? 'unknown',
                    'errors' => $e->getErrors(),
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 400, $e->getErrors());
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (PostNotFoundException $e) {
            // 記錄文章未找到錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $request->getAttribute('user_id'),
                reason: 'Post not found: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $args['id'] ?? 'unknown',
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 404);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (PostStatusException $e) {
            // 記錄狀態轉換錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $request->getAttribute('user_id'),
                reason: 'Post status error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $args['id'] ?? 'unknown',
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 422);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (Exception $e) {
            // 記錄一般錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $request->getAttribute('user_id'),
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $args['id'] ?? 'unknown',
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    #[OA\Patch(
        path: '/posts/{id}/pin',
        summary: '更新貼文置頂狀態',
        description: '設定或取消貼文的置頂狀態，需要 CSRF Token 驗證',
        operationId: 'togglePostPin',
        tags: ['posts'],
        security: [
            ['csrfToken' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '貼文 ID',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
        ],
        requestBody: new OA\RequestBody(
            description: '置頂狀態',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'pinned',
                        type: 'boolean',
                        description: '是否置頂',
                        example: true,
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '置頂狀態更新成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '置頂狀態已更新'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Post'),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: '請求資料格式錯誤',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ValidationError',
                ),
            ),
            new OA\Response(
                response: 401,
                description: '未授權存取',
                content: new OA\JsonContent(
                    ref: '#/components/responses/Unauthorized',
                ),
            ),
            new OA\Response(
                response: 403,
                description: 'CSRF Token 驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'CSRF token verification failed'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '貼文不存在',
                content: new OA\JsonContent(
                    ref: '#/components/responses/NotFound',
                ),
            ),
        ],
    )]
    public function togglePin(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];

            if ($id <= 0) {
                // 記錄無效 ID 錯誤
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_PINNED,
                    userId: $request->getAttribute('user_id'),
                    reason: 'Invalid post ID: ' . $args['id'],
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // 記錄 JSON 格式錯誤
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_PINNED,
                    userId: $request->getAttribute('user_id'),
                    reason: 'Invalid JSON format',
                    metadata: [
                        'post_id' => $id,
                        'ip_address' => $this->getUserIp($request),
                    ],
                );

                $errorResponse = $this->errorResponse('Invalid JSON format', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if (!isset($data['pinned']) || !is_bool($data['pinned'])) {
                // 記錄參數錯誤
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_PINNED,
                    userId: $request->getAttribute('user_id'),
                    reason: 'Missing or invalid pinned parameter',
                    metadata: [
                        'post_id' => $id,
                        'received_data' => $data,
                        'ip_address' => $this->getUserIp($request),
                    ],
                );

                $errorResponse = $this->errorResponse('Missing or invalid pinned parameter', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $this->postService->setPinned($id, $data['pinned']);
            $post = $this->postService->findById($id);

            // 記錄置頂狀態變更活動
            $actionType = $data['pinned'] ? ActivityType::POST_PINNED : ActivityType::POST_UNPINNED;
            $this->activityLogger->logSuccess(
                actionType: $actionType,
                userId: $request->getAttribute('user_id'),
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $post->getTitle(),
                    'pinned' => $data['pinned'],
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $message = $data['pinned'] ? '貼文已設為置頂' : '貼文已取消置頂';
            $successResponse = $this->successResponse($post->toSafeArray($this->sanitizer), $message);
            $response->getBody()->write(($successResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PostNotFoundException $e) {
            // 記錄文章未找到錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_PINNED,
                userId: $request->getAttribute('user_id'),
                reason: 'Post not found: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $args['id'] ?? 'unknown',
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 404);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (StateTransitionException $e) {
            // 記錄狀態轉換錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_PINNED,
                userId: $request->getAttribute('user_id'),
                reason: 'State transition error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $args['id'] ?? 'unknown',
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 422);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (Exception $e) {
            // 記錄一般錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_PINNED,
                userId: $request->getAttribute('user_id'),
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $args['id'] ?? 'unknown',
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 取得使用者 IP 位址
     */
    private function getUserIp(Request $request): string
    {
        $serverParams = $request->getServerParams();

        // 優先順序：X-Forwarded-For > X-Real-IP > REMOTE_ADDR
        $ipSources = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($ipSources as $source) {
            if (!empty($serverParams[$source])) {
                $ip = $serverParams[$source];
                if ($source === 'HTTP_X_FORWARDED_FOR') {
                    // X-Forwarded-For 可能包含多個 IP，取第一個
                    $ip = trim(explode(',', $ip)[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // 如果無法取得有效 IP，使用 REMOTE_ADDR 或預設值
        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * 刪除貼文.
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $postId = (int) $args['id'];
            $this->postService->deletePost($postId);

            $responseData = [
                'success' => true,
                'message' => '貼文已成功刪除',
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{"error": "JSON encoding failed"}'));

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '刪除貼文失敗: ' . $e->getMessage(),
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{"error": "JSON encoding failed"}'));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 發布貼文.
     *
     * POST /api/posts/{id}/publish
     */
    #[OA\Post(
        path: '/posts/{id}/publish',
        summary: '發布貼文',
        description: '將草稿貼文發布為公開狀態',
        operationId: 'publishPost',
        tags: ['posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: '貼文 ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '發布成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '貼文已發布'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 404, description: '貼文不存在'),
            new OA\Response(response: 500, description: '伺服器錯誤'),
        ],
    )]
    public function publish(Request $request, Response $response, array $args): Response
    {
        try {
            $postId = (int) $args['id'];

            // 更新貼文狀態為 published
            $post = $this->postService->updatePostStatus($postId, 'published');

            $responseData = [
                'success' => true,
                'message' => '貼文已發布',
                'data' => $post,
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{"error": "JSON encoding failed"}'));

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '發布貼文失敗: ' . $e->getMessage(),
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{"error": "JSON encoding failed"}'));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 取消發布貼文.
     *
     * POST /api/posts/{id}/unpublish
     */
    #[OA\Post(
        path: '/posts/{id}/unpublish',
        summary: '取消發布貼文',
        description: '將已發布的貼文改為草稿狀態',
        operationId: 'unpublishPost',
        tags: ['posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: '貼文 ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '取消發布成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '貼文已取消發布'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 404, description: '貼文不存在'),
            new OA\Response(response: 500, description: '伺服器錯誤'),
        ],
    )]
    public function unpublish(Request $request, Response $response, array $args): Response
    {
        try {
            $postId = (int) $args['id'];

            // 更新貼文狀態為 draft
            $post = $this->postService->updatePostStatus($postId, 'draft');

            $responseData = [
                'success' => true,
                'message' => '貼文已取消發布',
                'data' => $post,
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{"error": "JSON encoding failed"}'));

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '取消發布失敗: ' . $e->getMessage(),
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{"error": "JSON encoding failed"}'));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 取消置頂貼文.
     *
     * DELETE /api/posts/{id}/pin
     */
    #[OA\Delete(
        path: '/posts/{id}/pin',
        summary: '取消置頂貼文',
        description: '取消貼文的置頂狀態',
        operationId: 'unpinPost',
        tags: ['posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: '貼文 ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '取消置頂成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '已取消置頂'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 404, description: '貼文不存在'),
            new OA\Response(response: 500, description: '伺服器錯誤'),
        ],
    )]
    public function unpin(Request $request, Response $response, array $args): Response
    {
        try {
            $postId = (int) $args['id'];

            // 取消置頂
            $post = $this->postService->unpinPost($postId);

            $responseData = [
                'success' => true,
                'message' => '已取消置頂',
                'data' => $post,
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{"error": "JSON encoding failed"}'));

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $responseData = [
                'success' => false,
                'error' => '取消置頂失敗: ' . $e->getMessage(),
            ];

            $response->getBody()->write((json_encode($responseData) ?: '{"error": "JSON encoding failed"}'));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
