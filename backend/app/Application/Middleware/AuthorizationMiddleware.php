<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domains\Auth\Contracts\AuthorizationServiceInterface;
use App\Infrastructure\Http\Response;

class AuthorizationMiddleware
{
    private AuthorizationServiceInterface $authorizationService;

    public function __construct(AuthorizationServiceInterface $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    public function checkPermission(int $userId, string $resource, string $action): bool
    {
        return $this->authorizationService->can($userId, $resource, $action);
    }

    public function requirePermission(int $userId, string $resource, string $action): Response
    {
        if (!$this->checkPermission($userId, $resource, $action)) {
            return new Response(
                statusCode: 403,
                headers: ['Content-Type' => 'application/json'],
                body: (string) json_encode([
                    'error' => '您沒有權限執行此操作',
                    'code' => 'FORBIDDEN',
                ], JSON_UNESCAPED_UNICODE),
            );
        }

        return new Response(statusCode: 200);
    }

    public function requireRole(int $userId, string $roleName): Response
    {
        if (!$this->authorizationService->hasRole($userId, $roleName)) {
            return new Response(
                statusCode: 403,
                headers: ['Content-Type' => 'application/json'],
                body: (string) json_encode([
                    'error' => '需要特定角色才能執行此操作',
                    'code' => 'FORBIDDEN',
                ], JSON_UNESCAPED_UNICODE),
            );
        }

        return new Response(statusCode: 200);
    }

    public function extractResourceFromPath(string $path): string
    {
        if (str_contains($path, '/posts')) {
            return 'post';
        } elseif (str_contains($path, '/attachments')) {
            return 'attachment';
        } elseif (str_contains($path, '/ip')) {
            return 'ip';
        } elseif (str_contains($path, '/users')) {
            return 'user';
        } elseif (str_contains($path, '/system')) {
            return 'system';
        }

        return 'unknown';
    }

    public function extractActionFromMethod(string $method): string
    {
        return match (strtoupper($method)) {
            'GET' => 'read',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'unknown'
        };
    }
}
