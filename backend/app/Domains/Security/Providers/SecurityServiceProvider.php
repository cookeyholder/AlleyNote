<?php

declare(strict_types=1);

namespace App\Domains\Security\Providers;

use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Domains\Security\Contracts\CsrfProtectionServiceInterface;
use App\Domains\Security\Contracts\ErrorHandlerServiceInterface;
use App\Domains\Security\Contracts\IpRepositoryInterface;
use App\Domains\Security\Contracts\IpServiceInterface;
use App\Domains\Security\Contracts\SecretsManagerInterface;
use App\Domains\Security\Contracts\SecurityHeaderServiceInterface;
use App\Domains\Security\Contracts\XssProtectionServiceInterface;
use App\Domains\Security\Repositories\ActivityLogRepository;
use App\Domains\Security\Repositories\IpRepository;
use App\Domains\Security\Services\ActivityLoggingService;
use App\Domains\Security\Services\Core\CsrfProtectionService;
use App\Domains\Security\Services\Core\XssProtectionService;
use App\Domains\Security\Services\Error\ErrorHandlerService;
use App\Domains\Security\Services\Headers\SecurityHeaderService;
use App\Domains\Security\Services\IpService;
use App\Domains\Security\Services\Secrets\SecretsManager;
use App\Shared\Contracts\CacheServiceInterface;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Security 領域服務提供者.
 *
 * 負責註冊所有 Security 相關服務到 DI 容器，
 * 遵循 DDD 架構原則，確保依賴注入的正確配置。
 */
class SecurityServiceProvider
{
    /**
     * 取得所有 Security 服務定義.
     */
    public static function getDefinitions(): array
    {
        return [
            // Repository Interfaces
            ActivityLogRepositoryInterface::class => static function (ContainerInterface $container): ActivityLogRepository {
                return new ActivityLogRepository(
                    $container->get(PDO::class),
                );
            },

            IpRepositoryInterface::class => static function (ContainerInterface $container): IpRepository {
                return new IpRepository(
                    $container->get(PDO::class),
                    $container->get(CacheServiceInterface::class),
                );
            },

            // Service Interfaces
            ActivityLoggingServiceInterface::class => static function (ContainerInterface $container): ActivityLoggingService {
                return new ActivityLoggingService(
                    $container->get(ActivityLogRepositoryInterface::class),
                    $container->get(LoggerInterface::class),
                );
            },

            CsrfProtectionServiceInterface::class => static function (ContainerInterface $container): CsrfProtectionService {
                return new CsrfProtectionService(
                    $container->get(ActivityLoggingServiceInterface::class),
                );
            },

            XssProtectionServiceInterface::class => static function (ContainerInterface $container): XssProtectionService {
                return new XssProtectionService(
                    $container->get(LoggerInterface::class),
                );
            },

            IpServiceInterface::class => static function (ContainerInterface $container): IpService {
                return new IpService(
                    $container->get(IpRepositoryInterface::class),
                    $container->get(LoggerInterface::class),
                );
            },

            SecurityHeaderServiceInterface::class => static function (ContainerInterface $container): SecurityHeaderService {
                return new SecurityHeaderService(
                    $container->get(LoggerInterface::class),
                );
            },

            ErrorHandlerServiceInterface::class => static function (ContainerInterface $container): ErrorHandlerService {
                return new ErrorHandlerService(
                    $container->get(LoggerInterface::class),
                );
            },

            SecretsManagerInterface::class => static function (ContainerInterface $container): SecretsManager {
                return new SecretsManager(
                    $container->get(LoggerInterface::class),
                );
            },

            // Concrete Implementations (optional bindings)
            ActivityLogRepository::class => static function (ContainerInterface $container): ActivityLogRepository {
                return $container->get(ActivityLogRepositoryInterface::class);
            },

            IpRepository::class => static function (ContainerInterface $container): IpRepository {
                return $container->get(IpRepositoryInterface::class);
            },

            ActivityLoggingService::class => static function (ContainerInterface $container): ActivityLoggingService {
                return $container->get(ActivityLoggingServiceInterface::class);
            },

            CsrfProtectionService::class => static function (ContainerInterface $container): CsrfProtectionService {
                return $container->get(CsrfProtectionServiceInterface::class);
            },

            XssProtectionService::class => static function (ContainerInterface $container): XssProtectionService {
                return $container->get(XssProtectionServiceInterface::class);
            },

            IpService::class => static function (ContainerInterface $container): IpService {
                return $container->get(IpServiceInterface::class);
            },

            SecurityHeaderService::class => static function (ContainerInterface $container): SecurityHeaderService {
                return $container->get(SecurityHeaderServiceInterface::class);
            },

            ErrorHandlerService::class => static function (ContainerInterface $container): ErrorHandlerService {
                return $container->get(ErrorHandlerServiceInterface::class);
            },

            SecretsManager::class => static function (ContainerInterface $container): SecretsManager {
                return $container->get(SecretsManagerInterface::class);
            },
        ];
    }

    /**
     * 取得 Security 領域的設定值.
     */
    public static function getConfiguration(): array
    {
        return [
            'security' => [
                'csrf' => [
                    'token_name' => '_token',
                    'token_length' => 32,
                    'expire_time' => 3600, // 1 hour
                ],
                'xss' => [
                    'encoding' => 'UTF-8',
                    'double_encode' => false,
                    'allowed_tags' => ['<b>', '<i>', '<u>', '<strong>', '<em>'],
                ],
                'headers' => [
                    'x_frame_options' => 'DENY',
                    'x_content_type_options' => 'nosniff',
                    'x_xss_protection' => '1; mode=block',
                    'strict_transport_security' => 'max-age=31536000; includeSubDomains',
                    'content_security_policy' => "default-src 'self'",
                ],
                'rate_limiting' => [
                    'max_attempts' => 100,
                    'decay_minutes' => 60,
                    'block_duration' => 900, // 15 minutes
                ],
                'ip_filtering' => [
                    'whitelist_enabled' => false,
                    'blacklist_enabled' => true,
                    'auto_ban_threshold' => 50,
                    'auto_ban_duration' => 3600, // 1 hour
                ],
            ],
        ];
    }

    /**
     * 註冊所有 Security 服務到容器.
     */
    public static function register(ContainerInterface $container): void
    {
        $definitions = self::getDefinitions();

        foreach ($definitions as $abstract => $concrete) {
            if (method_exists($container, 'set')) {
                $container->set($abstract, $concrete);
            }
        }
    }

    /**
     * 取得需要初始化的服務清單.
     */
    public static function getBootableServices(): array
    {
        return [
            SecurityHeaderServiceInterface::class,
            ErrorHandlerServiceInterface::class,
            SecretsManagerInterface::class,
        ];
    }

    /**
     * 執行服務初始化.
     */
    public static function boot(ContainerInterface $container): void
    {
        $bootableServices = self::getBootableServices();

        foreach ($bootableServices as $serviceClass) {
            if ($container->has($serviceClass)) {
                $service = $container->get($serviceClass);

                // 如果服務有 boot 方法，執行初始化
                if (method_exists($service, 'boot')) {
                    $service->boot();
                }
            }
        }
    }

    /**
     * 檢查服務依賴是否滿足.
     */
    public static function checkDependencies(ContainerInterface $container): array
    {
        $dependencies = [
            PDO::class => $container->has(PDO::class),
            LoggerInterface::class => $container->has(LoggerInterface::class),
        ];

        return $dependencies;
    }

    /**
     * 取得服務健康檢查資訊.
     */
    public static function getHealthCheck(ContainerInterface $container): array
    {
        $services = [];
        $definitions = self::getDefinitions();

        foreach (array_keys($definitions) as $serviceName) {
            if (!str_contains($serviceName, '\\')) {
                continue; // 跳過非類別名稱的定義
            }

            try {
                $service = $container->get($serviceName);
                $services[$serviceName] = [
                    'status' => 'healthy',
                    'class' => get_class($service),
                    'memory_usage' => memory_get_usage(true),
                ];
            } catch (\Exception $e) {
                $services[$serviceName] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'provider' => self::class,
            'services' => $services,
            'dependencies' => self::checkDependencies($container),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
}
