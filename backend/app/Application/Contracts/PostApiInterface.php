<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface PostApiInterface
{
    #[OA\Get(path: '/api/posts', summary: '取得貼文列表', tags: ['posts'])]
    public function index(Request $request, Response $response): Response;

    #[OA\Post(path: '/api/posts', summary: '建立貼文', tags: ['posts'])]
    public function store(Request $request, Response $response): Response;

    #[OA\Get(path: '/api/posts/{id}', summary: '取得單一貼文', tags: ['posts'])]
    public function show(Request $request, Response $response, array $args): Response;

    #[OA\Put(path: '/api/posts/{id}', summary: '更新貼文', tags: ['posts'])]
    public function update(Request $request, Response $response, array $args): Response;

    #[OA\Delete(path: '/api/posts/{id}', summary: '刪除貼文', tags: ['posts'])]
    public function delete(Request $request, Response $response, array $args): Response;

    #[OA\Delete(path: '/api/posts/{id}/destroy', summary: '永久刪除貼文', tags: ['posts'])]
    public function destroy(Request $request, Response $response, array $args): Response;

    #[OA\Patch(path: '/api/posts/{id}/pin', summary: '切換置頂狀態', tags: ['posts'])]
    public function togglePin(Request $request, Response $response, array $args): Response;

    #[OA\Patch(path: '/api/posts/{id}/publish', summary: '發佈貼文', tags: ['posts'])]
    public function publish(Request $request, Response $response, array $args): Response;

    #[OA\Patch(path: '/api/posts/{id}/unpublish', summary: '取消發佈貼文', tags: ['posts'])]
    public function unpublish(Request $request, Response $response, array $args): Response;

    #[OA\Delete(path: '/api/posts/batch', summary: '批次刪除貼文', tags: ['posts'])]
    public function batchDelete(Request $request, Response $response): Response;

    #[OA\Patch(path: '/api/posts/{id}/unpin', summary: '取消置頂貼文', tags: ['posts'])]
    public function unpin(Request $request, Response $response, array $args): Response;
}

