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
use Exception;
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
     * @return array<string, callable>
     */
    public static function getDefinitions(): array
    {
        return [
            // Repository Interfaces
            ActivityLogRepositoryInterface::class => static function (ContainerInterface $container): ActivityLogRepository {
                /** @var PDO $db */
                $db = $container->get(PDO::class);

                return new ActivityLogRepository($db);
            },

            IpRepositoryInterface::class => static function (ContainerInterface $container): IpRepository {
                /** @var PDO $db */
                $db = $container->get(PDO::class);
                /** @var CacheServiceInterface $cache */
                $cache = $container->get(CacheServiceInterface::class);

                return new IpRepository($db, $cache);
            },

            // Service Interfaces
            ActivityLoggingServiceInterface::class => static function (ContainerInterface $container): ActivityLoggingService {
                /** @var ActivityLogRepositoryInterface $repository */
                $repository = $container->get(ActivityLogRepositoryInterface::class);
                /** @var LoggerInterface $logger */
                $logger = $container->get(LoggerInterface::class);

                return new ActivityLoggingService($repository, $logger);
            },

            CsrfProtectionServiceInterface::class => static function (ContainerInterface $container): CsrfProtectionService {
                /** @var ActivityLoggingServiceInterface $activityLogger */
                $activityLogger = $container->get(ActivityLoggingServiceInterface::class);

                return new CsrfProtectionService($activityLogger);
            },

            XssProtectionServiceInterface::class => static function (ContainerInterface $container): XssProtectionService {
                /** @var ActivityLoggingServiceInterface $activityLogger */
                $activityLogger = $container->get(ActivityLoggingServiceInterface::class);

                return new XssProtectionService($activityLogger);
            },

            IpServiceInterface::class => static function (ContainerInterface $container): IpService {
                /** @var IpRepositoryInterface $repository */
                $repository = $container->get(IpRepositoryInterface::class);
                /** @var ActivityLoggingServiceInterface $activityLogger */
                $activityLogger = $container->get(ActivityLoggingServiceInterface::class);

                return new IpService($repository, $activityLogger);
            },

            SecurityHeaderServiceInterface::class => static function (ContainerInterface $container): SecurityHeaderService {
                /** @var array<string, mixed> $config */
                $config = $container->get('config.security.headers') ?? [];

                return new SecurityHeaderService($config);
            },

            ErrorHandlerServiceInterface::class => static function (ContainerInterface $container): ErrorHandlerService {
                /** @var string $logPath */
                $logPath = $container->get('config.logging.path') ?? '/tmp/error.log';

                return new ErrorHandlerService($logPath);
            },

            SecretsManagerInterface::class => static function (ContainerInterface $container): SecretsManager {
                /** @var string $envPath */
                $envPath = $container->get('config.env.path') ?? '.env';

                return new SecretsManager($envPath);
            },

            // Concrete Implementations (optional bindings)
            ActivityLogRepository::class => static function (ContainerInterface $container): ActivityLogRepository {
                /** @var ActivityLogRepository $repository */
                $repository = $container->get(ActivityLogRepositoryInterface::class);

                return $repository;
            },

            IpRepository::class => static function (ContainerInterface $container): IpRepository {
                /** @var IpRepository $repository */
                $repository = $container->get(IpRepositoryInterface::class);

                return $repository;
            },

            ActivityLoggingService::class => static function (ContainerInterface $container): ActivityLoggingService {
                /** @var ActivityLoggingService $service */
                $service = $container->get(ActivityLoggingServiceInterface::class);

                return $service;
            },

            CsrfProtectionService::class => static function (ContainerInterface $container): CsrfProtectionService {
                /** @var CsrfProtectionService $service */
                $service = $container->get(CsrfProtectionServiceInterface::class);

                return $service;
            },

            XssProtectionService::class => static function (ContainerInterface $container): XssProtectionService {
                /** @var XssProtectionService $service */
                $service = $container->get(XssProtectionServiceInterface::class);

                return $service;
            },

            IpService::class => static function (ContainerInterface $container): IpService {
                /** @var IpService $service */
                $service = $container->get(IpServiceInterface::class);

                return $service;
            },

            SecurityHeaderService::class => static function (ContainerInterface $container): SecurityHeaderService {
                /** @var SecurityHeaderService $service */
                $service = $container->get(SecurityHeaderServiceInterface::class);

                return $service;
            },

            ErrorHandlerService::class => static function (ContainerInterface $container): ErrorHandlerService {
                /** @var ErrorHandlerService $service */
                $service = $container->get(ErrorHandlerServiceInterface::class);

                return $service;
            },

            SecretsManager::class => static function (ContainerInterface $container): SecretsManager {
                /** @var SecretsManager $service */
                $service = $container->get(SecretsManagerInterface::class);

                return $service;
            },
        ];
    }

    /**
     * 取得 Security 領域的設定值.
     */
    /**
     * @return array<string, mixed>
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
            // 檢查容器是否支援 set 方法
            if (method_exists($container, 'set')) {
                // 安全地調用 set 方法
                /** @var callable $setMethod */
                $setMethod = [$container, 'set'];
                call_user_func($setMethod, $abstract, $concrete);
            }
        }
    }

    /**
     * 取得需要初始化的服務清單.
     *
     * @return array<string>
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

                // 確保 service 是物件且有 boot 方法
                if (is_object($service) && method_exists($service, 'boot')) {
                    $service->boot();
                }
            }
        }
    }

    /**
     * 檢查服務依賴是否滿足.
     *
     * @return array<string, mixed>
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
     *
     * @return array<string, mixed>
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
                /** @var object $service */
                $service = $container->get($serviceName);
                $services[$serviceName] = [
                    'status' => 'healthy',
                    'class' => get_class($service),
                    'memory_usage' => memory_get_usage(true),
                ];
            } catch (Exception $e) {
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
