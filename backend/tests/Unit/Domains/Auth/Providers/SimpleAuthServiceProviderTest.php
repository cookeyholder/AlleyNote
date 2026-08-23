<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Providers;

use App\Application\Middleware\JwtAuthenticationMiddleware;
use App\Application\Middleware\JwtAuthorizationMiddleware;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use App\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\Providers\SimpleAuthServiceProvider;
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
use Mockery;
use PDO;
use Psr\Container\ContainerInterface;
use Tests\Support\UnitTestCase;

/**
 * 簡易 Auth 服務提供者單元測試.
 */
final class SimpleAuthServiceProviderTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['JWT_ALGORITHM'] = 'RS256';
        $_ENV['JWT_PRIVATE_KEY'] = "-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC7bjwmxz3eqbfh\nx2FiapDublS2Qx78RwQJCquPopfQKj1BxoGH7VQl65FU1Dytu+Q4E8o6FpjobU1i\n0+Ij7rAEe3mvLAqLmWZGFAQdF8mBhXEZLFVk3Fb7plwbBJMHWDrL9T19KJbp0TxL\n3XIao0YGfM5JqzlJ1dLVf9gBHhTGZpJiwLoA5prM5ABPS2QXMIBsNhzCQ0YR5dWj\n94fBTLYHPPj+9HeDtqq72iz6aTt+bgonm/5uqdH9Hcw0Vjiu6akvhPxE2HT4XXsE\nq7Z6LZDNRLUwKcQ+z9PHj8ZWM4PfF5OXGok90Vmj7NBVLpl7LZjaGY9wUC6mKgLa\nh+8WEmMlAgMBAAECggEACzhd5vdh84im7p/sK0NUYkWeEhwiCHmq2uy12P8jhe1l\nZeDfg7bYJP39YP3klQTstFOw9TnBlRZn/czP2pVRGa+XmP4ysmkwN20+0swH/tYx\nb0+ZXBSZq25p0J89OwET4f5QHEQ4Bo7FRIhg6onQKRbDFaNnpk0j1i6VTHnTxg2n\nI2nGb84Yfi1dUIDb6QkCwEe3xKMqGfEbNmBq6Rl2flW/bfhUJ6TbI5eyFgqaPCA8\nO0n2HOGckxVSHtJ+c4xBimalEXVDiWgyhkPRW++JWudb5OiP6EW1iO7vhyOXdNJJ\nmeGudIlAy9LQdETRzRyWlnSy+Y2kIgbAEjbMdZKnAQKBgQDipVL/cn0GoVGOXC6W\nxM5Kp+KuW+UW12940EWQRLEERfVD4FISaHJwGa802QFR2/7oe2uRbfiuLXuJc472\nskC+XStG5o3vN4j7UkalT5Ypofyyq1ku/ULUedw3zVrAw1cFNuJo+Pcans/feQYm\nRnJQp/sQvw/R4qYv5Tgi/U5rAQKBgQDTtLDEx4F9k0AhVuNJu1Bk08a+pyrlqoTn\nW6wClBdCYgucBBveqBA4lNWoKjw5hU0QHkUNRZ22x04B0WTS09CZ3cYT6WtcY3zz\njeWGHTSLJKTs865J7vNPCD4W37B6ptZWzX95NabKId2cqcP9bjnPGfk1N+yM4Ex3\nuNC1JxjsJQKBgQCqiBhmCh/WgETcJ7IKUTSi6aVe6df6ksjWD2d4AKdsfrLnin5W\nSW5puHmi+vDKRgyLommyeBtX+vLr3h4gssiSM4ofg9QhvRh9eU+cjMCAvNhlGxY0\ni+zf8HzpI8N4LMJqMvyyXTmYNwxTqj0dSX4z/+ChnhDqLG48tWzCrvN1AQKBgGVB\n/XKBQgxAC+JmXpv7fb5cFKlH55ql7p+CF0m8b0uO/aKHzJS4qdmGRpMCcH/KpEtb\nTwfEDmVH+qWf86trKFEP5BfOA03TQAZ2DhwRh/otcrzq6KfwJGves2PZZd2kQsyN\nybS91qLDg+3UvStQN1I5SBsOPpQ7DBgPS7P5mVAJAoGBAIC2SKgUbPxDM/IfeJF8\nI8qOp8rWxhTL5WTJN2zQ+pQnxAGuFwvr3SlAWvs2PcFzpitfJsT6qH+7/WtEdIte\npRcYEQK1OWubudCUg6c6Ou1QS+vEJwTx7wrK3vOTOOq1JfAw6A9+sIP9T0oejPzY\nk0i7JrI2Y0POAZZXyi/bxcF/\n-----END PRIVATE KEY-----";
        $_ENV['JWT_PUBLIC_KEY'] = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu248Jsc93qm34cdhYmqQ\n7m5UtkMe/EcECQqrj6KX0Co9QcaBh+1UJeuRVNQ8rbvkOBPKOhaY6G1NYtPiI+6w\nBHt5rywKi5lmRhQEHRfJgYVxGSxVZNxW+6ZcGwSTB1g6y/U9fSiW6dE8S91yGqNG\nBnzOSas5SdXS1X/YAR4UxmaSYsC6AOaazOQAT0tkFzCAbDYcwkNGEeXVo/eHwUy2\nBzz4/vR3g7aqu9os+mk7fm4KJ5v+bqnR/R3MNFY4rumpL4T8RNh0+F17BKu2ei2Q\nzUS1MCnEPs/Tx4/GVjOD3xeTlxqJPdFZo+zQVS6Zey2Y2hmPcFAupioC2ofvFhJj\nJQIDAQAB\n-----END PUBLIC KEY-----";
    }

    /**
     * 測試取得 DI 定義.
     */
    public function testGetDefinitions(): void
    {
        $definitions = SimpleAuthServiceProvider::getDefinitions();
        $this->assertArrayHasKey(JwtConfig::class, $definitions);
        $this->assertArrayHasKey(UserRepositoryInterface::class, $definitions);
        $this->assertArrayHasKey('jwt.auth', $definitions);
    }

    /**
     * 測試所有工廠方法.
     */
    public function testFactoryMethods(): void
    {
        $container = Mockery::mock(ContainerInterface::class);
        $pdo = new PDO('sqlite::memory:');

        // 1. JwtConfig & FirebaseJwtProvider
        $jwtConfig = SimpleAuthServiceProvider::createJwtConfig($container);
        $this->assertInstanceOf(JwtConfig::class, $jwtConfig);

        $container->shouldReceive('get')->with(JwtConfig::class)->andReturn($jwtConfig);
        $jwtProvider = SimpleAuthServiceProvider::createFirebaseJwtProvider($container);
        $this->assertInstanceOf(FirebaseJwtProvider::class, $jwtProvider);

        // 2. Repositories
        $container->shouldReceive('get')->with(PDO::class)->andReturn($pdo);
        $refreshTokenRepo = SimpleAuthServiceProvider::createRefreshTokenRepository($container);
        $this->assertInstanceOf(RefreshTokenRepository::class, $refreshTokenRepo);

        $blacklistRepo = SimpleAuthServiceProvider::createTokenBlacklistRepository($container);
        $this->assertInstanceOf(TokenBlacklistRepository::class, $blacklistRepo);

        $passwordSecurity = new PasswordSecurityService();
        $container->shouldReceive('get')->with(PasswordSecurityServiceInterface::class)->andReturn($passwordSecurity);
        $userRepoAdapter = SimpleAuthServiceProvider::createUserRepository($container);
        $this->assertInstanceOf(UserRepositoryAdapter::class, $userRepoAdapter);

        // 3. Services
        $container->shouldReceive('get')->with(FirebaseJwtProvider::class)->andReturn($jwtProvider);
        $container->shouldReceive('get')->with(RefreshTokenRepositoryInterface::class)->andReturn($refreshTokenRepo);
        $container->shouldReceive('get')->with(TokenBlacklistRepositoryInterface::class)->andReturn($blacklistRepo);
        $jwtTokenService = SimpleAuthServiceProvider::createJwtTokenService($container);
        $this->assertInstanceOf(JwtTokenService::class, $jwtTokenService);

        $blacklistService = SimpleAuthServiceProvider::createTokenBlacklistService($container);
        $this->assertInstanceOf(TokenBlacklistService::class, $blacklistService);

        $container->shouldReceive('get')->with(JwtTokenServiceInterface::class)->andReturn($jwtTokenService);
        $container->shouldReceive('get')->with(UserRepositoryInterface::class)->andReturn($userRepoAdapter);
        $authService = SimpleAuthServiceProvider::createAuthenticationService($container);
        $this->assertInstanceOf(AuthenticationService::class, $authService);

        $authMiddleware = SimpleAuthServiceProvider::createJwtAuthenticationMiddleware($container);
        $this->assertInstanceOf(JwtAuthenticationMiddleware::class, $authMiddleware);

        // 4. Authorization Strategies & Orchestrator & Middleware
        $s1 = SimpleAuthServiceProvider::createSuperAdminAuthorizationStrategy($container);
        $this->assertInstanceOf(SuperAdminAuthorizationStrategy::class, $s1);

        $s2 = SimpleAuthServiceProvider::createRoleAuthorizationStrategy($container);
        $this->assertInstanceOf(RoleAuthorizationStrategy::class, $s2);

        $s3 = new PermissionAuthorizationStrategy();

        $s4 = SimpleAuthServiceProvider::createAttributeAuthorizationStrategy($container);
        $this->assertInstanceOf(AttributeAuthorizationStrategy::class, $s4);

        $s5 = SimpleAuthServiceProvider::createCustomRuleAuthorizationStrategy($container);
        $this->assertInstanceOf(CustomRuleAuthorizationStrategy::class, $s5);

        $container->shouldReceive('get')->with(SuperAdminAuthorizationStrategy::class)->andReturn($s1);
        $container->shouldReceive('get')->with(RoleAuthorizationStrategy::class)->andReturn($s2);
        $container->shouldReceive('get')->with(PermissionAuthorizationStrategy::class)->andReturn($s3);
        $container->shouldReceive('get')->with(AttributeAuthorizationStrategy::class)->andReturn($s4);
        $container->shouldReceive('get')->with(CustomRuleAuthorizationStrategy::class)->andReturn($s5);

        $orchestrator = SimpleAuthServiceProvider::createAuthorizationOrchestrator($container);
        $this->assertInstanceOf(AuthorizationOrchestratorService::class, $orchestrator);

        $container->shouldReceive('get')->with(AuthorizationOrchestratorService::class)->andReturn($orchestrator);
        $authzMiddleware = SimpleAuthServiceProvider::createJwtAuthorizationMiddleware($container);
        $this->assertInstanceOf(JwtAuthorizationMiddleware::class, $authzMiddleware);
    }
}
