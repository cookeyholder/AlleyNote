<?php

declare(strict_types=1);

namespace App\Controllers;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class TestController
{
    #[OA\Get(
        path: "/test",
        tags: ["test"],
        responses: [new OA\Response(response: 200, description: "Test endpoint")]
    )]
    public function test(Request $request, Response $response): Response
    {
        return $response;
    }

    #[OA\Post(
        path: "/test",
        tags: ["test"],
        responses: [new OA\Response(response: 201, description: "Test post endpoint")]
    )]
    public function testPost(Request $request, Response $response): Response
    {
        return $response;
    }
}
