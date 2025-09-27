<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\Exceptions\PostNotFoundException;
use App\Domains\Statistics\Events\PostViewed;
use App\Shared\Events\Contracts\EventDispatcherInterface;
use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * 文章瀏覽追蹤控制器.
 *
 * 專門處理文章瀏覽行為記錄的高效能端點
 */
#[OA\Tag(name: 'posts', description: '文章瀏覽追蹤 API')]
class PostViewController extends BaseController
{
    public function __construct(
        private readonly PostServiceInterface $postService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * 記錄文章瀏覽.
     *
     * POST /api/posts/{id}/view
     *
     * 輕量級端點，用於追蹤文章瀏覽行為並觸發相關事件。
     * 此端點專為高頻呼叫設計，回應時間需保持在 100ms 以下。
     */
    #[OA\Post(
        path: '/api/posts/{id}/view',
        summary: '記錄文章瀏覽',
        description: '記錄使用者瀏覽文章的行為，觸發相關統計事件。此端點針對高頻呼叫進行最佳化。',
        operationId: 'recordPostView',
        tags: ['posts'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: '文章 ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 123),
            ),
        ],
        requestBody: new OA\RequestBody(
            description: '瀏覽資訊（可選）',
            required: false,
            content: new OA\JsonContent(
                properties: [
                    'referrer' => new OA\Property(
                        property: 'referrer',
                        description: '來源頁面 URL',
                        type: 'string',
                        format: 'uri',
                        example: 'https://example.com/home',
                        nullable: true,
                    ),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '成功記錄瀏覽',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: true),
                        'message' => new OA\Property(property: 'message', type: 'string', example: '已記錄瀏覽'),
                        'data' => new OA\Property(
                            property: 'data',
                            properties: [
                                'post_id' => new OA\Property(property: 'post_id', type: 'integer', example: 123),
                                'viewed_at' => new OA\Property(property: 'viewed_at', type: 'string', format: 'date-time', example: '2025-09-25T10:30:00Z'),
                                'is_authenticated' => new OA\Property(property: 'is_authenticated', type: 'boolean', example: true),
                            ],
                            type: 'object',
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 400,
                description: '無效的文章 ID',
                content: new OA\JsonContent(
                    ref: '#/components/responses/BadRequest',
                ),
            ),
            new OA\Response(
                response: 404,
                description: '文章不存在',
                content: new OA\JsonContent(
                    ref: '#/components/responses/NotFound',
                ),
            ),
            new OA\Response(
                response: 429,
                description: '請求過於頻繁',
                content: new OA\JsonContent(
                    properties: [
                        'success' => new OA\Property(property: 'success', type: 'boolean', example: false),
                        'error' => new OA\Property(
                            property: 'error',
                            properties: [
                                'message' => new OA\Property(property: 'message', type: 'string', example: '請求過於頻繁，請稍後再試'),
                                'retry_after' => new OA\Property(property: 'retry_after', type: 'integer', example: 60),
                            ],
                            type: 'object',
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 500,
                description: '內部伺服器錯誤',
                content: new OA\JsonContent(
                    ref: '#/components/responses/InternalServerError',
                ),
            ),
        ],
        security: [], // 允許匿名訪問
    )]
    public function recordView(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int|string|array|null $id = null,
    ): ResponseInterface {
        try {
            $startTime = microtime(true);

            // 1. 驗證文章 ID
            $postId = $this->validatePostId($id);

            // 2. 收集瀏覽資訊
            $viewData = $this->extractViewData($request);

            // 3. 驗證文章存在性（輕量級檢查）
            $this->validatePostExists($postId);

            // 4. 發布 PostViewed 事件
            $event = $this->createPostViewedEvent($postId, $viewData);
            $this->eventDispatcher->dispatch($event);

            // 5. 建立回應資料
            $responseData = [
                'post_id' => $postId,
                'viewed_at' => $event->getViewedAt()->format('c'),
                'is_authenticated' => $event->isAuthenticatedUser(),
            ];

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            return $this->json($response, [
                'success' => true,
                'message' => '已記錄瀏覽',
                'data' => $responseData,
                'meta' => [
                    'processing_time_ms' => $duration,
                ],
            ], 200);
        } catch (PostNotFoundException $e) {
            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '文章不存在',
                    'code' => 'POST_NOT_FOUND',
                ],
            ], 404);
        } catch (Exception $e) {
            // 記錄詳細錯誤但不暴露給客戶端
            error_log(sprintf(
                'PostViewController::recordView error: %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            ));

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '記錄瀏覽時發生錯誤',
                    'code' => 'RECORD_VIEW_ERROR',
                ],
            ], 500);
        } catch (Throwable $e) {
            // 捕捉所有其他例外
            error_log(sprintf(
                'PostViewController::recordView critical error: %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            ));

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '系統錯誤，請稍後再試',
                    'code' => 'SYSTEM_ERROR',
                ],
            ], 500);
        }
    }

    /**
     * 驗證文章 ID.
     */
    private function validatePostId(mixed $id): int
    {
        if (is_array($id)) {
            $id = $id['id'] ?? null;
        }

        if ($id === null || $id === '') {
            throw new Exception('文章 ID 不能為空');
        }

        if (!is_string($id) && !is_numeric($id)) {
            throw new Exception('無效的文章 ID 格式');
        }

        $postId = filter_var((string) $id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($postId === false) {
            throw new Exception('無效的文章 ID');
        }

        return $postId;
    }

    /**
     * 從請求中提取瀏覽資料.
     *
     * @return array{user_id: int|null, user_ip: string, user_agent: string|null, referrer: string|null}
     */
    private function extractViewData(ServerRequestInterface $request): array
    {
        // 取得使用者 ID（如果已認證）
        $userIdAttr = $request->getAttribute('user_id');
        $userId = null;
        if (is_numeric($userIdAttr)) {
            $userId = (int) $userIdAttr;
        }

        // 取得 IP 地址
        $userIp = $this->getUserIp($request);

        // 取得 User-Agent
        $userAgent = $request->getHeaderLine('User-Agent') ?: null;

        // 從 body 或 headers 取得 referrer
        $bodyContent = (string) $request->getBody();
        $body = [];
        if (!empty($bodyContent)) {
            $decodedBody = json_decode($bodyContent, true);
            if (is_array($decodedBody)) {
                $body = $decodedBody;
            }
        }

        $referrer = null;
        if (isset($body['referrer']) && is_string($body['referrer'])) {
            $referrer = $body['referrer'];
        } else {
            $referrerHeader = $request->getHeaderLine('Referer');
            $referrer = !empty($referrerHeader) ? $referrerHeader : null;
        }

        return [
            'user_id' => $userId,
            'user_ip' => $userIp,
            'user_agent' => $userAgent,
            'referrer' => $referrer,
        ];
    }

    /**
     * 取得使用者真實 IP.
     */
    private function getUserIp(ServerRequestInterface $request): string
    {
        // 檢查代理伺服器 headers
        $ipHeaders = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
        ];

        $serverParams = $request->getServerParams();

        foreach ($ipHeaders as $header) {
            if (!empty($serverParams[$header]) && is_string($serverParams[$header])) {
                $ipList = explode(',', $serverParams[$header]);
                $ip = trim($ipList[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // 回退到 REMOTE_ADDR
        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';

        return is_string($remoteAddr) ? $remoteAddr : '127.0.0.1';
    }

    /**
     * 輕量級文章存在性驗證.
     */
    private function validatePostExists(int $postId): void
    {
        // 使用 PostService 的輕量級檢查方法
        // 這裡假設 PostService 有一個快速檢查方法
        try {
            $this->postService->findById($postId);
        } catch (PostNotFoundException $e) {
            throw $e; // 重新拋出以便上層處理
        }
    }

    /**
     * 建立 PostViewed 事件.
     */
    private function createPostViewedEvent(int $postId, array $viewData): PostViewed
    {
        // 確保 viewData 有正確的類型
        $userId = $viewData['user_id'];
        $userIp = $viewData['user_ip'];
        $userAgent = $viewData['user_agent'];
        $referrer = $viewData['referrer'];

        if (!is_string($userIp)) {
            $userIp = '127.0.0.1';
        }

        if ($userId !== null && is_int($userId)) {
            return PostViewed::createAuthenticated(
                postId: $postId,
                userId: $userId,
                userIp: $userIp,
                userAgent: is_string($userAgent) ? $userAgent : null,
                referrer: is_string($referrer) ? $referrer : null,
            );
        }

        return PostViewed::createAnonymous(
            postId: $postId,
            userIp: $userIp,
            userAgent: is_string($userAgent) ? $userAgent : null,
            referrer: is_string($referrer) ? $referrer : null,
        );
    }
}
