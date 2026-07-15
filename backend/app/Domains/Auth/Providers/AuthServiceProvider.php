<?php

declare(strict_types=1);

namespace App\Domains\Auth\Providers;

use App\Application\Middleware\JwtAuthenticationMiddleware;
use App\Application\Middleware\JwtAuthorizationMiddleware;
use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use App\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use App\Domains\Auth\Services\AuthenticationService;
use App\Domains\Auth\Services\Authorization\AttributeAuthorizationStrategy;
use App\Domains\Auth\Services\Authorization\AuthorizationOrchestratorService;
use App\Domains\Auth\Services\Authorization\CustomRuleAuthorizationStrategy;
use App\Domains\Auth\Services\Authorization\PermissionAuthorizationStrategy;
use App\Domains\Auth\Services\Authorization\RoleAuthorizationStrategy;
use App\Domains\Auth\Services\Authorization\SuperAdminAuthorizationStrategy;
use App\Domains\Auth\Services\JwtTokenService;
use App\Domains\Auth\Services\RefreshTokenService;
use App\Domains\Auth\Services\TokenBlacklistService;
use App\Infrastructure\Auth\Jwt\FirebaseJwtProvider;
use App\Infrastructure\Auth\Repositories\RefreshTokenRepository;
use App\Infrastructure\Auth\Repositories\TokenBlacklistRepository;
use App\Shared\Config\JwtConfig;
use Psr\Container\ContainerInterface;

class AuthServiceProvider
{
    public static function getDefinitions(): array
    {
        return [
            JwtConfig::class                         => \DI\factory([self::class, 'createJwtConfig']),
            FirebaseJwtProvider::class               => \DI\factory([self::class, 'createFirebaseJwtProvider']),
            RefreshTokenRepositoryInterface::class   => \DI\create(RefreshTokenRepository::class),
            RefreshTokenRepository::class            => \DI\create(RefreshTokenRepository::class),
            TokenBlacklistRepositoryInterface::class => \DI\create(TokenBlacklistRepository::class),
            TokenBlacklistRepository::class          => \DI\create(TokenBlacklistRepository::class),
            JwtTokenServiceInterface::class          => \DI\factory([self::class, 'createJwtTokenService']),
            JwtTokenService::class                   => \DI\factory([self::class, 'createJwtTokenService']),
            AuthenticationServiceInterface::class    => \DI\factory([self::class, 'createAuthenticationService']),
            AuthenticationService::class             => \DI\factory([self::class, 'createAuthenticationService']),
            RefreshTokenService::class               => \DI\factory([self::class, 'createRefreshTokenService']),
            TokenBlacklistService::class             => \DI\factory([self::class, 'createTokenBlacklistService']),
            JwtAuthenticationMiddleware::class       => \DI\factory([self::class, 'createJwtAuthenticationMiddleware']),

            SuperAdminAuthorizationStrategy::class  => \DI\factory([self::class, 'createSuperAdminAuthorizationStrategy']),
            RoleAuthorizationStrategy::class        => \DI\factory([self::class, 'createRoleAuthorizationStrategy']),
            PermissionAuthorizationStrategy::class  => \DI\autowire(PermissionAuthorizationStrategy::class),
            AttributeAuthorizationStrategy::class   => \DI\factory([self::class, 'createAttributeAuthorizationStrategy']),
            CustomRuleAuthorizationStrategy::class  => \DI\factory([self::class, 'createCustomRuleAuthorizationStrategy']),
            AuthorizationOrchestratorService::class => \DI\factory([self::class, 'createAuthorizationOrchestrator']),
            JwtAuthorizationMiddleware::class       => \DI\factory([self::class, 'createJwtAuthorizationMiddleware']),

            'jwt.auth'      => \DI\get(JwtAuthenticationMiddleware::class),
            'jwt.authorize' => \DI\get(JwtAuthorizationMiddleware::class),
        ];
    }

    public static function createJwtConfig(ContainerInterface $container): JwtConfig
    {
        return new JwtConfig();
    }

    public static function createFirebaseJwtProvider(ContainerInterface $container): FirebaseJwtProvider
    {
        $config = $container->get(JwtConfig::class);

        return new FirebaseJwtProvider($config);
    }

    public static function createJwtTokenService(ContainerInterface $container): JwtTokenService
    {
        $jwtProvider = $container->get(FirebaseJwtProvider::class);
        $refreshTokenRepository = $container->get(RefreshTokenRepositoryInterface::class);
        $blacklistRepository = $container->get(TokenBlacklistRepositoryInterface::class);
        $config = $container->get(JwtConfig::class);

        return new JwtTokenService($jwtProvider, $refreshTokenRepository, $blacklistRepository, $config);
    }

    public static function createAuthenticationService(ContainerInterface $container): AuthenticationService
    {
        $jwtTokenService = $container->get(JwtTokenServiceInterface::class);
        $refreshTokenService = $container->get(RefreshTokenService::class);

        return new AuthenticationService($jwtTokenService, $refreshTokenService, null);
    }

    public static function createRefreshTokenService(ContainerInterface $container): RefreshTokenService
    {
        $jwtTokenService = $container->get(JwtTokenServiceInterface::class);
        $refreshTokenRepository = $container->get(RefreshTokenRepositoryInterface::class);
        $blacklistService = $container->get(TokenBlacklistService::class);

        return new RefreshTokenService($jwtTokenService, $refreshTokenRepository, $blacklistService);
    }

    public static function createTokenBlacklistService(ContainerInterface $container): TokenBlacklistService
    {
        $blacklistRepository = $container->get(TokenBlacklistRepositoryInterface::class);

        return new TokenBlacklistService($blacklistRepository);
    }

    public static function createJwtAuthenticationMiddleware(ContainerInterface $container): JwtAuthenticationMiddleware
    {
        $jwtTokenService = $container->get(JwtTokenServiceInterface::class);

        return new JwtAuthenticationMiddleware($jwtTokenService);
    }

    public static function createSuperAdminAuthorizationStrategy(ContainerInterface $container): SuperAdminAuthorizationStrategy
    {
        return new SuperAdminAuthorizationStrategy();
    }

    public static function createRoleAuthorizationStrategy(ContainerInterface $container): RoleAuthorizationStrategy
    {
        $rolePermissions = [
            'admin'     => ['*'],
            'moderator' => ['posts.*', 'comments.*'],
            'user'      => ['posts.show', 'posts.create', 'comments.show', 'comments.create'],
            'guest'     => ['posts.show', 'comments.show'],
        ];

        return new RoleAuthorizationStrategy($rolePermissions);
    }

    public static function createAttributeAuthorizationStrategy(ContainerInterface $container): AttributeAuthorizationStrategy
    {
        return new AttributeAuthorizationStrategy();
    }

    public static function createCustomRuleAuthorizationStrategy(ContainerInterface $container): CustomRuleAuthorizationStrategy
    {
        return new CustomRuleAuthorizationStrategy();
    }

    public static function createAuthorizationOrchestrator(ContainerInterface $container): AuthorizationOrchestratorService
    {
        /** @var SuperAdminAuthorizationStrategy $s1 */
        $s1 = $container->get(SuperAdminAuthorizationStrategy::class);
        /** @var RoleAuthorizationStrategy $s2 */
        $s2 = $container->get(RoleAuthorizationStrategy::class);
        /** @var PermissionAuthorizationStrategy $s3 */
        $s3 = $container->get(PermissionAuthorizationStrategy::class);
        /** @var AttributeAuthorizationStrategy $s4 */
        $s4 = $container->get(AttributeAuthorizationStrategy::class);
        /** @var CustomRuleAuthorizationStrategy $s5 */
        $s5 = $container->get(CustomRuleAuthorizationStrategy::class);

        return new AuthorizationOrchestratorService(
            strategies: [$s1, $s2, $s3, $s4, $s5],
            defaultPolicy: 'deny',
        );
    }

    public static function createJwtAuthorizationMiddleware(ContainerInterface $container): JwtAuthorizationMiddleware
    {
        /** @var AuthorizationOrchestratorService $orchestrator */
        $orchestrator = $container->get(AuthorizationOrchestratorService::class);

        return new JwtAuthorizationMiddleware(
            authorizationOrchestrator: $orchestrator,
        );
    }

    public static function getMiddlewareAliases(): array
    {
        return [
            'jwt.auth'      => JwtAuthenticationMiddleware::class,
            'jwt.authorize' => JwtAuthorizationMiddleware::class,
        ];
    }
}
