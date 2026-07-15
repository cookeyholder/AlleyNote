<?php

declare(strict_types=1);

namespace App\Domains\Auth\Providers;

use App\Application\Middleware\JwtAuthenticationMiddleware;
use App\Application\Middleware\JwtAuthorizationMiddleware;
use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use App\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\Repositories\UserRepository;
use App\Domains\Auth\Repositories\UserRepositoryAdapter;
use App\Domains\Auth\Services\AuthenticationService;
use App\Domains\Auth\Services\Authorization\AttributeAuthorizationStrategy;
use App\Domains\Auth\Services\Authorization\AuthorizationOrchestratorService;
use App\Domains\Auth\Services\Authorization\CustomRuleAuthorizationStrategy;
use App\Domains\Auth\Services\Authorization\PermissionAuthorizationStrategy;
use App\Domains\Auth\Services\Authorization\RoleAuthorizationStrategy;
use App\Domains\Auth\Services\Authorization\SuperAdminAuthorizationStrategy;
use App\Domains\Auth\Services\JwtTokenService;
use App\Domains\Auth\Services\PasswordSecurityService;
use App\Domains\Auth\Services\TokenBlacklistService;
use App\Infrastructure\Auth\Jwt\FirebaseJwtProvider;
use App\Infrastructure\Auth\Repositories\RefreshTokenRepository;
use App\Infrastructure\Auth\Repositories\TokenBlacklistRepository;
use App\Shared\Config\JwtConfig;
use PDO;
use Psr\Container\ContainerInterface;

class SimpleAuthServiceProvider
{
    public static function getDefinitions(): array
    {
        return [
            JwtConfig::class                         => \DI\factory([self::class, 'createJwtConfig']),
            FirebaseJwtProvider::class               => \DI\factory([self::class, 'createFirebaseJwtProvider']),
            RefreshTokenRepositoryInterface::class   => \DI\factory([self::class, 'createRefreshTokenRepository']),
            TokenBlacklistRepositoryInterface::class => \DI\factory([self::class, 'createTokenBlacklistRepository']),
            UserRepositoryInterface::class           => \DI\factory([self::class, 'createUserRepository']),
            PasswordSecurityServiceInterface::class  => \DI\autowire(PasswordSecurityService::class),
            AuthenticationServiceInterface::class    => \DI\factory([self::class, 'createAuthenticationService']),
            JwtTokenServiceInterface::class          => \DI\factory([self::class, 'createJwtTokenService']),
            TokenBlacklistService::class             => \DI\factory([self::class, 'createTokenBlacklistService']),
            JwtAuthenticationMiddleware::class       => \DI\factory([self::class, 'createJwtAuthenticationMiddleware']),

            // 授權策略註冊
            SuperAdminAuthorizationStrategy::class => \DI\factory([self::class, 'createSuperAdminAuthorizationStrategy']),
            RoleAuthorizationStrategy::class       => \DI\factory([self::class, 'createRoleAuthorizationStrategy']),
            PermissionAuthorizationStrategy::class => \DI\autowire(PermissionAuthorizationStrategy::class),
            AttributeAuthorizationStrategy::class  => \DI\factory([self::class, 'createAttributeAuthorizationStrategy']),
            CustomRuleAuthorizationStrategy::class => \DI\factory([self::class, 'createCustomRuleAuthorizationStrategy']),

            // 授權協調服務
            AuthorizationOrchestratorService::class => \DI\factory([self::class, 'createAuthorizationOrchestrator']),

            // 中介軟體（注入協調服務）
            JwtAuthorizationMiddleware::class => \DI\factory([self::class, 'createJwtAuthorizationMiddleware']),

            'jwt.auth'      => \DI\get(JwtAuthenticationMiddleware::class),
            'jwt.authorize' => \DI\get(JwtAuthorizationMiddleware::class),
        ];
    }

    public static function createJwtConfig(ContainerInterface $container): JwtConfig
    {
        return new JwtConfig();
    }

    public static function createRefreshTokenRepository(ContainerInterface $container): RefreshTokenRepository
    {
        $pdo = $container->get(PDO::class);

        return new RefreshTokenRepository($pdo);
    }

    public static function createTokenBlacklistRepository(ContainerInterface $container): TokenBlacklistRepository
    {
        $pdo = $container->get(PDO::class);

        return new TokenBlacklistRepository($pdo);
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

    public static function createTokenBlacklistService(ContainerInterface $container): TokenBlacklistService
    {
        $blacklistRepository = $container->get(TokenBlacklistRepositoryInterface::class);

        return new TokenBlacklistService($blacklistRepository);
    }

    public static function createUserRepository(ContainerInterface $container): UserRepositoryAdapter
    {
        /** @var PDO $pdo */
        $pdo = $container->get(PDO::class);
        /** @var PasswordSecurityServiceInterface $passwordService */
        $passwordService = $container->get(PasswordSecurityServiceInterface::class);
        $userRepository = new UserRepository($pdo, $passwordService);

        return new UserRepositoryAdapter($userRepository);
    }

    public static function createAuthenticationService(ContainerInterface $container): AuthenticationService
    {
        /** @var JwtTokenServiceInterface $jwtTokenService */
        $jwtTokenService = $container->get(JwtTokenServiceInterface::class);
        /** @var RefreshTokenRepositoryInterface $refreshTokenRepository */
        $refreshTokenRepository = $container->get(RefreshTokenRepositoryInterface::class);
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $container->get(UserRepositoryInterface::class);

        return new AuthenticationService($jwtTokenService, $refreshTokenRepository, $userRepository);
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
}
