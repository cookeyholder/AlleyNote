<?php

declare(strict_types=1);

namespace App\Domains\Security\Providers;

use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Domains\Security\Contracts\CsrfProtectionServiceInterface;
use App\Domains\Security\Contracts\IpRepositoryInterface;
use App\Domains\Security\Contracts\IpServiceInterface;
use App\Domains\Security\Contracts\XssProtectionServiceInterface;
use App\Domains\Security\Repositories\ActivityLogRepository;
use App\Domains\Security\Services\ActivityLoggingService;
use App\Domains\Security\Services\Core\CsrfProtectionService;
use App\Domains\Security\Services\Core\XssProtectionService;
use App\Domains\Security\Services\IpService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Security 領域服務提供者.
 *
 * 負責註冊所有 Security 相關服務到 DI 容器
 */
class SecurityServiceProvider
{
    /**
     * 取得所有 Security 服務定義.
     */
    public static function getDefinitions(): array
    {
        return [
            // Activity Log Repository Interface
            ActivityLogRepositoryInterface::class => \DI\create(ActivityLogRepository::class),

            // Activity Logging Service
            ActivityLoggingServiceInterface::class => \DI\factory([self::class, 'createActivityLoggingService']),

            // CSRF Protection Service Interface & Implementation
            CsrfProtectionServiceInterface::class => \DI\factory([self::class, 'createCsrfProtectionService']),
            CsrfProtectionService::class => \DI\get(CsrfProtectionServiceInterface::class),

            // XSS Protection Service Interface & Implementation
            XssProtectionServiceInterface::class => \DI\factory([self::class, 'createXssProtectionService']),
            XssProtectionService::class => \DI\get(XssProtectionServiceInterface::class),

            // IP Service Interface & Implementation
            IpServiceInterface::class => \DI\factory([self::class, 'createIpService']),
            IpService::class => \DI\get(IpServiceInterface::class),
        ];
    }

    /**
     * 建立 ActivityLoggingService 實例.
     */
    public static function createActivityLoggingService(ContainerInterface $container): ActivityLoggingService
    {
        $repository = $container->get(ActivityLogRepositoryInterface::class);

        // 使用簡單的 error_log 作為 logger（暫時解決方案）
        $logger = new class implements LoggerInterface {
            public function emergency(Stringable|string $message, array $context = []): void
            {
                error_log("[EMERGENCY] $message");
            }

            public function alert(Stringable|string $message, array $context = []): void
            {
                error_log("[ALERT] $message");
            }

            public function critical(Stringable|string $message, array $context = []): void
            {
                error_log("[CRITICAL] $message");
            }

            public function error(Stringable|string $message, array $context = []): void
            {
                error_log("[ERROR] $message");
            }

            public function warning(Stringable|string $message, array $context = []): void
            {
                error_log("[WARNING] $message");
            }

            public function notice(Stringable|string $message, array $context = []): void
            {
                error_log("[NOTICE] $message");
            }

            public function info(Stringable|string $message, array $context = []): void
            {
                error_log("[INFO] $message");
            }

            public function debug(Stringable|string $message, array $context = []): void
            {
                error_log("[DEBUG] $message");
            }

            public function log($level, Stringable|string $message, array $context = []): void
            {
                error_log("[$level] $message");
            }
        };

        return new ActivityLoggingService($repository, $logger);
    }

    /**
     * 建立 CsrfProtectionService 實例.
     */
    public static function createCsrfProtectionService(ContainerInterface $container): CsrfProtectionServiceInterface
    {
        /** @var ActivityLoggingServiceInterface $activityLogger */
        $activityLogger = $container->get(ActivityLoggingServiceInterface::class);

        return new CsrfProtectionService($activityLogger);
    }

    /**
     * 建立 XssProtectionService 實例.
     */
    public static function createXssProtectionService(ContainerInterface $container): XssProtectionServiceInterface
    {
        /** @var ActivityLoggingServiceInterface $activityLogger */
        $activityLogger = $container->get(ActivityLoggingServiceInterface::class);

        return new XssProtectionService($activityLogger);
    }

    /**
     * 建立 IpService 實例.
     */
    public static function createIpService(ContainerInterface $container): IpServiceInterface
    {
        /** @var IpRepositoryInterface $repository */
        $repository = $container->get(IpRepositoryInterface::class);
        /** @var ActivityLoggingServiceInterface $activityLogger */
        $activityLogger = $container->get(ActivityLoggingServiceInterface::class);

        return new IpService($repository, $activityLogger);
    }
}
