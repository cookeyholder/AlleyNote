<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use OpenApi\Attributes as OA;

class PostController
{
    #[OA\Get(
        path: "/posts",
        summary: "取得所有貼文",
        description: "取得分頁的貼文列表，支援搜尋和篩選",
        operationId: "getPosts",
        tags: ["posts"],
        parameters: [
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "頁碼",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                description: "每頁筆數",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10, minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: "search",
                in: "query",
                description: "搜尋關鍵字",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "category",
                in: "query",
                description: "貼文分類篩選",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["general", "announcement", "urgent", "notice"]
                )
            ),
            new OA\Parameter(
                name: "status",
                in: "query",
                description: "貼文狀態篩選",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["draft", "published", "archived"]
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "成功取得貼文列表",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/PaginatedResponse"
                )
            ),
            new OA\Response(
                response: 400,
                description: "請求參數錯誤",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/ValidationError"
                )
            )
        ]
    )]
    public function index(Request $request, Response $response): Response
    {
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Post(
        path: "/posts",
        summary: "建立新貼文",
        description: "建立一篇新的貼文，需要 CSRF Token 驗證",
        operationId: "createPost",
        tags: ["posts"],
        security: [
            ["csrfToken" => []]
        ],
        requestBody: new OA\RequestBody(
            description: "貼文資料",
            required: true,
            content: new OA\JsonContent(
                ref: "#/components/schemas/CreatePostRequest"
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "貼文建立成功",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/ApiResponse"
                )
            ),
            new OA\Response(
                response: 400,
                description: "輸入資料驗證失敗",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/ValidationError"
                )
            ),
            new OA\Response(
                response: 401,
                description: "未授權存取",
                content: new OA\JsonContent(
                    ref: "#/components/responses/Unauthorized"
                )
            ),
            new OA\Response(
                response: 403,
                description: "CSRF Token 驗證失敗",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "CSRF token verification failed")
                    ]
                )
            )
        ]
    )]
    public function store(Request $request, Response $response): Response
    {
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Get(
        path: "/posts/{id}",
        summary: "取得單一貼文",
        description: "根據 ID 取得貼文詳細資訊，並記錄瀏覽次數",
        operationId: "getPostById",
        tags: ["posts"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "貼文 ID",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "成功取得貼文詳細資訊",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/Post")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "貼文不存在",
                content: new OA\JsonContent(
                    ref: "#/components/responses/NotFound"
                )
            )
        ]
    )]
    public function show(Request $request, Response $response, array $args): Response
    {
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Put(
        path: "/posts/{id}",
        summary: "更新貼文",
        description: "更新指定 ID 的貼文，需要 CSRF Token 驗證",
        operationId: "updatePost",
        tags: ["posts"],
        security: [
            ["csrfToken" => []]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "貼文 ID",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            description: "更新的貼文資料",
            required: true,
            content: new OA\JsonContent(
                ref: "#/components/schemas/UpdatePostRequest"
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "貼文更新成功",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/ApiResponse"
                )
            ),
            new OA\Response(
                response: 400,
                description: "輸入資料驗證失敗",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/ValidationError"
                )
            ),
            new OA\Response(
                response: 401,
                description: "未授權存取",
                content: new OA\JsonContent(
                    ref: "#/components/responses/Unauthorized"
                )
            ),
            new OA\Response(
                response: 403,
                description: "CSRF Token 驗證失敗",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "CSRF token verification failed")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "貼文不存在",
                content: new OA\JsonContent(
                    ref: "#/components/responses/NotFound"
                )
            )
        ]
    )]
    public function update(Request $request, Response $response, array $args): Response
    {
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Delete(
        path: "/posts/{id}",
        summary: "刪除貼文",
        description: "刪除指定 ID 的貼文，需要 CSRF Token 驗證",
        operationId: "deletePost",
        tags: ["posts"],
        security: [
            ["csrfToken" => []]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "貼文 ID",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: "貼文刪除成功"
            ),
            new OA\Response(
                response: 401,
                description: "未授權存取",
                content: new OA\JsonContent(
                    ref: "#/components/responses/Unauthorized"
                )
            ),
            new OA\Response(
                response: 403,
                description: "CSRF Token 驗證失敗",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "CSRF token verification failed")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "貼文不存在",
                content: new OA\JsonContent(
                    ref: "#/components/responses/NotFound"
                )
            )
        ]
    )]
    public function delete(Request $request, Response $response, array $args): Response
    {
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Patch(
        path: "/posts/{id}/pin",
        summary: "更新貼文置頂狀態",
        description: "設定或取消貼文的置頂狀態，需要 CSRF Token 驗證",
        operationId: "togglePostPin",
        tags: ["posts"],
        security: [
            ["csrfToken" => []]
        ],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "貼文 ID",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            description: "置頂狀態",
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "pinned",
                        type: "boolean",
                        description: "是否置頂",
                        example: true
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "置頂狀態更新成功",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "置頂狀態已更新"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Post")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "請求資料格式錯誤",
                content: new OA\JsonContent(
                    ref: "#/components/schemas/ValidationError"
                )
            ),
            new OA\Response(
                response: 401,
                description: "未授權存取",
                content: new OA\JsonContent(
                    ref: "#/components/responses/Unauthorized"
                )
            ),
            new OA\Response(
                response: 403,
                description: "CSRF Token 驗證失敗",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "CSRF token verification failed")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "貼文不存在",
                content: new OA\JsonContent(
                    ref: "#/components/responses/NotFound"
                )
            )
        ]
    )]
    public function togglePin(Request $request, Response $response, array $args): Response
    {
        return $response->withHeader('Content-Type', 'application/json');
    }
}
