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
                    ref: '#/components/schemas/PaginatedResponse'),
            ),
            new OA\Response(
                response: 400,
                description: '請求參數錯誤',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ValidationError'),
            ),
        ]
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
            if (!empty($queryParams['search'] {
                $filters['search'] = trim(is_string($queryParams['search']) ? $queryParams['search'] : '');
            }
            if (!empty($queryParams['category'] {
                $filters['category'] = $queryParams['category'];
            }
            if (!empty($queryParams['status'] {
                $filters['status'] = $queryParams['status'];
            }

            $result = $this->postService->listPosts($page, $limit, $filters);

            // 確保 result 包含必要的鍵
            if (!array_key_exists('items', $result) {
                || !array_key_exists('total', $result)
                || !array_key_exists('page', $result)
                || !array_key_exists('per_page', $result)) {
                throw new Exception('Invalid service response format');
                    } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
                } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
                } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
                } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
                } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
                } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
        }
        }
    }
    }