<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface AuthApiInterface
{
    #[OA\Post(path: '/api/auth/register', summary: '使用者註冊', tags: ['auth'])]
    public function register(Request $request, Response $response): Response;

    #[OA\Post(path: '/api/auth/login', summary: '使用者登入', tags: ['auth'])]
    public function login(Request $request, Response $response): Response;

    #[OA\Post(path: '/api/auth/logout', summary: '使用者登出', tags: ['auth'])]
    public function logout(Request $request, Response $response): Response;

    #[OA\Get(path: '/api/auth/me', summary: '取得當前使用者資訊', tags: ['auth'])]
    public function me(Request $request, Response $response): Response;

    #[OA\Post(path: '/api/auth/refresh', summary: '刷新 Token', tags: ['auth'])]
    public function refresh(Request $request, Response $response): Response;

    #[OA\Put(path: '/api/auth/profile', summary: '更新個人資料', tags: ['auth'])]
    public function updateProfile(Request $request, Response $response): Response;

    #[OA\Post(path: '/api/auth/change-password', summary: '變更密碼', tags: ['auth'])]
    public function changePassword(Request $request, Response $response): Response;
}

