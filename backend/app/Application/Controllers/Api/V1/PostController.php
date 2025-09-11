<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\DTOs\UpdatePostDTO;
use App\Domains\Post\Exceptions\PostStatusException;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
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

            // 安全地獲取page參數
            $pageParam = $queryParams['page'] ?? '1';
            $page = max(1, is_numeric($pageParam) ? (int) $pageParam : 1);

            // 安全地獲取limit參數
            $limitParam = $queryParams['limit'] ?? '10';
            $limit = min(100, max(1, is_numeric($limitParam) ? (int) $limitParam : 10));

            $filters = [];
            if (!empty($queryParams['search'])) {
                $filters['search'] = trim(is_string($queryParams['search']) ? $queryParams['search'] : '');
            }
            if (!empty($queryParams['category'])) {
                $filters['category'] = $queryParams['category'];
            }
            if (!empty($queryParams['status'])) {
                $filters['status'] = $queryParams['status'];
            }

            $result = $this->postService->listPosts($page, $limit, $filters);

            // 記錄活動
            $this->activityLogger->log(
                CreateActivityLogDTO::success(
                    ActivityType::POST_VIEWED,
                    $this->getCurrentUserId($request),
                    null,
                    null,
                    '取得貼文列表',
                    ['filters' => $filters, 'page' => $page, 'limit' => $limit],
                ),
            );

            return $this->json($response, [
                'success' => true,
                'data' => array_map(
                    fn($post) => is_object($post) && method_exists($post, 'toSafeArray') ? $post->toSafeArray($this->sanitizer) : $post,
                    $result['items'],
                ),
                'pagination' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'per_page' => $result['per_page'],
                    'last_page' => $result['last_page'],
                ],
            ]);
        } catch (Exception $e) {
            error_log('取得貼文列表失敗: ' . $e->getMessage());

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'server_error',
                    'message' => '取得貼文列表時發生錯誤',
                ],
            ], 500);
        }
    }

    /**
     * 創建新貼文.
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            if (!is_array($data)) {
                throw new RequestValidationException('Invalid request body');
            }

            $dto = new CreatePostDTO($this->validator, $data);
            $post = $this->postService->createPost($dto);

            // 記錄活動
            $this->activityLogger->log(
                CreateActivityLogDTO::success(
                    ActivityType::POST_CREATED,
                    $this->getCurrentUserId($request),
                    null,
                    (string) $post->getId(),
                    '建立貼文',
                    ['title' => $post->getTitle()],
                ),
            );

            return $this->json($response, [
                'success' => true,
                'data' => $post->toSafeArray($this->sanitizer),
                'message' => '貼文創建成功',
            ], 201);
        } catch (Exception $e) {
            error_log('建立貼文失敗: ' . $e->getMessage());

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'server_error',
                    'message' => '創建貼文時發生錯誤',
                ],
            ], 500);
        }
    }

    /**
     * 取得特定貼文.
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? '';
            if (empty($id)) {
                throw new RequestValidationException('貼文 ID 不能為空');
            }

            $post = $this->postService->findById((int) $id);

            // 記錄活動
            $this->activityLogger->log(
                CreateActivityLogDTO::success(
                    ActivityType::POST_VIEWED,
                    $this->getCurrentUserId($request),
                    null,
                    (string) $post->getId(),
                    '查看貼文',
                    ['title' => $post->getTitle()],
                ),
            );

            return $this->json($response, [
                'success' => true,
                'data' => $post->toSafeArray($this->sanitizer),
            ]);
        } catch (Exception $e) {
            error_log('查看貼文失敗: ' . $e->getMessage());

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'server_error',
                    'message' => '取得貼文時發生錯誤',
                ],
            ], 500);
        }
    }

    /**
     * 更新貼文.
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? '';
            if (empty($id)) {
                throw new RequestValidationException('貼文 ID 不能為空');
            }

            $data = $request->getParsedBody();
            if (!is_array($data)) {
                throw new RequestValidationException('Invalid request body');
            }

            /** @var array<string, mixed> $typedData */
            $typedData = $data;
            $dto = new UpdatePostDTO($this->validator, $typedData);
            $post = $this->postService->updatePost((int) $id, $dto);

            // 記錄活動
            $this->activityLogger->log(
                CreateActivityLogDTO::success(
                    ActivityType::POST_UPDATED,
                    $this->getCurrentUserId($request),
                    null,
                    (string) $post->getId(),
                    '更新貼文',
                    ['title' => $post->getTitle()],
                ),
            );

            return $this->json($response, [
                'success' => true,
                'data' => $post->toSafeArray($this->sanitizer),
                'message' => '貼文更新成功',
            ]);
        } catch (ValidationException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'validation_error',
                    'message' => '資料驗證失敗',
                    'details' => $e->getErrors(),
                ],
            ], 400);
        } catch (Exception $e) {
            error_log('更新貼文失敗: ' . $e->getMessage());

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'server_error',
                    'message' => '更新貼文時發生錯誤',
                ],
            ], 500);
        }
    }

    /**
     * 刪除貼文.
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? '';
            if (empty($id)) {
                throw new RequestValidationException('貼文 ID 不能為空');
            }

            $this->postService->deletePost((int) $id);

            // 記錄活動
            $this->activityLogger->log(
                CreateActivityLogDTO::success(
                    ActivityType::POST_DELETED,
                    $this->getCurrentUserId($request),
                    null,
                    (string) $id,
                    '刪除貼文',
                    [],
                ),
            );

            return $this->json($response, [
                'success' => true,
                'message' => '貼文刪除成功',
            ]);
        } catch (PostStatusException|StateTransitionException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'business_logic_error',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        } catch (Exception $e) {
            error_log('刪除貼文失敗: ' . $e->getMessage());

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'type' => 'server_error',
                    'message' => '刪除貼文時發生錯誤',
                ],
            ], 500);
        }
    }

    /**
     * 從請求中取得當前使用者 ID.
     */
    private function getCurrentUserId(Request $request): ?int
    {
        $user = $request->getAttribute('user');

        return $user ? (int) $user['id'] : null;
    }
}
