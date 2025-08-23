<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use OpenApi\Attributes as OA;

class PostController
{
    #[OA\Get(path: "/posts", summary: "列出所有文章")]
    #[OA\Response(response: 200, description: "成功")]
    public function index(Request $request, Response $response): Response
    {
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Post(path: "/posts", summary: "建立文章")]
    #[OA\Response(response: 201, description: "建立成功")]
    public function store(Request $request, Response $response): Response
    {
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Get(path: "/posts/{id}", summary: "取得文章")]
    #[OA\Response(response: 200, description: "成功")]
    public function show(Request $request, Response $response, array $args): Response
    {
        return $response->withHeader('Content-Type', 'application/json');
    }
}
