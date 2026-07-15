<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domains\Auth\Services\Authorization\AuthorizationContext;
use App\Domains\Auth\Services\Authorization\AuthorizationOrchestratorService;
use App\Domains\Auth\ValueObjects\AuthorizationResult;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class JwtAuthorizationMiddleware implements MiddlewareInterface
{
    private const DEFAULT_PRIORITY = 20;

    private const MIDDLEWARE_NAME = 'jwt-authorization';

    private const DEFAULT_POLICY = 'deny';

    private const ADMIN_ROLES = ['admin', 'super_admin', 'system_admin'];

    private array $config;

    public function __construct(
        private AuthorizationOrchestratorService $authorizationOrchestrator,
        private int $priority = self::DEFAULT_PRIORITY,
        private bool $enabled = true,
        array $config = [],
    ) {
        $this->config = array_merge(self::getDefaultConfig(), $config);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (!$this->enabled || !$this->shouldProcess($request)) {
            return $handler->handle($request);
        }

        try {
            if (!$request->getAttribute('authenticated', false)) {
                return $this->createForbiddenResponse('使用者未認證', 'NOT_AUTHENTICATED');
            }

            $userRoleAttr = $request->getAttribute('role');
            $userPermissionsAttr = $request->getAttribute('permissions', []);
            $userIdAttr = $request->getAttribute('user_id');
            $userRole = is_string($userRoleAttr) ? $userRoleAttr : null;
            /** @var array<string> $userPermissions */
            $userPermissions = is_array($userPermissionsAttr)
                ? array_values(array_filter($userPermissionsAttr, 'is_string'))
                : [];
            $userId = is_numeric($userIdAttr) ? (int) $userIdAttr : 0;

            $resource = $this->extractResource($request);
            $action = $this->extractAction($request);

            $context = new AuthorizationContext(
                userId: $userId,
                userRole: $userRole,
                userPermissions: $userPermissions,
                resource: $resource,
                action: $action,
                request: $request,
            );

            $authorizationResult = $this->authorizationOrchestrator->authorize($context);

            if (!$authorizationResult->isAllowed()) {
                return $this->createForbiddenResponse(
                    $authorizationResult->getReason(),
                    $authorizationResult->getCode(),
                );
            }

            $request = $this->injectAuthorizationContext($request, $authorizationResult);

            return $handler->handle($request);
        } catch (Throwable $e) {
            return $this->createForbiddenResponse('授權檢查失敗', 'AUTHORIZATION_ERROR');
        }
    }

    private function extractResource(ServerRequestInterface $request): string
    {
        $path = trim($request->getUri()->getPath(), '/');
        $segments = explode('/', $path);

        if (count($segments) >= 3 && $segments[0] === 'api') {
            return $segments[2];
        }

        $routeResource = $request->getAttribute('route_resource');
        if ($routeResource !== null) {
            return $routeResource;
        }

        return 'unknown';
    }

    private function extractAction(ServerRequestInterface $request): string
    {
        $method = strtoupper($request->getMethod());
        $path = trim($request->getUri()->getPath(), '/');
        $segments = explode('/', $path);

        $routeAction = $request->getAttribute('route_action');
        if ($routeAction !== null) {
            return $routeAction;
        }

        $resourceId = $this->extractResourceIdFromPath($segments);

        return match ($method) {
            'GET'          => $resourceId ? 'show' : 'index',
            'POST'         => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE'       => 'delete',
            default        => 'unknown'
        };
    }

    private function extractResourceIdFromPath(array $segments): ?int
    {
        if (count($segments) >= 4 && $segments[0] === 'api' && is_numeric($segments[3])) {
            return (int) $segments[3];
        }

        foreach ($segments as $segment) {
            if (is_numeric($segment)) {
                return (int) $segment;
            }
        }

        return null;
    }

    private function injectAuthorizationContext(
        ServerRequestInterface $request,
        AuthorizationResult $result,
    ): ServerRequestInterface {
        return $request
            ->withAttribute('authorization_result', $result)
            ->withAttribute('authorization_allowed', $result->isAllowed())
            ->withAttribute('authorization_reason', $result->getReason())
            ->withAttribute('authorization_code', $result->getCode())
            ->withAttribute('applied_rules', $result->getAppliedRules());
    }

    private function createForbiddenResponse(string $message, string $code = 'FORBIDDEN'): ResponseInterface
    {
        $responseData = [
            'success'   => false,
            'error'     => $message,
            'code'      => $code,
            'timestamp' => date('c'),
        ];

        return new Response(
            statusCode: 403,
            headers: ['Content-Type' => 'application/json'],
            body: json_encode($responseData, JSON_UNESCAPED_UNICODE) ?: '',
        );
    }

    public function shouldProcess(ServerRequestInterface $request): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $skipPaths = array_merge(
            [
                '/auth/login',
                '/auth/register',
                '/auth/refresh',
                '/health',
                '/status',
                '/favicon.ico',
            ],
            $this->config['skip_paths'] ?? [],
        );
        $path = $request->getUri()->getPath();
        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return false;
            }
        }

        $authPaths = $this->config['auth_paths'] ?? ['/api/', '/admin/'];
        foreach ($authPaths as $authPath) {
            if (str_starts_with($path, $authPath)) {
                return true;
            }
            if ($authPath === '/admin/' && $path === '/admin') {
                return true;
            }
        }

        return false;
    }

    private static function getDefaultConfig(): array
    {
        return [
            'default_policy' => self::DEFAULT_POLICY,
            'admin_roles'    => self::ADMIN_ROLES,
            'skip_paths'     => [],
            'auth_paths'     => ['/api/', '/admin/'],
        ];
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getName(): string
    {
        return self::MIDDLEWARE_NAME;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }
}
