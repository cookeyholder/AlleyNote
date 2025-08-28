# 專案架構分析報告

**生成時間**: 2025-08-28 19:16:12

## 📁 目錄結構

- `app`
- `app/Application`
- `app/Application/Controllers`
- `app/Application/Controllers/Health`
- `app/Application/Controllers/Health/.`
- `app/Application/Controllers/Health/..`
- `app/Application/Controllers/Security`
- `app/Application/Controllers/Security/.`
- `app/Application/Controllers/Security/..`
- `app/Application/Controllers/.`
- `app/Application/Controllers/Web`
- `app/Application/Controllers/Web/.`
- `app/Application/Controllers/Web/..`
- `app/Application/Controllers/..`
- `app/Application/Controllers/Api`
- `app/Application/Controllers/Api/V1`
- `app/Application/Controllers/Api/V1/.`
- `app/Application/Controllers/Api/V1/..`
- `app/Application/Controllers/Api/.`
- `app/Application/Controllers/Api/..`
- `app/Application/.`
- `app/Application/..`
- `app/Application/Middleware`
- `app/Application/Middleware/.`
- `app/Application/Middleware/..`
- `app/Shared`
- `app/Shared/Helpers`
- `app/Shared/Helpers/.`
- `app/Shared/Helpers/..`
- `app/Shared/Schemas`
- `app/Shared/Schemas/.`
- `app/Shared/Schemas/..`
- `app/Shared/.`
- `app/Shared/Exceptions`
- `app/Shared/Exceptions/.`
- `app/Shared/Exceptions/..`
- `app/Shared/Exceptions/Validation`
- `app/Shared/Exceptions/Validation/.`
- `app/Shared/Exceptions/Validation/..`
- `app/Shared/..`
- `app/Shared/DTOs`
- `app/Shared/DTOs/.`
- `app/Shared/DTOs/..`
- `app/Shared/Contracts`
- `app/Shared/Contracts/.`
- `app/Shared/Contracts/..`
- `app/Shared/Validation`
- `app/Shared/Validation/.`
- `app/Shared/Validation/..`
- `app/Shared/Validation/Factory`
- `app/Shared/Validation/Factory/.`
- `app/Shared/Validation/Factory/..`
- `app/Shared/Http`
- `app/Shared/Http/.`
- `app/Shared/Http/..`
- `app/.`
- `app/Domains`
- `app/Domains/Attachment`
- `app/Domains/Attachment/Models`
- `app/Domains/Attachment/Models/.`
- `app/Domains/Attachment/Models/..`
- `app/Domains/Attachment/Services`
- `app/Domains/Attachment/Services/.`
- `app/Domains/Attachment/Services/..`
- `app/Domains/Attachment/Repositories`
- `app/Domains/Attachment/Repositories/.`
- `app/Domains/Attachment/Repositories/..`
- `app/Domains/Attachment/.`
- `app/Domains/Attachment/..`
- `app/Domains/Attachment/DTOs`
- `app/Domains/Attachment/DTOs/.`
- `app/Domains/Attachment/DTOs/..`
- `app/Domains/Attachment/Contracts`
- `app/Domains/Attachment/Contracts/.`
- `app/Domains/Attachment/Contracts/..`
- `app/Domains/Attachment/Enums`
- `app/Domains/Attachment/Enums/.`
- `app/Domains/Attachment/Enums/..`
- `app/Domains/Security`
- `app/Domains/Security/Models`
- `app/Domains/Security/Models/.`
- `app/Domains/Security/Models/..`
- `app/Domains/Security/Services`
- `app/Domains/Security/Services/Advanced`
- `app/Domains/Security/Services/Advanced/.`
- `app/Domains/Security/Services/Advanced/..`
- `app/Domains/Security/Services/Headers`
- `app/Domains/Security/Services/Headers/.`
- `app/Domains/Security/Services/Headers/..`
- `app/Domains/Security/Services/Core`
- `app/Domains/Security/Services/Core/.`
- `app/Domains/Security/Services/Core/..`
- `app/Domains/Security/Services/Error`
- `app/Domains/Security/Services/Error/.`
- `app/Domains/Security/Services/Error/..`
- `app/Domains/Security/Services/Logging`
- `app/Domains/Security/Services/Logging/.`
- `app/Domains/Security/Services/Logging/..`
- `app/Domains/Security/Services/.`
- `app/Domains/Security/Services/..`
- `app/Domains/Security/Services/Secrets`
- `app/Domains/Security/Services/Secrets/.`
- `app/Domains/Security/Services/Secrets/..`
- `app/Domains/Security/Services/Content`
- `app/Domains/Security/Services/Content/.`
- `app/Domains/Security/Services/Content/..`
- `app/Domains/Security/Entities`
- `app/Domains/Security/Entities/.`
- `app/Domains/Security/Entities/..`
- `app/Domains/Security/Repositories`
- `app/Domains/Security/Repositories/.`
- `app/Domains/Security/Repositories/..`
- `app/Domains/Security/.`
- `app/Domains/Security/..`
- `app/Domains/Security/DTOs`
- `app/Domains/Security/DTOs/.`
- `app/Domains/Security/DTOs/..`
- `app/Domains/Security/Contracts`
- `app/Domains/Security/Contracts/.`
- `app/Domains/Security/Contracts/..`
- `app/Domains/Security/Enums`
- `app/Domains/Security/Enums/.`
- `app/Domains/Security/Enums/..`
- `app/Domains/Post`
- `app/Domains/Post/Models`
- `app/Domains/Post/Models/.`
- `app/Domains/Post/Models/..`
- `app/Domains/Post/Services`
- `app/Domains/Post/Services/.`
- `app/Domains/Post/Services/..`
- `app/Domains/Post/Repositories`
- `app/Domains/Post/Repositories/.`
- `app/Domains/Post/Repositories/..`
- `app/Domains/Post/.`
- `app/Domains/Post/Exceptions`
- `app/Domains/Post/Exceptions/.`
- `app/Domains/Post/Exceptions/..`
- `app/Domains/Post/..`
- `app/Domains/Post/DTOs`
- `app/Domains/Post/DTOs/.`
- `app/Domains/Post/DTOs/..`
- `app/Domains/Post/Contracts`
- `app/Domains/Post/Contracts/.`
- `app/Domains/Post/Contracts/..`
- `app/Domains/Post/Enums`
- `app/Domains/Post/Enums/.`
- `app/Domains/Post/Enums/..`
- `app/Domains/Post/Validation`
- `app/Domains/Post/Validation/.`
- `app/Domains/Post/Validation/..`
- `app/Domains/.`
- `app/Domains/..`
- `app/Domains/Auth`
- `app/Domains/Auth/Models`
- `app/Domains/Auth/Models/.`
- `app/Domains/Auth/Models/..`
- `app/Domains/Auth/Services`
- `app/Domains/Auth/Services/Advanced`
- `app/Domains/Auth/Services/Advanced/.`
- `app/Domains/Auth/Services/Advanced/..`
- `app/Domains/Auth/Services/.`
- `app/Domains/Auth/Services/..`
- `app/Domains/Auth/Repositories`
- `app/Domains/Auth/Repositories/.`
- `app/Domains/Auth/Repositories/..`
- `app/Domains/Auth/.`
- `app/Domains/Auth/Exceptions`
- `app/Domains/Auth/Exceptions/.`
- `app/Domains/Auth/Exceptions/..`
- `app/Domains/Auth/..`
- `app/Domains/Auth/DTOs`
- `app/Domains/Auth/DTOs/.`
- `app/Domains/Auth/DTOs/..`
- `app/Domains/Auth/Contracts`
- `app/Domains/Auth/Contracts/.`
- `app/Domains/Auth/Contracts/..`
- `app/..`
- `app/Infrastructure`
- `app/Infrastructure/Services`
- `app/Infrastructure/Services/.`
- `app/Infrastructure/Services/..`
- `app/Infrastructure/Cache`
- `app/Infrastructure/Cache/.`
- `app/Infrastructure/Cache/..`
- `app/Infrastructure/OpenApi`
- `app/Infrastructure/OpenApi/.`
- `app/Infrastructure/OpenApi/..`
- `app/Infrastructure/Database`
- `app/Infrastructure/Database/.`
- `app/Infrastructure/Database/..`
- `app/Infrastructure/.`
- `app/Infrastructure/Config`
- `app/Infrastructure/Config/.`
- `app/Infrastructure/Config/..`
- `app/Infrastructure/..`
- `app/Infrastructure/Routing`
- `app/Infrastructure/Routing/Cache`
- `app/Infrastructure/Routing/Cache/.`
- `app/Infrastructure/Routing/Cache/..`
- `app/Infrastructure/Routing/Core`
- `app/Infrastructure/Routing/Core/.`
- `app/Infrastructure/Routing/Core/..`
- `app/Infrastructure/Routing/Providers`
- `app/Infrastructure/Routing/Providers/.`
- `app/Infrastructure/Routing/Providers/..`
- `app/Infrastructure/Routing/.`
- `app/Infrastructure/Routing/Exceptions`
- `app/Infrastructure/Routing/Exceptions/.`
- `app/Infrastructure/Routing/Exceptions/..`
- `app/Infrastructure/Routing/..`
- `app/Infrastructure/Routing/Contracts`
- `app/Infrastructure/Routing/Contracts/.`
- `app/Infrastructure/Routing/Contracts/..`
- `app/Infrastructure/Routing/Middleware`
- `app/Infrastructure/Routing/Middleware/.`
- `app/Infrastructure/Routing/Middleware/..`
- `app/Infrastructure/Http`
- `app/Infrastructure/Http/.`
- `app/Infrastructure/Http/..`
- `scripts`
- `scripts/.`
- `scripts/lib`
- `scripts/lib/.`
- `scripts/lib/..`
- `scripts/..`
- `config`
- `config/routes`
- `config/routes/.`
- `config/routes/..`
- `config/.`
- `config/..`
- `database`
- `database/migrations`
- `database/migrations/.`
- `database/migrations/..`
- `database/seeds`
- `database/seeds/.`
- `database/seeds/..`
- `database/.`
- `database/..`
- `docs`
- `docs/.`
- `docs/..`
- `.`
- `.github`
- `.github/workflows`
- `.github/workflows/.`
- `.github/workflows/..`
- `.github/.`
- `.github/..`
- `.github/chatmodes`
- `.github/chatmodes/.`
- `.github/chatmodes/..`
- `..`
- `ssl-data`
- `ssl-data/.`
- `ssl-data/..`
- `certbot-data`
- `certbot-data/.`
- `certbot-data/..`
- `examples`
- `examples/.`
- `examples/..`

## 🏷️ 命名空間分析

### `App\Application\Controllers\Health`
- app/Application/Controllers/Health/HealthController.php

### `App\Application\Controllers`
- app/Application/Controllers/TestController.php
- app/Application/Controllers/PostController.php
- app/Application/Controllers/BaseController.php

### `App\Application\Controllers\Security`
- app/Application/Controllers/Security/CSPReportController.php

### `App\Application\Controllers\Web`
- app/Application/Controllers/Web/SwaggerController.php

### `App\Application\Controllers\Api\V1`
- app/Application/Controllers/Api/V1/IpController.php
- app/Application/Controllers/Api/V1/PostController.php
- app/Application/Controllers/Api/V1/ActivityLogController.php
- app/Application/Controllers/Api/V1/AuthController.php
- app/Application/Controllers/Api/V1/AttachmentController.php

### `App\Application\Middleware`
- app/Application/Middleware/RateLimitMiddleware.php
- app/Application/Middleware/AuthorizationMiddleware.php

### `App`
- app/Application.php

### `App\Shared\Schemas`
- app/Shared/Schemas/PostSchema.php
- app/Shared/Schemas/AuthSchema.php
- app/Shared/Schemas/PostRequestSchema.php

### `App\Shared\Exceptions`
- app/Shared/Exceptions/NotFoundException.php
- app/Shared/Exceptions/CsrfTokenException.php
- app/Shared/Exceptions/ValidationException.php
- app/Shared/Exceptions/StateTransitionException.php

### `App\Shared\Exceptions\Validation`
- app/Shared/Exceptions/Validation/RequestValidationException.php

### `App\Shared\DTOs`
- app/Shared/DTOs/BaseDTO.php

### `App\Shared\Contracts`
- app/Shared/Contracts/CacheServiceInterface.php
- app/Shared/Contracts/OutputSanitizerInterface.php
- app/Shared/Contracts/ValidatorInterface.php
- app/Shared/Contracts/RepositoryInterface.php

### `App\Shared\Validation`
- app/Shared/Validation/Validator.php
- app/Shared/Validation/ValidationResult.php

### `App\Shared\Validation\Factory`
- app/Shared/Validation/Factory/ValidatorFactory.php

### `App\Shared\Http`
- app/Shared/Http/ApiResponse.php

### `App\Domains\Attachment\Models`
- app/Domains/Attachment/Models/Attachment.php

### `App\Domains\Attachment\Services`
- app/Domains/Attachment/Services/AttachmentService.php
- app/Domains/Attachment/Services/FileSecurityService.php

### `App\Domains\Attachment\Repositories`
- app/Domains/Attachment/Repositories/AttachmentRepository.php

### `App\Domains\Attachment\DTOs`
- app/Domains/Attachment/DTOs/CreateAttachmentDTO.php

### `App\Domains\Attachment\Contracts`
- app/Domains/Attachment/Contracts/FileSecurityServiceInterface.php
- app/Domains/Attachment/Contracts/AttachmentServiceInterface.php
- app/Domains/Attachment/Contracts/AttachmentRepositoryInterface.php

### `App\Domains\Attachment\Enums`
- app/Domains/Attachment/Enums/FileRules.php

### `App\Domains\Security\Models`
- app/Domains/Security/Models/IpList.php

### `App\Domains\Security\Services\Advanced`
- app/Domains/Security/Services/Advanced/SecurityTestService.php

### `App\Domains\Security\Services\Headers`
- app/Domains/Security/Services/Headers/SecurityHeaderService.php

### `App\Domains\Security\Services\Core`
- app/Domains/Security/Services/Core/CsrfProtectionService.php
- app/Domains/Security/Services/Core/XssProtectionService.php

### `App\Domains\Security\Services\Error`
- app/Domains/Security/Services/Error/ErrorHandlerService.php

### `App\Domains\Security\Services`
- app/Domains/Security/Services/ActivityLoggingService.php
- app/Domains/Security/Services/IpService.php

### `App\Domains\Security\Services\Logging`
- app/Domains/Security/Services/Logging/LoggingSecurityService.php

### `App\Domains\Security\Services\Secrets`
- app/Domains/Security/Services/Secrets/SecretsManager.php

### `App\Domains\Security\Services\Content`
- app/Domains/Security/Services/Content/XssProtectionExtensionService.php

### `App\Domains\Security\Entities`
- app/Domains/Security/Entities/ActivityLog.php

### `App\Domains\Security\Repositories`
- app/Domains/Security/Repositories/IpRepository.php
- app/Domains/Security/Repositories/ActivityLogRepository.php

### `App\Domains\Security\DTOs`
- app/Domains/Security/DTOs/CreateActivityLogDTO.php
- app/Domains/Security/DTOs/ActivityLogSearchDTO.php
- app/Domains/Security/DTOs/CreateIpRuleDTO.php

### `App\Domains\Security\Contracts`
- app/Domains/Security/Contracts/ActivityLogRepositoryInterface.php
- app/Domains/Security/Contracts/CsrfProtectionServiceInterface.php
- app/Domains/Security/Contracts/LoggingSecurityServiceInterface.php
- app/Domains/Security/Contracts/IpRepositoryInterface.php
- app/Domains/Security/Contracts/SecurityTestInterface.php
- app/Domains/Security/Contracts/SecretsManagerInterface.php
- app/Domains/Security/Contracts/XssProtectionServiceInterface.php
- app/Domains/Security/Contracts/SecurityHeaderServiceInterface.php
- app/Domains/Security/Contracts/ErrorHandlerServiceInterface.php
- app/Domains/Security/Contracts/ActivityLoggingServiceInterface.php

### `App\Domains\Security\Enums`
- app/Domains/Security/Enums/ActivitySeverity.php
- app/Domains/Security/Enums/ActivityCategory.php
- app/Domains/Security/Enums/ActivityType.php
- app/Domains/Security/Enums/ActivityStatus.php

### `App\Domains\Post\Models`
- app/Domains/Post/Models/Post.php

### `App\Domains\Post\Services`
- app/Domains/Post/Services/RichTextProcessorService.php
- app/Domains/Post/Services/PostService.php
- app/Domains/Post/Services/PostCacheKeyService.php
- app/Domains/Post/Services/ContentModerationService.php

### `App\Domains\Post\Repositories`
- app/Domains/Post/Repositories/PostRepository.php

### `App\Domains\Post\Exceptions`
- app/Domains/Post/Exceptions/PostStatusException.php
- app/Domains/Post/Exceptions/PostValidationException.php
- app/Domains/Post/Exceptions/PostNotFoundException.php

### `App\Domains\Post\DTOs`
- app/Domains/Post/DTOs/CreatePostDTO.php
- app/Domains/Post/DTOs/UpdatePostDTO.php

### `App\Domains\Post\Contracts`
- app/Domains/Post/Contracts/PostServiceInterface.php
- app/Domains/Post/Contracts/PostRepositoryInterface.php

### `App\Domains\Post\Enums`
- app/Domains/Post/Enums/PostStatus.php

### `App\Domains\Post\Validation`
- app/Domains/Post/Validation/PostValidator.php

### `App\Domains\Auth\Models`
- app/Domains/Auth/Models/Role.php
- app/Domains/Auth/Models/Permission.php

### `App\Domains\Auth\Services`
- app/Domains/Auth/Services/AuthService.php
- app/Domains/Auth/Services/PasswordManagementService.php
- app/Domains/Auth/Services/SessionSecurityService.php
- app/Domains/Auth/Services/PasswordSecurityService.php
- app/Domains/Auth/Services/AuthorizationService.php

### `App\Domains\Auth\Services\Advanced`
- app/Domains/Auth/Services/Advanced/PwnedPasswordService.php

### `App\Domains\Auth\Repositories`
- app/Domains/Auth/Repositories/UserRepository.php

### `App\Domains\Auth\Exceptions`
- app/Domains/Auth/Exceptions/ForbiddenException.php
- app/Domains/Auth/Exceptions/UnauthorizedException.php

### `App\Domains\Auth\DTOs`
- app/Domains/Auth/DTOs/RegisterUserDTO.php

### `App\Domains\Auth\Contracts`
- app/Domains/Auth/Contracts/PasswordSecurityServiceInterface.php
- app/Domains/Auth/Contracts/UserRepositoryInterface.php
- app/Domains/Auth/Contracts/SessionSecurityServiceInterface.php
- app/Domains/Auth/Contracts/AuthorizationServiceInterface.php

### `App\Infrastructure\Services`
- app/Infrastructure/Services/CacheService.php
- app/Infrastructure/Services/RateLimitService.php
- app/Infrastructure/Services/OutputSanitizer.php

### `App\Infrastructure\Cache`
- app/Infrastructure/Cache/CacheKeys.php
- app/Infrastructure/Cache/CacheManager.php

### `App\Infrastructure\OpenApi`
- app/Infrastructure/OpenApi/OpenApiSpec.php

### `App\Infrastructure\Database`
- app/Infrastructure/Database/DatabaseConnection.php

### `App\Infrastructure\Config`
- app/Infrastructure/Config/ContainerFactory.php

### `App\Infrastructure\Routing\Cache`
- app/Infrastructure/Routing/Cache/FileRouteCache.php
- app/Infrastructure/Routing/Cache/RouteCacheFactory.php
- app/Infrastructure/Routing/Cache/MemoryRouteCache.php
- app/Infrastructure/Routing/Cache/RedisRouteCache.php

### `App\Infrastructure\Routing\Core`
- app/Infrastructure/Routing/Core/Router.php
- app/Infrastructure/Routing/Core/Route.php
- app/Infrastructure/Routing/Core/RouteCollection.php

### `App\Infrastructure\Routing`
- app/Infrastructure/Routing/RouteDispatcher.php
- app/Infrastructure/Routing/ControllerResolver.php
- app/Infrastructure/Routing/RouteLoader.php
- app/Infrastructure/Routing/RouteValidator.php
- app/Infrastructure/Routing/ClosureRequestHandler.php

### `App\Infrastructure\Routing\Providers`
- app/Infrastructure/Routing/Providers/RoutingServiceProvider.php

### `App\Infrastructure\Routing\Exceptions`
- app/Infrastructure/Routing/Exceptions/RouteConfigurationException.php

### `App\Infrastructure\Routing\Contracts`
- app/Infrastructure/Routing/Contracts/RouterInterface.php
- app/Infrastructure/Routing/Contracts/MiddlewareManagerInterface.php
- app/Infrastructure/Routing/Contracts/MiddlewareInterface.php
- app/Infrastructure/Routing/Contracts/RequestHandlerInterface.php
- app/Infrastructure/Routing/Contracts/MiddlewareDispatcherInterface.php
- app/Infrastructure/Routing/Contracts/RouteMatchResult.php
- app/Infrastructure/Routing/Contracts/RouteCacheInterface.php
- app/Infrastructure/Routing/Contracts/RouteCollectionInterface.php
- app/Infrastructure/Routing/Contracts/RouteInterface.php

### `App\Infrastructure\Routing\Middleware`
- app/Infrastructure/Routing/Middleware/MiddlewareDispatcher.php
- app/Infrastructure/Routing/Middleware/MiddlewareManager.php
- app/Infrastructure/Routing/Middleware/AbstractMiddleware.php
- app/Infrastructure/Routing/Middleware/RouteParametersMiddleware.php
- app/Infrastructure/Routing/Middleware/RouteInfoMiddleware.php

### `App\Infrastructure\Http`
- app/Infrastructure/Http/ServerRequest.php
- app/Infrastructure/Http/Uri.php
- app/Infrastructure/Http/ServerRequestFactory.php

### `後添加
            if (preg_match('/^namespace [^`
- scripts/fix-phpunit-deprecations.php

### `$new`
- scripts/ddd-namespace-updater.php

### `= trim($matches[1])`
- scripts/scan-project-architecture.php


## 🏗️ DDD 架構分析

### Application 層
**子目錄**: Controllers, Controllers/Health, Controllers/Health/., Controllers/Health/.., Controllers/Security, Controllers/Security/., Controllers/Security/.., Controllers/., Controllers/Web, Controllers/Web/., Controllers/Web/.., Controllers/.., Controllers/Api, Controllers/Api/V1, Controllers/Api/V1/., Controllers/Api/V1/.., Controllers/Api/., Controllers/Api/.., .., Middleware, Middleware/., Middleware/..
**檔案數量**: 13

### Domains 層
**子目錄**: Attachment, Attachment/Models, Attachment/Models/., Attachment/Models/.., Attachment/Services, Attachment/Services/., Attachment/Services/.., Attachment/Repositories, Attachment/Repositories/., Attachment/Repositories/.., Attachment/., Attachment/.., Attachment/DTOs, Attachment/DTOs/., Attachment/DTOs/.., Attachment/Contracts, Attachment/Contracts/., Attachment/Contracts/.., Attachment/Enums, Attachment/Enums/., Attachment/Enums/.., Security, Security/Models, Security/Models/., Security/Models/.., Security/Services, Security/Services/Advanced, Security/Services/Advanced/., Security/Services/Advanced/.., Security/Services/Headers, Security/Services/Headers/., Security/Services/Headers/.., Security/Services/Core, Security/Services/Core/., Security/Services/Core/.., Security/Services/Error, Security/Services/Error/., Security/Services/Error/.., Security/Services/Logging, Security/Services/Logging/., Security/Services/Logging/.., Security/Services/., Security/Services/.., Security/Services/Secrets, Security/Services/Secrets/., Security/Services/Secrets/.., Security/Services/Content, Security/Services/Content/., Security/Services/Content/.., Security/Entities, Security/Entities/., Security/Entities/.., Security/Repositories, Security/Repositories/., Security/Repositories/.., Security/., Security/.., Security/DTOs, Security/DTOs/., Security/DTOs/.., Security/Contracts, Security/Contracts/., Security/Contracts/.., Security/Enums, Security/Enums/., Security/Enums/.., Post, Post/Models, Post/Models/., Post/Models/.., Post/Services, Post/Services/., Post/Services/.., Post/Repositories, Post/Repositories/., Post/Repositories/.., Post/., Post/Exceptions, Post/Exceptions/., Post/Exceptions/.., Post/.., Post/DTOs, Post/DTOs/., Post/DTOs/.., Post/Contracts, Post/Contracts/., Post/Contracts/.., Post/Enums, Post/Enums/., Post/Enums/.., Post/Validation, Post/Validation/., Post/Validation/.., .., Auth, Auth/Models, Auth/Models/., Auth/Models/.., Auth/Services, Auth/Services/Advanced, Auth/Services/Advanced/., Auth/Services/Advanced/.., Auth/Services/., Auth/Services/.., Auth/Repositories, Auth/Repositories/., Auth/Repositories/.., Auth/., Auth/Exceptions, Auth/Exceptions/., Auth/Exceptions/.., Auth/.., Auth/DTOs, Auth/DTOs/., Auth/DTOs/.., Auth/Contracts, Auth/Contracts/., Auth/Contracts/.., storage, storage/., storage/.., storage/cache, storage/cache/htmlpurifier, storage/cache/htmlpurifier/., storage/cache/htmlpurifier/.., storage/cache/., storage/cache/..
**檔案數量**: 71

### Infrastructure 層
**子目錄**: Services, Services/., Services/.., Cache, Cache/., Cache/.., OpenApi, OpenApi/., OpenApi/.., Database, Database/., Database/.., Config, Config/., Config/.., .., Routing, Routing/Cache, Routing/Cache/., Routing/Cache/.., Routing/Core, Routing/Core/., Routing/Core/.., Routing/Providers, Routing/Providers/., Routing/Providers/.., Routing/., Routing/Exceptions, Routing/Exceptions/., Routing/Exceptions/.., Routing/.., Routing/Contracts, Routing/Contracts/., Routing/Contracts/.., Routing/Middleware, Routing/Middleware/., Routing/Middleware/.., Http, Http/., Http/..
**檔案數量**: 40

### Shared 層
**子目錄**: Helpers, Helpers/., Helpers/.., Schemas, Schemas/., Schemas/.., Exceptions, Exceptions/., Exceptions/.., Exceptions/Validation, Exceptions/Validation/., Exceptions/Validation/.., .., DTOs, DTOs/., DTOs/.., Contracts, Contracts/., Contracts/.., Validation, Validation/., Validation/.., Validation/Factory, Validation/Factory/., Validation/Factory/.., Http, Http/., Http/..
**檔案數量**: 18


## 📊 類別統計

- **類別總數**: 114
- **介面總數**: 31
- **Trait 總數**: 0

## ⚠️ 發現的架構問題

- ⚠️  可能的循環依賴: app/Application/Controllers/Health/HealthController.php -> App\Application\Controllers\BaseController
- ⚠️  可能的循環依賴: app/Application/Controllers/Api/V1/PostController.php -> App\Application\Controllers\BaseController
- ⚠️  可能的循環依賴: app/Application/Controllers/Api/V1/ActivityLogController.php -> App\Application\Controllers\BaseController
- ⚠️  可能的循環依賴: app/Application/Controllers/Api/V1/AuthController.php -> App\Application\Controllers\BaseController

## 🔑 重要類別清單

- **HealthController**: `app/Application/Controllers/TestController.php`
  - 實作: 
- **CSPReportController**: `app/Application/Controllers/Security/CSPReportController.php`
  - 實作: 
- **PostController**: `app/Application/Controllers/Api/V1/PostController.php`
  - 繼承: BaseController
  - 實作: 
- **SwaggerController**: `app/Application/Controllers/Web/SwaggerController.php`
  - 實作: 
- **BaseController**: `app/Application/Controllers/BaseController.php`
  - 實作: 
- **IpController**: `app/Application/Controllers/Api/V1/IpController.php`
  - 實作: 
- **ActivityLogController**: `app/Application/Controllers/Api/V1/ActivityLogController.php`
  - 繼承: BaseController
  - 實作: 
- **AuthController**: `app/Application/Controllers/Api/V1/AuthController.php`
  - 繼承: BaseController
  - 實作: 
- **AttachmentController**: `app/Application/Controllers/Api/V1/AttachmentController.php`
  - 實作: 
- **implements**: `app/Infrastructure/Routing/ControllerResolver.php`
  - 實作: 
- **AttachmentService**: `app/Domains/Attachment/Services/AttachmentService.php`
  - 實作: AttachmentServiceInterface
- **FileSecurityService**: `app/Domains/Attachment/Services/FileSecurityService.php`
  - 實作: FileSecurityServiceInterface
- **AttachmentRepository**: `app/Domains/Attachment/Repositories/AttachmentRepository.php`
  - 實作: 
- **SecurityTestService**: `app/Domains/Security/Services/Advanced/SecurityTestService.php`
  - 實作: SecurityTestInterface
- **SecurityHeaderService**: `app/Domains/Security/Services/Headers/SecurityHeaderService.php`
  - 實作: SecurityHeaderServiceInterface
- **CsrfProtectionService**: `app/Domains/Security/Services/Core/CsrfProtectionService.php`
  - 實作: 
- **XssProtectionService**: `app/Domains/Security/Services/Core/XssProtectionService.php`
  - 實作: 
- **ErrorHandlerService**: `app/Domains/Security/Services/Error/ErrorHandlerService.php`
  - 實作: ErrorHandlerServiceInterface
- **ActivityLoggingService**: `app/Domains/Security/Services/ActivityLoggingService.php`
  - 實作: ActivityLoggingServiceInterface
- **LoggingSecurityService**: `app/Domains/Security/Services/Logging/LoggingSecurityService.php`
  - 實作: LoggingSecurityServiceInterface
- **SecretsManager**: `app/Domains/Security/Services/Secrets/SecretsManager.php`
  - 實作: SecretsManagerInterface
- **XssProtectionExtensionService**: `app/Domains/Security/Services/Content/XssProtectionExtensionService.php`
  - 實作: 
- **IpService**: `app/Domains/Security/Services/IpService.php`
  - 實作: 
- **IpRepository**: `app/Domains/Security/Repositories/IpRepository.php`
  - 實作: IpRepositoryInterface
- **ActivityLogRepository**: `app/Domains/Security/Repositories/ActivityLogRepository.php`
  - 實作: ActivityLogRepositoryInterface
- **RichTextProcessorService**: `app/Domains/Post/Services/RichTextProcessorService.php`
  - 實作: 
- **PostService**: `app/Domains/Post/Services/PostService.php`
  - 實作: PostServiceInterface
- **PostCacheKeyService**: `app/Domains/Post/Services/PostCacheKeyService.php`
  - 實作: 
- **ContentModerationService**: `app/Domains/Post/Services/ContentModerationService.php`
  - 實作: 
- **PostRepository**: `app/Domains/Post/Repositories/PostRepository.php`
  - 實作: PostRepositoryInterface
- **AuthService**: `app/Domains/Auth/Services/AuthService.php`
  - 實作: 
- **PwnedPasswordService**: `app/Domains/Auth/Services/Advanced/PwnedPasswordService.php`
  - 實作: 
- **PasswordManagementService**: `app/Domains/Auth/Services/PasswordManagementService.php`
  - 實作: 
- **SessionSecurityService**: `app/Domains/Auth/Services/SessionSecurityService.php`
  - 實作: SessionSecurityServiceInterface
- **PasswordSecurityService**: `app/Domains/Auth/Services/PasswordSecurityService.php`
  - 實作: PasswordSecurityServiceInterface
- **AuthorizationService**: `app/Domains/Auth/Services/AuthorizationService.php`
  - 實作: AuthorizationServiceInterface
- **UserRepository**: `app/Domains/Auth/Repositories/UserRepository.php`
  - 實作: 
- **CacheService**: `app/Infrastructure/Services/CacheService.php`
  - 實作: CacheServiceInterface
- **RateLimitService**: `app/Infrastructure/Services/RateLimitService.php`
  - 實作: 
- **OutputSanitizer**: `app/Infrastructure/Services/OutputSanitizer.php`
  - 實作: 
- **OutputSanitizerService**: `app/Infrastructure/Services/OutputSanitizer.php`
  - 實作: OutputSanitizerInterface
- **RoutingServiceProvider**: `app/Infrastructure/Routing/Providers/RoutingServiceProvider.php`
  - 實作: 
- **ControllerResolver**: `app/Infrastructure/Routing/ControllerResolver.php`
  - 實作: 

## 🔌 介面實作分析

### ``
- HealthController (`app/Application/Controllers/TestController.php`)
- CSPReportController (`app/Application/Controllers/Security/CSPReportController.php`)
- PostController (`app/Application/Controllers/Api/V1/PostController.php`)
- SwaggerController (`app/Application/Controllers/Web/SwaggerController.php`)
- BaseController (`app/Application/Controllers/BaseController.php`)
- IpController (`app/Application/Controllers/Api/V1/IpController.php`)
- ActivityLogController (`app/Application/Controllers/Api/V1/ActivityLogController.php`)
- AuthController (`app/Application/Controllers/Api/V1/AuthController.php`)
- AttachmentController (`app/Application/Controllers/Api/V1/AttachmentController.php`)
- AuthorizationMiddleware (`app/Application/Middleware/AuthorizationMiddleware.php`)
- Application (`app/Application.php`)
- implements (`app/Infrastructure/Routing/ControllerResolver.php`)
- PostSchema (`app/Shared/Schemas/PostSchema.php`)
- AuthSchema (`app/Shared/Schemas/AuthSchema.php`)
- PostRequestSchema (`app/Shared/Schemas/PostRequestSchema.php`)
- NotFoundException (`app/Shared/Exceptions/NotFoundException.php`)
- CsrfTokenException (`app/Shared/Exceptions/CsrfTokenException.php`)
- RequestValidationException (`app/Shared/Exceptions/Validation/RequestValidationException.php`)
- ValidationException (`app/Shared/Exceptions/ValidationException.php`)
- StateTransitionException (`app/Shared/Exceptions/StateTransitionException.php`)
- ValidatorFactory (`app/Shared/Validation/Factory/ValidatorFactory.php`)
- ApiResponse (`app/Shared/Http/ApiResponse.php`)
- Attachment (`app/Domains/Attachment/Models/Attachment.php`)
- AttachmentRepository (`app/Domains/Attachment/Repositories/AttachmentRepository.php`)
- CreateAttachmentDTO (`app/Domains/Attachment/DTOs/CreateAttachmentDTO.php`)
- FileRules (`app/Domains/Attachment/Enums/FileRules.php`)
- CsrfProtectionService (`app/Domains/Security/Services/Core/CsrfProtectionService.php`)
- XssProtectionService (`app/Domains/Security/Services/Core/XssProtectionService.php`)
- XssProtectionExtensionService (`app/Domains/Security/Services/Content/XssProtectionExtensionService.php`)
- IpService (`app/Domains/Security/Services/IpService.php`)
- ActivityLog (`app/Domains/Security/Entities/ActivityLog.php`)
- ActivityLogSearchDTO (`app/Domains/Security/DTOs/ActivityLogSearchDTO.php`)
- CreateIpRuleDTO (`app/Domains/Security/DTOs/CreateIpRuleDTO.php`)
- RichTextProcessorService (`app/Domains/Post/Services/RichTextProcessorService.php`)
- PostCacheKeyService (`app/Domains/Post/Services/PostCacheKeyService.php`)
- ContentModerationService (`app/Domains/Post/Services/ContentModerationService.php`)
- PostStatusException (`app/Domains/Post/Exceptions/PostStatusException.php`)
- PostValidationException (`app/Domains/Post/Exceptions/PostValidationException.php`)
- PostNotFoundException (`app/Domains/Post/Exceptions/PostNotFoundException.php`)
- CreatePostDTO (`app/Domains/Post/DTOs/CreatePostDTO.php`)
- UpdatePostDTO (`app/Domains/Post/DTOs/UpdatePostDTO.php`)
- PostValidator (`app/Domains/Post/Validation/PostValidator.php`)
- Role (`app/Domains/Auth/Models/Role.php`)
- Permission (`app/Domains/Auth/Models/Permission.php`)
- AuthService (`app/Domains/Auth/Services/AuthService.php`)
- PwnedPasswordService (`app/Domains/Auth/Services/Advanced/PwnedPasswordService.php`)
- PasswordManagementService (`app/Domains/Auth/Services/PasswordManagementService.php`)
- UserRepository (`app/Domains/Auth/Repositories/UserRepository.php`)
- ForbiddenException (`app/Domains/Auth/Exceptions/ForbiddenException.php`)
- UnauthorizedException (`app/Domains/Auth/Exceptions/UnauthorizedException.php`)
- RegisterUserDTO (`app/Domains/Auth/DTOs/RegisterUserDTO.php`)
- RateLimitService (`app/Infrastructure/Services/RateLimitService.php`)
- OutputSanitizer (`app/Infrastructure/Services/OutputSanitizer.php`)
- CacheKeys (`app/Infrastructure/Cache/CacheKeys.php`)
- CacheManager (`app/Infrastructure/Cache/CacheManager.php`)
- OpenApiSpec (`app/Infrastructure/OpenApi/OpenApiSpec.php`)
- DatabaseConnection (`app/Infrastructure/Database/DatabaseConnection.php`)
- ContainerFactory (`app/Infrastructure/Config/ContainerFactory.php`)
- RouteCacheFactory (`app/Infrastructure/Routing/Cache/RouteCacheFactory.php`)
- RouteDispatcher (`app/Infrastructure/Routing/RouteDispatcher.php`)
- RoutingServiceProvider (`app/Infrastructure/Routing/Providers/RoutingServiceProvider.php`)
- RouteConfigurationException (`app/Infrastructure/Routing/Exceptions/RouteConfigurationException.php`)
- ControllerResolver (`app/Infrastructure/Routing/ControllerResolver.php`)
- RouteLoader (`app/Infrastructure/Routing/RouteLoader.php`)
- RouteMatchResult (`app/Infrastructure/Routing/Contracts/RouteMatchResult.php`)
- RouteParametersMiddleware (`app/Infrastructure/Routing/Middleware/RouteParametersMiddleware.php`)
- RouteInfoMiddleware (`app/Infrastructure/Routing/Middleware/RouteInfoMiddleware.php`)
- RouteValidator (`app/Infrastructure/Routing/RouteValidator.php`)
- ServerRequestFactory (`app/Infrastructure/Http/ServerRequestFactory.php`)
- PhpUnitDeprecationFixer (`scripts/fix-phpunit-deprecations.php`)
- DDDNamespaceUpdater (`scripts/ddd-namespace-updater.php`)
- ProjectArchitectureScanner (`scripts/scan-project-architecture.php`)
- TestFixer (`scripts/test-fixer.php`)
- ConsoleOutput (`scripts/lib/ConsoleOutput.php`)
- ImprovementShowcase (`scripts/show-improvements.php`)
- InitialSchema (`database/migrations/20250823051608_initial_schema.php`)
- CreateUserActivityLogsTable (`database/migrations/create_user_activity_logs_table.php`)
- UserActivityLogsSeeder (`database/seeds/UserActivityLogsSeeder.php`)

### `MiddlewareInterface`
- RateLimitMiddleware (`app/Application/Middleware/RateLimitMiddleware.php`)
- AbstractMiddleware (`app/Infrastructure/Routing/Middleware/AbstractMiddleware.php`)

### `JsonSerializable`
- BaseDTO (`app/Shared/DTOs/BaseDTO.php`)
- ValidationResult (`app/Shared/Validation/ValidationResult.php`)
- IpList (`app/Domains/Security/Models/IpList.php`)
- CreateActivityLogDTO (`app/Domains/Security/DTOs/CreateActivityLogDTO.php`)
- Post (`app/Domains/Post/Models/Post.php`)

### `ValidatorInterface`
- Validator (`app/Shared/Validation/Validator.php`)

### `AttachmentServiceInterface`
- AttachmentService (`app/Domains/Attachment/Services/AttachmentService.php`)

### `FileSecurityServiceInterface`
- FileSecurityService (`app/Domains/Attachment/Services/FileSecurityService.php`)

### `SecurityTestInterface`
- SecurityTestService (`app/Domains/Security/Services/Advanced/SecurityTestService.php`)

### `SecurityHeaderServiceInterface`
- SecurityHeaderService (`app/Domains/Security/Services/Headers/SecurityHeaderService.php`)

### `ErrorHandlerServiceInterface`
- ErrorHandlerService (`app/Domains/Security/Services/Error/ErrorHandlerService.php`)

### `ActivityLoggingServiceInterface`
- ActivityLoggingService (`app/Domains/Security/Services/ActivityLoggingService.php`)

### `LoggingSecurityServiceInterface`
- LoggingSecurityService (`app/Domains/Security/Services/Logging/LoggingSecurityService.php`)

### `SecretsManagerInterface`
- SecretsManager (`app/Domains/Security/Services/Secrets/SecretsManager.php`)

### `IpRepositoryInterface`
- IpRepository (`app/Domains/Security/Repositories/IpRepository.php`)

### `ActivityLogRepositoryInterface`
- ActivityLogRepository (`app/Domains/Security/Repositories/ActivityLogRepository.php`)

### `PostServiceInterface`
- PostService (`app/Domains/Post/Services/PostService.php`)

### `PostRepositoryInterface`
- PostRepository (`app/Domains/Post/Repositories/PostRepository.php`)

### `SessionSecurityServiceInterface`
- SessionSecurityService (`app/Domains/Auth/Services/SessionSecurityService.php`)

### `PasswordSecurityServiceInterface`
- PasswordSecurityService (`app/Domains/Auth/Services/PasswordSecurityService.php`)

### `AuthorizationServiceInterface`
- AuthorizationService (`app/Domains/Auth/Services/AuthorizationService.php`)

### `CacheServiceInterface`
- CacheService (`app/Infrastructure/Services/CacheService.php`)

### `OutputSanitizerInterface`
- OutputSanitizerService (`app/Infrastructure/Services/OutputSanitizer.php`)

### `RouteCacheInterface`
- FileRouteCache (`app/Infrastructure/Routing/Cache/FileRouteCache.php`)
- MemoryRouteCache (`app/Infrastructure/Routing/Cache/MemoryRouteCache.php`)
- RedisRouteCache (`app/Infrastructure/Routing/Cache/RedisRouteCache.php`)

### `RouterInterface`
- Router (`app/Infrastructure/Routing/Core/Router.php`)

### `RouteInterface`
- Route (`app/Infrastructure/Routing/Core/Route.php`)

### `RouteCollectionInterface`
- RouteCollection (`app/Infrastructure/Routing/Core/RouteCollection.php`)

### `MiddlewareDispatcherInterface`
- MiddlewareDispatcher (`app/Infrastructure/Routing/Middleware/MiddlewareDispatcher.php`)

### `MiddlewareManagerInterface`
- MiddlewareManager (`app/Infrastructure/Routing/Middleware/MiddlewareManager.php`)

### `RequestHandlerInterface`
- ClosureRequestHandler (`app/Infrastructure/Routing/ClosureRequestHandler.php`)

### `ServerRequestInterface`
- ServerRequest (`app/Infrastructure/Http/ServerRequest.php`)

### `UriInterface`
- Uri (`app/Infrastructure/Http/Uri.php`)


## 🧪 測試覆蓋分析

- **有測試的類別**: 0 個
- **缺少測試的類別**: 114 個

### 缺少測試的重要類別


## 💉 依賴注入分析

### 依賴較多的類別 (≥3個依賴)
- **PostController** (3 個依賴)
  - `PostServiceInterface` $postService
  - `ValidatorInterface` $validator
  - `OutputSanitizerInterface` $sanitizer

- **IpController** (3 個依賴)
  - `IpService` $service
  - `ValidatorInterface` $validator
  - `OutputSanitizerInterface` $sanitizer

- **AttachmentService** (4 個依賴)
  - `AttachmentRepository` $attachmentRepo
  - `PostRepository` $postRepo
  - `CacheServiceInterface` $cache
  - `AuthorizationService` $authService

- **SecurityTestService** (7 個依賴)
  - `SessionSecurityServiceInterface` $sessionService
  - `AuthorizationServiceInterface` $authService
  - `FileSecurityServiceInterface` $fileService
  - `SecurityHeaderServiceInterface` $headerService
  - `ErrorHandlerServiceInterface` $errorService
  - `PasswordSecurityServiceInterface` $passwordService
  - `SecretsManagerInterface` $secretsManager

- **XssProtectionExtensionService** (3 個依賴)
  - `XssProtectionService` $baseXssProtection
  - `RichTextProcessorService` $richTextProcessor
  - `ContentModerationService` $contentModerator

- **ActivityLog** (3 個依賴)
  - `ActivityType` $actionType
  - `ActivityStatus` $status
  - `DateTimeImmutable` $occurredAt

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

- **PostRepository** (3 個依賴)
  - `PDO` $db
  - `CacheServiceInterface` $cache
  - `LoggingSecurityServiceInterface` $logger

- **RouteDispatcher** (4 個依賴)
  - `RouterInterface` $router
  - `ControllerResolver` $controllerResolver
  - `MiddlewareDispatcher` $middlewareDispatcher
  - `ContainerInterface` $container


## ❓ 可能的問題引用

- ❓ 找不到類別/介面: App\Domains\Security\Enums\ActivityCategory (在 app/Application/Controllers/Api/V1/ActivityLogController.php 中使用)
- ❓ 找不到類別/介面: App\Domains\Security\Enums\ActivityType (在 app/Application/Controllers/Api/V1/ActivityLogController.php 中使用)
- ❓ 找不到類別/介面: GuzzleHttp\Psr7\Response (在 app/Application/Middleware/RateLimitMiddleware.php 中使用)
- ❓ 找不到類別/介面: DI\ContainerBuilder (在 app/Application.php 中使用)
- ❓ 找不到類別/介面: Throwable (在 app/Shared/Exceptions/ValidationException.php 中使用)
- ❓ 找不到類別/介面: the first error from ValidationResult
        if (empty($message)) {
            $message = $validationResult->getFirstError() ?? '驗證失敗' (在 app/Shared/Exceptions/ValidationException.php 中使用)
- ❓ 找不到類別/介面: ($id) {
            $sql = '
                SELECT *
                FROM attachments
                WHERE id = :id
            ' (在 app/Domains/Attachment/Repositories/AttachmentRepository.php 中使用)
- ❓ 找不到類別/介面: ($uuid) {
            $sql = '
                SELECT *
                FROM attachments
                WHERE uuid = :uuid
            ' (在 app/Domains/Attachment/Repositories/AttachmentRepository.php 中使用)
- ❓ 找不到類別/介面: ($postId) {
            $sql = '
                SELECT *
                FROM attachments
                WHERE post_id = :post_id
                AND deleted_at IS NULL
                ORDER BY created_at DESC
            ' (在 app/Domains/Attachment/Repositories/AttachmentRepository.php 中使用)
- ❓ 找不到類別/介面: HTMLPurifier (在 app/Domains/Security/Services/Core/XssProtectionService.php 中使用)
- ... 還有 93 個
