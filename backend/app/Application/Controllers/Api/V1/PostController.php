<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Contracts\PostApiInterface;
use App\Application\Controllers\BaseController;
use App\Application\Resources\PostResource;
use App\Domains\Auth\Contracts\AuthorizationServiceInterface;
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
use App\Shared\Helpers\NetworkHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class PostController extends BaseController implements PostApiInterface
{
    public function __construct(
        private readonly PostServiceInterface $postService,
        private readonly ValidatorInterface $validator,
        private readonly OutputSanitizerInterface $sanitizer,
        private readonly ActivityLoggingServiceInterface $activityLogger,
        private readonly PostViewStatisticsService $postViewStatsService,
        private readonly AuthorizationServiceInterface $authService,
    ) {}

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
            // 將 Post 透過 Resource 轉換為 API 陣列
            /** @var array<int, Post> $postItems */
            $postItems = $result['items'];
            // 批量獲取瀏覽統計
            $postIds = array_values(array_map(
                static fn(Post $post): int => $post->getId(),
                $postItems,
            ));
            $viewStats = $this->postViewStatsService->getBatchPostViewStats($postIds);
            $items = array_map(static function (Post $post) use ($viewStats): array {
                $stats = $viewStats[$post->getId()] ?? ['views' => 0, 'unique_visitors' => 0];

                return new PostResource($post, ['stats' => $stats])->resolve();
            }, $postItems);
            $responseData = $this->paginatedResponse(
                $items,
                $result['total'],
                $result['page'],
                $result['per_page'] ?? $result['perPage'] ?? $limit,
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
                    'ip_address' => NetworkHelper::getClientIp($request),
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
                    'ip_address' => NetworkHelper::getClientIp($request),
                ],
            );
            $errorResponse = $this->errorResponse($e->getMessage(), 422, $e->getErrors());
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (Throwable $e) {
            // 記錄一般錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_VIEWED,
                userId: $request->getAttribute('user_id'),
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'ip_address' => NetworkHelper::getClientIp($request),
                ],
            );
            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                // 記錄資料格式錯誤
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_CREATED,
                    userId: $request->getAttribute('user_id'),
                    reason: 'Invalid request data format',
                    metadata: ['ip_address' => NetworkHelper::getClientIp($request)],
                );
                $errorResponse = $this->errorResponse('Invalid request data format', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            // 添加必需的欄位
            $userId = $request->getAttribute('user_id');
            if ($userId === null) {
                $errorResponse = $this->errorResponse('需要身分驗證', 401);
                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }
            $data['user_id'] = (int) $userId;
            $data['user_ip'] = NetworkHelper::getClientIp($request);
            $dto = new CreatePostDTO($this->validator, $data);
            $post = $this->postService->createPost($dto);
            // 處理標籤
            if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
                $tagIds = array_values(array_filter(array_map(function ($id) {
                    return is_numeric($id) ? (int) $id : null;
                }, $data['tag_ids']), fn($id) => $id !== null));
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
                    'ip_address' => NetworkHelper::getClientIp($request),
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
                    'ip_address' => NetworkHelper::getClientIp($request),
                ],
            );
            $errorResponse = $this->errorResponse($e->getMessage(), 400, $e->getErrors());
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (Throwable $e) {
            // 記錄一般錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_CREATED,
                userId: $request->getAttribute('user_id'),
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: ['ip_address' => NetworkHelper::getClientIp($request)],
            );
            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

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
                    metadata: ['ip_address' => NetworkHelper::getClientIp($request)],
                );
                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            $post = $this->postService->findById($id);
            // 記錄瀏覽次數
            $userIp = NetworkHelper::getClientIp($request);
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
            // 添加瀏覽統計
            $viewStats = $this->postViewStatsService->getPostViewStats($id);
            $postData = new PostResource($post, [
                'sanitizer' => $this->sanitizer,
                'stats' => $viewStats,
            ])->resolve();
            $successResponse = $this->successResponse($postData, '成功取得貼文');
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
                    'ip_address' => NetworkHelper::getClientIp($request),
                ],
            );
            $errorResponse = $this->errorResponse($e->getMessage(), 404);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (Throwable $e) {
            // 記錄一般錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_VIEWED,
                userId: $request->getAttribute('user_id'),
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'requested_id' => $args['id'] ?? 'unknown',
                    'ip_address' => NetworkHelper::getClientIp($request),
                ],
            );
            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

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
                    metadata: ['ip_address' => NetworkHelper::getClientIp($request)],
                );
                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                // 記錄資料格式錯誤
                $this->activityLogger->logFailure(
                    actionType: ActivityType::POST_UPDATED,
                    userId: $request->getAttribute('user_id'),
                    reason: 'Invalid request data format',
                    metadata: [
                        'post_id' => $id,
                        'ip_address' => NetworkHelper::getClientIp($request),
                    ],
                );
                $errorResponse = $this->errorResponse('Invalid request data format', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            // 驗證操作者權限：僅文章作者或管理員可更新
            $post = $this->postService->findById($id);
            $userId = (int) $request->getAttribute('user_id');
            if (!$this->canManagePost($userId, $id) && $post->getUserId() !== $userId) {
                $errorResponse = $this->errorResponse('權限不足，僅文章作者或管理員可更新', 403);
                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
            $dto = new UpdatePostDTO($this->validator, $data);
            // 處理標籤更新（獨立於文章內容更新）
            $hasTagUpdate = false;
            if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
                $tagIds = array_values(array_filter(array_map(function ($id) {
                    return is_numeric($id) ? (int) $id : null;
                }, $data['tag_ids']), fn($id) => $id !== null));
                $this->postService->setTags($id, $tagIds);
                $hasTagUpdate = true;
            }
            // 更新文章內容（如果有變更）
            if ($dto->hasChanges()) {
                $post = $this->postService->updatePost($id, $dto);
            } else {
                // 如果沒有文章內容更新，但有標籤更新，仍然返回成功
                if (!$hasTagUpdate) {
                    $errorResponse = $this->errorResponse('沒有要更新的欄位', 400);
                    $response->getBody()->write(($errorResponse ?: ''));

                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
                // 重新取得文章資料
                $post = $this->postService->findById($id);
            }
            // 記錄成功更新文章的活動
            $this->activityLogger->logSuccess(
                actionType: ActivityType::POST_UPDATED,
                userId: $request->getAttribute('user_id'),
                targetType: 'post',
                targetId: (string) $id,
                metadata: [
                    'title' => $post->getTitle(),
                    'status' => $post->getStatusValue(),
                    'changed_fields' => array_keys($data),
                    'ip_address' => NetworkHelper::getClientIp($request),
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
                    'ip_address' => NetworkHelper::getClientIp($request),
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
                    'ip_address' => NetworkHelper::getClientIp($request),
                ],
            );
            $errorResponse = $this->errorResponse($e->getMessage(), 404);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (Throwable $e) {
            // 記錄一般錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_UPDATED,
                userId: $request->getAttribute('user_id'),
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $args['id'] ?? 'unknown',
                    'ip_address' => NetworkHelper::getClientIp($request),
                ],
            );
            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

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
                    metadata: ['ip_address' => NetworkHelper::getClientIp($request)],
                );
                $errorResponse = $this->errorResponse('Invalid post ID', 400);
                $response->getBody()->write(($errorResponse ?: ''));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            $post = $this->postService->findById($id);
            $postTitle = $post->getTitle();
            $postStatus = $post->getStatusValue();
            // 驗證操作者權限：僅文章作者或管理員可刪除
            $userId = (int) $request->getAttribute('user_id');
            if (!$this->canManagePost($userId, $id) && $post->getUserId() !== $userId) {
                $errorResponse = $this->errorResponse('權限不足，僅文章作者或管理員可刪除', 403);
                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
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
                    'ip_address' => NetworkHelper::getClientIp($request),
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
                    'ip_address' => NetworkHelper::getClientIp($request),
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
                    'ip_address' => NetworkHelper::getClientIp($request),
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
                    'ip_address' => NetworkHelper::getClientIp($request),
                ],
            );
            $errorResponse = $this->errorResponse($e->getMessage(), 422);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (Throwable $e) {
            // 記錄一般錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_DELETED,
                userId: $request->getAttribute('user_id'),
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $args['id'] ?? 'unknown',
                    'ip_address' => NetworkHelper::getClientIp($request),
                ],
            );
            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        return $this->delete($request, $response, $args);
    }

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
                    metadata: ['ip_address' => NetworkHelper::getClientIp($request)],
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
                        'ip_address' => NetworkHelper::getClientIp($request),
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
                        'ip_address' => NetworkHelper::getClientIp($request),
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
                    'ip_address' => NetworkHelper::getClientIp($request),
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
                    'ip_address' => NetworkHelper::getClientIp($request),
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
                    'ip_address' => NetworkHelper::getClientIp($request),
                ],
            );
            $errorResponse = $this->errorResponse($e->getMessage(), 422);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (Throwable $e) {
            // 記錄一般錯誤
            $this->activityLogger->logFailure(
                actionType: ActivityType::POST_PINNED,
                userId: $request->getAttribute('user_id'),
                reason: 'Internal server error: ' . $e->getMessage(),
                metadata: [
                    'post_id' => $args['id'] ?? 'unknown',
                    'ip_address' => NetworkHelper::getClientIp($request),
                ],
            );
            $errorResponse = $this->handleException($e);
            $response->getBody()->write(($errorResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 發布貼文.
     *
     * POST /api/posts/{id}/publish
     */
    public function publish(Request $request, Response $response, array $args): Response
    {
        try {
            $postIdAttr = $args['id'] ?? null;
            $userIdAttr = $request->getAttribute('user_id');
            if (!is_numeric($postIdAttr) || !is_numeric($userIdAttr)) {
                return $this->json($response, ['error' => '無效的請求參數'], 400);
            }
            $postId = (int) $postIdAttr;
            $userId = (int) $userIdAttr;
            $post = $this->postService->findById($postId);
            if (!$this->canManagePost($userId, $postId) && $post->getUserId() !== $userId) {
                $errorResponse = $this->errorResponse('權限不足', 403);
                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
            $updatedPost = $this->postService->updatePostStatus($postId, 'published');
            $responseData = $this->successResponse($updatedPost, '貼文已發布');
            $response->getBody()->write($responseData);

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (PostNotFoundException $e) {
            $errorResponse = $this->errorResponse('貼文不存在', 404);
            $response->getBody()->write($errorResponse);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (Throwable $e) {
            $responseData = $this->handleException($e);
            $response->getBody()->write($responseData);

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 取消發布貼文.
     *
     * POST /api/posts/{id}/unpublish
     */
    public function unpublish(Request $request, Response $response, array $args): Response
    {
        try {
            $postIdAttr = $args['id'] ?? null;
            $userIdAttr = $request->getAttribute('user_id');
            if (!is_numeric($postIdAttr) || !is_numeric($userIdAttr)) {
                return $this->json($response, ['error' => '無效的請求參數'], 400);
            }
            $postId = (int) $postIdAttr;
            $userId = (int) $userIdAttr;
            $post = $this->postService->findById($postId);
            if (!$this->canManagePost($userId, $postId) && $post->getUserId() !== $userId) {
                $errorResponse = $this->errorResponse('權限不足', 403);
                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
            $updatedPost = $this->postService->updatePostStatus($postId, 'draft');
            $responseData = $this->successResponse($updatedPost, '貼文已取消發布');
            $response->getBody()->write($responseData);

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (PostNotFoundException $e) {
            $errorResponse = $this->errorResponse('貼文不存在', 404);
            $response->getBody()->write($errorResponse);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (Throwable $e) {
            $responseData = $this->handleException($e);
            $response->getBody()->write($responseData);

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 批次刪除貼文.
     *
     * DELETE /api/posts/batch
     */
    public function batchDelete(Request $request, Response $response): Response
    {
        $userIdAttr = $request->getAttribute('user_id');
        if (!is_numeric($userIdAttr)) {
            return $this->json($response, ['error' => '未經授權的存取'], 401);
        }
        $userId = (int) $userIdAttr;
        // 僅允許管理員或超級管理員執行批次刪除
        if (!$this->authService->can($userId, 'post', 'delete') && !$this->authService->can($userId, 'post', 'manage')) {
            $errorResponse = $this->errorResponse('權限不足，僅管理員可執行批次刪除', 403);
            $response->getBody()->write($errorResponse);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        $body = json_decode($request->getBody()->getContents(), true);
        $ids = is_array($body) ? ($body['ids'] ?? []) : [];
        if (empty($ids) || !is_array($ids)) {
            $errorResponse = $this->errorResponse('請提供有效的文章 ID 列表', 400);
            $response->getBody()->write($errorResponse);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $deleted = 0;
        $failed = [];
        foreach ($ids as $id) {
            try {
                if (!is_numeric($id)) {
                    continue;
                }
                $this->postService->deletePost((int) $id);
                $deleted++;
            } catch (Throwable $e) {
                $failed[] = [
                    'id' => $id,
                    'error' => '刪除失敗',
                ];
            }
        }
        $responseData = $this->successResponse([
            'deleted' => $deleted,
            'total' => count($ids),
            'failed' => $failed,
        ]);
        $response->getBody()->write($responseData);

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * 取消置頂貼文.
     *
     * DELETE /api/posts/{id}/pin
     */
    public function unpin(Request $request, Response $response, array $args): Response
    {
        try {
            $postIdAttr = $args['id'] ?? null;
            $userIdAttr = $request->getAttribute('user_id');
            if (!is_numeric($postIdAttr) || !is_numeric($userIdAttr)) {
                return $this->json($response, ['error' => '無效的請求參數'], 400);
            }
            $postId = (int) $postIdAttr;
            $userId = (int) $userIdAttr;
            $post = $this->postService->findById($postId);
            if (!$this->canManagePost($userId, $postId) && $post->getUserId() !== $userId) {
                $errorResponse = $this->errorResponse('權限不足', 403);
                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
            $this->postService->setPinned($postId, false);
            $updatedPost = $this->postService->findById($postId);
            $responseData = $this->successResponse($updatedPost->toSafeArray($this->sanitizer), '已取消置頂');
            $response->getBody()->write($responseData);

            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (PostNotFoundException $e) {
            $errorResponse = $this->errorResponse('貼文不存在', 404);
            $response->getBody()->write($errorResponse);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (Throwable $e) {
            $responseData = $this->handleException($e);
            $response->getBody()->write($responseData);

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * 檢查使用者是否有權限操作指定文章.
     */
    private function canManagePost(int $userId, int $postId): bool
    {
        return $this->authService->can($userId, 'post', 'manage')
            || $this->authService->can($userId, 'post', 'update');
    }
}
