<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PostController extends BaseController
{
    /**
     * 取得所有貼文.
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = [
            'posts' => [
                ['id' => 1, 'title' => '第一篇貼文', 'content' => '這是第一篇貼文的內容'],
                ['id' => 2, 'title' => '第二篇貼文', 'content' => '這是第二篇貼文的內容'],
            ],
        ];

        $response->getBody()->write($this->successResponse($data));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 取得單一貼文.
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, int $id): ResponseInterface
    {
        $post = [
            'id' => $id,
            'title' => "貼文 #{$id}",
            'content' => "這是第 {$id} 篇貼文的內容",
            'created_at' => date('c'),
        ];

        $response->getBody()->write($this->successResponse($post));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 建立新貼文.
     */
    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $request->getParsedBody();

        $post = [
            'id' => rand(1000, 9999),
            'title' => '未命名貼文',
            'content' => $data ? $body->content : null) ?? '',
            'created_at' => date('c'),
        ];

        $response->getBody()->write($this->successResponse($post, '貼文建立成功'));

        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    /**
     * 更新貼文.
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, int $id): ResponseInterface
    {
        $body = $request->getParsedBody();

        $post = [
            'id' => $id,
            'title' => "更新的貼文 #{$id}",
            'content' => $data ? $body->content : null) ?? '更新後的內容',
            'updated_at' => date('c'),
        ];

        $response->getBody()->write($this->successResponse($post, '貼文更新成功'));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 刪除貼文.
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, int $id): ResponseInterface
    {
        $result = [
            'deleted_id' => $id,
            'deleted_at' => date('c'),
        ];

        $response->getBody()->write($this->successResponse($result, '貼文刪除成功'));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
