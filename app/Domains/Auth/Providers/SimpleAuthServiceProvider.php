<?php

declare(strict_types=1);

namespace App\Domains\Auth\Providers;

use App\Application\Middleware\JwtAuthenticationMiddleware;
use App\Application\Middleware\JwtAuthorizationMiddleware;
use AlleyNote\Domains\Auth\Contracts\JwtTokenServiceInterface;
use AlleyNote\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use AlleyNote\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use AlleyNote\Domains\Auth\Services\JwtTokenService;
use AlleyNote\Domains\Auth\Services\TokenBlacklistService;
use AlleyNote\Infrastructure\Auth\Jwt\FirebaseJwtProvider;
use AlleyNote\Infrastructure\Auth\Repositories\RefreshTokenRepository;
use AlleyNote\Infrastructure\Auth\Repositories\TokenBlacklistRepository;
use AlleyNote\Shared\Config\JwtConfig;
use Psr\Container\ContainerInterface;

/**
 * JWT 認證服務提供者（簡化版）.
 *
 * 負責註冊 JWT 中介軟體和基本服務到 DI 容器
 */
class SimpleAuthServiceProvider
{
    /**
     * 取得中介軟體和基本服務定義.
     */
    public static function getDefinitions(): array
    {
        return [
            // 基本配置和服務
            JwtConfig::class => \DI\factory([self::class, 'createJwtConfig']),
            FirebaseJwtProvider::class => \DI\factory([self::class, 'createFirebaseJwtProvider']),
            
            // Repository (簡單建立)
            RefreshTokenRepositoryInterface::class => \DI\create(RefreshTokenRepository::class),
            TokenBlacklistRepositoryInterface::class => \DI\create(TokenBlacklistRepository::class),
            
            // Token Service (簡化版本)
            JwtTokenServiceInterface::class => \DI\factory([self::class, 'createJwtTokenService']),
            
            // Blacklist Service
            TokenBlacklistService::class => \DI\factory([self::class, 'createTokenBlacklistService']),

            // Middleware - 主要目標
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
        return new FirebaseJwtProvider($config);
    }

    /**
     * 建立 JWT Token Service 實例（簡化版）.
     */
    public static function createJwtTokenService(ContainerInterface $container): JwtTokenService
    {
        $jwtProvider = $container->get(FirebaseJwtProvider::class);
        $refreshTokenRepository = $container->get(RefreshTokenRepositoryInterface::class);
        $blacklistRepository = $container->get(TokenBlacklistRepositoryInterface::class);
        $config = $container->get(JwtConfig::class);

        return new JwtTokenService($jwtProvider, $refreshTokenRepository, $blacklistRepository, $config);
    }

    /**
     * 建立 Token Blacklist Service 實例.
     */
    public static function createTokenBlacklistService(ContainerInterface $container): TokenBlacklistService
    {
        $blacklistRepository = $container->get(TokenBlacklistRepositoryInterface::class);
        return new TokenBlacklistService($blacklistRepository);
    }

    /**
     * 建立 JWT 認證中介軟體實例.
     */
    public static function createJwtAuthenticationMiddleware(ContainerInterface $container): JwtAuthenticationMiddleware
    {
        $jwtTokenService = $container->get(JwtTokenServiceInterface::class);
        $blacklistService = $container->get(TokenBlacklistService::class);

        return new JwtAuthenticationMiddleware($jwtTokenService, $blacklistService);
    }

    /**
     * 建立 JWT 授權中介軟體實例.
     */
    public static function createJwtAuthorizationMiddleware(ContainerInterface $container): JwtAuthorizationMiddleware
    {
        return new JwtAuthorizationMiddleware();
    }
}