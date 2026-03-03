<?php

declare(strict_types=1);

namespace App\Domains\Auth\Providers;

use App\Application\Middleware\JwtAuthenticationMiddleware;
use App\Application\Middleware\JwtAuthorizationMiddleware;
use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Contracts\JwtProviderInterface;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use App\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\Services\AuthenticationService;
use App\Domains\Auth\Services\JwtTokenService;
use App\Domains\Auth\Services\RefreshTokenService;
use App\Domains\Auth\Services\TokenBlacklistService;
use App\Infrastructure\Auth\Jwt\FirebaseJwtProvider;
use App\Infrastructure\Auth\Repositories\RefreshTokenRepository;
use App\Infrastructure\Auth\Repositories\TokenBlacklistRepository;
use App\Shared\Config\JwtConfig;
use Psr\Container\ContainerInterface;

/**
 * JWT 認證服務提供者.
 *
 * 負責註冊所有 JWT 認證相關服務到 DI 容器
 */
class AuthServiceProvider
{
    /**
     * 取得所有認證服務定義.
     */
    public static function getDefinitions(): array
    {
        return [
            // JWT 配置
            JwtConfig::class => \DI\factory([self::class, 'createJwtConfig']),

            // JWT Provider
            FirebaseJwtProvider::class => \DI\factory([self::class, 'createFirebaseJwtProvider']),

            // Repository 層
            RefreshTokenRepositoryInterface::class => \DI\create(RefreshTokenRepository::class),
            RefreshTokenRepository::class => \DI\create(RefreshTokenRepository::class),

            TokenBlacklistRepositoryInterface::class => \DI\create(TokenBlacklistRepository::class),
            TokenBlacklistRepository::class => \DI\create(TokenBlacklistRepository::class),

            // Service 層
            JwtTokenServiceInterface::class => \DI\factory([self::class, 'createJwtTokenService']),
            JwtTokenService::class => \DI\factory([self::class, 'createJwtTokenService']),

            AuthenticationServiceInterface::class => \DI\factory([self::class, 'createAuthenticationService']),
            AuthenticationService::class => \DI\factory([self::class, 'createAuthenticationService']),

            RefreshTokenService::class => \DI\factory([self::class, 'createRefreshTokenService']),

            TokenBlacklistService::class => \DI\factory([self::class, 'createTokenBlacklistService']),

            // Middleware
            JwtAuthenticationMiddleware::class => \DI\factory([self::class, 'createJwtAuthenticationMiddleware']),
            JwtAuthorizationMiddleware::class => \DI\factory([self::class, 'createJwtAuthorizationMiddleware']),

            // Middleware 別名（為路由配置使用）
            'jwt.auth' => \DI\get(JwtAuthenticationMiddleware::class),
            'jwt.authorize' => \DI\get(JwtAuthorizationMiddleware::class),
        ];
    }

    /**
     * 建立 JWT 配置實例.
     */
    public static function createJwtConfig(ContainerInterface $container): JwtConfig
    {
        return new JwtConfig();
    }

    /**
     * 建立 Firebase JWT Provider 實例.
     */
    public static function createFirebaseJwtProvider(ContainerInterface $container): FirebaseJwtProvider
    {
        $config = $container->get(JwtConfig::class);
        assert($config instanceof JwtConfig);

        return new FirebaseJwtProvider($config);
    }

    /**
     * 建立 JWT Token Service 實例.
     */
    public static function createJwtTokenService(ContainerInterface $container): JwtTokenService
    {
        $jwtProvider = $container->get(FirebaseJwtProvider::class);
        assert($jwtProvider instanceof JwtProviderInterface);

        $refreshTokenRepository = $container->get(RefreshTokenRepositoryInterface::class);
        assert($refreshTokenRepository instanceof RefreshTokenRepositoryInterface);

        $blacklistRepository = $container->get(TokenBlacklistRepositoryInterface::class);
        assert($blacklistRepository instanceof TokenBlacklistRepositoryInterface);

        $config = $container->get(JwtConfig::class);
        assert($config instanceof JwtConfig);

        return new JwtTokenService($jwtProvider, $refreshTokenRepository, $blacklistRepository, $config);
    }

    /**
     * 建立認證服務實例.
     */
    public static function createAuthenticationService(ContainerInterface $container): AuthenticationService
    {
        $jwtTokenService = $container->get(JwtTokenServiceInterface::class);
        assert($jwtTokenService instanceof JwtTokenServiceInterface);

        $refreshTokenRepository = $container->get(RefreshTokenRepositoryInterface::class);
        assert($refreshTokenRepository instanceof RefreshTokenRepositoryInterface);

        $userRepository = $container->get(UserRepositoryInterface::class);
        assert($userRepository instanceof UserRepositoryInterface);

        return new AuthenticationService($jwtTokenService, $refreshTokenRepository, $userRepository);
    }

    /**
     * 建立 Refresh Token Service 實例.
     */
    public static function createRefreshTokenService(ContainerInterface $container): RefreshTokenService
    {
        $jwtTokenService = $container->get(JwtTokenServiceInterface::class);
        assert($jwtTokenService instanceof JwtTokenServiceInterface);

        $refreshTokenRepository = $container->get(RefreshTokenRepositoryInterface::class);
        assert($refreshTokenRepository instanceof RefreshTokenRepositoryInterface);

        $blacklistRepository = $container->get(TokenBlacklistRepositoryInterface::class);
        assert($blacklistRepository instanceof TokenBlacklistRepositoryInterface);

        return new RefreshTokenService($jwtTokenService, $refreshTokenRepository, $blacklistRepository);
    }

    /**
     * 建立 Token Blacklist Service 實例.
     */
    public static function createTokenBlacklistService(ContainerInterface $container): TokenBlacklistService
    {
        $blacklistRepository = $container->get(TokenBlacklistRepositoryInterface::class);
        assert($blacklistRepository instanceof TokenBlacklistRepositoryInterface);

        return new TokenBlacklistService($blacklistRepository);
    }

    /**
     * 建立 JWT 認證中介軟體實例.
     */
    public static function createJwtAuthenticationMiddleware(ContainerInterface $container): JwtAuthenticationMiddleware
    {
        $jwtTokenService = $container->get(JwtTokenServiceInterface::class);
        assert($jwtTokenService instanceof JwtTokenServiceInterface);

        return new JwtAuthenticationMiddleware($jwtTokenService);
    }

    /**
     * 建立 JWT 授權中介軟體實例.
     */
    public static function createJwtAuthorizationMiddleware(ContainerInterface $container): JwtAuthorizationMiddleware
    {
        return new JwtAuthorizationMiddleware();
    }

    /**
     * 取得中介軟體別名映射.
     */
    public static function getMiddlewareAliases(): array
    {
        return [
            'jwt.auth' => JwtAuthenticationMiddleware::class,
            'jwt.authorize' => JwtAuthorizationMiddleware::class,
        ];
    }
}
