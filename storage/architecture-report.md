# 專案架構分析報告（基於 Context7 MCP 最新技術）

**生成時間**: 2025-08-28 10:18:32

## 📊 程式碼品質指標

| 指標 | 數值 | 狀態 |
|------|------|------|
| 總類別數 | 161 | - |
| 介面與類別比例 | 22.98% | ✅ 良好 |
| 平均依賴數/類別 | 0.00 | ✅ 良好 |
| 現代 PHP 採用率 | 52.17% | ✅ 良好 |
| PSR-4 合規率 | 86.83% | ❌ 需修正 |
| DDD 結構完整性 | 80.00% | ✅ 良好 |

## 🎯 DDD 邊界上下文分析

### Attachment 上下文

| 組件類型 | 數量 | 項目 |
|----------|------|------|
| 實體 | 0 | - |
| 值物件 | 0 | - |
| 聚合 | 0 | - |
| 儲存庫 | 2 | AttachmentRepository, AttachmentRepositoryInterface |
| 領域服務 | 4 | FileSecurityService, AttachmentService, AttachmentServiceInterface... |
| 領域事件 | 0 | - |

### Auth 上下文

| 組件類型 | 數量 | 項目 |
|----------|------|------|
| 實體 | 0 | - |
| 值物件 | 16 | JwtPayload, TokenPair, TokenBlacklistEntry... |
| 聚合 | 0 | - |
| 儲存庫 | 4 | UserRepository, AuthServiceProvider, SimpleAuthServiceProvider... |
| 領域服務 | 8 | SessionSecurityService, PasswordManagementService, AuthorizationService... |
| 領域事件 | 0 | - |

### Post 上下文

| 組件類型 | 數量 | 項目 |
|----------|------|------|
| 實體 | 0 | - |
| 值物件 | 0 | - |
| 聚合 | 0 | - |
| 儲存庫 | 3 | PostRepository, PostService, PostRepositoryInterface |
| 領域服務 | 4 | PostCacheKeyService, ContentModerationService, RichTextProcessorService... |
| 領域事件 | 0 | - |

### Security 上下文

| 組件類型 | 數量 | 項目 |
|----------|------|------|
| 實體 | 0 | - |
| 值物件 | 0 | - |
| 聚合 | 0 | - |
| 儲存庫 | 3 | IpRepository, IpService, IpRepositoryInterface |
| 領域服務 | 12 | LoggingSecurityService, ErrorHandlerService, SecurityHeaderService... |
| 領域事件 | 0 | - |

### storage 上下文

| 組件類型 | 數量 | 項目 |
|----------|------|------|
| 實體 | 0 | - |
| 值物件 | 0 | - |
| 聚合 | 0 | - |
| 儲存庫 | 0 | - |
| 領域服務 | 0 | - |
| 領域事件 | 0 | - |

## 🚀 現代 PHP 特性使用情況

| 特性 | 使用次數 | 描述 |
|------|----------|------|
| Match 表達式 (PHP 8.0+) | 123 | ✅ 更安全的條件分支 |
| 唯讀屬性 (PHP 8.1+) | 82 | ✅ 提升資料不變性 |
| 屬性標籤 (PHP 8.0+) | 57 | ✅ 現代化 metadata |
| 空安全運算子 (PHP 8.0+) | 49 | ✅ 防止 null 指標異常 |
| 建構子屬性提升 (PHP 8.0+) | 21 | ✅ 減少樣板程式碼 |
| 聯合型別 (PHP 8.0+) | 9 | ✅ 更靈活的型別定義 |
| 列舉型別 (PHP 8.1+) | 1 | ✅ 型別安全的常數 |

## 📁 目錄結構

- `ssl-data`
- `ssl-data/..`
- `ssl-data/.`
- `..`
- `config`
- `config/..`
- `config/.`
- `config/routes`
- `config/routes/..`
- `config/routes/.`
- `app`
- `app/..`
- `app/Domains`
- `app/Domains/..`
- `app/Domains/Attachment`
- `app/Domains/Attachment/..`
- `app/Domains/Attachment/Repositories`
- `app/Domains/Attachment/Repositories/..`
- `app/Domains/Attachment/Repositories/.`
- `app/Domains/Attachment/Enums`
- `app/Domains/Attachment/Enums/..`
- `app/Domains/Attachment/Enums/.`
- `app/Domains/Attachment/.`
- `app/Domains/Attachment/Models`
- `app/Domains/Attachment/Models/..`
- `app/Domains/Attachment/Models/.`
- `app/Domains/Attachment/DTOs`
- `app/Domains/Attachment/DTOs/..`
- `app/Domains/Attachment/DTOs/.`
- `app/Domains/Attachment/Services`
- `app/Domains/Attachment/Services/..`
- `app/Domains/Attachment/Services/.`
- `app/Domains/Attachment/Contracts`
- `app/Domains/Attachment/Contracts/..`
- `app/Domains/Attachment/Contracts/.`
- `app/Domains/.`
- `app/Domains/Auth`
- `app/Domains/Auth/..`
- `app/Domains/Auth/ValueObjects`
- `app/Domains/Auth/ValueObjects/..`
- `app/Domains/Auth/ValueObjects/.`
- `app/Domains/Auth/Repositories`
- `app/Domains/Auth/Repositories/..`
- `app/Domains/Auth/Repositories/.`
- `app/Domains/Auth/.`
- `app/Domains/Auth/Exceptions`
- `app/Domains/Auth/Exceptions/..`
- `app/Domains/Auth/Exceptions/.`
- `app/Domains/Auth/Models`
- `app/Domains/Auth/Models/..`
- `app/Domains/Auth/Models/.`
- `app/Domains/Auth/DTOs`
- `app/Domains/Auth/DTOs/..`
- `app/Domains/Auth/DTOs/.`
- `app/Domains/Auth/Services`
- `app/Domains/Auth/Services/..`
- `app/Domains/Auth/Services/.`
- `app/Domains/Auth/Services/Advanced`
- `app/Domains/Auth/Services/Advanced/..`
- `app/Domains/Auth/Services/Advanced/.`
- `app/Domains/Auth/Providers`
- `app/Domains/Auth/Providers/..`
- `app/Domains/Auth/Providers/.`
- `app/Domains/Auth/Entities`
- `app/Domains/Auth/Entities/..`
- `app/Domains/Auth/Entities/.`
- `app/Domains/Auth/Contracts`
- `app/Domains/Auth/Contracts/..`
- `app/Domains/Auth/Contracts/.`
- `app/Domains/Security`
- `app/Domains/Security/..`
- `app/Domains/Security/Repositories`
- `app/Domains/Security/Repositories/..`
- `app/Domains/Security/Repositories/.`
- `app/Domains/Security/.`
- `app/Domains/Security/Models`
- `app/Domains/Security/Models/..`
- `app/Domains/Security/Models/.`
- `app/Domains/Security/DTOs`
- `app/Domains/Security/DTOs/..`
- `app/Domains/Security/DTOs/.`
- `app/Domains/Security/Services`
- `app/Domains/Security/Services/Logging`
- `app/Domains/Security/Services/Logging/..`
- `app/Domains/Security/Services/Logging/.`
- `app/Domains/Security/Services/..`
- `app/Domains/Security/Services/Error`
- `app/Domains/Security/Services/Error/..`
- `app/Domains/Security/Services/Error/.`
- `app/Domains/Security/Services/Headers`
- `app/Domains/Security/Services/Headers/..`
- `app/Domains/Security/Services/Headers/.`
- `app/Domains/Security/Services/.`
- `app/Domains/Security/Services/Content`
- `app/Domains/Security/Services/Content/..`
- `app/Domains/Security/Services/Content/.`
- `app/Domains/Security/Services/Core`
- `app/Domains/Security/Services/Core/..`
- `app/Domains/Security/Services/Core/.`
- `app/Domains/Security/Services/Advanced`
- `app/Domains/Security/Services/Advanced/..`
- `app/Domains/Security/Services/Advanced/.`
- `app/Domains/Security/Services/Secrets`
- `app/Domains/Security/Services/Secrets/..`
- `app/Domains/Security/Services/Secrets/.`
- `app/Domains/Security/Contracts`
- `app/Domains/Security/Contracts/..`
- `app/Domains/Security/Contracts/.`
- `app/Domains/Post`
- `app/Domains/Post/..`
- `app/Domains/Post/Repositories`
- `app/Domains/Post/Repositories/..`
- `app/Domains/Post/Repositories/.`
- `app/Domains/Post/Enums`
- `app/Domains/Post/Enums/..`
- `app/Domains/Post/Enums/.`
- `app/Domains/Post/.`
- `app/Domains/Post/Exceptions`
- `app/Domains/Post/Exceptions/..`
- `app/Domains/Post/Exceptions/.`
- `app/Domains/Post/Models`
- `app/Domains/Post/Models/..`
- `app/Domains/Post/Models/.`
- `app/Domains/Post/DTOs`
- `app/Domains/Post/DTOs/..`
- `app/Domains/Post/DTOs/.`
- `app/Domains/Post/Services`
- `app/Domains/Post/Services/..`
- `app/Domains/Post/Services/.`
- `app/Domains/Post/Validation`
- `app/Domains/Post/Validation/..`
- `app/Domains/Post/Validation/.`
- `app/Domains/Post/Contracts`
- `app/Domains/Post/Contracts/..`
- `app/Domains/Post/Contracts/.`
- `app/.`
- `app/Infrastructure`
- `app/Infrastructure/..`
- `app/Infrastructure/Cache`
- `app/Infrastructure/Cache/..`
- `app/Infrastructure/Cache/.`
- `app/Infrastructure/Http`
- `app/Infrastructure/Http/..`
- `app/Infrastructure/Http/.`
- `app/Infrastructure/Database`
- `app/Infrastructure/Database/..`
- `app/Infrastructure/Database/.`
- `app/Infrastructure/.`
- `app/Infrastructure/Auth`
- `app/Infrastructure/Auth/..`
- `app/Infrastructure/Auth/Jwt`
- `app/Infrastructure/Auth/Jwt/..`
- `app/Infrastructure/Auth/Jwt/.`
- `app/Infrastructure/Auth/Repositories`
- `app/Infrastructure/Auth/Repositories/..`
- `app/Infrastructure/Auth/Repositories/.`
- `app/Infrastructure/Auth/.`
- `app/Infrastructure/OpenApi`
- `app/Infrastructure/OpenApi/..`
- `app/Infrastructure/OpenApi/.`
- `app/Infrastructure/Services`
- `app/Infrastructure/Services/..`
- `app/Infrastructure/Services/.`
- `app/Infrastructure/Routing`
- `app/Infrastructure/Routing/..`
- `app/Infrastructure/Routing/Cache`
- `app/Infrastructure/Routing/Cache/..`
- `app/Infrastructure/Routing/Cache/.`
- `app/Infrastructure/Routing/.`
- `app/Infrastructure/Routing/Exceptions`
- `app/Infrastructure/Routing/Exceptions/..`
- `app/Infrastructure/Routing/Exceptions/.`
- `app/Infrastructure/Routing/Core`
- `app/Infrastructure/Routing/Core/..`
- `app/Infrastructure/Routing/Core/.`
- `app/Infrastructure/Routing/Providers`
- `app/Infrastructure/Routing/Providers/..`
- `app/Infrastructure/Routing/Providers/.`
- `app/Infrastructure/Routing/Middleware`
- `app/Infrastructure/Routing/Middleware/..`
- `app/Infrastructure/Routing/Middleware/.`
- `app/Infrastructure/Routing/Contracts`
- `app/Infrastructure/Routing/Contracts/..`
- `app/Infrastructure/Routing/Contracts/.`
- `app/Infrastructure/Config`
- `app/Infrastructure/Config/..`
- `app/Infrastructure/Config/.`
- `app/Shared`
- `app/Shared/..`
- `app/Shared/Http`
- `app/Shared/Http/..`
- `app/Shared/Http/.`
- `app/Shared/.`
- `app/Shared/Exceptions`
- `app/Shared/Exceptions/..`
- `app/Shared/Exceptions/.`
- `app/Shared/Exceptions/Validation`
- `app/Shared/Exceptions/Validation/..`
- `app/Shared/Exceptions/Validation/.`
- `app/Shared/OpenApi`
- `app/Shared/OpenApi/..`
- `app/Shared/OpenApi/.`
- `app/Shared/DTOs`
- `app/Shared/DTOs/..`
- `app/Shared/DTOs/.`
- `app/Shared/Helpers`
- `app/Shared/Helpers/..`
- `app/Shared/Helpers/.`
- `app/Shared/Schemas`
- `app/Shared/Schemas/..`
- `app/Shared/Schemas/.`
- `app/Shared/Validation`
- `app/Shared/Validation/..`
- `app/Shared/Validation/.`
- `app/Shared/Validation/Factory`
- `app/Shared/Validation/Factory/..`
- `app/Shared/Validation/Factory/.`
- `app/Shared/Config`
- `app/Shared/Config/..`
- `app/Shared/Config/.`
- `app/Shared/Contracts`
- `app/Shared/Contracts/..`
- `app/Shared/Contracts/.`
- `app/Application`
- `app/Application/..`
- `app/Application/.`
- `app/Application/Controllers`
- `app/Application/Controllers/..`
- `app/Application/Controllers/Api`
- `app/Application/Controllers/Api/..`
- `app/Application/Controllers/Api/.`
- `app/Application/Controllers/Api/V1`
- `app/Application/Controllers/Api/V1/..`
- `app/Application/Controllers/Api/V1/.`
- `app/Application/Controllers/Health`
- `app/Application/Controllers/Health/..`
- `app/Application/Controllers/Health/.`
- `app/Application/Controllers/.`
- `app/Application/Controllers/Web`
- `app/Application/Controllers/Web/..`
- `app/Application/Controllers/Web/.`
- `app/Application/Controllers/Security`
- `app/Application/Controllers/Security/..`
- `app/Application/Controllers/Security/.`
- `app/Application/Middleware`
- `app/Application/Middleware/..`
- `app/Application/Middleware/.`
- `.`
- `database`
- `database/..`
- `database/migrations`
- `database/migrations/..`
- `database/migrations/.`
- `database/.`
- `database/seeds`
- `database/seeds/..`
- `database/seeds/.`
- `docs`
- `docs/..`
- `docs/.`
- `.github`
- `.github/..`
- `.github/workflows`
- `.github/workflows/..`
- `.github/workflows/.`
- `.github/.`
- `.github/chatmodes`
- `.github/chatmodes/..`
- `.github/chatmodes/.`
- `certbot-data`
- `certbot-data/..`
- `certbot-data/.`
- `scripts`
- `scripts/..`
- `scripts/lib`
- `scripts/lib/..`
- `scripts/lib/.`
- `scripts/.`
- `scripts/consolidated`
- `scripts/consolidated/..`
- `scripts/consolidated/.`
- `examples`
- `examples/..`
- `examples/.`
- `.vscode`
- `.vscode/..`
- `.vscode/.`

## 🏷️ 命名空間分析

### `App\Domains\Attachment\Repositories`
- app/Domains/Attachment/Repositories/AttachmentRepository.php

### `App\Domains\Attachment\Enums`
- app/Domains/Attachment/Enums/FileRules.php

### `App\Domains\Attachment\Models`
- app/Domains/Attachment/Models/Attachment.php

### `App\Domains\Attachment\DTOs`
- app/Domains/Attachment/DTOs/CreateAttachmentDTO.php

### `App\Domains\Attachment\Services`
- app/Domains/Attachment/Services/FileSecurityService.php
- app/Domains/Attachment/Services/AttachmentService.php

### `App\Domains\Attachment\Contracts`
- app/Domains/Attachment/Contracts/AttachmentServiceInterface.php
- app/Domains/Attachment/Contracts/AttachmentRepositoryInterface.php
- app/Domains/Attachment/Contracts/FileSecurityServiceInterface.php

### `AlleyNote\Domains\Auth\ValueObjects`
- app/Domains/Auth/ValueObjects/JwtPayload.php
- app/Domains/Auth/ValueObjects/TokenPair.php
- app/Domains/Auth/ValueObjects/TokenBlacklistEntry.php
- app/Domains/Auth/ValueObjects/DeviceInfo.php

### `App\Domains\Auth\Repositories`
- app/Domains/Auth/Repositories/UserRepository.php

### `AlleyNote\Domains\Auth\Exceptions`
- app/Domains/Auth/Exceptions/JwtConfigurationException.php
- app/Domains/Auth/Exceptions/TokenGenerationException.php
- app/Domains/Auth/Exceptions/RefreshTokenException.php
- app/Domains/Auth/Exceptions/InvalidTokenException.php
- app/Domains/Auth/Exceptions/JwtException.php
- app/Domains/Auth/Exceptions/TokenParsingException.php
- app/Domains/Auth/Exceptions/TokenValidationException.php
- app/Domains/Auth/Exceptions/TokenExpiredException.php
- app/Domains/Auth/Exceptions/AuthenticationException.php

### `App\Domains\Auth\Exceptions`
- app/Domains/Auth/Exceptions/ForbiddenException.php
- app/Domains/Auth/Exceptions/UnauthorizedException.php

### `App\Domains\Auth\Models`
- app/Domains/Auth/Models/Role.php
- app/Domains/Auth/Models/Permission.php

### `App\Domains\Auth\DTOs`
- app/Domains/Auth/DTOs/RegisterUserDTO.php

### `AlleyNote\Domains\Auth\DTOs`
- app/Domains/Auth/DTOs/LogoutRequestDTO.php
- app/Domains/Auth/DTOs/LoginResponseDTO.php
- app/Domains/Auth/DTOs/LoginRequestDTO.php
- app/Domains/Auth/DTOs/RefreshRequestDTO.php
- app/Domains/Auth/DTOs/RefreshResponseDTO.php

### `App\Domains\Auth\Services`
- app/Domains/Auth/Services/SessionSecurityService.php
- app/Domains/Auth/Services/PasswordManagementService.php
- app/Domains/Auth/Services/AuthorizationService.php
- app/Domains/Auth/Services/AuthService.php
- app/Domains/Auth/Services/PasswordSecurityService.php

### `AlleyNote\Domains\Auth\Services`
- app/Domains/Auth/Services/RefreshTokenService.php
- app/Domains/Auth/Services/JwtTokenService.php
- app/Domains/Auth/Services/AuthenticationService.php
- app/Domains/Auth/Services/TokenBlacklistService.php

### `App\Domains\Auth\Services\Advanced`
- app/Domains/Auth/Services/Advanced/PwnedPasswordService.php

### `App\Domains\Auth\Providers`
- app/Domains/Auth/Providers/AuthServiceProvider.php
- app/Domains/Auth/Providers/SimpleAuthServiceProvider.php

### `AlleyNote\Domains\Auth\Entities`
- app/Domains/Auth/Entities/RefreshToken.php

### `App\Domains\Auth\Contracts`
- app/Domains/Auth/Contracts/PasswordSecurityServiceInterface.php
- app/Domains/Auth/Contracts/AuthorizationServiceInterface.php
- app/Domains/Auth/Contracts/SessionSecurityServiceInterface.php
- app/Domains/Auth/Contracts/UserRepositoryInterface.php

### `AlleyNote\Domains\Auth\Contracts`
- app/Domains/Auth/Contracts/RefreshTokenRepositoryInterface.php
- app/Domains/Auth/Contracts/JwtTokenServiceInterface.php
- app/Domains/Auth/Contracts/TokenBlacklistRepositoryInterface.php
- app/Domains/Auth/Contracts/JwtProviderInterface.php
- app/Domains/Auth/Contracts/AuthenticationServiceInterface.php

### `App\Domains\Security\Repositories`
- app/Domains/Security/Repositories/IpRepository.php

### `App\Domains\Security\Models`
- app/Domains/Security/Models/IpList.php

### `App\Domains\Security\DTOs`
- app/Domains/Security/DTOs/CreateIpRuleDTO.php

### `App\Domains\Security\Services\Logging`
- app/Domains/Security/Services/Logging/LoggingSecurityService.php

### `App\Domains\Security\Services\Error`
- app/Domains/Security/Services/Error/ErrorHandlerService.php

### `App\Domains\Security\Services\Headers`
- app/Domains/Security/Services/Headers/SecurityHeaderService.php

### `App\Domains\Security\Services\Content`
- app/Domains/Security/Services/Content/XssProtectionExtensionService.php

### `App\Domains\Security\Services\Core`
- app/Domains/Security/Services/Core/XssProtectionService.php
- app/Domains/Security/Services/Core/CsrfProtectionService.php

### `App\Domains\Security\Services\Advanced`
- app/Domains/Security/Services/Advanced/SecurityTestService.php

### `App\Domains\Security\Services`
- app/Domains/Security/Services/IpService.php

### `App\Domains\Security\Services\Secrets`
- app/Domains/Security/Services/Secrets/SecretsManager.php

### `App\Domains\Security\Contracts`
- app/Domains/Security/Contracts/SecretsManagerInterface.php
- app/Domains/Security/Contracts/SecurityHeaderServiceInterface.php
- app/Domains/Security/Contracts/LoggingSecurityServiceInterface.php
- app/Domains/Security/Contracts/XssProtectionServiceInterface.php
- app/Domains/Security/Contracts/IpRepositoryInterface.php
- app/Domains/Security/Contracts/ErrorHandlerServiceInterface.php
- app/Domains/Security/Contracts/SecurityTestInterface.php
- app/Domains/Security/Contracts/CsrfProtectionServiceInterface.php

### `App\Domains\Post\Repositories`
- app/Domains/Post/Repositories/PostRepository.php

### `App\Domains\Post\Enums`
- app/Domains/Post/Enums/PostStatus.php

### `App\Domains\Post\Exceptions`
- app/Domains/Post/Exceptions/PostNotFoundException.php
- app/Domains/Post/Exceptions/PostValidationException.php
- app/Domains/Post/Exceptions/PostStatusException.php

### `App\Domains\Post\Models`
- app/Domains/Post/Models/Post.php

### `App\Domains\Post\DTOs`
- app/Domains/Post/DTOs/CreatePostDTO.php
- app/Domains/Post/DTOs/UpdatePostDTO.php

### `App\Domains\Post\Services`
- app/Domains/Post/Services/PostCacheKeyService.php
- app/Domains/Post/Services/PostService.php
- app/Domains/Post/Services/ContentModerationService.php
- app/Domains/Post/Services/RichTextProcessorService.php

### `App\Domains\Post\Validation`
- app/Domains/Post/Validation/PostValidator.php

### `App\Domains\Post\Contracts`
- app/Domains/Post/Contracts/PostServiceInterface.php
- app/Domains/Post/Contracts/PostRepositoryInterface.php

### `App`
- app/Application.php

### `App\Infrastructure\Cache`
- app/Infrastructure/Cache/CacheManager.php
- app/Infrastructure/Cache/CacheKeys.php

### `App\Infrastructure\Http`
- app/Infrastructure/Http/Stream.php
- app/Infrastructure/Http/ServerRequestFactory.php
- app/Infrastructure/Http/ServerRequest.php
- app/Infrastructure/Http/Uri.php
- app/Infrastructure/Http/Response.php

### `App\Infrastructure\Database`
- app/Infrastructure/Database/DatabaseConnection.php

### `App\Infrastructure\Auth\Jwt`
- app/Infrastructure/Auth/Jwt/FirebaseJwtProvider.php

### `AlleyNote\Infrastructure\Auth\Repositories`
- app/Infrastructure/Auth/Repositories/TokenBlacklistRepository.php
- app/Infrastructure/Auth/Repositories/RefreshTokenRepository.php

### `App\Infrastructure\OpenApi`
- app/Infrastructure/OpenApi/OpenApiSpec.php

### `App\Infrastructure\Services`
- app/Infrastructure/Services/RateLimitService.php
- app/Infrastructure/Services/OutputSanitizer.php
- app/Infrastructure/Services/CacheService.php

### `App\Infrastructure\Routing\Cache`
- app/Infrastructure/Routing/Cache/MemoryRouteCache.php
- app/Infrastructure/Routing/Cache/RouteCacheFactory.php
- app/Infrastructure/Routing/Cache/FileRouteCache.php
- app/Infrastructure/Routing/Cache/RedisRouteCache.php

### `App\Infrastructure\Routing`
- app/Infrastructure/Routing/ControllerResolver.php
- app/Infrastructure/Routing/ClosureRequestHandler.php
- app/Infrastructure/Routing/RouteValidator.php
- app/Infrastructure/Routing/RouteLoader.php
- app/Infrastructure/Routing/RouteDispatcher.php

### `App\Infrastructure\Routing\Exceptions`
- app/Infrastructure/Routing/Exceptions/RouteConfigurationException.php

### `App\Infrastructure\Routing\Core`
- app/Infrastructure/Routing/Core/RouteCollection.php
- app/Infrastructure/Routing/Core/Route.php
- app/Infrastructure/Routing/Core/Router.php

### `App\Infrastructure\Routing\Providers`
- app/Infrastructure/Routing/Providers/RoutingServiceProvider.php

### `App\Infrastructure\Routing\Middleware`
- app/Infrastructure/Routing/Middleware/RouteParametersMiddleware.php
- app/Infrastructure/Routing/Middleware/MiddlewareDispatcher.php
- app/Infrastructure/Routing/Middleware/MiddlewareResolver.php
- app/Infrastructure/Routing/Middleware/RouteInfoMiddleware.php
- app/Infrastructure/Routing/Middleware/AbstractMiddleware.php
- app/Infrastructure/Routing/Middleware/MiddlewareManager.php

### `App\Infrastructure\Routing\Contracts`
- app/Infrastructure/Routing/Contracts/RequestHandlerInterface.php
- app/Infrastructure/Routing/Contracts/MiddlewareManagerInterface.php
- app/Infrastructure/Routing/Contracts/RouterInterface.php
- app/Infrastructure/Routing/Contracts/RouteCollectionInterface.php
- app/Infrastructure/Routing/Contracts/RouteCacheInterface.php
- app/Infrastructure/Routing/Contracts/MiddlewareInterface.php
- app/Infrastructure/Routing/Contracts/RouteInterface.php
- app/Infrastructure/Routing/Contracts/RouteMatchResult.php
- app/Infrastructure/Routing/Contracts/MiddlewareDispatcherInterface.php

### `App\Infrastructure\Config`
- app/Infrastructure/Config/ContainerFactory.php

### `App\Shared\Http`
- app/Shared/Http/ApiResponse.php

### `App\Shared\Exceptions`
- app/Shared/Exceptions/ValidationException.php
- app/Shared/Exceptions/NotFoundException.php
- app/Shared/Exceptions/CsrfTokenException.php
- app/Shared/Exceptions/StateTransitionException.php

### `App\Shared\Exceptions\Validation`
- app/Shared/Exceptions/Validation/RequestValidationException.php

### `App\Shared\DTOs`
- app/Shared/DTOs/BaseDTO.php

### `App\Shared\Schemas`
- app/Shared/Schemas/PostRequestSchema.php
- app/Shared/Schemas/PostSchema.php
- app/Shared/Schemas/AuthSchema.php

### `App\Shared\Validation`
- app/Shared/Validation/Validator.php
- app/Shared/Validation/ValidationResult.php

### `App\Shared\Validation\Factory`
- app/Shared/Validation/Factory/ValidatorFactory.php

### `App\Shared\Config`
- app/Shared/Config/JwtConfig.php

### `App\Shared\Contracts`
- app/Shared/Contracts/CacheServiceInterface.php
- app/Shared/Contracts/RepositoryInterface.php
- app/Shared/Contracts/ValidatorInterface.php
- app/Shared/Contracts/OutputSanitizerInterface.php

### `App\Application\Controllers\Api\V1`
- app/Application/Controllers/Api/V1/AuthController.php
- app/Application/Controllers/Api/V1/IpController.php
- app/Application/Controllers/Api/V1/PostController.php
- app/Application/Controllers/Api/V1/AttachmentController.php

### `App\Application\Controllers\Health`
- app/Application/Controllers/Health/HealthController.php

### `App\Application\Controllers`
- app/Application/Controllers/TestController.php
- app/Application/Controllers/PostController.php
- app/Application/Controllers/BaseController.php

### `App\Application\Controllers\Web`
- app/Application/Controllers/Web/SwaggerController.php

### `App\Application\Controllers\Security`
- app/Application/Controllers/Security/CSPReportController.php

### `App\Application\Middleware`
- app/Application/Middleware/AuthorizationMiddleware.php
- app/Application/Middleware/RateLimitMiddleware.php
- app/Application/Middleware/JwtAuthenticationMiddleware.php
- app/Application/Middleware/AuthorizationResult.php
- app/Application/Middleware/JwtAuthorizationMiddleware.php

### `= trim($matches[1])`
- scripts/scan-project-architecture.php

### `AlleyNote\Scripts\Consolidated`
- scripts/consolidated/DefaultScriptConfiguration.php
- scripts/consolidated/ConsolidatedDeployer.php
- scripts/consolidated/ConsolidatedErrorFixer.php
- scripts/consolidated/ConsolidatedTestManager.php
- scripts/consolidated/DefaultScriptExecutor.php
- scripts/consolidated/DefaultScriptAnalyzer.php
- scripts/consolidated/ScriptManager.php
- scripts/consolidated/ConsolidatedMaintainer.php
- scripts/consolidated/ConsolidatedAnalyzer.php


## 🏗️ DDD 架構分析

### Application 層
**子目錄**: .., Controllers, Controllers/.., Controllers/Api, Controllers/Api/.., Controllers/Api/., Controllers/Api/V1, Controllers/Api/V1/.., Controllers/Api/V1/., Controllers/Health, Controllers/Health/.., Controllers/Health/., Controllers/., Controllers/Web, Controllers/Web/.., Controllers/Web/., Controllers/Security, Controllers/Security/.., Controllers/Security/., Middleware, Middleware/.., Middleware/.
**檔案數量**: 15

### Domains 層
**子目錄**: .., Attachment, Attachment/.., Attachment/Repositories, Attachment/Repositories/.., Attachment/Repositories/., Attachment/Enums, Attachment/Enums/.., Attachment/Enums/., Attachment/., Attachment/Models, Attachment/Models/.., Attachment/Models/., Attachment/DTOs, Attachment/DTOs/.., Attachment/DTOs/., Attachment/Services, Attachment/Services/.., Attachment/Services/., Attachment/Contracts, Attachment/Contracts/.., Attachment/Contracts/., storage, storage/.., storage/., storage/cache, storage/cache/.., storage/cache/htmlpurifier, storage/cache/htmlpurifier/.., storage/cache/htmlpurifier/., storage/cache/., Auth, Auth/.., Auth/ValueObjects, Auth/ValueObjects/.., Auth/ValueObjects/., Auth/Repositories, Auth/Repositories/.., Auth/Repositories/., Auth/., Auth/Exceptions, Auth/Exceptions/.., Auth/Exceptions/., Auth/Models, Auth/Models/.., Auth/Models/., Auth/DTOs, Auth/DTOs/.., Auth/DTOs/., Auth/Services, Auth/Services/.., Auth/Services/., Auth/Services/Advanced, Auth/Services/Advanced/.., Auth/Services/Advanced/., Auth/Providers, Auth/Providers/.., Auth/Providers/., Auth/Entities, Auth/Entities/.., Auth/Entities/., Auth/Contracts, Auth/Contracts/.., Auth/Contracts/., Security, Security/.., Security/Repositories, Security/Repositories/.., Security/Repositories/., Security/., Security/Models, Security/Models/.., Security/Models/., Security/DTOs, Security/DTOs/.., Security/DTOs/., Security/Services, Security/Services/Logging, Security/Services/Logging/.., Security/Services/Logging/., Security/Services/.., Security/Services/Error, Security/Services/Error/.., Security/Services/Error/., Security/Services/Headers, Security/Services/Headers/.., Security/Services/Headers/., Security/Services/., Security/Services/Content, Security/Services/Content/.., Security/Services/Content/., Security/Services/Core, Security/Services/Core/.., Security/Services/Core/., Security/Services/Advanced, Security/Services/Advanced/.., Security/Services/Advanced/., Security/Services/Secrets, Security/Services/Secrets/.., Security/Services/Secrets/., Security/Contracts, Security/Contracts/.., Security/Contracts/., Post, Post/.., Post/Repositories, Post/Repositories/.., Post/Repositories/., Post/Enums, Post/Enums/.., Post/Enums/., Post/., Post/Exceptions, Post/Exceptions/.., Post/Exceptions/., Post/Models, Post/Models/.., Post/Models/., Post/DTOs, Post/DTOs/.., Post/DTOs/., Post/Services, Post/Services/.., Post/Services/., Post/Validation, Post/Validation/.., Post/Validation/., Post/Contracts, Post/Contracts/.., Post/Contracts/.
**檔案數量**: 90

### Infrastructure 層
**子目錄**: .., Cache, Cache/.., Cache/., Http, Http/.., Http/., Database, Database/.., Database/., Auth, Auth/.., Auth/Jwt, Auth/Jwt/.., Auth/Jwt/., Auth/Repositories, Auth/Repositories/.., Auth/Repositories/., Auth/., OpenApi, OpenApi/.., OpenApi/., Services, Services/.., Services/., Routing, Routing/.., Routing/Cache, Routing/Cache/.., Routing/Cache/., Routing/., Routing/Exceptions, Routing/Exceptions/.., Routing/Exceptions/., Routing/Core, Routing/Core/.., Routing/Core/., Routing/Providers, Routing/Providers/.., Routing/Providers/., Routing/Middleware, Routing/Middleware/.., Routing/Middleware/., Routing/Contracts, Routing/Contracts/.., Routing/Contracts/., Config, Config/.., Config/.
**檔案數量**: 46

### Shared 層
**子目錄**: .., Http, Http/.., Http/., Exceptions, Exceptions/.., Exceptions/., Exceptions/Validation, Exceptions/Validation/.., Exceptions/Validation/., OpenApi, OpenApi/.., OpenApi/., DTOs, DTOs/.., DTOs/., Helpers, Helpers/.., Helpers/., Schemas, Schemas/.., Schemas/., Validation, Validation/.., Validation/., Validation/Factory, Validation/Factory/.., Validation/Factory/., Config, Config/.., Config/., Contracts, Contracts/.., Contracts/.
**檔案數量**: 20


## 📊 類別統計

- **類別總數**: 161
- **介面總數**: 37
- **Trait 總數**: 0

## ⚠️ 發現的架構問題

- ❌ Domain層不應依賴Infrastructure層: app/Domains/Auth/Providers/AuthServiceProvider.php -> App\Infrastructure\Auth\Jwt\FirebaseJwtProvider
- ❌ Domain層不應依賴Infrastructure層: app/Domains/Auth/Providers/SimpleAuthServiceProvider.php -> App\Infrastructure\Auth\Jwt\FirebaseJwtProvider
- ⚠️  可能的循環依賴: app/Application/Controllers/Api/V1/AuthController.php -> App\Application\Controllers\BaseController
- ⚠️  可能的循環依賴: app/Application/Controllers/Api/V1/PostController.php -> App\Application\Controllers\BaseController
- ⚠️  可能的循環依賴: app/Application/Controllers/Health/HealthController.php -> App\Application\Controllers\BaseController

## 🔑 重要類別清單

- **AttachmentRepository**: `app/Domains/Attachment/Repositories/AttachmentRepository.php`
  - 實作: 
- **FileSecurityService**: `app/Domains/Attachment/Services/FileSecurityService.php`
  - 實作: FileSecurityServiceInterface
- **AttachmentService**: `app/Domains/Attachment/Services/AttachmentService.php`
  - 實作: AttachmentServiceInterface
- **UserRepository**: `app/Domains/Auth/Repositories/UserRepository.php`
  - 實作: 
- **SessionSecurityService**: `app/Domains/Auth/Services/SessionSecurityService.php`
  - 實作: SessionSecurityServiceInterface
- **RefreshTokenService**: `app/Domains/Auth/Services/RefreshTokenService.php`
  - 實作: 
- **PasswordManagementService**: `app/Domains/Auth/Services/PasswordManagementService.php`
  - 實作: 
- **AuthorizationService**: `app/Domains/Auth/Services/AuthorizationService.php`
  - 實作: AuthorizationServiceInterface
- **JwtTokenService**: `app/Domains/Auth/Services/JwtTokenService.php`
  - 實作: JwtTokenServiceInterface
- **PwnedPasswordService**: `app/Domains/Auth/Services/Advanced/PwnedPasswordService.php`
  - 實作: 
- **AuthenticationService**: `app/Domains/Auth/Services/AuthenticationService.php`
  - 實作: AuthenticationServiceInterface
- **TokenBlacklistService**: `app/Domains/Auth/Services/TokenBlacklistService.php`
  - 實作: 
- **AuthService**: `app/Domains/Auth/Services/AuthService.php`
  - 實作: 
- **PasswordSecurityService**: `app/Domains/Auth/Services/PasswordSecurityService.php`
  - 實作: PasswordSecurityServiceInterface
- **AuthServiceProvider**: `app/Domains/Auth/Providers/AuthServiceProvider.php`
  - 實作: 
- **SimpleAuthServiceProvider**: `app/Domains/Auth/Providers/SimpleAuthServiceProvider.php`
  - 實作: 
- **IpRepository**: `app/Domains/Security/Repositories/IpRepository.php`
  - 實作: IpRepositoryInterface
- **LoggingSecurityService**: `app/Domains/Security/Services/Logging/LoggingSecurityService.php`
  - 實作: LoggingSecurityServiceInterface
- **ErrorHandlerService**: `app/Domains/Security/Services/Error/ErrorHandlerService.php`
  - 實作: ErrorHandlerServiceInterface
- **SecurityHeaderService**: `app/Domains/Security/Services/Headers/SecurityHeaderService.php`
  - 實作: SecurityHeaderServiceInterface
- **XssProtectionExtensionService**: `app/Domains/Security/Services/Content/XssProtectionExtensionService.php`
  - 實作: 
- **XssProtectionService**: `app/Domains/Security/Services/Core/XssProtectionService.php`
  - 實作: 
- **CsrfProtectionService**: `app/Domains/Security/Services/Core/CsrfProtectionService.php`
  - 實作: 
- **SecurityTestService**: `app/Domains/Security/Services/Advanced/SecurityTestService.php`
  - 實作: SecurityTestInterface
- **IpService**: `app/Domains/Security/Services/IpService.php`
  - 實作: 
- **SecretsManager**: `app/Domains/Security/Services/Secrets/SecretsManager.php`
  - 實作: SecretsManagerInterface
- **PostRepository**: `app/Domains/Post/Repositories/PostRepository.php`
  - 實作: PostRepositoryInterface
- **PostCacheKeyService**: `app/Domains/Post/Services/PostCacheKeyService.php`
  - 實作: 
- **PostService**: `app/Domains/Post/Services/PostService.php`
  - 實作: PostServiceInterface
- **ContentModerationService**: `app/Domains/Post/Services/ContentModerationService.php`
  - 實作: 
- **RichTextProcessorService**: `app/Domains/Post/Services/RichTextProcessorService.php`
  - 實作: 
- **implements**: `app/Infrastructure/Routing/ControllerResolver.php`
  - 實作: 
- **TokenBlacklistRepository**: `app/Infrastructure/Auth/Repositories/TokenBlacklistRepository.php`
  - 實作: TokenBlacklistRepositoryInterface
- **RefreshTokenRepository**: `app/Infrastructure/Auth/Repositories/RefreshTokenRepository.php`
  - 實作: RefreshTokenRepositoryInterface
- **RateLimitService**: `app/Infrastructure/Services/RateLimitService.php`
  - 實作: 
- **OutputSanitizer**: `app/Infrastructure/Services/OutputSanitizer.php`
  - 實作: 
- **OutputSanitizerService**: `app/Infrastructure/Services/OutputSanitizer.php`
  - 實作: OutputSanitizerInterface
- **CacheService**: `app/Infrastructure/Services/CacheService.php`
  - 實作: CacheServiceInterface
- **ControllerResolver**: `app/Infrastructure/Routing/ControllerResolver.php`
  - 實作: 
- **RoutingServiceProvider**: `app/Infrastructure/Routing/Providers/RoutingServiceProvider.php`
  - 實作: 
- **AuthController**: `app/Application/Controllers/Api/V1/AuthController.php`
  - 繼承: BaseController
  - 實作: 
- **IpController**: `app/Application/Controllers/Api/V1/IpController.php`
  - 實作: 
- **PostController**: `app/Application/Controllers/PostController.php`
  - 繼承: BaseController
  - 實作: 
- **AttachmentController**: `app/Application/Controllers/Api/V1/AttachmentController.php`
  - 實作: 
- **HealthController**: `app/Application/Controllers/TestController.php`
  - 實作: 
- **SwaggerController**: `app/Application/Controllers/Web/SwaggerController.php`
  - 實作: 
- **CSPReportController**: `app/Application/Controllers/Security/CSPReportController.php`
  - 實作: 
- **BaseController**: `app/Application/Controllers/BaseController.php`
  - 實作: 

## 🔌 介面實作分析

### ``
- AttachmentRepository (`app/Domains/Attachment/Repositories/AttachmentRepository.php`)
- FileRules (`app/Domains/Attachment/Enums/FileRules.php`)
- Attachment (`app/Domains/Attachment/Models/Attachment.php`)
- CreateAttachmentDTO (`app/Domains/Attachment/DTOs/CreateAttachmentDTO.php`)
- UserRepository (`app/Domains/Auth/Repositories/UserRepository.php`)
- JwtConfigurationException (`app/Domains/Auth/Exceptions/JwtConfigurationException.php`)
- TokenGenerationException (`app/Domains/Auth/Exceptions/TokenGenerationException.php`)
- ForbiddenException (`app/Domains/Auth/Exceptions/ForbiddenException.php`)
- RefreshTokenException (`app/Domains/Auth/Exceptions/RefreshTokenException.php`)
- InvalidTokenException (`app/Domains/Auth/Exceptions/InvalidTokenException.php`)
- UnauthorizedException (`app/Domains/Auth/Exceptions/UnauthorizedException.php`)
- JwtException (`app/Domains/Auth/Exceptions/JwtException.php`)
- TokenParsingException (`app/Domains/Auth/Exceptions/TokenParsingException.php`)
- TokenValidationException (`app/Domains/Auth/Exceptions/TokenValidationException.php`)
- TokenExpiredException (`app/Domains/Auth/Exceptions/TokenExpiredException.php`)
- AuthenticationException (`app/Domains/Auth/Exceptions/AuthenticationException.php`)
- Role (`app/Domains/Auth/Models/Role.php`)
- Permission (`app/Domains/Auth/Models/Permission.php`)
- RegisterUserDTO (`app/Domains/Auth/DTOs/RegisterUserDTO.php`)
- LogoutRequestDTO (`app/Domains/Auth/DTOs/LogoutRequestDTO.php`)
- LoginResponseDTO (`app/Domains/Auth/DTOs/LoginResponseDTO.php`)
- LoginRequestDTO (`app/Domains/Auth/DTOs/LoginRequestDTO.php`)
- RefreshRequestDTO (`app/Domains/Auth/DTOs/RefreshRequestDTO.php`)
- RefreshResponseDTO (`app/Domains/Auth/DTOs/RefreshResponseDTO.php`)
- RefreshTokenService (`app/Domains/Auth/Services/RefreshTokenService.php`)
- PasswordManagementService (`app/Domains/Auth/Services/PasswordManagementService.php`)
- PwnedPasswordService (`app/Domains/Auth/Services/Advanced/PwnedPasswordService.php`)
- TokenBlacklistService (`app/Domains/Auth/Services/TokenBlacklistService.php`)
- AuthService (`app/Domains/Auth/Services/AuthService.php`)
- AuthServiceProvider (`app/Domains/Auth/Providers/AuthServiceProvider.php`)
- SimpleAuthServiceProvider (`app/Domains/Auth/Providers/SimpleAuthServiceProvider.php`)
- CreateIpRuleDTO (`app/Domains/Security/DTOs/CreateIpRuleDTO.php`)
- XssProtectionExtensionService (`app/Domains/Security/Services/Content/XssProtectionExtensionService.php`)
- XssProtectionService (`app/Domains/Security/Services/Core/XssProtectionService.php`)
- CsrfProtectionService (`app/Domains/Security/Services/Core/CsrfProtectionService.php`)
- IpService (`app/Domains/Security/Services/IpService.php`)
- PostNotFoundException (`app/Domains/Post/Exceptions/PostNotFoundException.php`)
- PostValidationException (`app/Domains/Post/Exceptions/PostValidationException.php`)
- PostStatusException (`app/Domains/Post/Exceptions/PostStatusException.php`)
- CreatePostDTO (`app/Domains/Post/DTOs/CreatePostDTO.php`)
- UpdatePostDTO (`app/Domains/Post/DTOs/UpdatePostDTO.php`)
- PostCacheKeyService (`app/Domains/Post/Services/PostCacheKeyService.php`)
- ContentModerationService (`app/Domains/Post/Services/ContentModerationService.php`)
- RichTextProcessorService (`app/Domains/Post/Services/RichTextProcessorService.php`)
- PostValidator (`app/Domains/Post/Validation/PostValidator.php`)
- Application (`app/Application.php`)
- implements (`app/Infrastructure/Routing/ControllerResolver.php`)
- CacheManager (`app/Infrastructure/Cache/CacheManager.php`)
- CacheKeys (`app/Infrastructure/Cache/CacheKeys.php`)
- ServerRequestFactory (`app/Infrastructure/Http/ServerRequestFactory.php`)
- DatabaseConnection (`app/Infrastructure/Database/DatabaseConnection.php`)
- OpenApiSpec (`app/Infrastructure/OpenApi/OpenApiSpec.php`)
- RateLimitService (`app/Infrastructure/Services/RateLimitService.php`)
- OutputSanitizer (`app/Infrastructure/Services/OutputSanitizer.php`)
- RouteCacheFactory (`app/Infrastructure/Routing/Cache/RouteCacheFactory.php`)
- ControllerResolver (`app/Infrastructure/Routing/ControllerResolver.php`)
- RouteConfigurationException (`app/Infrastructure/Routing/Exceptions/RouteConfigurationException.php`)
- RouteValidator (`app/Infrastructure/Routing/RouteValidator.php`)
- RoutingServiceProvider (`app/Infrastructure/Routing/Providers/RoutingServiceProvider.php`)
- RouteLoader (`app/Infrastructure/Routing/RouteLoader.php`)
- RouteDispatcher (`app/Infrastructure/Routing/RouteDispatcher.php`)
- RouteParametersMiddleware (`app/Infrastructure/Routing/Middleware/RouteParametersMiddleware.php`)
- MiddlewareResolver (`app/Infrastructure/Routing/Middleware/MiddlewareResolver.php`)
- RouteInfoMiddleware (`app/Infrastructure/Routing/Middleware/RouteInfoMiddleware.php`)
- RouteMatchResult (`app/Infrastructure/Routing/Contracts/RouteMatchResult.php`)
- ContainerFactory (`app/Infrastructure/Config/ContainerFactory.php`)
- ApiResponse (`app/Shared/Http/ApiResponse.php`)
- ValidationException (`app/Shared/Exceptions/ValidationException.php`)
- NotFoundException (`app/Shared/Exceptions/NotFoundException.php`)
- CsrfTokenException (`app/Shared/Exceptions/CsrfTokenException.php`)
- StateTransitionException (`app/Shared/Exceptions/StateTransitionException.php`)
- RequestValidationException (`app/Shared/Exceptions/Validation/RequestValidationException.php`)
- OpenApiConfig (`app/Shared/OpenApi/OpenApiConfig.php`)
- PostRequestSchema (`app/Shared/Schemas/PostRequestSchema.php`)
- PostSchema (`app/Shared/Schemas/PostSchema.php`)
- AuthSchema (`app/Shared/Schemas/AuthSchema.php`)
- ValidatorFactory (`app/Shared/Validation/Factory/ValidatorFactory.php`)
- JwtConfig (`app/Shared/Config/JwtConfig.php`)
- AuthController (`app/Application/Controllers/Api/V1/AuthController.php`)
- IpController (`app/Application/Controllers/Api/V1/IpController.php`)
- PostController (`app/Application/Controllers/PostController.php`)
- AttachmentController (`app/Application/Controllers/Api/V1/AttachmentController.php`)
- HealthController (`app/Application/Controllers/TestController.php`)
- SwaggerController (`app/Application/Controllers/Web/SwaggerController.php`)
- CSPReportController (`app/Application/Controllers/Security/CSPReportController.php`)
- BaseController (`app/Application/Controllers/BaseController.php`)
- AuthorizationMiddleware (`app/Application/Middleware/AuthorizationMiddleware.php`)
- InitialSchema (`database/migrations/20250823051608_initial_schema.php`)
- AddTokenHashToRefreshTokensTable (`database/migrations/20250826023305_add_token_hash_to_refresh_tokens_table.php`)
- AddMissingColumnsToRefreshTokens (`database/migrations/20250103000000_add_missing_columns_to_refresh_tokens.php`)
- CreateRefreshTokensTable (`database/migrations/20250825165731_create_refresh_tokens_table.php`)
- CreateTokenBlacklistTable (`database/migrations/20250825165750_create_token_blacklist_table.php`)
- ConsoleOutput (`scripts/lib/ConsoleOutput.php`)
- ProjectArchitectureScanner (`scripts/scan-project-architecture.php`)
- ConsolidatedDeployer (`scripts/consolidated/ConsolidatedDeployer.php`)
- ConsolidatedErrorFixer (`scripts/consolidated/ConsolidatedErrorFixer.php`)
- ConsolidatedTestManager (`scripts/consolidated/ConsolidatedTestManager.php`)
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
- ConsolidatedMaintainer (`scripts/consolidated/ConsolidatedMaintainer.php`)
- ConsolidatedAnalyzer (`scripts/consolidated/ConsolidatedAnalyzer.php`)

### `FileSecurityServiceInterface`
- FileSecurityService (`app/Domains/Attachment/Services/FileSecurityService.php`)

### `AttachmentServiceInterface`
- AttachmentService (`app/Domains/Attachment/Services/AttachmentService.php`)

### `JsonSerializable`
- JwtPayload (`app/Domains/Auth/ValueObjects/JwtPayload.php`)
- TokenPair (`app/Domains/Auth/ValueObjects/TokenPair.php`)
- TokenBlacklistEntry (`app/Domains/Auth/ValueObjects/TokenBlacklistEntry.php`)
- DeviceInfo (`app/Domains/Auth/ValueObjects/DeviceInfo.php`)
- RefreshToken (`app/Domains/Auth/Entities/RefreshToken.php`)
- IpList (`app/Domains/Security/Models/IpList.php`)
- Post (`app/Domains/Post/Models/Post.php`)
- BaseDTO (`app/Shared/DTOs/BaseDTO.php`)
- ValidationResult (`app/Shared/Validation/ValidationResult.php`)
- AuthorizationResult (`app/Application/Middleware/AuthorizationResult.php`)

### `SessionSecurityServiceInterface`
- SessionSecurityService (`app/Domains/Auth/Services/SessionSecurityService.php`)

### `AuthorizationServiceInterface`
- AuthorizationService (`app/Domains/Auth/Services/AuthorizationService.php`)

### `JwtTokenServiceInterface`
- JwtTokenService (`app/Domains/Auth/Services/JwtTokenService.php`)

### `AuthenticationServiceInterface`
- AuthenticationService (`app/Domains/Auth/Services/AuthenticationService.php`)

### `PasswordSecurityServiceInterface`
- PasswordSecurityService (`app/Domains/Auth/Services/PasswordSecurityService.php`)

### `IpRepositoryInterface`
- IpRepository (`app/Domains/Security/Repositories/IpRepository.php`)

### `LoggingSecurityServiceInterface`
- LoggingSecurityService (`app/Domains/Security/Services/Logging/LoggingSecurityService.php`)

### `ErrorHandlerServiceInterface`
- ErrorHandlerService (`app/Domains/Security/Services/Error/ErrorHandlerService.php`)

### `SecurityHeaderServiceInterface`
- SecurityHeaderService (`app/Domains/Security/Services/Headers/SecurityHeaderService.php`)

### `SecurityTestInterface`
- SecurityTestService (`app/Domains/Security/Services/Advanced/SecurityTestService.php`)

### `SecretsManagerInterface`
- SecretsManager (`app/Domains/Security/Services/Secrets/SecretsManager.php`)

### `PostRepositoryInterface`
- PostRepository (`app/Domains/Post/Repositories/PostRepository.php`)

### `PostServiceInterface`
- PostService (`app/Domains/Post/Services/PostService.php`)

### `StreamInterface`
- Stream (`app/Infrastructure/Http/Stream.php`)

### `ServerRequestInterface`
- ServerRequest (`app/Infrastructure/Http/ServerRequest.php`)

### `UriInterface`
- Uri (`app/Infrastructure/Http/Uri.php`)

### `ResponseInterface`
- Response (`app/Infrastructure/Http/Response.php`)

### `JwtProviderInterface`
- FirebaseJwtProvider (`app/Infrastructure/Auth/Jwt/FirebaseJwtProvider.php`)

### `TokenBlacklistRepositoryInterface`
- TokenBlacklistRepository (`app/Infrastructure/Auth/Repositories/TokenBlacklistRepository.php`)

### `RefreshTokenRepositoryInterface`
- RefreshTokenRepository (`app/Infrastructure/Auth/Repositories/RefreshTokenRepository.php`)

### `OutputSanitizerInterface`
- OutputSanitizerService (`app/Infrastructure/Services/OutputSanitizer.php`)

### `CacheServiceInterface`
- CacheService (`app/Infrastructure/Services/CacheService.php`)

### `RouteCacheInterface`
- MemoryRouteCache (`app/Infrastructure/Routing/Cache/MemoryRouteCache.php`)
- FileRouteCache (`app/Infrastructure/Routing/Cache/FileRouteCache.php`)
- RedisRouteCache (`app/Infrastructure/Routing/Cache/RedisRouteCache.php`)

### `RequestHandlerInterface`
- ClosureRequestHandler (`app/Infrastructure/Routing/ClosureRequestHandler.php`)

### `RouteCollectionInterface`
- RouteCollection (`app/Infrastructure/Routing/Core/RouteCollection.php`)

### `RouteInterface`
- Route (`app/Infrastructure/Routing/Core/Route.php`)

### `RouterInterface`
- Router (`app/Infrastructure/Routing/Core/Router.php`)

### `MiddlewareDispatcherInterface`
- MiddlewareDispatcher (`app/Infrastructure/Routing/Middleware/MiddlewareDispatcher.php`)

### `MiddlewareInterface`
- AbstractMiddleware (`app/Infrastructure/Routing/Middleware/AbstractMiddleware.php`)
- RateLimitMiddleware (`app/Application/Middleware/RateLimitMiddleware.php`)
- JwtAuthenticationMiddleware (`app/Application/Middleware/JwtAuthenticationMiddleware.php`)
- JwtAuthorizationMiddleware (`app/Application/Middleware/JwtAuthorizationMiddleware.php`)

### `MiddlewareManagerInterface`
- MiddlewareManager (`app/Infrastructure/Routing/Middleware/MiddlewareManager.php`)

### `ValidatorInterface`
- Validator (`app/Shared/Validation/Validator.php`)

### `ScriptConfigurationInterface`
- DefaultScriptConfiguration (`scripts/consolidated/DefaultScriptConfiguration.php`)

### `ScriptExecutorInterface`
- DefaultScriptExecutor (`scripts/consolidated/DefaultScriptExecutor.php`)

### `ScriptAnalyzerInterface`
- DefaultScriptAnalyzer (`scripts/consolidated/DefaultScriptAnalyzer.php`)


## 🧪 測試覆蓋分析

- **有測試的類別**: 0 個
- **缺少測試的類別**: 161 個

### 缺少測試的重要類別
- **AttachmentRepository**: `app/Domains/Attachment/Repositories/AttachmentRepository.php`
- **FileSecurityService**: `app/Domains/Attachment/Services/FileSecurityService.php`
- **AttachmentService**: `app/Domains/Attachment/Services/AttachmentService.php`
- **UserRepository**: `app/Domains/Auth/Repositories/UserRepository.php`


## 💉 依賴注入分析

### 依賴較多的類別 (≥3個依賴)
- **AttachmentService** (3 個依賴)
  - `AttachmentRepository` $attachmentRepo
  - `PostRepository` $postRepo
  - `AuthorizationService` $authService

- **JwtPayload** (3 個依賴)
  - `DateTimeImmutable` $iat
  - `DateTimeImmutable` $exp
  - `DateTimeImmutable` $nbf

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

- **AuthenticationService** (3 個依賴)
  - `JwtTokenServiceInterface` $jwtTokenService
  - `RefreshTokenRepositoryInterface` $refreshTokenRepository
  - `UserRepositoryInterface` $userRepository

- **AuthService** (3 個依賴)
  - `UserRepository` $userRepository
  - `PasswordSecurityServiceInterface` $passwordService
  - `JwtTokenServiceInterface` $jwtTokenService

- **RefreshToken** (6 個依賴)
  - `DateTime` $expiresAt
  - `DeviceInfo` $deviceInfo
  - `DateTime` $revokedAt
  - `DateTime` $lastUsedAt
  - `DateTime` $createdAt
  - `DateTime` $updatedAt

- **XssProtectionExtensionService** (3 個依賴)
  - `XssProtectionService` $baseXssProtection
  - `RichTextProcessorService` $richTextProcessor
  - `ContentModerationService` $contentModerator

- **SecurityTestService** (7 個依賴)
  - `SessionSecurityServiceInterface` $sessionService
  - `AuthorizationServiceInterface` $authService
  - `FileSecurityServiceInterface` $fileService
  - `SecurityHeaderServiceInterface` $headerService
  - `ErrorHandlerServiceInterface` $errorService
  - `PasswordSecurityServiceInterface` $passwordService
  - `SecretsManagerInterface` $secretsManager

- **PostRepository** (3 個依賴)
  - `PDO` $db
  - `CacheServiceInterface` $cache
  - `LoggingSecurityServiceInterface` $logger

- **RouteDispatcher** (4 個依賴)
  - `RouterInterface` $router
  - `ControllerResolver` $controllerResolver
  - `MiddlewareDispatcher` $middlewareDispatcher
  - `ContainerInterface` $container

- **AuthController** (4 個依賴)
  - `AuthService` $authService
  - `AuthenticationServiceInterface` $authenticationService
  - `JwtTokenServiceInterface` $jwtTokenService
  - `ValidatorInterface` $validator

- **IpController** (3 個依賴)
  - `IpService` $service
  - `ValidatorInterface` $validator
  - `OutputSanitizerInterface` $sanitizer

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
- ❓ 找不到類別/介面: reserved claim '{$claim}' as custom claim") (在 app/Domains/Auth/ValueObjects/JwtPayload.php 中使用)
- ❓ 找不到類別/介面: Throwable (在 app/Domains/Auth/Exceptions/JwtConfigurationException.php 中使用)
- ❓ 找不到類別/介面: Throwable (在 app/Domains/Auth/Exceptions/TokenParsingException.php 中使用)
- ❓ 找不到類別/介面: Throwable (在 app/Domains/Auth/Exceptions/TokenValidationException.php 中使用)
- ❓ 找不到類別/介面: ($data) {
            if (!is_string($value)) {
                return false (在 app/Domains/Auth/DTOs/RegisterUserDTO.php 中使用)
- ❓ 找不到類別/介面: Throwable (在 app/Domains/Auth/Services/RefreshTokenService.php 中使用)
- ❓ 找不到類別/介面: ($userId) {
            return $this->getUserPermissions($userId) (在 app/Domains/Auth/Services/AuthorizationService.php 中使用)
- ... 還有 65 個
