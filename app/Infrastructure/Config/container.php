<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

// Database 相關
use App\Infrastructure\Database\DatabaseConnection;

// Repositories
use App\Domains\Post\Contracts\PostRepositoryInterface;
use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\Repositories\UserRepository;
use App\Domains\Attachment\Contracts\AttachmentRepositoryInterface;
use App\Domains\Attachment\Repositories\AttachmentRepository;
use App\Domains\Security\Contracts\IpRepositoryInterface;
use App\Domains\Security\Repositories\IpRepository;

// Services
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\Services\PostService;
use App\Shared\Contracts\CacheServiceInterface;
use App\Infrastructure\Services\CacheService;
use App\Domains\Attachment\Services\AttachmentService;
use App\Domains\Auth\Services\AuthService;
use App\Domains\Security\Services\IpService;
use App\Infrastructure\Services\RateLimitService;

// Security Services
use App\Domains\Security\Contracts\XssProtectionServiceInterface;
use App\Domains\Security\Services\Core\XssProtectionService;
use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\Services\PasswordSecurityService;
use App\Domains\Security\Contracts\CsrfProtectionServiceInterface;
use App\Domains\Security\Services\Core\CsrfProtectionService;
use App\Domains\Auth\Contracts\AuthorizationServiceInterface;
use App\Domains\Auth\Services\AuthorizationService;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Domains\Security\Services\Logging\LoggingSecurityService;
use App\Domains\Auth\Contracts\SessionSecurityServiceInterface;
use App\Domains\Auth\Services\SessionSecurityService;
use App\Domains\Security\Services\Headers\SecurityHeaderService;

// Validation Services
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Validation\Validator;
use App\Shared\Validation\Factory\ValidatorFactory;

return [
    // Database
    DatabaseConnection::class => function (ContainerInterface $container) {
        return DatabaseConnection::getInstance();
    },

    PDO::class => function (ContainerInterface $container) {
        return DatabaseConnection::getInstance();
    },

    // Cache Service
    CacheServiceInterface::class => DI\autowire(CacheService::class),

    // 直接註冊 CacheService 以向後相容
    CacheService::class => DI\autowire(CacheService::class),

    // Repositories
    PostRepositoryInterface::class => DI\autowire(PostRepository::class)
        ->constructorParameter('db', DI\get(PDO::class))
        ->constructorParameter('cache', DI\get(CacheServiceInterface::class))
        ->constructorParameter('logger', DI\get(LoggingSecurityServiceInterface::class)),

    UserRepositoryInterface::class => DI\autowire(UserRepository::class)
        ->constructorParameter('db', DI\get(PDO::class)),

    AttachmentRepositoryInterface::class => DI\autowire(AttachmentRepository::class)
        ->constructorParameter('db', DI\get(PDO::class)),

    IpRepositoryInterface::class => DI\autowire(IpRepository::class)
        ->constructorParameter('db', DI\get(PDO::class)),

    // Services
    PostServiceInterface::class => DI\autowire(PostService::class)
        ->constructorParameter('repository', DI\get(PostRepositoryInterface::class)),

    AuthService::class => DI\autowire(AuthService::class)
        ->constructorParameter('userRepository', DI\get(UserRepositoryInterface::class))
        ->constructorParameter('passwordService', DI\get(PasswordSecurityServiceInterface::class)),

    AttachmentService::class => DI\autowire(AttachmentService::class)
        ->constructorParameter('attachmentRepo', DI\get(AttachmentRepositoryInterface::class))
        ->constructorParameter('postRepo', DI\get(PostRepositoryInterface::class))
        ->constructorParameter('cache', DI\get(CacheServiceInterface::class))
        ->constructorParameter('authService', DI\get(AuthorizationServiceInterface::class))
        ->constructorParameter('uploadDir', __DIR__ . '/../../../storage/uploads'),

    IpService::class => DI\autowire(IpService::class)
        ->constructorParameter('ipRepository', DI\get(IpRepositoryInterface::class)),

    RateLimitService::class => DI\autowire(RateLimitService::class)
        ->constructorParameter('cache', DI\get(CacheServiceInterface::class)),

    // Security Services
    XssProtectionServiceInterface::class => DI\autowire(XssProtectionService::class),

    CsrfProtectionServiceInterface::class => DI\autowire(CsrfProtectionService::class),

    LoggingSecurityServiceInterface::class => DI\autowire(LoggingSecurityService::class),

    PasswordSecurityServiceInterface::class => DI\autowire(PasswordSecurityService::class),

    SessionSecurityServiceInterface::class => DI\autowire(SessionSecurityService::class),

    // AuthorizationService
    AuthorizationServiceInterface::class => DI\autowire(AuthorizationService::class)
        ->constructorParameter('db', DI\get(PDO::class))
        ->constructorParameter('cache', DI\get(CacheServiceInterface::class)),

    SecurityHeaderService::class => DI\autowire(SecurityHeaderService::class)
        ->constructorParameter('config', [
            'csp' => [
                'enabled' => true,
                'policy' => [
                    'default-src' => ["'self'"],
                    'script-src' => ["'self'", "'unsafe-inline'"],
                    'style-src' => ["'self'", "'unsafe-inline'"],
                    'img-src' => ["'self'", 'data:', 'https:'],
                    'font-src' => ["'self'"],
                    'connect-src' => ["'self'"],
                    'media-src' => ["'self'"],
                    'object-src' => ["'none'"],
                    'child-src' => ["'self'"],
                    'frame-ancestors' => ["'none'"],
                    'form-action' => ["'self'"],
                    'upgrade-insecure-requests' => true,
                ],
            ],
            'hsts' => [
                'enabled' => true,
                'max_age' => 31536000,
                'include_subdomains' => true,
                'preload' => false,
            ],
            'frame_options' => [
                'enabled' => true,
                'value' => 'DENY',
            ],
            'content_type_options' => [
                'enabled' => true,
            ],
            'xss_protection' => [
                'enabled' => true,
            ],
        ]),

    // Validation Services
    ValidatorFactory::class => DI\autowire(ValidatorFactory::class),

    ValidatorInterface::class => DI\factory(function (ValidatorFactory $factory) {
        return $factory->createForDTO();
    }),

    // Legacy validator registration for backwards compatibility
    Validator::class => DI\autowire(Validator::class),

    // Controllers (使用 autowire 自動注入依賴)
    App\Application\Controllers\Api\V1\PostController::class => DI\autowire(),
    App\Application\Controllers\Api\V1\AttachmentController::class => DI\autowire(),
    App\Application\Controllers\Api\V1\AuthController::class => DI\autowire(),
    App\Application\Controllers\Api\V1\IpController::class => DI\autowire(),
    App\Application\Controllers\Web\SwaggerController::class => DI\autowire(),
    App\Application\Controllers\Health\HealthController::class => DI\autowire(),
    App\Application\Controllers\Security\CSPReportController::class => DI\autowire(),
];
