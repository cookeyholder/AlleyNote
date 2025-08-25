# 專案架構分析報告

**生成時間**: 2025-08-26 00:51:46

## 📁 目錄結構

- `.`
- `..`
- `database`
- `database/.`
- `database/..`
- `database/migrations`
- `database/migrations/.`
- `database/migrations/..`
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
- `app/Domains/Auth`
- `app/Domains/Auth/.`
- `app/Domains/Auth/..`
- `app/Domains/Auth/Contracts`
- `app/Domains/Auth/Contracts/.`
- `app/Domains/Auth/Contracts/..`
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
- `app/Shared/Contracts`
- `app/Shared/Contracts/.`
- `app/Shared/Contracts/..`
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
- `certbot-data`
- `certbot-data/.`
- `certbot-data/..`
- `config`
- `config/.`
- `config/..`
- `config/routes`
- `config/routes/.`
- `config/routes/..`
- `docs`
- `docs/.`
- `docs/..`
- `logs`
- `logs/.`
- `logs/..`
- `logs/redis`
- `logs/redis/.`
- `logs/redis/..`
- `logs/certbot`
- `logs/certbot/.`
- `logs/certbot/..`
- `logs/nginx`
- `logs/nginx/.`
- `logs/nginx/..`
- `logs/mysql`
- `logs/mysql/.`
- `logs/mysql/..`
- `examples`
- `examples/.`
- `examples/..`
- `scripts`
- `scripts/.`
- `scripts/..`
- `scripts/lib`
- `scripts/lib/.`
- `scripts/lib/..`
- `.github`
- `.github/.`
- `.github/..`
- `.github/workflows`
- `.github/workflows/.`
- `.github/workflows/..`
- `.github/chatmodes`
- `.github/chatmodes/.`
- `.github/chatmodes/..`
- `ssl-data`
- `ssl-data/.`
- `ssl-data/..`

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
- app/Domains/Security/Contracts/SecretsManagerInterface.php
- app/Domains/Security/Contracts/CsrfProtectionServiceInterface.php

### `App\Domains\Security\Repositories`
- app/Domains/Security/Repositories/IpRepository.php

### `App\Domains\Security\Models`
- app/Domains/Security/Models/IpList.php

### `App\Domains\Security\DTOs`
- app/Domains/Security/DTOs/CreateIpRuleDTO.php

### `App\Domains\Security\Services\Advanced`
- app/Domains/Security/Services/Advanced/SecurityTestService.php

### `App\Domains\Security\Services\Core`
- app/Domains/Security/Services/Core/XssProtectionService.php
- app/Domains/Security/Services/Core/CsrfProtectionService.php

### `App\Domains\Security\Services\Secrets`
- app/Domains/Security/Services/Secrets/SecretsManager.php

### `App\Domains\Security\Services\Content`
- app/Domains/Security/Services/Content/XssProtectionExtensionService.php

### `App\Domains\Security\Services`
- app/Domains/Security/Services/IpService.php

### `App\Domains\Security\Services\Headers`
- app/Domains/Security/Services/Headers/SecurityHeaderService.php

### `App\Domains\Security\Services\Error`
- app/Domains/Security/Services/Error/ErrorHandlerService.php

### `App\Domains\Security\Services\Logging`
- app/Domains/Security/Services/Logging/LoggingSecurityService.php

### `App\Domains\Auth\Contracts`
- app/Domains/Auth/Contracts/UserRepositoryInterface.php
- app/Domains/Auth/Contracts/SessionSecurityServiceInterface.php
- app/Domains/Auth/Contracts/AuthorizationServiceInterface.php
- app/Domains/Auth/Contracts/PasswordSecurityServiceInterface.php

### `App\Domains\Auth\Repositories`
- app/Domains/Auth/Repositories/UserRepository.php

### `App\Domains\Auth\Models`
- app/Domains/Auth/Models/Role.php
- app/Domains/Auth/Models/Permission.php

### `App\Domains\Auth\Exceptions`
- app/Domains/Auth/Exceptions/ForbiddenException.php
- app/Domains/Auth/Exceptions/UnauthorizedException.php

### `App\Domains\Auth\DTOs`
- app/Domains/Auth/DTOs/RegisterUserDTO.php

### `App\Domains\Auth\Services\Advanced`
- app/Domains/Auth/Services/Advanced/PwnedPasswordService.php

### `App\Domains\Auth\Services`
- app/Domains/Auth/Services/SessionSecurityService.php
- app/Domains/Auth/Services/AuthService.php
- app/Domains/Auth/Services/PasswordManagementService.php
- app/Domains/Auth/Services/PasswordSecurityService.php
- app/Domains/Auth/Services/AuthorizationService.php

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

### `App\Shared\Validation`
- app/Shared/Validation/Validator.php
- app/Shared/Validation/ValidationResult.php

### `App\Shared\Validation\Factory`
- app/Shared/Validation/Factory/ValidatorFactory.php

### `App\Application\Middleware`
- app/Application/Middleware/AuthorizationMiddleware.php
- app/Application/Middleware/RateLimitMiddleware.php

### `App\Application\Controllers\Security`
- app/Application/Controllers/Security/CSPReportController.php

### `App\Application\Controllers\Web`
- app/Application/Controllers/Web/SwaggerController.php

### `App\Application\Controllers\Health`
- app/Application/Controllers/Health/HealthController.php

### `App\Application\Controllers`
- app/Application/Controllers/PostController.php
- app/Application/Controllers/TestController.php
- app/Application/Controllers/BaseController.php

### `App\Application\Controllers\Api\V1`
- app/Application/Controllers/Api/V1/IpController.php
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

### `App\Infrastructure\OpenApi`
- app/Infrastructure/OpenApi/OpenApiSpec.php

### `App\Infrastructure\Http`
- app/Infrastructure/Http/Uri.php
- app/Infrastructure/Http/ServerRequest.php
- app/Infrastructure/Http/ServerRequestFactory.php

### `App\Infrastructure\Routing\Middleware`
- app/Infrastructure/Routing/Middleware/AbstractMiddleware.php
- app/Infrastructure/Routing/Middleware/MiddlewareDispatcher.php
- app/Infrastructure/Routing/Middleware/RouteParametersMiddleware.php
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

### `= trim($matches[1])`
- scripts/scan-project-architecture.php

### `$new`
- scripts/ddd-namespace-updater.php


## 🏗️ DDD 架構分析

### Application 層
**子目錄**: .., Middleware, Middleware/., Middleware/.., Controllers, Controllers/., Controllers/.., Controllers/Security, Controllers/Security/., Controllers/Security/.., Controllers/Web, Controllers/Web/., Controllers/Web/.., Controllers/Health, Controllers/Health/., Controllers/Health/.., Controllers/Api, Controllers/Api/., Controllers/Api/.., Controllers/Api/V1, Controllers/Api/V1/., Controllers/Api/V1/..
**檔案數量**: 12

### Domains 層
**子目錄**: .., Post, Post/., Post/.., Post/Contracts, Post/Contracts/., Post/Contracts/.., Post/Enums, Post/Enums/., Post/Enums/.., Post/Repositories, Post/Repositories/., Post/Repositories/.., Post/Models, Post/Models/., Post/Models/.., Post/Exceptions, Post/Exceptions/., Post/Exceptions/.., Post/DTOs, Post/DTOs/., Post/DTOs/.., Post/Services, Post/Services/., Post/Services/.., Post/Validation, Post/Validation/., Post/Validation/.., Security, Security/., Security/.., Security/Contracts, Security/Contracts/., Security/Contracts/.., Security/Repositories, Security/Repositories/., Security/Repositories/.., Security/Models, Security/Models/., Security/Models/.., Security/DTOs, Security/DTOs/., Security/DTOs/.., Security/Services, Security/Services/., Security/Services/.., Security/Services/Advanced, Security/Services/Advanced/., Security/Services/Advanced/.., Security/Services/Core, Security/Services/Core/., Security/Services/Core/.., Security/Services/Secrets, Security/Services/Secrets/., Security/Services/Secrets/.., Security/Services/Content, Security/Services/Content/., Security/Services/Content/.., Security/Services/Headers, Security/Services/Headers/., Security/Services/Headers/.., Security/Services/Error, Security/Services/Error/., Security/Services/Error/.., Security/Services/Logging, Security/Services/Logging/., Security/Services/Logging/.., Auth, Auth/., Auth/.., Auth/Contracts, Auth/Contracts/., Auth/Contracts/.., Auth/Repositories, Auth/Repositories/., Auth/Repositories/.., Auth/Models, Auth/Models/., Auth/Models/.., Auth/Exceptions, Auth/Exceptions/., Auth/Exceptions/.., Auth/DTOs, Auth/DTOs/., Auth/DTOs/.., Auth/Services, Auth/Services/., Auth/Services/.., Auth/Services/Advanced, Auth/Services/Advanced/., Auth/Services/Advanced/.., Attachment, Attachment/., Attachment/.., Attachment/Contracts, Attachment/Contracts/., Attachment/Contracts/.., Attachment/Enums, Attachment/Enums/., Attachment/Enums/.., Attachment/Repositories, Attachment/Repositories/., Attachment/Repositories/.., Attachment/Models, Attachment/Models/., Attachment/Models/.., Attachment/DTOs, Attachment/DTOs/., Attachment/DTOs/.., Attachment/Services, Attachment/Services/., Attachment/Services/.., storage, storage/., storage/.., storage/cache, storage/cache/., storage/cache/.., storage/cache/htmlpurifier, storage/cache/htmlpurifier/., storage/cache/htmlpurifier/..
**檔案數量**: 60

### Infrastructure 層
**子目錄**: .., Database, Database/., Database/.., Cache, Cache/., Cache/.., Config, Config/., Config/.., OpenApi, OpenApi/., OpenApi/.., Http, Http/., Http/.., Routing, Routing/., Routing/.., Routing/Middleware, Routing/Middleware/., Routing/Middleware/.., Routing/Core, Routing/Core/., Routing/Core/.., Routing/Cache, Routing/Cache/., Routing/Cache/.., Routing/Contracts, Routing/Contracts/., Routing/Contracts/.., Routing/Providers, Routing/Providers/., Routing/Providers/.., Routing/Exceptions, Routing/Exceptions/., Routing/Exceptions/.., Services, Services/., Services/..
**檔案數量**: 40

### Shared 層
**子目錄**: .., Contracts, Contracts/., Contracts/.., Exceptions, Exceptions/., Exceptions/.., Exceptions/Validation, Exceptions/Validation/., Exceptions/Validation/.., Schemas, Schemas/., Schemas/.., DTOs, DTOs/., DTOs/.., Http, Http/., Http/.., Helpers, Helpers/., Helpers/.., Validation, Validation/., Validation/.., Validation/Factory, Validation/Factory/., Validation/Factory/..
**檔案數量**: 18


## 📊 類別統計

- **類別總數**: 105
- **介面總數**: 29
- **Trait 總數**: 0

## ⚠️ 發現的架構問題

- ⚠️  可能的循環依賴: app/Application/Controllers/Health/HealthController.php -> App\Application\Controllers\BaseController
- ⚠️  可能的循環依賴: app/Application/Controllers/Api/V1/AuthController.php -> App\Application\Controllers\BaseController
- ⚠️  可能的循環依賴: app/Application/Controllers/Api/V1/PostController.php -> App\Application\Controllers\BaseController

## 🔑 重要類別清單

- **implements**: `app/Infrastructure/Routing/ControllerResolver.php`
  - 實作: 
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
- **IpRepository**: `app/Domains/Security/Repositories/IpRepository.php`
  - 實作: IpRepositoryInterface
- **SecurityTestService**: `app/Domains/Security/Services/Advanced/SecurityTestService.php`
  - 實作: SecurityTestInterface
- **XssProtectionService**: `app/Domains/Security/Services/Core/XssProtectionService.php`
  - 實作: 
- **CsrfProtectionService**: `app/Domains/Security/Services/Core/CsrfProtectionService.php`
  - 實作: 
- **SecretsManager**: `app/Domains/Security/Services/Secrets/SecretsManager.php`
  - 實作: SecretsManagerInterface
- **XssProtectionExtensionService**: `app/Domains/Security/Services/Content/XssProtectionExtensionService.php`
  - 實作: 
- **IpService**: `app/Domains/Security/Services/IpService.php`
  - 實作: 
- **SecurityHeaderService**: `app/Domains/Security/Services/Headers/SecurityHeaderService.php`
  - 實作: SecurityHeaderServiceInterface
- **ErrorHandlerService**: `app/Domains/Security/Services/Error/ErrorHandlerService.php`
  - 實作: ErrorHandlerServiceInterface
- **LoggingSecurityService**: `app/Domains/Security/Services/Logging/LoggingSecurityService.php`
  - 實作: LoggingSecurityServiceInterface
- **UserRepository**: `app/Domains/Auth/Repositories/UserRepository.php`
  - 實作: 
- **PwnedPasswordService**: `app/Domains/Auth/Services/Advanced/PwnedPasswordService.php`
  - 實作: 
- **SessionSecurityService**: `app/Domains/Auth/Services/SessionSecurityService.php`
  - 實作: SessionSecurityServiceInterface
- **AuthService**: `app/Domains/Auth/Services/AuthService.php`
  - 實作: 
- **PasswordManagementService**: `app/Domains/Auth/Services/PasswordManagementService.php`
  - 實作: 
- **PasswordSecurityService**: `app/Domains/Auth/Services/PasswordSecurityService.php`
  - 實作: PasswordSecurityServiceInterface
- **AuthorizationService**: `app/Domains/Auth/Services/AuthorizationService.php`
  - 實作: AuthorizationServiceInterface
- **AttachmentRepository**: `app/Domains/Attachment/Repositories/AttachmentRepository.php`
  - 實作: 
- **AttachmentService**: `app/Domains/Attachment/Services/AttachmentService.php`
  - 實作: AttachmentServiceInterface
- **FileSecurityService**: `app/Domains/Attachment/Services/FileSecurityService.php`
  - 實作: FileSecurityServiceInterface
- **CSPReportController**: `app/Application/Controllers/Security/CSPReportController.php`
  - 實作: 
- **SwaggerController**: `app/Application/Controllers/Web/SwaggerController.php`
  - 實作: 
- **HealthController**: `app/Application/Controllers/TestController.php`
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
- InitialSchema (`database/migrations/20250823051608_initial_schema.php`)
- Application (`app/Application.php`)
- implements (`app/Infrastructure/Routing/ControllerResolver.php`)
- PostStatusException (`app/Domains/Post/Exceptions/PostStatusException.php`)
- PostValidationException (`app/Domains/Post/Exceptions/PostValidationException.php`)
- PostNotFoundException (`app/Domains/Post/Exceptions/PostNotFoundException.php`)
- UpdatePostDTO (`app/Domains/Post/DTOs/UpdatePostDTO.php`)
- CreatePostDTO (`app/Domains/Post/DTOs/CreatePostDTO.php`)
- ContentModerationService (`app/Domains/Post/Services/ContentModerationService.php`)
- RichTextProcessorService (`app/Domains/Post/Services/RichTextProcessorService.php`)
- PostCacheKeyService (`app/Domains/Post/Services/PostCacheKeyService.php`)
- PostValidator (`app/Domains/Post/Validation/PostValidator.php`)
- CreateIpRuleDTO (`app/Domains/Security/DTOs/CreateIpRuleDTO.php`)
- XssProtectionService (`app/Domains/Security/Services/Core/XssProtectionService.php`)
- CsrfProtectionService (`app/Domains/Security/Services/Core/CsrfProtectionService.php`)
- XssProtectionExtensionService (`app/Domains/Security/Services/Content/XssProtectionExtensionService.php`)
- IpService (`app/Domains/Security/Services/IpService.php`)
- UserRepository (`app/Domains/Auth/Repositories/UserRepository.php`)
- Role (`app/Domains/Auth/Models/Role.php`)
- Permission (`app/Domains/Auth/Models/Permission.php`)
- ForbiddenException (`app/Domains/Auth/Exceptions/ForbiddenException.php`)
- UnauthorizedException (`app/Domains/Auth/Exceptions/UnauthorizedException.php`)
- RegisterUserDTO (`app/Domains/Auth/DTOs/RegisterUserDTO.php`)
- PwnedPasswordService (`app/Domains/Auth/Services/Advanced/PwnedPasswordService.php`)
- AuthService (`app/Domains/Auth/Services/AuthService.php`)
- PasswordManagementService (`app/Domains/Auth/Services/PasswordManagementService.php`)
- FileRules (`app/Domains/Attachment/Enums/FileRules.php`)
- AttachmentRepository (`app/Domains/Attachment/Repositories/AttachmentRepository.php`)
- Attachment (`app/Domains/Attachment/Models/Attachment.php`)
- CreateAttachmentDTO (`app/Domains/Attachment/DTOs/CreateAttachmentDTO.php`)
- NotFoundException (`app/Shared/Exceptions/NotFoundException.php`)
- StateTransitionException (`app/Shared/Exceptions/StateTransitionException.php`)
- CsrfTokenException (`app/Shared/Exceptions/CsrfTokenException.php`)
- ValidationException (`app/Shared/Exceptions/ValidationException.php`)
- RequestValidationException (`app/Shared/Exceptions/Validation/RequestValidationException.php`)
- PostSchema (`app/Shared/Schemas/PostSchema.php`)
- PostRequestSchema (`app/Shared/Schemas/PostRequestSchema.php`)
- AuthSchema (`app/Shared/Schemas/AuthSchema.php`)
- ApiResponse (`app/Shared/Http/ApiResponse.php`)
- ValidatorFactory (`app/Shared/Validation/Factory/ValidatorFactory.php`)
- AuthorizationMiddleware (`app/Application/Middleware/AuthorizationMiddleware.php`)
- CSPReportController (`app/Application/Controllers/Security/CSPReportController.php`)
- SwaggerController (`app/Application/Controllers/Web/SwaggerController.php`)
- HealthController (`app/Application/Controllers/TestController.php`)
- PostController (`app/Application/Controllers/Api/V1/PostController.php`)
- IpController (`app/Application/Controllers/Api/V1/IpController.php`)
- AuthController (`app/Application/Controllers/Api/V1/AuthController.php`)
- AttachmentController (`app/Application/Controllers/Api/V1/AttachmentController.php`)
- BaseController (`app/Application/Controllers/BaseController.php`)
- DatabaseConnection (`app/Infrastructure/Database/DatabaseConnection.php`)
- CacheManager (`app/Infrastructure/Cache/CacheManager.php`)
- CacheKeys (`app/Infrastructure/Cache/CacheKeys.php`)
- ContainerFactory (`app/Infrastructure/Config/ContainerFactory.php`)
- OpenApiSpec (`app/Infrastructure/OpenApi/OpenApiSpec.php`)
- ServerRequestFactory (`app/Infrastructure/Http/ServerRequestFactory.php`)
- RouteParametersMiddleware (`app/Infrastructure/Routing/Middleware/RouteParametersMiddleware.php`)
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
- ImprovementShowcase (`scripts/show-improvements.php`)
- TestFixer (`scripts/test-fixer.php`)
- ConsoleOutput (`scripts/lib/ConsoleOutput.php`)
- ProjectArchitectureScanner (`scripts/scan-project-architecture.php`)
- DDDNamespaceUpdater (`scripts/ddd-namespace-updater.php`)

### `PostRepositoryInterface`
- PostRepository (`app/Domains/Post/Repositories/PostRepository.php`)

### `JsonSerializable`
- Post (`app/Domains/Post/Models/Post.php`)
- IpList (`app/Domains/Security/Models/IpList.php`)
- BaseDTO (`app/Shared/DTOs/BaseDTO.php`)
- ValidationResult (`app/Shared/Validation/ValidationResult.php`)

### `PostServiceInterface`
- PostService (`app/Domains/Post/Services/PostService.php`)

### `IpRepositoryInterface`
- IpRepository (`app/Domains/Security/Repositories/IpRepository.php`)

### `SecurityTestInterface`
- SecurityTestService (`app/Domains/Security/Services/Advanced/SecurityTestService.php`)

### `SecretsManagerInterface`
- SecretsManager (`app/Domains/Security/Services/Secrets/SecretsManager.php`)

### `SecurityHeaderServiceInterface`
- SecurityHeaderService (`app/Domains/Security/Services/Headers/SecurityHeaderService.php`)

### `ErrorHandlerServiceInterface`
- ErrorHandlerService (`app/Domains/Security/Services/Error/ErrorHandlerService.php`)

### `LoggingSecurityServiceInterface`
- LoggingSecurityService (`app/Domains/Security/Services/Logging/LoggingSecurityService.php`)

### `SessionSecurityServiceInterface`
- SessionSecurityService (`app/Domains/Auth/Services/SessionSecurityService.php`)

### `PasswordSecurityServiceInterface`
- PasswordSecurityService (`app/Domains/Auth/Services/PasswordSecurityService.php`)

### `AuthorizationServiceInterface`
- AuthorizationService (`app/Domains/Auth/Services/AuthorizationService.php`)

### `AttachmentServiceInterface`
- AttachmentService (`app/Domains/Attachment/Services/AttachmentService.php`)

### `FileSecurityServiceInterface`
- FileSecurityService (`app/Domains/Attachment/Services/FileSecurityService.php`)

### `ValidatorInterface`
- Validator (`app/Shared/Validation/Validator.php`)

### `MiddlewareInterface`
- RateLimitMiddleware (`app/Application/Middleware/RateLimitMiddleware.php`)
- AbstractMiddleware (`app/Infrastructure/Routing/Middleware/AbstractMiddleware.php`)

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


## 🧪 測試覆蓋分析

- **有測試的類別**: 0 個
- **缺少測試的類別**: 105 個

### 缺少測試的重要類別
- **PostRepository**: `app/Domains/Post/Repositories/PostRepository.php`
- **PostService**: `app/Domains/Post/Services/PostService.php`
- **ContentModerationService**: `app/Domains/Post/Services/ContentModerationService.php`
- **RichTextProcessorService**: `app/Domains/Post/Services/RichTextProcessorService.php`
- **PostCacheKeyService**: `app/Domains/Post/Services/PostCacheKeyService.php`
- **IpRepository**: `app/Domains/Security/Repositories/IpRepository.php`
- **SecurityTestService**: `app/Domains/Security/Services/Advanced/SecurityTestService.php`
- **XssProtectionService**: `app/Domains/Security/Services/Core/XssProtectionService.php`


## 💉 依賴注入分析

### 依賴較多的類別 (≥3個依賴)
- **PostRepository** (3 個依賴)
  - `PDO` $db
  - `CacheServiceInterface` $cache
  - `LoggingSecurityServiceInterface` $logger

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

- **AttachmentService** (4 個依賴)
  - `AttachmentRepository` $attachmentRepo
  - `PostRepository` $postRepo
  - `CacheServiceInterface` $cache
  - `AuthorizationService` $authService

- **PostController** (3 個依賴)
  - `PostServiceInterface` $postService
  - `ValidatorInterface` $validator
  - `OutputSanitizerInterface` $sanitizer

- **IpController** (3 個依賴)
  - `IpService` $service
  - `ValidatorInterface` $validator
  - `OutputSanitizerInterface` $sanitizer

- **RouteDispatcher** (4 個依賴)
  - `RouterInterface` $router
  - `ControllerResolver` $controllerResolver
  - `MiddlewareDispatcher` $middlewareDispatcher
  - `ContainerInterface` $container


## ❓ 可能的問題引用

- ❓ 找不到類別/介面: Phinx\Migration\AbstractMigration (在 database/migrations/20250823051608_initial_schema.php 中使用)
- ❓ 找不到類別/介面: DI\ContainerBuilder (在 app/Application.php 中使用)
- ❓ 找不到類別/介面: App\Domains\Post\Enums\PostStatus (在 app/Domains/Post/Repositories/PostRepository.php 中使用)
- ❓ 找不到類別/介面: ($id) {
            $sql = $this->buildSelectQuery('id = ?') (在 app/Domains/Post/Repositories/PostRepository.php 中使用)
- ❓ 找不到類別/介面: ($uuid) {
            $sql = $this->buildSelectQuery('uuid = ?') (在 app/Domains/Post/Repositories/PostRepository.php 中使用)
- ❓ 找不到類別/介面: ($id) {
            $post = $this->findWithLock($id) (在 app/Domains/Post/Repositories/PostRepository.php 中使用)
- ❓ 找不到類別/介面: ($id, $isPinned) {
            $post = $this->findWithLock($id) (在 app/Domains/Post/Repositories/PostRepository.php 中使用)
- ❓ 找不到類別/介面: ($data, $tagIds) {
            // 資料已在 DTO 層級完成驗證，這裡直接處理

            // 準備資料
            $data = $this->prepareNewPostData($data) (在 app/Domains/Post/Repositories/PostRepository.php 中使用)
- ❓ 找不到類別/介面: ($page, $perPage, $conditions) {
            $offset = ($page - 1) * $perPage (在 app/Domains/Post/Repositories/PostRepository.php 中使用)
- ❓ 找不到類別/介面: ($limit) {
            $sql = $this->buildSelectQuery('is_pinned = 1')
                . ' ORDER BY publish_date DESC LIMIT :limit' (在 app/Domains/Post/Repositories/PostRepository.php 中使用)
- ... 還有 62 個
