<?php

/**
 * DDD 重構命名空間映射配置
 *
 * 此檔案定義了從舊命名空間到新 DDD 架構命名空間的完整映射關係
 */

return [
    // 主要命名空間映射
    'namespace_mappings' => [
        // Domain Models
        'App\\Models\\Domain\\Post' => 'App\\Domains\\Post\\Models\\Post',
        'App\\Models\\Domain\\Attachment' => 'App\\Domains\\Attachment\\Models\\Attachment',
        'App\\Models\\Domain\\IpList' => 'App\\Domains\\Security\\Models\\IpList',
        'App\\Models\\Auth\\Role' => 'App\\Domains\\Auth\\Models\\Role',
        'App\\Models\\Auth\\Permission' => 'App\\Domains\\Auth\\Models\\Permission',

        // Domain Services
        'App\\Services\\Content\\PostService' => 'App\\Domains\\Post\\Services\\PostService',
        'App\\Services\\Content\\AttachmentService' => 'App\\Domains\\Attachment\\Services\\AttachmentService',
        'App\\Services\\Auth\\AuthService' => 'App\\Domains\\Auth\\Services\\AuthService',
        'App\\Services\\Auth\\PasswordManagementService' => 'App\\Domains\\Auth\\Services\\PasswordManagementService',
        'App\\Services\\Infrastructure\\IpService' => 'App\\Domains\\Security\\Services\\IpService',

        // Security Services
        'App\\Services\\Security\\Core\\AuthorizationService' => 'App\\Domains\\Auth\\Services\\AuthorizationService',
        'App\\Services\\Security\\Core\\XssProtectionService' => 'App\\Domains\\Security\\Services\\Core\\XssProtectionService',
        'App\\Services\\Security\\Core\\CsrfProtectionService' => 'App\\Domains\\Security\\Services\\Core\\CsrfProtectionService',
        'App\\Services\\Security\\Core\\PasswordSecurityService' => 'App\\Domains\\Auth\\Services\\PasswordSecurityService',
        'App\\Services\\Security\\Advanced\\PwnedPasswordService' => 'App\\Domains\\Auth\\Services\\Advanced\\PwnedPasswordService',
        'App\\Services\\Security\\Advanced\\AdvancedRateLimitService' => 'App\\Domains\\Security\\Services\\Advanced\\RateLimitService',
        'App\\Services\\Security\\Advanced\\SecurityTestService' => 'App\\Domains\\Security\\Services\\Advanced\\SecurityTestService',
        'App\\Services\\Security\\Content\\ContentModerationService' => 'App\\Domains\\Post\\Services\\ContentModerationService',
        'App\\Services\\Security\\Content\\FileSecurityService' => 'App\\Domains\\Attachment\\Services\\FileSecurityService',
        'App\\Services\\Security\\Content\\RichTextProcessorService' => 'App\\Domains\\Post\\Services\\RichTextProcessorService',
        'App\\Services\\Security\\Content\\XssProtectionExtensionService' => 'App\\Domains\\Security\\Services\\Content\\XssProtectionExtensionService',
        'App\\Services\\Security\\Error\\ErrorHandlerService' => 'App\\Domains\\Security\\Services\\Error\\ErrorHandlerService',
        'App\\Services\\Security\\Headers\\SecurityHeaderService' => 'App\\Domains\\Security\\Services\\Headers\\SecurityHeaderService',
        'App\\Services\\Security\\Logging\\LoggingSecurityService' => 'App\\Domains\\Security\\Services\\Logging\\LoggingSecurityService',
        'App\\Services\\Security\\Secrets\\SecretsManager' => 'App\\Domains\\Security\\Services\\Secrets\\SecretsManager',
        'App\\Services\\Security\\Session\\SessionSecurityService' => 'App\\Domains\\Auth\\Services\\SessionSecurityService',

        // DTOs
        'App\\DTOs\\Post\\CreatePostDTO' => 'App\\Domains\\Post\\DTOs\\CreatePostDTO',
        'App\\DTOs\\Post\\UpdatePostDTO' => 'App\\Domains\\Post\\DTOs\\UpdatePostDTO',
        'App\\DTOs\\Attachment\\CreateAttachmentDTO' => 'App\\Domains\\Attachment\\DTOs\\CreateAttachmentDTO',
        'App\\DTOs\\Auth\\RegisterUserDTO' => 'App\\Domains\\Auth\\DTOs\\RegisterUserDTO',
        'App\\DTOs\\IpManagement\\CreateIpRuleDTO' => 'App\\Domains\\Security\\DTOs\\CreateIpRuleDTO',

        // Repositories
        'App\\Repositories\\Eloquent\\PostRepository' => 'App\\Domains\\Post\\Repositories\\PostRepository',
        'App\\Repositories\\Eloquent\\AttachmentRepository' => 'App\\Domains\\Attachment\\Repositories\\AttachmentRepository',
        'App\\Repositories\\Eloquent\\UserRepository' => 'App\\Domains\\Auth\\Repositories\\UserRepository',
        'App\\Repositories\\Eloquent\\IpRepository' => 'App\\Domains\\Security\\Repositories\\IpRepository',

        // Exceptions
        'App\\Exceptions\\Post\\PostNotFoundException' => 'App\\Domains\\Post\\Exceptions\\PostNotFoundException',
        'App\\Exceptions\\Post\\PostStatusException' => 'App\\Domains\\Post\\Exceptions\\PostStatusException',
        'App\\Exceptions\\Post\\PostValidationException' => 'App\\Domains\\Post\\Exceptions\\PostValidationException',
        'App\\Exceptions\\Auth\\ForbiddenException' => 'App\\Domains\\Auth\\Exceptions\\ForbiddenException',
        'App\\Exceptions\\Auth\\UnauthorizedException' => 'App\\Domains\\Auth\\Exceptions\\UnauthorizedException',

        // Contracts
        'App\\Contracts\\Repositories\\PostRepositoryInterface' => 'App\\Domains\\Post\\Contracts\\PostRepositoryInterface',
        'App\\Contracts\\Repositories\\AttachmentRepositoryInterface' => 'App\\Domains\\Attachment\\Contracts\\AttachmentRepositoryInterface',
        'App\\Contracts\\Repositories\\UserRepositoryInterface' => 'App\\Domains\\Auth\\Contracts\\UserRepositoryInterface',
        'App\\Contracts\\Repositories\\IpRepositoryInterface' => 'App\\Domains\\Security\\Contracts\\IpRepositoryInterface',
        'App\\Contracts\\Services\\PostServiceInterface' => 'App\\Domains\\Post\\Contracts\\PostServiceInterface',
        'App\\Contracts\\Services\\AttachmentServiceInterface' => 'App\\Domains\\Attachment\\Contracts\\AttachmentServiceInterface',
        'App\\Contracts\\Services\\Security\\AuthorizationServiceInterface' => 'App\\Domains\\Auth\\Contracts\\AuthorizationServiceInterface',
        'App\\Contracts\\Services\\Security\\CsrfProtectionServiceInterface' => 'App\\Domains\\Security\\Contracts\\CsrfProtectionServiceInterface',
        'App\\Contracts\\Services\\Security\\ErrorHandlerServiceInterface' => 'App\\Domains\\Security\\Contracts\\ErrorHandlerServiceInterface',
        'App\\Contracts\\Services\\Security\\FileSecurityServiceInterface' => 'App\\Domains\\Attachment\\Contracts\\FileSecurityServiceInterface',
        'App\\Contracts\\Services\\Security\\LoggingSecurityServiceInterface' => 'App\\Domains\\Security\\Contracts\\LoggingSecurityServiceInterface',
        'App\\Contracts\\Services\\Security\\PasswordSecurityServiceInterface' => 'App\\Domains\\Auth\\Contracts\\PasswordSecurityServiceInterface',
        'App\\Contracts\\Services\\Security\\SecretsManagerInterface' => 'App\\Domains\\Security\\Contracts\\SecretsManagerInterface',
        'App\\Contracts\\Services\\Security\\SecurityHeaderServiceInterface' => 'App\\Domains\\Security\\Contracts\\SecurityHeaderServiceInterface',
        'App\\Contracts\\Services\\Security\\SecurityTestInterface' => 'App\\Domains\\Security\\Contracts\\SecurityTestInterface',
        'App\\Contracts\\Services\\Security\\SessionSecurityServiceInterface' => 'App\\Domains\\Auth\\Contracts\\SessionSecurityServiceInterface',
        'App\\Contracts\\Services\\Security\\XssProtectionServiceInterface' => 'App\\Domains\\Security\\Contracts\\XssProtectionServiceInterface',

        // Enums
        'App\\Enums\\PostStatus' => 'App\\Domains\\Post\\Enums\\PostStatus',
        'App\\Enums\\FileRules' => 'App\\Domains\\Attachment\\Enums\\FileRules',

        // Validation
        'App\\Validation\\PostValidator' => 'App\\Domains\\Post\\Validation\\PostValidator',
        'App\\Validation\\Validators\\PostValidator' => 'App\\Domains\\Post\\Validation\\Validators\\PostValidator',

        // Application Layer
        'App\\Controllers\\Api\\V1\\PostController' => 'App\\Application\\Controllers\\Api\\V1\\PostController',
        'App\\Controllers\\Api\\V1\\AttachmentController' => 'App\\Application\\Controllers\\Api\\V1\\AttachmentController',
        'App\\Controllers\\Api\\V1\\AuthController' => 'App\\Application\\Controllers\\Api\\V1\\AuthController',
        'App\\Controllers\\Api\\V1\\IpController' => 'App\\Application\\Controllers\\Api\\V1\\IpController',
        'App\\Controllers\\BaseController' => 'App\\Application\\Controllers\\BaseController',
        'App\\Controllers\\Health\\HealthController' => 'App\\Application\\Controllers\\Health\\HealthController',
        'App\\Controllers\\Security\\CSPReportController' => 'App\\Application\\Controllers\\Security\\CSPReportController',
        'App\\Controllers\\TestController' => 'App\\Application\\Controllers\\TestController',
        'App\\Controllers\\Web\\SwaggerController' => 'App\\Application\\Controllers\\Web\\SwaggerController',
        'App\\Middleware\\AuthorizationMiddleware' => 'App\\Application\\Middleware\\AuthorizationMiddleware',
        'App\\Middleware\\RateLimitMiddleware' => 'App\\Application\\Middleware\\RateLimitMiddleware',

        // Infrastructure Layer
        'App\\Services\\Infrastructure\\CacheService' => 'App\\Infrastructure\\Services\\CacheService',
        'App\\Services\\Infrastructure\\RateLimitService' => 'App\\Infrastructure\\Services\\RateLimitService',
        'App\\Services\\Content\\OutputSanitizer' => 'App\\Infrastructure\\Services\\OutputSanitizer',

        // Shared Layer
        'App\\DTOs\\BaseDTO' => 'App\\Shared\\DTOs\\BaseDTO',
        'App\\Exceptions\\NotFoundException' => 'App\\Shared\\Exceptions\\NotFoundException',
        'App\\Exceptions\\CsrfTokenException' => 'App\\Shared\\Exceptions\\CsrfTokenException',
        'App\\Exceptions\\StateTransitionException' => 'App\\Shared\\Exceptions\\StateTransitionException',
        'App\\Exceptions\\Validation\\RequestValidationException' => 'App\\Shared\\Exceptions\\Validation\\RequestValidationException',
        'App\\Exceptions\\ValidationException' => 'App\\Shared\\Exceptions\\ValidationException',
        'App\\Contracts\\Repositories\\RepositoryInterface' => 'App\\Shared\\Contracts\\RepositoryInterface',
        'App\\Contracts\\Services\\CacheServiceInterface' => 'App\\Shared\\Contracts\\CacheServiceInterface',
        'App\\Contracts\\Validation\\ValidatorInterface' => 'App\\Shared\\Contracts\\ValidatorInterface',
        'App\\Validation\\Factory\\ValidatorFactory' => 'App\\Shared\\Validation\\Factory\\ValidatorFactory',
        'App\\Validation\\ValidationException' => 'App\\Shared\\Validation\\ValidationException',
        'App\\Validation\\ValidationResult' => 'App\\Shared\\Validation\\ValidationResult',
        'App\\Validation\\Validator' => 'App\\Shared\\Validation\\Validator',
        'App\\Http\\ApiResponse' => 'App\\Shared\\Http\\ApiResponse',
        'App\\Helpers\\functions' => 'App\\Shared\\Helpers\\functions',
        'App\\Schemas\\AuthSchema' => 'App\\Shared\\Schemas\\AuthSchema',
        'App\\Schemas\\PostRequestSchema' => 'App\\Shared\\Schemas\\PostRequestSchema',
        'App\\Schemas\\PostSchema' => 'App\\Shared\\Schemas\\PostSchema',
    ],

    // Import/Use 語句映射 (用於更新檔案中的 use 語句)
    'import_mappings' => [
        // 自動根據 namespace_mappings 生成對應的 use 語句映射
    ],

    // 領域分組規則
    'domain_rules' => [
        'Post' => [
            'models' => ['Post'],
            'services' => ['PostService', 'ContentModerationService', 'RichTextProcessorService'],
            'dtos' => ['CreatePostDTO', 'UpdatePostDTO'],
            'repositories' => ['PostRepository'],
            'exceptions' => ['PostNotFoundException', 'PostStatusException', 'PostValidationException'],
            'contracts' => ['PostRepositoryInterface', 'PostServiceInterface'],
            'enums' => ['PostStatus'],
            'validation' => ['PostValidator'],
        ],
        'Attachment' => [
            'models' => ['Attachment'],
            'services' => ['AttachmentService', 'FileSecurityService'],
            'dtos' => ['CreateAttachmentDTO'],
            'repositories' => ['AttachmentRepository'],
            'contracts' => ['AttachmentRepositoryInterface', 'AttachmentServiceInterface', 'FileSecurityServiceInterface'],
            'enums' => ['FileRules'],
        ],
        'Auth' => [
            'models' => ['Role', 'Permission'],
            'services' => ['AuthService', 'PasswordManagementService', 'AuthorizationService', 'PasswordSecurityService', 'SessionSecurityService'],
            'advanced_services' => ['PwnedPasswordService'],
            'dtos' => ['RegisterUserDTO'],
            'repositories' => ['UserRepository'],
            'exceptions' => ['ForbiddenException', 'UnauthorizedException'],
            'contracts' => ['UserRepositoryInterface', 'AuthorizationServiceInterface', 'PasswordSecurityServiceInterface', 'SessionSecurityServiceInterface'],
        ],
        'Security' => [
            'models' => ['IpList'],
            'services' => ['IpService'],
            'core_services' => ['XssProtectionService', 'CsrfProtectionService'],
            'advanced_services' => ['RateLimitService', 'SecurityTestService'],
            'content_services' => ['XssProtectionExtensionService'],
            'error_services' => ['ErrorHandlerService'],
            'header_services' => ['SecurityHeaderService'],
            'logging_services' => ['LoggingSecurityService'],
            'secret_services' => ['SecretsManager'],
            'dtos' => ['CreateIpRuleDTO'],
            'repositories' => ['IpRepository'],
            'contracts' => [
                'IpRepositoryInterface',
                'CsrfProtectionServiceInterface',
                'ErrorHandlerServiceInterface',
                'LoggingSecurityServiceInterface',
                'SecretsManagerInterface',
                'SecurityHeaderServiceInterface',
                'SecurityTestInterface',
                'XssProtectionServiceInterface'
            ],
        ],
    ],

    // 排除的檔案 (不需要移動或更新命名空間)
    'excluded_files' => [
        'app/Infrastructure/Cache/CacheKeys.php',
        'app/Infrastructure/Cache/CacheManager.php',
        'app/Infrastructure/Config/ContainerFactory.php',
        'app/Infrastructure/Config/container.php',
        'app/Infrastructure/Database/DatabaseConnection.php',
        'app/Infrastructure/OpenApi/OpenApiSpec.php',
    ],

    // 特殊處理規則
    'special_rules' => [
        // 有些檔案可能需要特殊的命名空間處理邏輯
        'preserve_traits' => true,
        'update_interfaces' => true,
        'update_abstract_classes' => true,
        'handle_multiple_classes' => false, // 假設每個檔案只有一個類別
    ],

    // 驗證規則
    'validation_rules' => [
        'check_class_exists' => true,
        'check_interface_exists' => true,
        'check_trait_exists' => true,
        'validate_syntax' => true,
        'check_dependencies' => true,
    ],

    // 備份設定
    'backup_settings' => [
        'create_backup' => true,
        'backup_directory' => 'backups/ddd-refactoring',
        'timestamp_format' => 'Y-m-d_H-i-s',
    ],

    // 日誌設定
    'logging' => [
        'enabled' => true,
        'log_file' => 'logs/ddd-refactoring.log',
        'log_level' => 'DEBUG',
        'log_format' => '[%datetime%] %level_name%: %message% %context%' . PHP_EOL,
    ],
];
