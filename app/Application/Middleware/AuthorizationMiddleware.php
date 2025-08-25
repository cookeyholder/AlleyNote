<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domains\Auth\Contracts\AuthorizationServiceInterface;

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

    public function requirePermission(int $userId, string $resource, string $action): void
    {
        if (!$this->checkPermission($userId, $resource, $action)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => '您沒有權限執行此操作',
                'code' => 'FORBIDDEN',
            ]);
            exit;
        }
    }

    public function requireRole(int $userId, string $roleName): void
    {
        if (!$this->authorizationService->hasRole($userId, $roleName)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => '需要特定角色才能執行此操作',
                'code' => 'FORBIDDEN',
            ]);
            exit;
        }
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
