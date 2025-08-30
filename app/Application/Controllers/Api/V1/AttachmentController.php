<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Attachment\Services\AttachmentService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AttachmentController
{
    public function __construct(
        private AttachmentService $attachmentService,
    ) {}

    /**
     * 取得當前登入使用者 ID
     * TODO: 實作真正的使用者認證邏輯.
     */
    private function getCurrentUserId(Request $request): int
    {
        // 從 request attributes 或 session 中取得使用者 ID
        $userId = $request->getAttribute('user_id');
        if ($userId === null) {
            throw ValidationException::fromSingleError('user_id', '使用者未登入');
        }

        return (int) $userId;
    }

    #[OA\Post(
        path: '/posts/{post_id}/attachments',
        summary: '上傳文件附件',
        description: '為指定貼文上傳附件檔案，支援多種檔案格式',
        operationId: 'uploadAttachment',
        tags: ['attachments'],
        security: [
            ['bearerAuth' => []],
            ['sessionAuth' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'post_id',
                in: 'path',
                description: '貼文 ID',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
        ],
        requestBody: new OA\RequestBody(
            description: '上傳的檔案',
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary',
                            description: '要上傳的檔案',
                        ),
                    ],
                    required: ['file'],
                ),
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: '檔案上傳成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '檔案上傳成功'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'post_id', type: 'integer', example: 1),
                                new OA\Property(property: 'filename', type: 'string', example: 'document.pdf'),
                                new OA\Property(property: 'original_name', type: 'string', example: '重要文件.pdf'),
                                new OA\Property(property: 'file_size', type: 'integer', description: '檔案大小（位元組）', example: 1024000),
                                new OA\Property(property: 'mime_type', type: 'string', example: 'application/pdf'),
                                new OA\Property(property: 'download_url', type: 'string', example: '/api/attachments/550e8400-e29b-41d4-a716-446655440000/download'),
                                new OA\Property(property: 'uploaded_by', type: 'integer', example: 1),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00Z'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: '檔案上傳失敗或格式不支援',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: '不支援的檔案格式'),
                    ],
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
                response: 404,
                description: '貼文不存在',
                content: new OA\JsonContent(
                    ref: '#/components/responses/NotFound',
                ),
            ),
            new OA\Response(
                response: 413,
                description: '檔案大小超過限制',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: '檔案大小超過 10MB 限制'),
                    ],
                ),
            ),
        ],
    )]
    public function upload(Request $request, Response $response): Response
    {
        try {
            $currentUserId = $this->getCurrentUserId($request);
            $postId = (int) $request->getAttribute('post_id');
            $files = $request->getUploadedFiles();

            if (!isset($files['file'])) {
                $response->getBody()->write((json_encode([
                    'error' => '缺少上傳檔案',
                ]) ?: ''));

                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            $attachment = $this->attachmentService->upload($postId, $files['file'], $currentUserId);

            $jsonResponse = json_encode([
                'data' => $attachment->toArray(),
            ]);
            $response->getBody()->write($jsonResponse ?: '{"error": "JSON encoding failed"}');

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $response->getBody()->write((json_encode([
                'error' => $e->getMessage(),
            ]) ?: '{"error": "JSON encoding failed"}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $response->getBody()->write((json_encode([
                'error' => $e->getMessage(),
            ]) ?: '{"error": "JSON encoding failed"}'));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Get(
        path: '/attachments/{id}/download',
        summary: '下載附件',
        description: '下載指定的附件檔案',
        operationId: 'downloadAttachment',
        tags: ['attachments'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '附件 UUID',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '檔案下載成功',
                content: [
                    'application/octet-stream' => new OA\MediaType(
                        mediaType: 'application/octet-stream',
                        schema: new OA\Schema(
                            type: 'string',
                            format: 'binary',
                        ),
                    ),
                    '*/*' => new OA\MediaType(
                        mediaType: '*/*',
                        schema: new OA\Schema(
                            type: 'string',
                            format: 'binary',
                        ),
                    ),
                ],
                headers: [
                    'Content-Disposition' => new OA\Header(
                        header: 'Content-Disposition',
                        description: '檔案下載標頭',
                        schema: new OA\Schema(type: 'string', example: 'attachment; filename="document.pdf"'),
                    ),
                    'Content-Type' => new OA\Header(
                        header: 'Content-Type',
                        description: '檔案 MIME 類型',
                        schema: new OA\Schema(type: 'string', example: 'application/pdf'),
                    ),
                    'Content-Length' => new OA\Header(
                        header: 'Content-Length',
                        description: '檔案大小',
                        schema: new OA\Schema(type: 'integer', example: 1024000),
                    ),
                ],
            ),
            new OA\Response(
                response: 400,
                description: '無效的附件識別碼',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: '無效的附件識別碼'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '附件不存在',
                content: new OA\JsonContent(
                    ref: '#/components/responses/NotFound',
                ),
            ),
            new OA\Response(
                response: 410,
                description: '檔案已不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: '檔案已被刪除或移動'),
                    ],
                ),
            ),
        ],
    )]
    /**
     * 下載附件.
     *
     * @param array $args 路由參數
     */
    public function download(Request $request, Response $response, array $args): Response
    {
        // 這個方法需要實作檔案下載邏輯
        try {
            $uuid = $args['id'] ?? null;
            if (!$uuid || !is_string($uuid)) {
                throw ValidationException::fromSingleError('uuid', '無效的附件識別碼');
            }

            // TODO: 實作檔案下載邏輯
            // 1. 驗證附件是否存在
            // 2. 檢查檔案權限
            // 3. 讀取檔案並回傳

            $response->getBody()->write((json_encode([
                'error' => '檔案下載功能尚未實作',
            ]) ?: ''));

            return $response
                ->withStatus(501)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $response->getBody()->write((json_encode([
                'error' => $e->getMessage(),
            ]) ?: '{"error": "JSON encoding failed"}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $response->getBody()->write((json_encode([
                'error' => $e->getMessage(),
            ]) ?: '{"error": "JSON encoding failed"}'));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Get(
        path: '/posts/{post_id}/attachments',
        summary: '取得貼文附件列表',
        description: '取得指定貼文的所有附件檔案清單',
        operationId: 'listAttachments',
        tags: ['attachments'],
        parameters: [
            new OA\Parameter(
                name: 'post_id',
                in: 'path',
                description: '貼文 ID',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得附件列表',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', example: '550e8400-e29b-41d4-a716-446655440000'),
                                    new OA\Property(property: 'post_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'filename', type: 'string', example: 'document.pdf'),
                                    new OA\Property(property: 'original_name', type: 'string', example: '重要文件.pdf'),
                                    new OA\Property(property: 'file_size', type: 'integer', description: '檔案大小（位元組）', example: 1024000),
                                    new OA\Property(property: 'mime_type', type: 'string', example: 'application/pdf'),
                                    new OA\Property(property: 'download_url', type: 'string', example: '/api/attachments/550e8400-e29b-41d4-a716-446655440000/download'),
                                    new OA\Property(property: 'uploaded_by', type: 'integer', example: 1),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00Z'),
                                ],
                            ),
                        ),
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
    public function list(Request $request, Response $response): Response
    {
        $postId = (int) $request->getAttribute('post_id');
        $attachments = $this->attachmentService->getByPostId($postId);

        $response->getBody()->write((json_encode([
            'data' => array_map(
                fn($attachment) => $attachment->toArray(),
                $attachments,
            ),
        ]) ?: '{"error": "JSON encoding failed"}'));

        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    }

    #[OA\Delete(
        path: '/attachments/{id}',
        summary: '刪除附件',
        description: '刪除指定的附件檔案，只有上傳者或管理員可以刪除',
        operationId: 'deleteAttachment',
        tags: ['attachments'],
        security: [
            ['bearerAuth' => []],
            ['sessionAuth' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: '附件 UUID',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: '附件刪除成功',
            ),
            new OA\Response(
                response: 400,
                description: '無效的附件識別碼',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: '無效的附件識別碼'),
                    ],
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
                description: '無權限刪除此附件',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: '您沒有權限刪除此附件'),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '附件不存在',
                content: new OA\JsonContent(
                    ref: '#/components/responses/NotFound',
                ),
            ),
        ],
    )]
    public function delete(Request $request, Response $response): Response
    {
        try {
            $currentUserId = $this->getCurrentUserId($request);
            $uuid = $request->getAttribute('id');
            if (!$uuid || !is_string($uuid)) {
                throw ValidationException::fromSingleError('uuid', '無效的附件識別碼');
            }
            $this->attachmentService->delete($uuid, $currentUserId);

            return $response->withStatus(204);
        } catch (ValidationException $e) {
            $response->getBody()->write((json_encode([
                'error' => $e->getMessage(),
            ]) ?: '{"error": "JSON encoding failed"}'));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $response->getBody()->write((json_encode([
                'error' => $e->getMessage(),
            ]) ?: '{"error": "JSON encoding failed"}'));

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
