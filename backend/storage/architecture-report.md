# 專案架構分析報告（基於 Context7 MCP 最新技術）

**生成時間**: 2025-09-03 01:00:16

## 📊 程式碼品質指標

| 指標 | 數值 | 狀態 |
|------|------|------|
| 總類別數 | 222 | - |
| 介面與類別比例 | 22.97% | ✅ 良好 |
| 平均依賴數/類別 | 0.00 | ✅ 良好 |
| 現代 PHP 採用率 | 59.91% | ✅ 良好 |
| PSR-4 合規率 | 73.46% | ❌ 需修正 |
| DDD 結構完整性 | 0.00% | ⚠️ 可改善 |

## 🚀 現代 PHP 特性使用情況

| 特性 | 使用次數 | 描述 |
|------|----------|------|
| Match 表達式 (PHP 8.0+) | 234 | ✅ 更安全的條件分支 |
| 唯讀屬性 (PHP 8.1+) | 103 | ✅ 提升資料不變性 |
| 空安全運算子 (PHP 8.0+) | 70 | ✅ 防止 null 指標異常 |
| 屬性標籤 (PHP 8.0+) | 68 | ✅ 現代化 metadata |
| 建構子屬性提升 (PHP 8.0+) | 21 | ✅ 減少樣板程式碼 |
| 聯合型別 (PHP 8.0+) | 17 | ✅ 更靈活的型別定義 |
| 列舉型別 (PHP 8.1+) | 5 | ✅ 型別安全的常數 |

## 📁 目錄結構

- `.`
- `..`
- `database`
- `database/.`
- `database/..`
- `database/migrations`
- `database/migrations/.`
- `database/migrations/..`
- `database/seeds`
- `database/seeds/.`
- `database/seeds/..`
- `app`
- `app/.`
- `app/..`
- `app/Domains`
- `app/Domains/.`
- `app/Domains/..`
- `app/Domains/Post`
- `app/Domains/Post/.`
- `app/Domains/Post/..`
- `app/Domains/Post/Contracts`
- `app/Domains/Post/Contracts/.`
- `app/Domains/Post/Contracts/..`
- `app/Domains/Post/Enums`
- `app/Domains/Post/Enums/.`
- `app/Domains/Post/Enums/..`
- `app/Domains/Post/Repositories`
- `app/Domains/Post/Repositories/.`
- `app/Domains/Post/Repositories/..`
- `app/Domains/Post/Models`
- `app/Domains/Post/Models/.`
- `app/Domains/Post/Models/..`
- `app/Domains/Post/Exceptions`
- `app/Domains/Post/Exceptions/.`
- `app/Domains/Post/Exceptions/..`
- `app/Domains/Post/DTOs`
- `app/Domains/Post/DTOs/.`
- `app/Domains/Post/DTOs/..`
- `app/Domains/Post/Services`
- `app/Domains/Post/Services/.`
- `app/Domains/Post/Services/..`
- `app/Domains/Post/Validation`
- `app/Domains/Post/Validation/.`
- `app/Domains/Post/Validation/..`
- `app/Domains/Security`
- `app/Domains/Security/.`
- `app/Domains/Security/..`
- `app/Domains/Security/Contracts`
- `app/Domains/Security/Contracts/.`
- `app/Domains/Security/Contracts/..`
- `app/Domains/Security/Providers`
- `app/Domains/Security/Providers/.`
- `app/Domains/Security/Providers/..`
- `app/Domains/Security/Enums`
- `app/Domains/Security/Enums/.`
- `app/Domains/Security/Enums/..`
- `app/Domains/Security/Repositories`
- `app/Domains/Security/Repositories/.`
- `app/Domains/Security/Repositories/..`
- `app/Domains/Security/Models`
- `app/Domains/Security/Models/.`
- `app/Domains/Security/Models/..`
- `app/Domains/Security/DTOs`
- `app/Domains/Security/DTOs/.`
- `app/Domains/Security/DTOs/..`
- `app/Domains/Security/Services`
- `app/Domains/Security/Services/.`
- `app/Domains/Security/Services/..`
- `app/Domains/Security/Services/Advanced`
- `app/Domains/Security/Services/Advanced/.`
- `app/Domains/Security/Services/Advanced/..`
- `app/Domains/Security/Services/Core`
- `app/Domains/Security/Services/Core/.`
- `app/Domains/Security/Services/Core/..`
- `app/Domains/Security/Services/Secrets`
- `app/Domains/Security/Services/Secrets/.`
- `app/Domains/Security/Services/Secrets/..`
- `app/Domains/Security/Services/Content`
- `app/Domains/Security/Services/Content/.`
- `app/Domains/Security/Services/Content/..`
- `app/Domains/Security/Services/Headers`
- `app/Domains/Security/Services/Headers/.`
- `app/Domains/Security/Services/Headers/..`
- `app/Domains/Security/Services/Error`
- `app/Domains/Security/Services/Error/.`
- `app/Domains/Security/Services/Error/..`
- `app/Domains/Security/Services/Logging`
- `app/Domains/Security/Services/Logging/.`
- `app/Domains/Security/Services/Logging/..`
- `app/Domains/Security/Entities`
- `app/Domains/Security/Entities/.`
- `app/Domains/Security/Entities/..`
- `app/Domains/Auth`
- `app/Domains/Auth/.`
- `app/Domains/Auth/..`
- `app/Domains/Auth/Contracts`
- `app/Domains/Auth/Contracts/.`
- `app/Domains/Auth/Contracts/..`
- `app/Domains/Auth/Providers`
- `app/Domains/Auth/Providers/.`
- `app/Domains/Auth/Providers/..`
- `app/Domains/Auth/Repositories`
- `app/Domains/Auth/Repositories/.`
- `app/Domains/Auth/Repositories/..`
- `app/Domains/Auth/Models`
- `app/Domains/Auth/Models/.`
- `app/Domains/Auth/Models/..`
- `app/Domains/Auth/Exceptions`
- `app/Domains/Auth/Exceptions/.`
- `app/Domains/Auth/Exceptions/..`
- `app/Domains/Auth/DTOs`
- `app/Domains/Auth/DTOs/.`
- `app/Domains/Auth/DTOs/..`
- `app/Domains/Auth/Services`
- `app/Domains/Auth/Services/.`
- `app/Domains/Auth/Services/..`
- `app/Domains/Auth/Services/Advanced`
- `app/Domains/Auth/Services/Advanced/.`
- `app/Domains/Auth/Services/Advanced/..`
- `app/Domains/Auth/Entities`
- `app/Domains/Auth/Entities/.`
- `app/Domains/Auth/Entities/..`
- `app/Domains/Auth/ValueObjects`
- `app/Domains/Auth/ValueObjects/.`
- `app/Domains/Auth/ValueObjects/..`
- `app/Domains/Attachment`
- `app/Domains/Attachment/.`
- `app/Domains/Attachment/..`
- `app/Domains/Attachment/Contracts`
- `app/Domains/Attachment/Contracts/.`
- `app/Domains/Attachment/Contracts/..`
- `app/Domains/Attachment/Enums`
- `app/Domains/Attachment/Enums/.`
- `app/Domains/Attachment/Enums/..`
- `app/Domains/Attachment/Repositories`
- `app/Domains/Attachment/Repositories/.`
- `app/Domains/Attachment/Repositories/..`
- `app/Domains/Attachment/Models`
- `app/Domains/Attachment/Models/.`
- `app/Domains/Attachment/Models/..`
- `app/Domains/Attachment/DTOs`
- `app/Domains/Attachment/DTOs/.`
- `app/Domains/Attachment/DTOs/..`
- `app/Domains/Attachment/Services`
- `app/Domains/Attachment/Services/.`
- `app/Domains/Attachment/Services/..`
- `app/Shared`
- `app/Shared/.`
- `app/Shared/..`
- `app/Shared/Cache`
- `app/Shared/Cache/.`
- `app/Shared/Cache/..`
- `app/Shared/Cache/Drivers`
- `app/Shared/Cache/Drivers/.`
- `app/Shared/Cache/Drivers/..`
- `app/Shared/Cache/Strategies`
- `app/Shared/Cache/Strategies/.`
- `app/Shared/Cache/Strategies/..`
- `app/Shared/Cache/Contracts`
- `app/Shared/Cache/Contracts/.`
- `app/Shared/Cache/Contracts/..`
- `app/Shared/Cache/Providers`
- `app/Shared/Cache/Providers/.`
- `app/Shared/Cache/Providers/..`
- `app/Shared/Cache/Repositories`
- `app/Shared/Cache/Repositories/.`
- `app/Shared/Cache/Repositories/..`
- `app/Shared/Cache/Services`
- `app/Shared/Cache/Services/.`
- `app/Shared/Cache/Services/..`
- `app/Shared/Cache/ValueObjects`
- `app/Shared/Cache/ValueObjects/.`
- `app/Shared/Cache/ValueObjects/..`
- `app/Shared/Config`
- `app/Shared/Config/.`
- `app/Shared/Config/..`
- `app/Shared/Contracts`
- `app/Shared/Contracts/.`
- `app/Shared/Contracts/..`
- `app/Shared/OpenApi`
- `app/Shared/OpenApi/.`
- `app/Shared/OpenApi/..`
- `app/Shared/Exceptions`
- `app/Shared/Exceptions/.`
- `app/Shared/Exceptions/..`
- `app/Shared/Exceptions/Validation`
- `app/Shared/Exceptions/Validation/.`
- `app/Shared/Exceptions/Validation/..`
- `app/Shared/Schemas`
- `app/Shared/Schemas/.`
- `app/Shared/Schemas/..`
- `app/Shared/DTOs`
- `app/Shared/DTOs/.`
- `app/Shared/DTOs/..`
- `app/Shared/Http`
- `app/Shared/Http/.`
- `app/Shared/Http/..`
- `app/Shared/Monitoring`
- `app/Shared/Monitoring/.`
- `app/Shared/Monitoring/..`
- `app/Shared/Monitoring/Contracts`
- `app/Shared/Monitoring/Contracts/.`
- `app/Shared/Monitoring/Contracts/..`
- `app/Shared/Monitoring/Providers`
- `app/Shared/Monitoring/Providers/.`
- `app/Shared/Monitoring/Providers/..`
- `app/Shared/Monitoring/Services`
- `app/Shared/Monitoring/Services/.`
- `app/Shared/Monitoring/Services/..`
- `app/Shared/Helpers`
- `app/Shared/Helpers/.`
- `app/Shared/Helpers/..`
- `app/Shared/Validation`
- `app/Shared/Validation/.`
- `app/Shared/Validation/..`
- `app/Shared/Validation/Factory`
- `app/Shared/Validation/Factory/.`
- `app/Shared/Validation/Factory/..`
- `app/Application`
- `app/Application/.`
- `app/Application/..`
- `app/Application/Middleware`
- `app/Application/Middleware/.`
- `app/Application/Middleware/..`
- `app/Application/Controllers`
- `app/Application/Controllers/.`
- `app/Application/Controllers/..`
- `app/Application/Controllers/Security`
- `app/Application/Controllers/Security/.`
- `app/Application/Controllers/Security/..`
- `app/Application/Controllers/Web`
- `app/Application/Controllers/Web/.`
- `app/Application/Controllers/Web/..`
- `app/Application/Controllers/Health`
- `app/Application/Controllers/Health/.`
- `app/Application/Controllers/Health/..`
- `app/Application/Controllers/Admin`
- `app/Application/Controllers/Admin/.`
- `app/Application/Controllers/Admin/..`
- `app/Application/Controllers/Api`
- `app/Application/Controllers/Api/.`
- `app/Application/Controllers/Api/..`
- `app/Application/Controllers/Api/V1`
- `app/Application/Controllers/Api/V1/.`
- `app/Application/Controllers/Api/V1/..`
- `app/Infrastructure`
- `app/Infrastructure/.`
- `app/Infrastructure/..`
- `app/Infrastructure/Database`
- `app/Infrastructure/Database/.`
- `app/Infrastructure/Database/..`
- `app/Infrastructure/Cache`
- `app/Infrastructure/Cache/.`
- `app/Infrastructure/Cache/..`
- `app/Infrastructure/Config`
- `app/Infrastructure/Config/.`
- `app/Infrastructure/Config/..`
- `app/Infrastructure/Auth`
- `app/Infrastructure/Auth/.`
- `app/Infrastructure/Auth/..`
- `app/Infrastructure/Auth/Jwt`
- `app/Infrastructure/Auth/Jwt/.`
- `app/Infrastructure/Auth/Jwt/..`
- `app/Infrastructure/Auth/Repositories`
- `app/Infrastructure/Auth/Repositories/.`
- `app/Infrastructure/Auth/Repositories/..`
- `app/Infrastructure/OpenApi`
- `app/Infrastructure/OpenApi/.`
- `app/Infrastructure/OpenApi/..`
- `app/Infrastructure/Http`
- `app/Infrastructure/Http/.`
- `app/Infrastructure/Http/..`
- `app/Infrastructure/Routing`
- `app/Infrastructure/Routing/.`
- `app/Infrastructure/Routing/..`
- `app/Infrastructure/Routing/Middleware`
- `app/Infrastructure/Routing/Middleware/.`
- `app/Infrastructure/Routing/Middleware/..`
- `app/Infrastructure/Routing/Core`
- `app/Infrastructure/Routing/Core/.`
- `app/Infrastructure/Routing/Core/..`
- `app/Infrastructure/Routing/Cache`
- `app/Infrastructure/Routing/Cache/.`
- `app/Infrastructure/Routing/Cache/..`
- `app/Infrastructure/Routing/Contracts`
- `app/Infrastructure/Routing/Contracts/.`
- `app/Infrastructure/Routing/Contracts/..`
- `app/Infrastructure/Routing/Providers`
- `app/Infrastructure/Routing/Providers/.`
- `app/Infrastructure/Routing/Providers/..`
- `app/Infrastructure/Routing/Exceptions`
- `app/Infrastructure/Routing/Exceptions/.`
- `app/Infrastructure/Routing/Exceptions/..`
- `app/Infrastructure/Services`
- `app/Infrastructure/Services/.`
- `app/Infrastructure/Services/..`
- `config`
- `config/.`
- `config/..`
- `config/routes`
- `config/routes/.`
- `config/routes/..`
- `examples`
- `examples/.`
- `examples/..`
- `scripts`
- `scripts/.`
- `scripts/..`
- `scripts/consolidated`
- `scripts/consolidated/.`
- `scripts/consolidated/..`
- `scripts/lib`
- `scripts/lib/.`
- `scripts/lib/..`

## 🏷️ 命名空間分析

### `App`
- app/Application.php

### `App\Domains\Post\Contracts`
- app/Domains/Post/Contracts/PostRepositoryInterface.php
- app/Domains/Post/Contracts/PostServiceInterface.php

### `App\Domains\Post\Enums`
- app/Domains/Post/Enums/PostStatus.php

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
- app/Domains/Security/Enums/ActivityCategory.php
- app/Domains/Security/Enums/ActivitySeverity.php
- app/Domains/Security/Enums/ActivityStatus.php
- app/Domains/Security/Enums/ActivityType.php

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

### `App\Shared\Validation`
- app/Shared/Validation/Validator.php
- app/Shared/Validation/ValidationResult.php

### `App\Shared\Validation\Factory`
- app/Shared/Validation/Factory/ValidatorFactory.php

### `App\Application\Middleware`
- app/Application/Middleware/JwtAuthenticationMiddleware.php
- app/Application/Middleware/AuthorizationMiddleware.php
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
- app/Application/Controllers/Api/V1/AttachmentController.php

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

### `AlleyNote\Scripts\Consolidated`
- scripts/consolidated/ConsolidatedAnalyzer.php
- scripts/consolidated/ScriptManager.php
- scripts/consolidated/ConsolidatedErrorFixer.php
- scripts/consolidated/DefaultScriptAnalyzer.php
- scripts/consolidated/DefaultScriptConfiguration.php
- scripts/consolidated/ConsolidatedDeployer.php
- scripts/consolidated/ConsolidatedMaintainer.php
- scripts/consolidated/ConsolidatedTestManager.php
- scripts/consolidated/DefaultScriptExecutor.php

### `後添加
            if (preg_match('/^namespace [^`
- scripts/fix-phpunit-deprecations.php

### `$content = str_replace(
                'AlleyNote\Domains\Auth\Contracts\AuthenticationServiceInterface',
                'App\Domains\Auth\Contracts\AuthenticationServiceInterface',
                $content
            )`
- scripts/fix-test-constructor-errors.php

### `App\\Application\\Controllers\\Api\\V1`
- scripts/rebuild-activity-controller.php

### `重複
                if (preg_match('/^namespace ([^`
- scripts/fix-duplicate-content.php

### `= trim($matches[1])`
- scripts/scan-project-architecture.php

### `宣告
        $content = preg_replace('/^namespace AlleyNote\\\\/m', 'namespace App\\', $content, -1, $namespaceCount)`
- scripts/fix-namespace-consistency.php


## 🏗️ DDD 架構分析


## 📊 類別統計

- **類別總數**: 222
- **介面總數**: 51
- **Trait 總數**: 0

## 🔑 重要類別清單

- **PostRepository**: `app/Domains/Post/Repositories/PostRepository.php`
  - 實作: PostRepositoryInterface
- **PostService**: `app/Domains/Post/Services/PostService.php`
  - 實作: PostServiceInterface
- **ContentModerationService**: `app/Domains/Post/Services/ContentModerationService.php`
  - 實作: 
- **RichTextProcessorService**: `app/Domains/Post/Services/RichTextProcessorService.php`
  - 實作: 
- **PostCacheKeyService**: `app/Domains/Post/Services/PostCacheKeyService.php`
  - 實作: 
- **SecurityServiceProvider**: `app/Domains/Security/Providers/SecurityServiceProvider.php`
  - 實作: 
- **IpRepository**: `app/Domains/Security/Repositories/IpRepository.php`
  - 實作: IpRepositoryInterface
- **ActivityLogRepository**: `app/Domains/Security/Repositories/ActivityLogRepository.php`
  - 實作: ActivityLogRepositoryInterface
- **SecurityTestService**: `app/Domains/Security/Services/Advanced/SecurityTestService.php`
  - 實作: SecurityTestInterface
- **XssProtectionService**: `app/Domains/Security/Services/Core/XssProtectionService.php`
  - 實作: XssProtectionServiceInterface
- **CsrfProtectionService**: `app/Domains/Security/Services/Core/CsrfProtectionService.php`
  - 實作: CsrfProtectionServiceInterface
- **SuspiciousActivityDetector**: `app/Domains/Security/Services/SuspiciousActivityDetector.php`
  - 實作: SuspiciousActivityDetectorInterface
- **SecretsManager**: `app/Domains/Security/Services/Secrets/SecretsManager.php`
  - 實作: SecretsManagerInterface
- **XssProtectionExtensionService**: `app/Domains/Security/Services/Content/XssProtectionExtensionService.php`
  - 實作: 
- **IpService**: `app/Domains/Security/Services/IpService.php`
  - 實作: IpServiceInterface
- **SecurityHeaderService**: `app/Domains/Security/Services/Headers/SecurityHeaderService.php`
  - 實作: SecurityHeaderServiceInterface
- **ActivityLoggingService**: `app/Domains/Security/Services/ActivityLoggingService.php`
  - 實作: ActivityLoggingServiceInterface
- **ErrorHandlerService**: `app/Domains/Security/Services/Error/ErrorHandlerService.php`
  - 實作: ErrorHandlerServiceInterface
- **LoggingSecurityService**: `app/Domains/Security/Services/Logging/LoggingSecurityService.php`
  - 實作: LoggingSecurityServiceInterface
- **AuthServiceProvider**: `app/Domains/Auth/Providers/AuthServiceProvider.php`
  - 實作: 
- **SimpleAuthServiceProvider**: `app/Domains/Auth/Providers/SimpleAuthServiceProvider.php`
  - 實作: 
- **UserRepository**: `app/Domains/Auth/Repositories/UserRepository.php`
  - 實作: 
- **PwnedPasswordService**: `app/Domains/Auth/Services/Advanced/PwnedPasswordService.php`
  - 實作: 
- **SessionSecurityService**: `app/Domains/Auth/Services/SessionSecurityService.php`
  - 實作: SessionSecurityServiceInterface
- **AuthenticationService**: `app/Domains/Auth/Services/AuthenticationService.php`
  - 實作: AuthenticationServiceInterface
- **AuthService**: `app/Domains/Auth/Services/AuthService.php`
  - 實作: 
- **PasswordManagementService**: `app/Domains/Auth/Services/PasswordManagementService.php`
  - 實作: 
- **PasswordSecurityService**: `app/Domains/Auth/Services/PasswordSecurityService.php`
  - 實作: PasswordSecurityServiceInterface
- **RefreshTokenService**: `app/Domains/Auth/Services/RefreshTokenService.php`
  - 實作: 
- **AuthorizationService**: `app/Domains/Auth/Services/AuthorizationService.php`
  - 實作: AuthorizationServiceInterface
- **TokenBlacklistService**: `app/Domains/Auth/Services/TokenBlacklistService.php`
  - 實作: 
- **JwtTokenService**: `app/Domains/Auth/Services/JwtTokenService.php`
  - 實作: JwtTokenServiceInterface
- **AttachmentRepository**: `app/Domains/Attachment/Repositories/AttachmentRepository.php`
  - 實作: 
- **AttachmentService**: `app/Domains/Attachment/Services/AttachmentService.php`
  - 實作: AttachmentServiceInterface
- **FileSecurityService**: `app/Domains/Attachment/Services/FileSecurityService.php`
  - 實作: FileSecurityServiceInterface
- **DefaultCacheStrategy**: `app/Shared/Cache/Services/DefaultCacheStrategy.php`
  - 實作: CacheStrategyInterface
- **CacheServiceProvider**: `app/Shared/Cache/Providers/CacheServiceProvider.php`
  - 實作: 
- **CacheConfigBuilder**: `app/Shared/Cache/Providers/CacheServiceProvider.php`
  - 實作: 
- **RedisTagRepository**: `app/Shared/Cache/Repositories/RedisTagRepository.php`
  - 實作: TagRepositoryInterface
- **MemoryTagRepository**: `app/Shared/Cache/Repositories/MemoryTagRepository.php`
  - 實作: TagRepositoryInterface
- **CacheGroupManager**: `app/Shared/Cache/Services/CacheGroupManager.php`
  - 實作: 
- **TaggedCacheManager**: `app/Shared/Cache/Services/TaggedCacheManager.php`
  - 實作: TaggedCacheInterface
- **PrefixedCacheManager**: `app/Shared/Cache/Services/PrefixedCacheManager.php`
  - 實作: CacheManagerInterface
- **MonitoringServiceProvider**: `app/Shared/Monitoring/Providers/MonitoringServiceProvider.php`
  - 實作: 
- **SystemMonitorService**: `app/Shared/Monitoring/Services/SystemMonitorService.php`
  - 實作: SystemMonitorInterface
- **CacheMonitor**: `app/Shared/Monitoring/Services/CacheMonitor.php`
  - 實作: CacheMonitorInterface
- **PerformanceMonitorService**: `app/Shared/Monitoring/Services/PerformanceMonitorService.php`
  - 實作: PerformanceMonitorInterface
- **ErrorTrackerService**: `app/Shared/Monitoring/Services/ErrorTrackerService.php`
  - 實作: ErrorTrackerInterface
- **CSPReportController**: `app/Application/Controllers/Security/CSPReportController.php`
  - 實作: 
- **SwaggerController**: `app/Application/Controllers/Web/SwaggerController.php`
  - 實作: 
- **HealthController**: `app/Application/Controllers/TestController.php`
  - 實作: 
- **TagManagementController**: `app/Application/Controllers/Admin/TagManagementController.php`
  - 繼承: BaseController
  - 實作: 
- **CacheMonitorController**: `app/Application/Controllers/Admin/CacheMonitorController.php`
  - 繼承: BaseController
  - 實作: 
- **PostController**: `app/Application/Controllers/Api/V1/PostController.php`
  - 繼承: BaseController
  - 實作: 
- **IpController**: `app/Application/Controllers/Api/V1/IpController.php`
  - 實作: 
- **AuthController**: `app/Application/Controllers/Api/V1/AuthController.php`
  - 繼承: BaseController
  - 實作: 
- **AttachmentController**: `app/Application/Controllers/Api/V1/AttachmentController.php`
  - 實作: 
- **BaseController**: `app/Application/Controllers/BaseController.php`
  - 實作: 
- **RefreshTokenRepository**: `app/Infrastructure/Auth/Repositories/RefreshTokenRepository.php`
  - 實作: RefreshTokenRepositoryInterface
- **TokenBlacklistRepository**: `app/Infrastructure/Auth/Repositories/TokenBlacklistRepository.php`
  - 實作: TokenBlacklistRepositoryInterface
- **RoutingServiceProvider**: `app/Infrastructure/Routing/Providers/RoutingServiceProvider.php`
  - 實作: 
- **ControllerResolver**: `app/Infrastructure/Routing/ControllerResolver.php`
  - 實作: 
- **RateLimitService**: `app/Infrastructure/Services/RateLimitService.php`
  - 實作: 
- **CacheService**: `app/Infrastructure/Services/CacheService.php`
  - 實作: CacheServiceInterface
- **OutputSanitizer**: `app/Infrastructure/Services/OutputSanitizer.php`
  - 實作: 
- **OutputSanitizerService**: `app/Infrastructure/Services/OutputSanitizer.php`
  - 實作: OutputSanitizerInterface

## 🔌 介面實作分析

### ``
- CreateUserActivityLogsTable (`database/migrations/20250829000000_create_user_activity_logs_table.php`)
- CreateTokenBlacklistTable (`database/migrations/20250825165750_create_token_blacklist_table.php`)
- AddCompositeIndexesToUserActivityLogs (`database/migrations/20241227000002_add_composite_indexes_to_user_activity_logs.php`)
- CreateRefreshTokensTable (`database/migrations/20250825165731_create_refresh_tokens_table.php`)
- InitialSchema (`database/migrations/20250823051608_initial_schema.php`)
- AddTokenHashToRefreshTokensTable (`database/migrations/20250826023305_add_token_hash_to_refresh_tokens_table.php`)
- UserActivityLogsSeeder (`database/seeds/UserActivityLogsSeeder.php`)
- Application (`app/Application.php`)
- implements (`scripts/remaining-error-fixer.php`)
- PostStatusException (`app/Domains/Post/Exceptions/PostStatusException.php`)
- PostValidationException (`app/Domains/Post/Exceptions/PostValidationException.php`)
- PostNotFoundException (`app/Domains/Post/Exceptions/PostNotFoundException.php`)
- UpdatePostDTO (`app/Domains/Post/DTOs/UpdatePostDTO.php`)
- CreatePostDTO (`app/Domains/Post/DTOs/CreatePostDTO.php`)
- ContentModerationService (`app/Domains/Post/Services/ContentModerationService.php`)
- RichTextProcessorService (`app/Domains/Post/Services/RichTextProcessorService.php`)
- PostCacheKeyService (`app/Domains/Post/Services/PostCacheKeyService.php`)
- PostValidator (`app/Domains/Post/Validation/PostValidator.php`)
- SecurityServiceProvider (`app/Domains/Security/Providers/SecurityServiceProvider.php`)
- CreateIpRuleDTO (`app/Domains/Security/DTOs/CreateIpRuleDTO.php`)
- ActivityLogSearchDTO (`app/Domains/Security/DTOs/ActivityLogSearchDTO.php`)
- XssProtectionExtensionService (`app/Domains/Security/Services/Content/XssProtectionExtensionService.php`)
- ActivityLog (`app/Domains/Security/Entities/ActivityLog.php`)
- AuthServiceProvider (`app/Domains/Auth/Providers/AuthServiceProvider.php`)
- SimpleAuthServiceProvider (`app/Domains/Auth/Providers/SimpleAuthServiceProvider.php`)
- UserRepository (`app/Domains/Auth/Repositories/UserRepository.php`)
- Role (`app/Domains/Auth/Models/Role.php`)
- Permission (`app/Domains/Auth/Models/Permission.php`)
- TokenExpiredException (`app/Domains/Auth/Exceptions/TokenExpiredException.php`)
- JwtException (`app/Domains/Auth/Exceptions/JwtException.php`)
- ForbiddenException (`app/Domains/Auth/Exceptions/ForbiddenException.php`)
- InvalidTokenException (`app/Domains/Auth/Exceptions/InvalidTokenException.php`)
- RefreshTokenException (`app/Domains/Auth/Exceptions/RefreshTokenException.php`)
- TokenValidationException (`app/Domains/Auth/Exceptions/TokenValidationException.php`)
- UnauthorizedException (`app/Domains/Auth/Exceptions/UnauthorizedException.php`)
- AuthenticationException (`app/Domains/Auth/Exceptions/AuthenticationException.php`)
- TokenParsingException (`app/Domains/Auth/Exceptions/TokenParsingException.php`)
- TokenGenerationException (`app/Domains/Auth/Exceptions/TokenGenerationException.php`)
- JwtConfigurationException (`app/Domains/Auth/Exceptions/JwtConfigurationException.php`)
- LoginRequestDTO (`app/Domains/Auth/DTOs/LoginRequestDTO.php`)
- LogoutRequestDTO (`app/Domains/Auth/DTOs/LogoutRequestDTO.php`)
- RefreshResponseDTO (`app/Domains/Auth/DTOs/RefreshResponseDTO.php`)
- LoginResponseDTO (`app/Domains/Auth/DTOs/LoginResponseDTO.php`)
- RegisterUserDTO (`app/Domains/Auth/DTOs/RegisterUserDTO.php`)
- RefreshRequestDTO (`app/Domains/Auth/DTOs/RefreshRequestDTO.php`)
- PwnedPasswordService (`app/Domains/Auth/Services/Advanced/PwnedPasswordService.php`)
- AuthService (`app/Domains/Auth/Services/AuthService.php`)
- PasswordManagementService (`app/Domains/Auth/Services/PasswordManagementService.php`)
- RefreshTokenService (`app/Domains/Auth/Services/RefreshTokenService.php`)
- TokenBlacklistService (`app/Domains/Auth/Services/TokenBlacklistService.php`)
- FileRules (`app/Domains/Attachment/Enums/FileRules.php`)
- AttachmentRepository (`app/Domains/Attachment/Repositories/AttachmentRepository.php`)
- Attachment (`app/Domains/Attachment/Models/Attachment.php`)
- CreateAttachmentDTO (`app/Domains/Attachment/DTOs/CreateAttachmentDTO.php`)
- CacheServiceProvider (`app/Shared/Cache/Providers/CacheServiceProvider.php`)
- CacheConfigBuilder (`app/Shared/Cache/Providers/CacheServiceProvider.php`)
- CacheGroupManager (`app/Shared/Cache/Services/CacheGroupManager.php`)
- CacheManager (`app/Infrastructure/Cache/CacheManager.php`)
- CacheTag (`app/Shared/Cache/ValueObjects/CacheTag.php`)
- EnvironmentConfig (`app/Shared/Config/EnvironmentConfig.php`)
- JwtConfig (`app/Shared/Config/JwtConfig.php`)
- OpenApiConfig (`app/Shared/OpenApi/OpenApiConfig.php`)
- NotFoundException (`app/Shared/Exceptions/NotFoundException.php`)
- StateTransitionException (`app/Shared/Exceptions/StateTransitionException.php`)
- CsrfTokenException (`app/Shared/Exceptions/CsrfTokenException.php`)
- ValidationException (`app/Shared/Exceptions/ValidationException.php`)
- RequestValidationException (`app/Shared/Exceptions/Validation/RequestValidationException.php`)
- PostSchema (`app/Shared/Schemas/PostSchema.php`)
- PostRequestSchema (`app/Shared/Schemas/PostRequestSchema.php`)
- AuthSchema (`app/Shared/Schemas/AuthSchema.php`)
- ApiResponse (`app/Shared/Http/ApiResponse.php`)
- MonitoringServiceProvider (`app/Shared/Monitoring/Providers/MonitoringServiceProvider.php`)
- ValidatorFactory (`app/Shared/Validation/Factory/ValidatorFactory.php`)
- AuthorizationMiddleware (`app/Application/Middleware/AuthorizationMiddleware.php`)
- CSPReportController (`app/Application/Controllers/Security/CSPReportController.php`)
- SwaggerController (`app/Application/Controllers/Web/SwaggerController.php`)
- HealthController (`app/Application/Controllers/TestController.php`)
- TagManagementController (`app/Application/Controllers/Admin/TagManagementController.php`)
- CacheMonitorController (`app/Application/Controllers/Admin/CacheMonitorController.php`)
- PostController (`app/Application/Controllers/Api/V1/PostController.php`)
- IpController (`app/Application/Controllers/Api/V1/IpController.php`)
- ActivityLogController (`scripts/rebuild-activity-controller.php`)
- AuthController (`app/Application/Controllers/Api/V1/AuthController.php`)
- AttachmentController (`app/Application/Controllers/Api/V1/AttachmentController.php`)
- BaseController (`app/Application/Controllers/BaseController.php`)
- DatabaseConnection (`app/Infrastructure/Database/DatabaseConnection.php`)
- CacheKeys (`app/Infrastructure/Cache/CacheKeys.php`)
- ContainerFactory (`app/Infrastructure/Config/ContainerFactory.php`)
- OpenApiSpec (`app/Infrastructure/OpenApi/OpenApiSpec.php`)
- ServerRequestFactory (`app/Infrastructure/Http/ServerRequestFactory.php`)
- RouteParametersMiddleware (`app/Infrastructure/Routing/Middleware/RouteParametersMiddleware.php`)
- MiddlewareResolver (`app/Infrastructure/Routing/Middleware/MiddlewareResolver.php`)
- RouteInfoMiddleware (`app/Infrastructure/Routing/Middleware/RouteInfoMiddleware.php`)
- RouteDispatcher (`app/Infrastructure/Routing/RouteDispatcher.php`)
- RouteCacheFactory (`app/Infrastructure/Routing/Cache/RouteCacheFactory.php`)
- RouteMatchResult (`app/Infrastructure/Routing/Contracts/RouteMatchResult.php`)
- RoutingServiceProvider (`app/Infrastructure/Routing/Providers/RoutingServiceProvider.php`)
- RouteConfigurationException (`app/Infrastructure/Routing/Exceptions/RouteConfigurationException.php`)
- RouteLoader (`app/Infrastructure/Routing/RouteLoader.php`)
- ControllerResolver (`app/Infrastructure/Routing/ControllerResolver.php`)
- RouteValidator (`app/Infrastructure/Routing/RouteValidator.php`)
- RateLimitService (`app/Infrastructure/Services/RateLimitService.php`)
- OutputSanitizer (`app/Infrastructure/Services/OutputSanitizer.php`)
- JsonEncodeIssueFixer (`scripts/fix-json-encode-issues.php`)
- ControllerMethodFixer (`scripts/fix-controller-methods.php`)
- SpecificPhpstanFixer (`scripts/targeted-phpstan-fixer.php`)
- ConsolidatedAnalyzer (`scripts/consolidated/ConsolidatedAnalyzer.php`)
- ScriptManager (`scripts/consolidated/ScriptManager.php`)
- ScriptResult (`scripts/consolidated/ScriptManager.php`)
- ProjectStatus (`scripts/consolidated/ScriptManager.php`)
- TestStatus (`scripts/consolidated/ScriptManager.php`)
- ArchitectureMetrics (`scripts/consolidated/ScriptManager.php`)
- ModernPhpAdoption (`scripts/consolidated/ScriptManager.php`)
- ErrorFixingConfig (`scripts/consolidated/ScriptManager.php`)
- TestingConfig (`scripts/consolidated/ScriptManager.php`)
- AnalysisConfig (`scripts/consolidated/ScriptManager.php`)
- DeploymentConfig (`scripts/consolidated/ScriptManager.php`)
- MaintenanceConfig (`scripts/consolidated/ScriptManager.php`)
- ConsolidatedErrorFixer (`scripts/consolidated/ConsolidatedErrorFixer.php`)
- ConsolidatedDeployer (`scripts/consolidated/ConsolidatedDeployer.php`)
- ConsolidatedMaintainer (`scripts/consolidated/ConsolidatedMaintainer.php`)
- ConsolidatedTestManager (`scripts/consolidated/ConsolidatedTestManager.php`)
- FinalComprehensivePhpstanFixer (`scripts/final-comprehensive-phpstan-fixer.php`)
- SyntaxErrorFixer (`scripts/fix-all-syntax-errors.php`)
- Foo (`scripts/fix-all-syntax-errors.php`)
- PhpUnitDeprecationFixer (`scripts/fix-phpunit-deprecations.php`)
- PrecisionControllerSyntaxFixer (`scripts/precision-controller-syntax-fixer.php`)
- UltimateErrorFixer (`scripts/ultimate-error-fixer.php`)
- TestConstructorFixer (`scripts/fix-test-constructor-errors.php`)
- FinalPhpStanComprehensiveFixer (`scripts/final-phpstan-comprehensive-fixer.php`)
- GenericsPhpstanFixer (`scripts/generics-phpstan-fixer.php`)
- GenericSyntaxFixer (`scripts/fix-generic-syntax-errors.php`)
- SimpleSyntaxFixer (`scripts/simple-syntax-fixer.php`)
- AnonymousClassFixer (`scripts/anonymous-class-fixer.php`)
- IssetErrorFixer (`scripts/fix-isset-errors.php`)
- PhpGenericSyntaxFixer (`scripts/fix-php-generic-syntax.php`)
- RemainingErrorFixer (`scripts/remaining-error-fixer.php`)
- ConservativeDomainSyntaxFixer (`scripts/conservative-domain-syntax-fixer.php`)
- ConsoleOutput (`scripts/lib/ConsoleOutput.php`)
- ProjectArchitectureScanner (`scripts/scan-project-architecture.php`)
- CommonErrorFixer (`scripts/common-error-fixer.php`)
- PHPStanTypeFixer (`scripts/phpstan-type-fixer.php`)
- PhpstanFixCommander (`scripts/phpstan-fix-commander.php`)
- TryCatchFixer (`scripts/fix-trycatch-errors.php`)
- AdvancedPhpstanFixer (`scripts/advanced-phpstan-fixer.php`)
- FinalPhpstanCleanup (`scripts/final-phpstan-cleanup.php`)
- ComprehensiveSyntaxErrorFixer (`scripts/comprehensive-syntax-error-fixer.php`)
- BulkPHPStanFixer (`scripts/bulk-phpstan-fixer.php`)
- PropertyGenericSyntaxFixer (`scripts/fix-property-generic-syntax.php`)
- EnhancedPhpstanFixer (`scripts/enhanced-phpstan-fixer.php`)

### `PostRepositoryInterface`
- PostRepository (`app/Domains/Post/Repositories/PostRepository.php`)

### `JsonSerializable`
- Post (`app/Domains/Post/Models/Post.php`)
- IpList (`app/Domains/Security/Models/IpList.php`)
- SuspiciousActivityAnalysisDTO (`app/Domains/Security/DTOs/SuspiciousActivityAnalysisDTO.php`)
- CreateActivityLogDTO (`app/Domains/Security/DTOs/CreateActivityLogDTO.php`)
- RefreshToken (`app/Domains/Auth/Entities/RefreshToken.php`)
- TokenBlacklistEntry (`app/Domains/Auth/ValueObjects/TokenBlacklistEntry.php`)
- TokenPair (`app/Domains/Auth/ValueObjects/TokenPair.php`)
- DeviceInfo (`app/Domains/Auth/ValueObjects/DeviceInfo.php`)
- JwtPayload (`app/Domains/Auth/ValueObjects/JwtPayload.php`)
- BaseDTO (`app/Shared/DTOs/BaseDTO.php`)
- ValidationResult (`app/Shared/Validation/ValidationResult.php`)
- AuthorizationResult (`app/Application/Middleware/AuthorizationResult.php`)

### `PostServiceInterface`
- PostService (`app/Domains/Post/Services/PostService.php`)

### `IpRepositoryInterface`
- IpRepository (`app/Domains/Security/Repositories/IpRepository.php`)

### `ActivityLogRepositoryInterface`
- ActivityLogRepository (`app/Domains/Security/Repositories/ActivityLogRepository.php`)

### `SecurityTestInterface`
- SecurityTestService (`app/Domains/Security/Services/Advanced/SecurityTestService.php`)

### `XssProtectionServiceInterface`
- XssProtectionService (`app/Domains/Security/Services/Core/XssProtectionService.php`)

### `CsrfProtectionServiceInterface`
- CsrfProtectionService (`app/Domains/Security/Services/Core/CsrfProtectionService.php`)

### `SuspiciousActivityDetectorInterface`
- SuspiciousActivityDetector (`app/Domains/Security/Services/SuspiciousActivityDetector.php`)

### `SecretsManagerInterface`
- SecretsManager (`app/Domains/Security/Services/Secrets/SecretsManager.php`)

### `IpServiceInterface`
- IpService (`app/Domains/Security/Services/IpService.php`)

### `SecurityHeaderServiceInterface`
- SecurityHeaderService (`app/Domains/Security/Services/Headers/SecurityHeaderService.php`)

### `ActivityLoggingServiceInterface`
- ActivityLoggingService (`app/Domains/Security/Services/ActivityLoggingService.php`)

### `ErrorHandlerServiceInterface`
- ErrorHandlerService (`app/Domains/Security/Services/Error/ErrorHandlerService.php`)

### `LoggingSecurityServiceInterface`
- LoggingSecurityService (`app/Domains/Security/Services/Logging/LoggingSecurityService.php`)

### `SessionSecurityServiceInterface`
- SessionSecurityService (`app/Domains/Auth/Services/SessionSecurityService.php`)

### `AuthenticationServiceInterface`
- AuthenticationService (`app/Domains/Auth/Services/AuthenticationService.php`)

### `PasswordSecurityServiceInterface`
- PasswordSecurityService (`app/Domains/Auth/Services/PasswordSecurityService.php`)

### `AuthorizationServiceInterface`
- AuthorizationService (`app/Domains/Auth/Services/AuthorizationService.php`)

### `JwtTokenServiceInterface`
- JwtTokenService (`app/Domains/Auth/Services/JwtTokenService.php`)

### `AttachmentServiceInterface`
- AttachmentService (`app/Domains/Attachment/Services/AttachmentService.php`)

### `FileSecurityServiceInterface`
- FileSecurityService (`app/Domains/Attachment/Services/FileSecurityService.php`)

### `CacheDriverInterface`
- MemoryCacheDriver (`app/Shared/Cache/Drivers/MemoryCacheDriver.php`)
- FileCacheDriver (`app/Shared/Cache/Drivers/FileCacheDriver.php`)
- RedisCacheDriver (`app/Shared/Cache/Drivers/RedisCacheDriver.php`)
- LayeredCacheDriver (`app/Shared/Cache/Drivers/LayeredCacheDriver.php`)

### `TaggedCacheInterface`
- MemoryCacheDriver (`app/Shared/Cache/Drivers/MemoryCacheDriver.php`)
- RedisCacheDriver (`app/Shared/Cache/Drivers/RedisCacheDriver.php`)
- TaggedCacheManager (`app/Shared/Cache/Services/TaggedCacheManager.php`)

### `CacheStrategyInterface`
- DefaultCacheStrategy (`app/Shared/Cache/Services/DefaultCacheStrategy.php`)

### `TagRepositoryInterface`
- RedisTagRepository (`app/Shared/Cache/Repositories/RedisTagRepository.php`)
- MemoryTagRepository (`app/Shared/Cache/Repositories/MemoryTagRepository.php`)

### `CacheManagerInterface`
- PrefixedCacheManager (`app/Shared/Cache/Services/PrefixedCacheManager.php`)

### `SystemMonitorInterface`
- SystemMonitorService (`app/Shared/Monitoring/Services/SystemMonitorService.php`)

### `CacheMonitorInterface`
- CacheMonitor (`app/Shared/Monitoring/Services/CacheMonitor.php`)

### `PerformanceMonitorInterface`
- PerformanceMonitorService (`app/Shared/Monitoring/Services/PerformanceMonitorService.php`)

### `ErrorTrackerInterface`
- ErrorTrackerService (`app/Shared/Monitoring/Services/ErrorTrackerService.php`)

### `ValidatorInterface`
- Validator (`app/Shared/Validation/Validator.php`)

### `MiddlewareInterface`
- JwtAuthenticationMiddleware (`app/Application/Middleware/JwtAuthenticationMiddleware.php`)
- JwtAuthorizationMiddleware (`app/Application/Middleware/JwtAuthorizationMiddleware.php`)
- RateLimitMiddleware (`app/Application/Middleware/RateLimitMiddleware.php`)
- AbstractMiddleware (`app/Infrastructure/Routing/Middleware/AbstractMiddleware.php`)

### `JwtProviderInterface`
- FirebaseJwtProvider (`app/Infrastructure/Auth/Jwt/FirebaseJwtProvider.php`)

### `RefreshTokenRepositoryInterface`
- RefreshTokenRepository (`app/Infrastructure/Auth/Repositories/RefreshTokenRepository.php`)

### `TokenBlacklistRepositoryInterface`
- TokenBlacklistRepository (`app/Infrastructure/Auth/Repositories/TokenBlacklistRepository.php`)

### `ResponseInterface`
- Response (`app/Infrastructure/Http/Response.php`)

### `StreamInterface`
- Stream (`app/Infrastructure/Http/Stream.php`)

### `UriInterface`
- Uri (`app/Infrastructure/Http/Uri.php`)

### `ServerRequestInterface`
- ServerRequest (`app/Infrastructure/Http/ServerRequest.php`)

### `MiddlewareDispatcherInterface`
- MiddlewareDispatcher (`app/Infrastructure/Routing/Middleware/MiddlewareDispatcher.php`)

### `MiddlewareManagerInterface`
- MiddlewareManager (`app/Infrastructure/Routing/Middleware/MiddlewareManager.php`)

### `RequestHandlerInterface`
- ClosureRequestHandler (`app/Infrastructure/Routing/ClosureRequestHandler.php`)

### `RouteCollectionInterface`
- RouteCollection (`app/Infrastructure/Routing/Core/RouteCollection.php`)

### `RouteInterface`
- Route (`app/Infrastructure/Routing/Core/Route.php`)

### `RouterInterface`
- Router (`app/Infrastructure/Routing/Core/Router.php`)

### `RouteCacheInterface`
- MemoryRouteCache (`app/Infrastructure/Routing/Cache/MemoryRouteCache.php`)
- RedisRouteCache (`app/Infrastructure/Routing/Cache/RedisRouteCache.php`)
- FileRouteCache (`app/Infrastructure/Routing/Cache/FileRouteCache.php`)

### `CacheServiceInterface`
- CacheService (`app/Infrastructure/Services/CacheService.php`)

### `OutputSanitizerInterface`
- OutputSanitizerService (`app/Infrastructure/Services/OutputSanitizer.php`)

### `ScriptAnalyzerInterface`
- DefaultScriptAnalyzer (`scripts/consolidated/DefaultScriptAnalyzer.php`)

### `ScriptConfigurationInterface`
- DefaultScriptConfiguration (`scripts/consolidated/DefaultScriptConfiguration.php`)

### `ScriptExecutorInterface`
- DefaultScriptExecutor (`scripts/consolidated/DefaultScriptExecutor.php`)


## 🧪 測試覆蓋分析

- **有測試的類別**: 0 個
- **缺少測試的類別**: 222 個

### 缺少測試的重要類別
- **PostRepository**: `app/Domains/Post/Repositories/PostRepository.php`
- **PostService**: `app/Domains/Post/Services/PostService.php`
- **ContentModerationService**: `app/Domains/Post/Services/ContentModerationService.php`
- **RichTextProcessorService**: `app/Domains/Post/Services/RichTextProcessorService.php`
- **PostCacheKeyService**: `app/Domains/Post/Services/PostCacheKeyService.php`


## 💉 依賴注入分析

### 依賴較多的類別 (≥3個依賴)
- **PostRepository** (3 個依賴)
  - `PDO` $db
  - `CacheServiceInterface` $cache
  - `LoggingSecurityServiceInterface` $logger

- **CreateActivityLogDTO** (3 個依賴)
  - `ActivityType` $actionType
  - `ActivityStatus` $status
  - `DateTimeImmutable` $occurredAt

- **ActivityLogSearchDTO** (6 個依賴)
  - `ActivityType` $actionType
  - `ActivityCategory` $actionCategory
  - `ActivityStatus` $status
  - `ActivitySeverity` $minSeverity
  - `DateTime` $startDate
  - `DateTime` $endDate

- **SecurityTestService** (7 個依賴)
  - `SessionSecurityServiceInterface` $sessionService
  - `AuthorizationServiceInterface` $authService
  - `FileSecurityServiceInterface` $fileService
  - `SecurityHeaderServiceInterface` $headerService
  - `ErrorHandlerServiceInterface` $errorService
  - `PasswordSecurityServiceInterface` $passwordService
  - `SecretsManagerInterface` $secretsManager

- **SuspiciousActivityDetector** (3 個依賴)
  - `ActivityLogRepositoryInterface` $repository
  - `ActivityLoggingServiceInterface` $activityLogger
  - `LoggerInterface` $logger

- **XssProtectionExtensionService** (3 個依賴)
  - `XssProtectionService` $baseXssProtection
  - `RichTextProcessorService` $richTextProcessor
  - `ContentModerationService` $contentModerator

- **ActivityLog** (3 個依賴)
  - `ActivityType` $actionType
  - `ActivityStatus` $status
  - `DateTimeImmutable` $occurredAt

- **AuthenticationService** (3 個依賴)
  - `JwtTokenServiceInterface` $jwtTokenService
  - `RefreshTokenRepositoryInterface` $refreshTokenRepository
  - `UserRepositoryInterface` $userRepository

- **AuthService** (3 個依賴)
  - `UserRepository` $userRepository
  - `PasswordSecurityServiceInterface` $passwordService
  - `JwtTokenServiceInterface` $jwtTokenService

- **RefreshTokenService** (4 個依賴)
  - `JwtTokenServiceInterface` $jwtTokenService
  - `RefreshTokenRepositoryInterface` $refreshTokenRepository
  - `TokenBlacklistRepositoryInterface` $blacklistRepository
  - `LoggerInterface` $logger

- **JwtTokenService** (4 個依賴)
  - `JwtProviderInterface` $jwtProvider
  - `RefreshTokenRepositoryInterface` $refreshTokenRepository
  - `TokenBlacklistRepositoryInterface` $blacklistRepository
  - `JwtConfig` $config

- **RefreshToken** (6 個依賴)
  - `DateTime` $expiresAt
  - `DeviceInfo` $deviceInfo
  - `DateTime` $revokedAt
  - `DateTime` $lastUsedAt
  - `DateTime` $createdAt
  - `DateTime` $updatedAt

- **JwtPayload** (3 個依賴)
  - `DateTimeImmutable` $iat
  - `DateTimeImmutable` $exp
  - `DateTimeImmutable` $nbf

- **AttachmentService** (4 個依賴)
  - `AttachmentRepository` $attachmentRepo
  - `PostRepository` $postRepo
  - `AuthorizationService` $authService
  - `ActivityLoggingServiceInterface` $activityLogger

- **TaggedCacheManager** (4 個依賴)
  - `CacheManagerInterface` $cacheManager
  - `TagRepositoryInterface` $tagRepository
  - `LoggerInterface` $logger
  - `CacheMonitorInterface` $monitor

- **SystemMonitorService** (3 個依賴)
  - `LoggerInterface` $logger
  - `PDO` $database
  - `EnvironmentConfig` $config

- **TagManagementController** (3 個依賴)
  - `CacheManagerInterface` $cacheManager
  - `CacheGroupManager` $groupManager
  - `LoggerInterface` $logger

- **PostController** (4 個依賴)
  - `PostServiceInterface` $postService
  - `ValidatorInterface` $validator
  - `OutputSanitizerInterface` $sanitizer
  - `ActivityLoggingServiceInterface` $activityLogger

- **IpController** (3 個依賴)
  - `IpService` $service
  - `ValidatorInterface` $validator
  - `OutputSanitizerInterface` $sanitizer

- **AuthController** (5 個依賴)
  - `AuthService` $authService
  - `AuthenticationServiceInterface` $authenticationService
  - `JwtTokenServiceInterface` $jwtTokenService
  - `ValidatorInterface` $validator
  - `ActivityLoggingServiceInterface` $activityLoggingService

- **RouteDispatcher** (4 個依賴)
  - `RouterInterface` $router
  - `ControllerResolver` $controllerResolver
  - `MiddlewareDispatcher` $middlewareDispatcher
  - `ContainerInterface` $container

- **ScriptManager** (3 個依賴)
  - `ScriptConfigurationInterface` $config
  - `ScriptExecutorInterface` $executor
  - `ScriptAnalyzerInterface` $analyzer

- **ScriptResult** (3 個依賴)
  - `ScriptConfigurationInterface` $config
  - `ScriptExecutorInterface` $executor
  - `ScriptAnalyzerInterface` $analyzer

- **ProjectStatus** (3 個依賴)
  - `ScriptConfigurationInterface` $config
  - `ScriptExecutorInterface` $executor
  - `ScriptAnalyzerInterface` $analyzer

- **TestStatus** (3 個依賴)
  - `ScriptConfigurationInterface` $config
  - `ScriptExecutorInterface` $executor
  - `ScriptAnalyzerInterface` $analyzer

- **ArchitectureMetrics** (3 個依賴)
  - `ScriptConfigurationInterface` $config
  - `ScriptExecutorInterface` $executor
  - `ScriptAnalyzerInterface` $analyzer

- **ModernPhpAdoption** (3 個依賴)
  - `ScriptConfigurationInterface` $config
  - `ScriptExecutorInterface` $executor
  - `ScriptAnalyzerInterface` $analyzer

- **ErrorFixingConfig** (3 個依賴)
  - `ScriptConfigurationInterface` $config
  - `ScriptExecutorInterface` $executor
  - `ScriptAnalyzerInterface` $analyzer

- **TestingConfig** (3 個依賴)
  - `ScriptConfigurationInterface` $config
  - `ScriptExecutorInterface` $executor
  - `ScriptAnalyzerInterface` $analyzer

- **AnalysisConfig** (3 個依賴)
  - `ScriptConfigurationInterface` $config
  - `ScriptExecutorInterface` $executor
  - `ScriptAnalyzerInterface` $analyzer

- **DeploymentConfig** (3 個依賴)
  - `ScriptConfigurationInterface` $config
  - `ScriptExecutorInterface` $executor
  - `ScriptAnalyzerInterface` $analyzer

- **MaintenanceConfig** (3 個依賴)
  - `ScriptConfigurationInterface` $config
  - `ScriptExecutorInterface` $executor
  - `ScriptAnalyzerInterface` $analyzer


## ❓ 可能的問題引用

- ❓ 找不到類別/介面: Phinx\Migration\AbstractMigration (在 database/migrations/20250829000000_create_user_activity_logs_table.php 中使用)
- ❓ 找不到類別/介面: Phinx\Migration\AbstractMigration (在 database/migrations/20250825165750_create_token_blacklist_table.php 中使用)
- ❓ 找不到類別/介面: Phinx\Migration\AbstractMigration (在 database/migrations/20241227000002_add_composite_indexes_to_user_activity_logs.php 中使用)
- ❓ 找不到類別/介面: Phinx\Migration\AbstractMigration (在 database/migrations/20250825165731_create_refresh_tokens_table.php 中使用)
- ❓ 找不到類別/介面: Phinx\Migration\AbstractMigration (在 database/migrations/20250823051608_initial_schema.php 中使用)
- ❓ 找不到類別/介面: Phinx\Migration\AbstractMigration (在 database/migrations/20250826023305_add_token_hash_to_refresh_tokens_table.php 中使用)
- ❓ 找不到類別/介面: Phinx\Seed\AbstractSeed (在 database/seeds/UserActivityLogsSeeder.php 中使用)
- ❓ 找不到類別/介面: 語句
        'ordered_imports' => [                   // Import 語句排序（已存在，確保配置）
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
    ])
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache') (在 .php-cs-fixer.dist.php 中使用)
- ❓ 找不到類別/介面: DI\ContainerBuilder (在 app/Application.php 中使用)
- ❓ 找不到類別/介面: App\Domains\Post\Enums\PostStatus (在 app/Domains/Post/Repositories/PostRepository.php 中使用)
- ... 還有 171 個
