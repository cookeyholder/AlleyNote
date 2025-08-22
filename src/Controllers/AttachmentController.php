<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AttachmentService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class AttachmentController
{
    public function __construct(
        private AttachmentService $attachmentService
    ) {}

    /**
     * 取得當前登入使用者 ID
     * TODO: 實作真正的使用者認證邏輯
     */
    private function getCurrentUserId(Request $request): int
    {
        // 從 request attributes 或 session 中取得使用者 ID
        $userId = $request->getAttribute('user_id');
        if ($userId === null) {
            throw new ValidationException('使用者未登入');
        }
        return (int) $userId;
    }

    public function upload(Request $request, Response $response): Response
    {
        try {
            $currentUserId = $this->getCurrentUserId($request);
            $postId = (int)$request->getAttribute('post_id');
            $files = $request->getUploadedFiles();

            if (!isset($files['file'])) {
                $response->getBody()->write(json_encode([
                    'error' => '缺少上傳檔案'
                ]));
                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            $attachment = $this->attachmentService->upload($postId, $files['file'], $currentUserId);

            $response->getBody()->write(json_encode([
                'data' => $attachment->toArray()
            ]));
            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json');
        } catch (ValidationException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    public function list(Request $request, Response $response): Response
    {
        $postId = (int)$request->getAttribute('post_id');
        $attachments = $this->attachmentService->getByPostId($postId);

        $response->getBody()->write(json_encode([
            'data' => array_map(
                fn($attachment) => $attachment->toArray(),
                $attachments
            )
        ]));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response): Response
    {
        try {
            $currentUserId = $this->getCurrentUserId($request);
            $uuid = $request->getAttribute('id');
            if (!$uuid || !is_string($uuid)) {
                throw new ValidationException('無效的附件識別碼');
            }
            $this->attachmentService->delete($uuid, $currentUserId);

            return $response->withStatus(204);
        } catch (ValidationException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
