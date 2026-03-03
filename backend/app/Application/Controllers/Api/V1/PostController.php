<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\DTOs\UpdatePostDTO;
use App\Domains\Post\Exceptions\PostNotFoundException;
use App\Domains\Post\Exceptions\PostStatusException;
use App\Domains\Post\Models\Post;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Enums\ActivityType;
use App\Domains\Statistics\Services\PostViewStatisticsService;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\StateTransitionException;
use App\Shared\Exceptions\Validation\RequestValidationException;
use App\Shared\Exceptions\ValidationException;
use DateTime;
use DateTimeZone;
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
        private readonly PostViewStatisticsService $postViewStatsService,
    ) {}

    #[OA\Get(
        path: '/api/posts',
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
        $userId = $this->resolveUserId($request);

        try {
            $queryParams = $this->filterStringKeys($request->getQueryParams());

            $page = $this->positiveIntOrDefault($queryParams['page'] ?? null, 1, 1, 1000);
            $limit = $this->positiveIntOrDefault($queryParams['limit'] ?? null, 10, 1, 100);

            $filters = [];

            $search = $this->toStringOrNull($queryParams['search'] ?? null);
            if ($search !== null && $search !== '') {
                $filters['search'] = trim($search);
            }

            $category = $this->toStringOrNull($queryParams['category'] ?? null);
            if ($category !== null && $category !== '') {
                $filters['category'] = $category;
            }

            $status = $this->toStringOrNull($queryParams['status'] ?? null);
            if ($status !== null && $status !== '') {
                $filters['status'] = $status;
            }

            $result = $this->postService->listPosts($page, $limit, $filters);

            $postItems = [];
            if (isset($result['items']) && is_array($result['items'])) {
                foreach ($result['items'] as $item) {
                    if ($item instanceof Post) {
                        $postItems[] = $item;
                    }
                }
            }

            $items = array_map(static fn(Post $post): array => $post->toArray(), $postItems);

            $postIds = array_map(static fn(Post $post): int => $post->getId(), $postItems);
            $viewStats = $postIds === []
                ? []
                : $this->postViewStatsService->getBatchPostViewStats($postIds);

            foreach ($items as &$item) {
                $postId = isset($item['id']) ? $this->toIntOrNull($item['id']) : null;
                if ($postId !== null && isset($viewStats[$postId])) {
                    $stats = $viewStats[$postId];
                    $item['views'] = $this->toIntOrNull($stats['views'] ?? null) ?? 0;
                    $item['unique_visitors'] = $this->toIntOrNull($stats['unique_visitors'] ?? null) ?? 0;
                } else {
                    $item['views'] = 0;
                    $item['unique_visitors'] = 0;
                }
            }
            unset($item);

            $total = $this->toIntOrNull($result['total'] ?? null) ?? 0;
            $currentPage = $this->toIntOrNull($result['page'] ?? null) ?? $page;
            $perPage = $this->toIntOrNull($result['per_page'] ?? null) ?? $limit;

            $responseData = $this->paginatedResponse($items, $total, $currentPage, $perPage);
            $response->getBody()->write($responseData === '' ? '{}' : $responseData);

            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_VIEWED,
                userId: $userId,
                targetType: 'post_list',
                metadata: [
                    'page' => $currentPage,
                    'limit' => $perPage,
                    'filters' => $filters,
                    'total_results' => $total,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (RequestValidationException $e) {
            // 記錄驗證失敗活動
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_VIEWED,
                userId: $userId,
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
                userId: $userId,
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
        path: '/api/posts',
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
        $userId = $this->getUserIdOrDefault($request, 1);

        try {
            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // 記錄 JSON 格式錯誤
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_CREATED,
                    userId: $userId,
                    reason: 'Invalid JSON format',
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid JSON format', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if (!is_array($data)) {
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_CREATED,
                    userId: $userId,
                    reason: 'Invalid request payload structure',
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid request payload', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $payload = $this->filterStringKeys($data);

            // 添加必需的欄位
            $payload['user_id'] = $userId;
            $payload['user_ip'] = $this->getUserIp($request);

            $dto = new CreatePostDTO($this->validator, $payload);
            $post = $this->postService->createPost($dto);

            // 處理標籤
            $tagIds = $this->sanitizeTagIds($payload['tag_ids'] ?? null);
            if ($tagIds !== []) {
                $this->postService->setTags($post->getId(), $tagIds);
            }

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
                userId: $userId,
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
                userId: $userId,
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: ['ip_address' => $this->getUserIp($request)],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    #[OA\Get(
        path: '/api/posts/{id}',
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
        $userId = $this->resolveUserId($request);
        $requestedId = $args['id'] ?? null;
        $logRequestedId = $this->toLogString($requestedId);

        try {
            $id = $this->toIntOrNull($requestedId);

            if ($id === null || $id <= 0) {
                // 記錄無效 ID 錯誤
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_VIEWED,
                    userId: $userId,
                    reason: 'Invalid post ID: ' . $logRequestedId,
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $post = $this->postService->findById($id);

            // 記錄瀏覽次數
            $userIp = $this->getUserIp($request);
            if ($userIp !== '') {
                $this->postService->recordView($id, $userIp, $userId);
            }

            // 記錄文章查看活動
            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_VIEWED,
                userId: $userId,
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $post->getTitle(),
                    'status' => $post->getStatusValue(),
                    'ip_address' => $userIp,
                ],
            );

            $postData = $post->toSafeArray($this->sanitizer);

            // 確保 publish_date 是 RFC3339 格式
            if (isset($postData['publish_date']) && is_string($postData['publish_date'])) {
                if (strpos($postData['publish_date'], 'T') === false) {
                    try {
                        $dt = new DateTime($postData['publish_date'], new DateTimeZone('UTC'));
                        $postData['publish_date'] = $dt->format(DateTime::ATOM);
                    } catch (Exception $e) {
                        // 保持原值
                    }
                }
            }

            // 添加瀏覽統計
            $viewStats = $this->postViewStatsService->getPostViewStats($id);
            $postData['views'] = $this->toIntOrNull($viewStats['views'] ?? null) ?? 0;
            $postData['unique_visitors'] = $this->toIntOrNull($viewStats['unique_visitors'] ?? null) ?? 0;

            $successResponse = $this->successResponse($postData, '成功取得貼文');
            $response->getBody()->write(($successResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PostNotFoundException $e) {
            // 記錄文章未找到錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_VIEWED,
                userId: $userId,
                reason: 'Post not found: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $logRequestedId,
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
                userId: $userId,
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    #[OA\Put(
        path: '/api/posts/{id}',
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
        $userId = $this->resolveUserId($request);
        $requestedId = $args['id'] ?? null;
        $logRequestedId = $this->toLogString($requestedId);

        try {
            $id = $this->toIntOrNull($requestedId);

            if ($id === null || $id <= 0) {
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_UPDATED,
                    userId: $userId,
                    reason: 'Invalid post ID: ' . $logRequestedId,
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_UPDATED,
                    userId: $userId,
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

            if (!is_array($data)) {
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_UPDATED,
                    userId: $userId,
                    reason: 'Invalid request payload structure',
                    metadata: [
                        'post_id' => $id,
                        'ip_address' => $this->getUserIp($request),
                    ],
                );

                $errorResponse = $this->errorResponse('Invalid request payload', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $payload = $this->filterStringKeys($data);
            $dto = new UpdatePostDTO($this->validator, $payload);

            $tagIds = $this->sanitizeTagIds($payload['tag_ids'] ?? null);
            $hasTagUpdate = $tagIds !== [];

            if ($hasTagUpdate) {
                $this->postService->setTags($id, $tagIds);
            }

            if ($dto->hasChanges()) {
                $post = $this->postService->updatePost($id, $dto);
            } elseif ($hasTagUpdate) {
                $post = $this->postService->findById($id);
            } else {
                $errorResponse = $this->errorResponse('沒有要更新的欄位', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_UPDATED,
                userId: $userId,
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $post->getTitle(),
                    'status' => $post->getStatusValue(),
                    'changes' => $payload,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $successResponse = $this->successResponse($post->toSafeArray($this->sanitizer), '貼文更新成功');
            $response->getBody()->write(($successResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (ValidationException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UPDATED,
                userId: $userId,
                reason: 'Validation failed: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'errors' => $e->getErrors(),
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 400, $e->getErrors());
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (PostNotFoundException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UPDATED,
                userId: $userId,
                reason: 'Post not found: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 404);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (Exception $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UPDATED,
                userId: $userId,
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    #[OA\Delete(
        path: '/api/posts/{id}',
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
        $userId = $this->resolveUserId($request);
        $requestedId = $args['id'] ?? null;
        $logRequestedId = $this->toLogString($requestedId);

        try {
            $id = $this->toIntOrNull($requestedId);

            if ($id === null || $id <= 0) {
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_DELETED,
                    userId: $userId,
                    reason: 'Invalid post ID: ' . $logRequestedId,
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

            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_DELETED,
                userId: $userId,
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $postTitle,
                    'status' => $postStatus,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            return $response->withStatus(204);
        } catch (ValidationException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $userId,
                reason: 'Validation failed: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'errors' => $e->getErrors(),
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 400, $e->getErrors());
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (PostNotFoundException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $userId,
                reason: 'Post not found: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 404);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (PostStatusException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $userId,
                reason: 'Post status error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 422);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (Exception $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $userId,
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    #[OA\Patch(
        path: '/api/posts/{id}/pin',
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
        $userId = $this->resolveUserId($request);
        $requestedId = $args['id'] ?? null;
        $logRequestedId = $this->toLogString($requestedId);

        try {
            $id = $this->toIntOrNull($requestedId);

            if ($id === null || $id <= 0) {
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_PINNED,
                    userId: $userId,
                    reason: 'Invalid post ID: ' . $logRequestedId,
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_PINNED,
                    userId: $userId,
                    reason: 'Invalid JSON format',
                    metadata: [
                        'post_id' => $logRequestedId,
                        'ip_address' => $this->getUserIp($request),
                    ],
                );

                $errorResponse = $this->errorResponse('Invalid JSON format', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $payload = $this->filterStringKeys($data);
            $pinnedRaw = $payload['pinned'] ?? null;
            $pinned = is_bool($pinnedRaw) ? $pinnedRaw : null;

            if ($pinned === null) {
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_PINNED,
                    userId: $userId,
                    reason: 'Missing or invalid pinned parameter',
                    metadata: [
                        'post_id' => $logRequestedId,
                        'received_data' => $payload,
                        'ip_address' => $this->getUserIp($request),
                    ],
                );

                $errorResponse = $this->errorResponse('Missing or invalid pinned parameter', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $this->postService->setPinned($id, $pinned);
            $post = $this->postService->findById($id);

            $actionType = $pinned ? ActivityType::POST_PINNED : ActivityType::POST_UNPINNED;
            $this->activityLogger->logSuccess(
                actionType: $actionType,
                userId: $userId,
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $post->getTitle(),
                    'pinned' => $pinned,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $message = $pinned ? '貼文已設為置頂' : '貼文已取消置頂';
            $successResponse = $this->successResponse($post->toSafeArray($this->sanitizer), $message);
            $response->getBody()->write(($successResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PostNotFoundException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_PINNED,
                userId: $userId,
                reason: 'Post not found: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 404);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (StateTransitionException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_PINNED,
                userId: $userId,
                reason: 'State transition error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 422);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (Exception $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_PINNED,
                userId: $userId,
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
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

        $ipSources = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($ipSources as $source) {
            $rawValue = $serverParams[$source] ?? null;
            $ip = $this->toStringOrNull($rawValue);

            if ($ip === null) {
                continue;
            }

            if ($source === 'HTTP_X_FORWARDED_FOR') {
                $ip = trim(explode(',', $ip)[0]);
            }

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }

        return $this->toStringOrNull($serverParams['REMOTE_ADDR'] ?? null) ?? '127.0.0.1';
    }

    /**
     * 刪除貼文.
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $userId = $this->resolveUserId($request);
        $requestedId = $args['id'] ?? null;

        try {
            $id = $this->toIntOrNull($requestedId);

            if ($id === null || $id <= 0) {
                $responseData = [
                    'success' => false,
                    'error' => 'Invalid post ID',
                ];

                $response->getBody()->write(json_encode($responseData) ?: '{}');

                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $post = $this->postService->findById($id);
            $postTitle = $post->getTitle();
            $postStatus = $post->getStatusValue();

            $this->postService->deletePost($id);

            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_DELETED,
                userId: $userId,
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $postTitle,
                    'status' => $postStatus,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $responseData = [
                'success' => true,
                'message' => '貼文已成功刪除',
            ];

            $response->getBody()->write(json_encode($responseData) ?: '{}');

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $userId,
                reason: 'Validation failed: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $requestedId,
                    'errors' => $e->getErrors(),
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ];

            $response->getBody()->write(json_encode($responseData) ?: '{}');

            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (PostNotFoundException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $userId,
                reason: 'Post not found: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $requestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];

            $response->getBody()->write(json_encode($responseData) ?: '{}');

            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        } catch (PostStatusException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $userId,
                reason: 'Post status error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $requestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $responseData = [
                'success' => false,
                'error' => $e->getMessage(),
            ];

            $response->getBody()->write(json_encode($responseData) ?: '{}');

            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $userId,
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $requestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $responseData = [
                'success' => false,
                'error' => '刪除貼文失敗: ' . $e->getMessage(),
            ];

            $response->getBody()->write(json_encode($responseData) ?: '{}');

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 發布貼文.
     *
     * POST /api/posts/{id}/publish
     */
    #[OA\Post(
        path: '/api/posts/{id}/publish',
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
        $userId = $this->resolveUserId($request);
        $requestedId = $args['id'] ?? null;
        $logRequestedId = $this->toLogString($requestedId);

        try {
            $id = $this->toIntOrNull($requestedId);

            if ($id === null || $id <= 0) {
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_PUBLISHED,
                    userId: $userId,
                    reason: 'Invalid post ID: ' . $logRequestedId,
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $post = $this->postService->updatePostStatus($id, 'published');

            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_PUBLISHED,
                userId: $userId,
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $post->getTitle(),
                    'status' => $post->getStatusValue(),
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $successResponse = $this->successResponse($post->toSafeArray($this->sanitizer), '貼文已發布');
            $response->getBody()->write(($successResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PostNotFoundException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_PUBLISHED,
                userId: $userId,
                reason: 'Post not found: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 404);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (ValidationException|PostStatusException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_PUBLISHED,
                userId: $userId,
                reason: 'Validation failed: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 422, $e instanceof ValidationException ? $e->getErrors() : null);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (StateTransitionException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_PUBLISHED,
                userId: $userId,
                reason: 'State transition error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 422);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (Exception $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_PUBLISHED,
                userId: $userId,
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 取消發布貼文.
     *
     * POST /api/posts/{id}/unpublish
     */
    #[OA\Post(
        path: '/api/posts/{id}/unpublish',
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
        $userId = $this->resolveUserId($request);
        $requestedId = $args['id'] ?? null;
        $logRequestedId = $this->toLogString($requestedId);

        try {
            $id = $this->toIntOrNull($requestedId);

            if ($id === null || $id <= 0) {
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_UNPUBLISHED,
                    userId: $userId,
                    reason: 'Invalid post ID: ' . $logRequestedId,
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $post = $this->postService->updatePostStatus($id, 'draft');

            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_UNPUBLISHED,
                userId: $userId,
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $post->getTitle(),
                    'status' => $post->getStatusValue(),
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $successResponse = $this->successResponse($post->toSafeArray($this->sanitizer), '貼文已取消發布');
            $response->getBody()->write(($successResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PostNotFoundException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UNPUBLISHED,
                userId: $userId,
                reason: 'Post not found: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 404);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (ValidationException|PostStatusException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UNPUBLISHED,
                userId: $userId,
                reason: 'Validation failed: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 422, $e instanceof ValidationException ? $e->getErrors() : null);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (StateTransitionException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UNPUBLISHED,
                userId: $userId,
                reason: 'State transition error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 422);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (Exception $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UNPUBLISHED,
                userId: $userId,
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 取消置頂貼文.
     *
     * DELETE /api/posts/{id}/pin
     */
    #[OA\Delete(
        path: '/api/posts/{id}/pin',
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
        $userId = $this->resolveUserId($request);
        $requestedId = $args['id'] ?? null;
        $logRequestedId = $this->toLogString($requestedId);

        try {
            $id = $this->toIntOrNull($requestedId);

            if ($id === null || $id <= 0) {
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_UNPINNED,
                    userId: $userId,
                    reason: 'Invalid post ID: ' . $logRequestedId,
                    metadata: ['ip_address' => $this->getUserIp($request)],
                );

                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $post = $this->postService->unpinPost($id);

            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_UNPINNED,
                userId: $userId,
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $post->getTitle(),
                    'pinned' => false,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $successResponse = $this->successResponse($post->toSafeArray($this->sanitizer), '已取消置頂');
            $response->getBody()->write(($successResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PostNotFoundException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UNPINNED,
                userId: $userId,
                reason: 'Post not found: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse($e->getMessage(), 404);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (StateTransitionException|PostStatusException|ValidationException $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UNPINNED,
                userId: $userId,
                reason: 'State or validation error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->errorResponse(
                $e->getMessage(),
                422,
                $e instanceof ValidationException ? $e->getErrors() : null,
            );
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (Exception $e) {
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UNPINNED,
                userId: $userId,
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $logRequestedId,
                    'ip_address' => $this->getUserIp($request),
                ],
            );

            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
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

    private function positiveIntOrDefault(mixed $value, int $default, int $min, int $max): int
    {
        $intValue = $this->toIntOrNull($value);

        if ($intValue === null) {
            return $default;
        }

        if ($intValue < $min) {
            return $min;
        }

        if ($intValue > $max) {
            return $max;
        }

        return $intValue;
    }

    private function toLogString(mixed $value, string $fallback = 'unknown'): string
    {
        $stringValue = $this->toStringOrNull($value);

        return $stringValue ?? $fallback;
    }

    /**
     * @return array<int>
     */
    private function sanitizeTagIds(mixed $tagIds): array
    {
        if (!is_array($tagIds)) {
            return [];
        }

        $normalized = [];
        foreach ($tagIds as $tagId) {
            $value = $this->toIntOrNull($tagId);
            if ($value !== null) {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }

    private function resolveUserId(Request $request, ?int $default = null): ?int
    {
        $value = $request->getAttribute('user_id');
        $userId = $this->toIntOrNull($value);

        return $userId ?? $default;
    }

    private function getUserIdOrDefault(Request $request, int $default): int
    {
        return $this->resolveUserId($request, $default) ?? $default;
    }
}
