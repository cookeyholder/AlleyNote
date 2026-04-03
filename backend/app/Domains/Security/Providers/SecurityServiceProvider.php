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

class SecurityServiceProvider
{
    /**
     * 取得所有 Security 服務定義.
     */
    public static function getDefinitions(): array
    {
        return [
            // Activity Log Repository Interface
            ActivityLogRepositoryInterface::class => \DI\autowire(ActivityLogRepository::class),
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
        // 使用共用的 Monolog helper 作為 logger
        $logger = new class implements LoggerInterface {
            public function emergency(Stringable|string $message, array $context = []): void
            {
                app_log('emergency', (string) $message, $context);
            }

            public function alert(Stringable|string $message, array $context = []): void
            {
                app_log('alert', (string) $message, $context);
            }

            public function critical(Stringable|string $message, array $context = []): void
            {
                app_log('critical', (string) $message, $context);
            }

            public function error(Stringable|string $message, array $context = []): void
            {
                app_log('error', (string) $message, $context);
            }

            public function warning(Stringable|string $message, array $context = []): void
            {
                app_log('warning', (string) $message, $context);
            }

            public function notice(Stringable|string $message, array $context = []): void
            {
                app_log('notice', (string) $message, $context);
            }

            public function info(Stringable|string $message, array $context = []): void
            {
                app_log('info', (string) $message, $context);
            }

            public function debug(Stringable|string $message, array $context = []): void
            {
                app_log('debug', (string) $message, $context);
            }

            public function log($level, Stringable|string $message, array $context = []): void
            {
                $normalizedLevel = is_string($level) || $level instanceof Stringable
                    ? (string) $level
                    : 'info';
                app_log($normalizedLevel, (string) $message, $context);
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
