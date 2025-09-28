# 專案架構分析報告（基於 Context7 MCP 最新技術）

**生成時間**: 2025-09-28 21:45:48

## 📊 程式碼品質指標

| 指標 | 數值 | 狀態 |
|------|------|------|
| 總類別數 | 294 | - |
| PSR-4 合規率 | 98.64% | ✅ 優秀 |
| 現代 PHP 採用率 | 74.72% | ⚠️ 可改善 |

## 🚀 現代 PHP 特性使用情況

| 特性 | 使用次數 | 描述 |
|------|----------|------|
| Match 表達式 (PHP 8.0+) | 92 | ✅ 更安全的條件分支 |
| 空安全運算子 (PHP 8.0+) | 114 | ✅ 防止 null 指標異常 |
| 屬性標籤 (PHP 8.0+) | 72 | ✅ 現代化 metadata |
| 聯合型別 (PHP 8.0+) | 351 | ✅ 更靈活的型別定義 |
| 建構子屬性提升 (PHP 8.0+) | 21 | ✅ 減少樣板程式碼 |
| 列舉型別 (PHP 8.1+) | 9 | ✅ 型別安全的常數 |

## 🏷️ 命名空間分析

### `App`
- app/Application.php

### `App\Domains\Statistics\Contracts`
- app/Domains/Statistics/Contracts/StatisticsFormatterInterface.php
- app/Domains/Statistics/Contracts/StatisticsVisualizationServiceInterface.php
- app/Domains/Statistics/Contracts/StatisticsMonitoringServiceInterface.php
- app/Domains/Statistics/Contracts/BatchExportResult.php
- app/Domains/Statistics/Contracts/StatisticsQueryServiceInterface.php
- app/Domains/Statistics/Contracts/StatisticsExportServiceInterface.php
- app/Domains/Statistics/Contracts/StatisticsRepositoryInterface.php
- app/Domains/Statistics/Contracts/StatisticsAggregationServiceInterface.php
- app/Domains/Statistics/Contracts/PostStatisticsRepositoryInterface.php
- app/Domains/Statistics/Contracts/StatisticsCacheServiceInterface.php
- app/Domains/Statistics/Contracts/SlowQueryMonitoringServiceInterface.php
- app/Domains/Statistics/Contracts/StatisticsSnapshotRepositoryInterface.php
- app/Domains/Statistics/Contracts/ExportResult.php
- app/Domains/Statistics/Contracts/UserStatisticsRepositoryInterface.php

### `App\Domains\Statistics\Providers`
- app/Domains/Statistics/Providers/StatisticsServiceProvider.php

### `App\Domains\Statistics\Models`
- app/Domains/Statistics/Models/StatisticsSnapshot.php

### `App\Domains\Statistics\DTOs`
- app/Domains/Statistics/DTOs/StatisticsOverviewDTO.php
- app/Domains/Statistics/DTOs/ContentInsightsDTO.php
- app/Domains/Statistics/DTOs/SourceDistributionDTO.php
- app/Domains/Statistics/DTOs/PostStatisticsDTO.php
- app/Domains/Statistics/DTOs/UserStatisticsDTO.php

### `App\Domains\Statistics\Events`
- app/Domains/Statistics/Events/PostViewed.php
- app/Domains/Statistics/Events/StatisticsSnapshotCreated.php

### `App\Domains\Statistics\Listeners`
- app/Domains/Statistics/Listeners/StatisticsSnapshotCreatedListener.php
- app/Domains/Statistics/Listeners/PostViewedListener.php

### `App\Domains\Statistics\Services`
- app/Domains/Statistics/Services/StatisticsConfigService.php
- app/Domains/Statistics/Services/StatisticsAggregationService.php

### `App\Domains\Statistics\Entities`
- app/Domains/Statistics/Entities/StatisticsSnapshot.php

### `App\Domains\Statistics\ValueObjects`
- app/Domains/Statistics/ValueObjects/CategoryDataPoint.php
- app/Domains/Statistics/ValueObjects/ChartData.php
- app/Domains/Statistics/ValueObjects/TimeSeriesDataPoint.php
- app/Domains/Statistics/ValueObjects/ChartDataset.php
- app/Domains/Statistics/ValueObjects/StatisticsMetric.php
- app/Domains/Statistics/ValueObjects/StatisticsPeriod.php
- app/Domains/Statistics/ValueObjects/SourceType.php

### `App\Domains\Post\Contracts`
- app/Domains/Post/Contracts/PostRepositoryInterface.php
- app/Domains/Post/Contracts/PostServiceInterface.php

### `App\Domains\Post\Enums`

### `App\Domains\Post\Repositories`
- app/Domains/Post/Repositories/PostRepository.php

### `App\Domains\Post\Models`
- app/Domains/Post/Models/Post.php

### `App\Domains\Post\Exceptions`
- app/Domains/Post/Exceptions/PostStatusException.php
- app/Domains/Post/Exceptions/PostValidationException.php
- app/Domains/Post/Exceptions/PostNotFoundException.php

### `App\Domains\Post\DTOs`
- app/Domains/Post/DTOs/UpdatePostDTO.php
- app/Domains/Post/DTOs/CreatePostDTO.php

### `App\Domains\Post\Services`
- app/Domains/Post/Services/PostService.php
- app/Domains/Post/Services/ContentModerationService.php
- app/Domains/Post/Services/RichTextProcessorService.php
- app/Domains/Post/Services/PostCacheKeyService.php

### `App\Domains\Post\Validation`
- app/Domains/Post/Validation/PostValidator.php

### `App\Domains\Security\Contracts`
- app/Domains/Security/Contracts/IpRepositoryInterface.php
- app/Domains/Security/Contracts/ErrorHandlerServiceInterface.php
- app/Domains/Security/Contracts/SecurityTestInterface.php
- app/Domains/Security/Contracts/SecurityHeaderServiceInterface.php
- app/Domains/Security/Contracts/LoggingSecurityServiceInterface.php
- app/Domains/Security/Contracts/XssProtectionServiceInterface.php
- app/Domains/Security/Contracts/IpServiceInterface.php
- app/Domains/Security/Contracts/SecretsManagerInterface.php
- app/Domains/Security/Contracts/ActivityLoggingServiceInterface.php
- app/Domains/Security/Contracts/SuspiciousActivityDetectorInterface.php
- app/Domains/Security/Contracts/ActivityLogRepositoryInterface.php
- app/Domains/Security/Contracts/CsrfProtectionServiceInterface.php

### `App\Domains\Security\Providers`
- app/Domains/Security/Providers/SecurityServiceProvider.php

### `App\Domains\Security\Enums`

### `App\Domains\Security\Repositories`
- app/Domains/Security/Repositories/IpRepository.php
- app/Domains/Security/Repositories/ActivityLogRepository.php

### `App\Domains\Security\Models`
- app/Domains/Security/Models/IpList.php

### `App\Domains\Security\DTOs`
- app/Domains/Security/DTOs/SuspiciousActivityAnalysisDTO.php
- app/Domains/Security/DTOs/CreateActivityLogDTO.php
- app/Domains/Security/DTOs/CreateIpRuleDTO.php
- app/Domains/Security/DTOs/ActivityLogSearchDTO.php

### `App\Domains\Security\Services\Advanced`
- app/Domains/Security/Services/Advanced/SecurityTestService.php

### `App\Domains\Security\Services\Core`
- app/Domains/Security/Services/Core/XssProtectionService.php
- app/Domains/Security/Services/Core/CsrfProtectionService.php

### `App\Domains\Security\Services`
- app/Domains/Security/Services/SuspiciousActivityDetector.php
- app/Domains/Security/Services/IpService.php
- app/Domains/Security/Services/ActivityLoggingService.php

### `App\Domains\Security\Services\Secrets`
- app/Domains/Security/Services/Secrets/SecretsManager.php

### `App\Domains\Security\Services\Content`
- app/Domains/Security/Services/Content/XssProtectionExtensionService.php

### `App\Domains\Security\Services\Headers`
- app/Domains/Security/Services/Headers/SecurityHeaderService.php

### `App\Domains\Security\Services\Error`
- app/Domains/Security/Services/Error/ErrorHandlerService.php

### `App\Domains\Security\Services\Logging`
- app/Domains/Security/Services/Logging/LoggingSecurityService.php

### `App\Domains\Security\Entities`
- app/Domains/Security/Entities/ActivityLog.php

### `App\Domains\Auth\Contracts`
- app/Domains/Auth/Contracts/RefreshTokenRepositoryInterface.php
- app/Domains/Auth/Contracts/AuthenticationServiceInterface.php
- app/Domains/Auth/Contracts/JwtProviderInterface.php
- app/Domains/Auth/Contracts/UserRepositoryInterface.php
- app/Domains/Auth/Contracts/JwtTokenServiceInterface.php
- app/Domains/Auth/Contracts/SessionSecurityServiceInterface.php
- app/Domains/Auth/Contracts/TokenBlacklistRepositoryInterface.php
- app/Domains/Auth/Contracts/AuthorizationServiceInterface.php
- app/Domains/Auth/Contracts/PasswordSecurityServiceInterface.php

### `App\Domains\Auth\Providers`
- app/Domains/Auth/Providers/AuthServiceProvider.php
- app/Domains/Auth/Providers/SimpleAuthServiceProvider.php

### `App\Domains\Auth\Repositories`
- app/Domains/Auth/Repositories/UserRepositoryAdapter.php
- app/Domains/Auth/Repositories/UserRepository.php

### `App\Domains\Auth\Models`
- app/Domains/Auth/Models/Role.php
- app/Domains/Auth/Models/Permission.php

### `App\Domains\Auth\Exceptions`
- app/Domains/Auth/Exceptions/TokenExpiredException.php
- app/Domains/Auth/Exceptions/JwtException.php
- app/Domains/Auth/Exceptions/ForbiddenException.php
- app/Domains/Auth/Exceptions/InvalidTokenException.php
- app/Domains/Auth/Exceptions/RefreshTokenException.php
- app/Domains/Auth/Exceptions/TokenValidationException.php
- app/Domains/Auth/Exceptions/UnauthorizedException.php
- app/Domains/Auth/Exceptions/AuthenticationException.php
- app/Domains/Auth/Exceptions/TokenParsingException.php
- app/Domains/Auth/Exceptions/TokenGenerationException.php
- app/Domains/Auth/Exceptions/JwtConfigurationException.php

### `App\Domains\Auth\DTOs`
- app/Domains/Auth/DTOs/LoginRequestDTO.php
- app/Domains/Auth/DTOs/LogoutRequestDTO.php
- app/Domains/Auth/DTOs/RefreshResponseDTO.php
- app/Domains/Auth/DTOs/LoginResponseDTO.php
- app/Domains/Auth/DTOs/RegisterUserDTO.php
- app/Domains/Auth/DTOs/RefreshRequestDTO.php

### `App\Domains\Auth\Services\Advanced`
- app/Domains/Auth/Services/Advanced/PwnedPasswordService.php

### `App\Domains\Auth\Services`
- app/Domains/Auth/Services/SessionSecurityService.php
- app/Domains/Auth/Services/AuthenticationService.php
- app/Domains/Auth/Services/AuthService.php
- app/Domains/Auth/Services/PasswordManagementService.php
- app/Domains/Auth/Services/PasswordSecurityService.php
- app/Domains/Auth/Services/RefreshTokenService.php
- app/Domains/Auth/Services/AuthorizationService.php
- app/Domains/Auth/Services/TokenBlacklistService.php
- app/Domains/Auth/Services/JwtTokenService.php

### `App\Domains\Auth\Entities`
- app/Domains/Auth/Entities/RefreshToken.php

### `App\Domains\Auth\ValueObjects`
- app/Domains/Auth/ValueObjects/TokenBlacklistEntry.php
- app/Domains/Auth/ValueObjects/TokenPair.php
- app/Domains/Auth/ValueObjects/DeviceInfo.php
- app/Domains/Auth/ValueObjects/JwtPayload.php

### `App\Domains\Attachment\Contracts`
- app/Domains/Attachment/Contracts/FileSecurityServiceInterface.php
- app/Domains/Attachment/Contracts/AttachmentRepositoryInterface.php
- app/Domains/Attachment/Contracts/AttachmentServiceInterface.php

### `App\Domains\Attachment\Enums`
- app/Domains/Attachment/Enums/FileRules.php

### `App\Domains\Attachment\Repositories`
- app/Domains/Attachment/Repositories/AttachmentRepository.php

### `App\Domains\Attachment\Models`
- app/Domains/Attachment/Models/Attachment.php

### `App\Domains\Attachment\DTOs`
- app/Domains/Attachment/DTOs/CreateAttachmentDTO.php

### `App\Domains\Attachment\Services`
- app/Domains/Attachment/Services/AttachmentService.php
- app/Domains/Attachment/Services/FileSecurityService.php

### `App\Shared\Cache\Drivers`
- app/Shared/Cache/Drivers/MemoryCacheDriver.php
- app/Shared/Cache/Drivers/FileCacheDriver.php
- app/Shared/Cache/Drivers/RedisCacheDriver.php
- app/Shared/Cache/Drivers/LayeredCacheDriver.php

### `App\Shared\Cache\Strategies`
- app/Shared/Cache/Strategies/DefaultCacheStrategy.php

### `App\Shared\Cache\Contracts`
- app/Shared/Cache/Contracts/TagRepositoryInterface.php
- app/Shared/Cache/Contracts/CacheStrategyInterface.php
- app/Shared/Cache/Contracts/CacheDriverInterface.php
- app/Shared/Cache/Contracts/CacheManagerInterface.php
- app/Shared/Cache/Contracts/TaggedCacheInterface.php

### `App\Shared\Cache\Providers`
- app/Shared/Cache/Providers/CacheServiceProvider.php

### `App\Shared\Cache\Repositories`
- app/Shared/Cache/Repositories/RedisTagRepository.php
- app/Shared/Cache/Repositories/MemoryTagRepository.php

### `App\Shared\Cache\Services`
- app/Shared/Cache/Services/DefaultCacheStrategy.php
- app/Shared/Cache/Services/CacheGroupManager.php
- app/Shared/Cache/Services/TaggedCacheManager.php
- app/Shared/Cache/Services/CacheManager.php
- app/Shared/Cache/Services/PrefixedCacheManager.php

### `App\Shared\Cache\ValueObjects`
- app/Shared/Cache/ValueObjects/CacheTag.php

### `App\Shared\Config`
- app/Shared/Config/EnvironmentConfig.php
- app/Shared/Config/JwtConfig.php

### `App\Shared\Contracts`
- app/Shared/Contracts/RepositoryInterface.php
- app/Shared/Contracts/CacheServiceInterface.php
- app/Shared/Contracts/OutputSanitizerInterface.php
- app/Shared/Contracts/ValidatorInterface.php

### `App\Shared\Exceptions`
- app/Shared/Exceptions/NotFoundException.php
- app/Shared/Exceptions/StateTransitionException.php
- app/Shared/Exceptions/CsrfTokenException.php
- app/Shared/Exceptions/ValidationException.php

### `App\Shared\Exceptions\Validation`
- app/Shared/Exceptions/Validation/RequestValidationException.php

### `App\Shared\Schemas`
- app/Shared/Schemas/PostSchema.php
- app/Shared/Schemas/PostRequestSchema.php
- app/Shared/Schemas/AuthSchema.php

### `App\Shared\DTOs`
- app/Shared/DTOs/BaseDTO.php

### `App\Shared\Http`
- app/Shared/Http/ApiResponse.php

### `App\Shared\Monitoring\Contracts`
- app/Shared/Monitoring/Contracts/ErrorTrackerInterface.php
- app/Shared/Monitoring/Contracts/PerformanceMonitorInterface.php
- app/Shared/Monitoring/Contracts/SystemMonitorInterface.php
- app/Shared/Monitoring/Contracts/CacheMonitorInterface.php

### `App\Shared\Monitoring\Providers`
- app/Shared/Monitoring/Providers/MonitoringServiceProvider.php

### `App\Shared\Monitoring\Services`
- app/Shared/Monitoring/Services/SystemMonitorService.php
- app/Shared/Monitoring/Services/CacheMonitor.php
- app/Shared/Monitoring/Services/PerformanceMonitorService.php
- app/Shared/Monitoring/Services/ErrorTrackerService.php

### `App\Shared\Events`
- app/Shared/Events/SimpleEventDispatcher.php
- app/Shared/Events/AbstractDomainEvent.php

### `App\Shared\Events\Contracts`
- app/Shared/Events/Contracts/EventDispatcherInterface.php
- app/Shared/Events/Contracts/DomainEventInterface.php
- app/Shared/Events/Contracts/EventListenerInterface.php

### `App\Shared\Validation`
- app/Shared/Validation/Validator.php
- app/Shared/Validation/ValidationResult.php

### `App\Shared\Validation\Factory`
- app/Shared/Validation/Factory/ValidatorFactory.php

### `App\Application\Middleware`
- app/Application/Middleware/JwtAuthenticationMiddleware.php
- app/Application/Middleware/AuthorizationMiddleware.php
- app/Application/Middleware/PostViewRateLimitMiddleware.php
- app/Application/Middleware/AuthorizationResult.php
- app/Application/Middleware/JwtAuthorizationMiddleware.php
- app/Application/Middleware/RateLimitMiddleware.php

### `App\Application\Controllers\Security`
- app/Application/Controllers/Security/CSPReportController.php

### `App\Application\Controllers\Web`
- app/Application/Controllers/Web/SwaggerController.php

### `App\Application\Controllers\Health`
- app/Application/Controllers/Health/HealthController.php

### `App\Application\Controllers\Admin`
- app/Application/Controllers/Admin/TagManagementController.php
- app/Application/Controllers/Admin/CacheMonitorController.php

### `App\Application\Controllers`
- app/Application/Controllers/PostController.php
- app/Application/Controllers/TestController.php
- app/Application/Controllers/BaseController.php

### `App\Application\Controllers\Api\V1`
- app/Application/Controllers/Api/V1/IpController.php
- app/Application/Controllers/Api/V1/ActivityLogController.php
- app/Application/Controllers/Api/V1/AuthController.php
- app/Application/Controllers/Api/V1/PostController.php
- app/Application/Controllers/Api/V1/StatisticsAdminController.php
- app/Application/Controllers/Api/V1/AttachmentController.php
- app/Application/Controllers/Api/V1/PostViewController.php
- app/Application/Controllers/Api/V1/StatisticsChartController.php
- app/Application/Controllers/Api/V1/StatisticsController.php

### `App\Application\Services\Statistics`
- app/Application/Services/Statistics/StatisticsQueryService.php
- app/Application/Services/Statistics/StatisticsApplicationService.php

### `App\Application\Services\Statistics\DTOs`
- app/Application/Services/Statistics/DTOs/PaginatedStatisticsDTO.php
- app/Application/Services/Statistics/DTOs/StatisticsQueryDTO.php

### `App\Infrastructure\Statistics\Formatters`
- app/Infrastructure/Statistics/Formatters/CSVStatisticsFormatter.php
- app/Infrastructure/Statistics/Formatters/PDFStatisticsFormatter.php
- app/Infrastructure/Statistics/Formatters/JSONStatisticsFormatter.php

### `App\Infrastructure\Statistics\Repositories`
- app/Infrastructure/Statistics/Repositories/StatisticsRepository.php
- app/Infrastructure/Statistics/Repositories/PostStatisticsRepository.php
- app/Infrastructure/Statistics/Repositories/UserStatisticsRepository.php

### `App\Infrastructure\Statistics\Adapters`
- app/Infrastructure/Statistics/Adapters/StatisticsDatabaseAdapterFactory.php
- app/Infrastructure/Statistics/Adapters/StatisticsRepositoryLoggingAdapter.php
- app/Infrastructure/Statistics/Adapters/StatisticsRepositoryCacheAdapter.php
- app/Infrastructure/Statistics/Adapters/StatisticsRepositoryTransactionAdapter.php
- app/Infrastructure/Statistics/Adapters/StatisticsQueryAdapter.php

### `App\Infrastructure\Statistics\Processors`
- app/Infrastructure/Statistics/Processors/CategoryProcessor.php
- app/Infrastructure/Statistics/Processors/TimeSeriesProcessor.php
- app/Infrastructure/Statistics/Processors/TrendAnalysisProcessor.php

### `App\Infrastructure\Statistics\Commands`
- app/Infrastructure/Statistics/Commands/StatisticsRecalculationCommand.php
- app/Infrastructure/Statistics/Commands/StatisticsCalculationCommand.php

### `App\Infrastructure\Statistics\Services`
- app/Infrastructure/Statistics/Services/StatisticsPerformanceReportGenerator.php
- app/Infrastructure/Statistics/Services/StatisticsCacheService.php
- app/Infrastructure/Statistics/Services/StatisticsMonitoringService.php
- app/Infrastructure/Statistics/Services/SlowQueryMonitoringService.php
- app/Infrastructure/Statistics/Services/StatisticsVisualizationService.php
- app/Infrastructure/Statistics/Services/StatisticsExportService.php

### `App\Infrastructure\Database`
- app/Infrastructure/Database/DatabaseConnection.php

### `App\Infrastructure\Cache`
- app/Infrastructure/Cache/CacheManager.php
- app/Infrastructure/Cache/CacheKeys.php

### `App\Infrastructure\Config`
- app/Infrastructure/Config/ContainerFactory.php

### `App\Infrastructure\Auth\Jwt`
- app/Infrastructure/Auth/Jwt/FirebaseJwtProvider.php

### `App\Infrastructure\Auth\Repositories`
- app/Infrastructure/Auth/Repositories/RefreshTokenRepository.php
- app/Infrastructure/Auth/Repositories/TokenBlacklistRepository.php

### `App\Infrastructure\OpenApi`
- app/Infrastructure/OpenApi/OpenApiSpec.php

### `App\Infrastructure\Http`
- app/Infrastructure/Http/Response.php
- app/Infrastructure/Http/Stream.php
- app/Infrastructure/Http/Uri.php
- app/Infrastructure/Http/ServerRequest.php
- app/Infrastructure/Http/ServerRequestFactory.php

### `App\Infrastructure\Routing\Middleware`
- app/Infrastructure/Routing/Middleware/AbstractMiddleware.php
- app/Infrastructure/Routing/Middleware/MiddlewareDispatcher.php
- app/Infrastructure/Routing/Middleware/RouteParametersMiddleware.php
- app/Infrastructure/Routing/Middleware/MiddlewareResolver.php
- app/Infrastructure/Routing/Middleware/RouteInfoMiddleware.php
- app/Infrastructure/Routing/Middleware/MiddlewareManager.php

### `App\Infrastructure\Routing`
- app/Infrastructure/Routing/RouteDispatcher.php
- app/Infrastructure/Routing/ClosureRequestHandler.php
- app/Infrastructure/Routing/RouteLoader.php
- app/Infrastructure/Routing/ControllerResolver.php
- app/Infrastructure/Routing/RouteValidator.php

### `App\Infrastructure\Routing\Core`
- app/Infrastructure/Routing/Core/RouteCollection.php
- app/Infrastructure/Routing/Core/Route.php
- app/Infrastructure/Routing/Core/Router.php

### `App\Infrastructure\Routing\Cache`
- app/Infrastructure/Routing/Cache/MemoryRouteCache.php
- app/Infrastructure/Routing/Cache/RouteCacheFactory.php
- app/Infrastructure/Routing/Cache/RedisRouteCache.php
- app/Infrastructure/Routing/Cache/FileRouteCache.php

### `App\Infrastructure\Routing\Contracts`
- app/Infrastructure/Routing/Contracts/RouteMatchResult.php
- app/Infrastructure/Routing/Contracts/RouteCollectionInterface.php
- app/Infrastructure/Routing/Contracts/RouteCacheInterface.php
- app/Infrastructure/Routing/Contracts/MiddlewareInterface.php
- app/Infrastructure/Routing/Contracts/RequestHandlerInterface.php
- app/Infrastructure/Routing/Contracts/MiddlewareManagerInterface.php
- app/Infrastructure/Routing/Contracts/RouterInterface.php
- app/Infrastructure/Routing/Contracts/RouteInterface.php
- app/Infrastructure/Routing/Contracts/MiddlewareDispatcherInterface.php

### `App\Infrastructure\Routing\Providers`
- app/Infrastructure/Routing/Providers/RoutingServiceProvider.php

### `App\Infrastructure\Routing\Exceptions`
- app/Infrastructure/Routing/Exceptions/RouteConfigurationException.php

### `App\Infrastructure\Services`
- app/Infrastructure/Services/RateLimitService.php
- app/Infrastructure/Services/CacheService.php
- app/Infrastructure/Services/OutputSanitizer.php

