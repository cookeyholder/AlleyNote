<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Post\DTOs\CreateTagDTO;
use App\Domains\Post\DTOs\UpdateTagDTO;
use App\Domains\Post\Services\TagManagementService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 標籤管理 Controller.
 */
#[OA\Tag(
    name: 'Tags',
    description: 'Tag management endpoints',
)]
class TagController
{
    public function __construct(
        private readonly TagManagementService $tagManagementService,
    ) {}

    /**
     * 取得標籤列表.
     *
     * GET /api/tags
     */
    #[OA\Get(
        path: '/api/tags',
        operationId: 'getTags',
        summary: 'Get list of tags',
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tags retrieved successfully'),
            new OA\Response(response: 500, description: 'Internal server error'),
        ],
    )]
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $pageParam = $params['page'] ?? 1;
        $perPageParam = $params['per_page'] ?? 20;
        $searchParam = $params['search'] ?? '';

        $page = max(1, is_numeric($pageParam) ? (int) $pageParam : 1);
        $perPage = min(100, max(1, is_numeric($perPageParam) ? (int) $perPageParam : 20));
        $search = is_string($searchParam) ? $searchParam : '';

        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        $result = $this->tagManagementService->listTags($page, $perPage, $filters);

        $responseData = json_encode([
            'success' => true,
            'data' => $result['items'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'last_page' => $result['last_page'],
            ],
        ]);

        $response->getBody()->write($responseData ?: '');

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * 取得單一標籤.
     *
     * GET /api/tags/{id}
     */
    #[OA\Get(
        path: '/api/tags/{id}',
        operationId: 'getTag',
        summary: 'Get a single tag',
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tag retrieved successfully'),
            new OA\Response(response: 404, description: 'Tag not found'),
        ],
    )]
    public function show(Request $request, Response $response): Response
    {
        try {
            $idAttr = $request->getAttribute('id');
            if (!is_numeric($idAttr)) {
                throw new InvalidArgumentException('Invalid tag ID');
            }
            $id = (int) $idAttr;
            $tag = $this->tagManagementService->getTag($id);

            $responseData = json_encode([
                'success' => true,
                'data' => $tag,
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (NotFoundException $e) {
            $responseData = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }

    /**
     * 建立標籤.
     *
     * POST /api/tags
     */
    #[OA\Post(
        path: '/api/tags',
        operationId: 'createTag',
        summary: 'Create a new tag',
        tags: ['Tags'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 50),
                    new OA\Property(property: 'slug', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'color', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tag created successfully'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function store(Request $request, Response $response): Response
    {
        try {
            $data = json_decode((string) $request->getBody(), true);
            if (!is_array($data)) {
                $data = [];
            }

            $name = isset($data['name']) && is_string($data['name']) ? $data['name'] : '';
            $slug = isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null;
            $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : null;
            $color = isset($data['color']) && is_string($data['color']) ? $data['color'] : null;

            $dto = new CreateTagDTO(
                name: $name,
                slug: $slug,
                description: $description,
                color: $color,
            );

            $tag = $this->tagManagementService->createTag($dto);

            $responseData = json_encode([
                'success' => true,
                'data' => $tag,
                'message' => '標籤建立成功',
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (ValidationException $e) {
            $responseData = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }
    }

    /**
     * 更新標籤.
     *
     * PUT /api/tags/{id}
     */
    #[OA\Put(
        path: '/api/tags/{id}',
        operationId: 'updateTag',
        summary: 'Update a tag',
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 50),
                    new OA\Property(property: 'slug', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'color', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tag updated successfully'),
            new OA\Response(response: 404, description: 'Tag not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function update(Request $request, Response $response): Response
    {
        try {
            $idAttr = $request->getAttribute('id');
            if (!is_numeric($idAttr)) {
                throw new InvalidArgumentException('Invalid tag ID');
            }
            $id = (int) $idAttr;

            $data = json_decode((string) $request->getBody(), true);
            if (!is_array($data)) {
                $data = [];
            }

            $name = isset($data['name']) && is_string($data['name']) ? $data['name'] : null;
            $slug = isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null;
            $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : null;
            $color = isset($data['color']) && is_string($data['color']) ? $data['color'] : null;

            $dto = new UpdateTagDTO(
                id: $id,
                name: $name,
                slug: $slug,
                description: $description,
                color: $color,
            );

            $tag = $this->tagManagementService->updateTag($dto);

            $responseData = json_encode([
                'success' => true,
                'data' => $tag,
                'message' => '標籤更新成功',
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (NotFoundException $e) {
            $responseData = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (ValidationException $e) {
            $responseData = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }
    }

    /**
     * 刪除標籤.
     *
     * DELETE /api/tags/{id}
     */
    #[OA\Delete(
        path: '/api/tags/{id}',
        operationId: 'deleteTag',
        summary: 'Delete a tag',
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tag deleted successfully'),
            new OA\Response(response: 404, description: 'Tag not found'),
        ],
    )]
    public function destroy(Request $request, Response $response): Response
    {
        try {
            $idAttr = $request->getAttribute('id');
            if (!is_numeric($idAttr)) {
                throw new InvalidArgumentException('Invalid tag ID');
            }
            $id = (int) $idAttr;
            $this->tagManagementService->deleteTag($id);

            $responseData = json_encode([
                'success' => true,
                'message' => '標籤刪除成功',
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (NotFoundException $e) {
            $responseData = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }
}
