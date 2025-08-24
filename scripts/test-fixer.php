<?php

declare(strict_types=1);

/**
 * DDD 重構測試修復工具
 *
 * 此工具用於自動修復因 DDD 重構導致的測試檔案中的命名空間問題
 *
 * 使用方式：
 * php scripts/test-fixer.php --mode=validate
 * php scripts/test-fixer.php --mode=execute
 */

class TestFixer
{
    private array $namespaceMapping;
    private array $processedFiles = [];
    private array $errors = [];
    private int $fixedCount = 0;

    public function __construct()
    {
        $this->namespaceMapping = $this->buildNamespaceMapping();
    }

    /**
     * 建立命名空間映射表
     */
    private function buildNamespaceMapping(): array
    {
        return [
            // Domain Models
            'App\\Models\\Domain\\Post' => 'App\\Domains\\Post\\Models\\Post',
            'App\\Models\\Domain\\Attachment' => 'App\\Domains\\Attachment\\Models\\Attachment',
            'App\\Models\\Domain\\IpList' => 'App\\Domains\\Security\\Models\\IpList',
            'App\\Models\\Auth\\Role' => 'App\\Domains\\Auth\\Models\\Role',
            'App\\Models\\Auth\\Permission' => 'App\\Domains\\Auth\\Models\\Permission',

            // Services
            'App\\Services\\Content\\PostService' => 'App\\Domains\\Post\\Services\\PostService',
            'App\\Services\\Content\\AttachmentService' => 'App\\Domains\\Attachment\\Services\\AttachmentService',
            'App\\Services\\Auth\\AuthService' => 'App\\Domains\\Auth\\Services\\AuthService',
            'App\\Services\\Auth\\PasswordManagementService' => 'App\\Domains\\Auth\\Services\\PasswordManagementService',
            'App\\Services\\Infrastructure\\IpService' => 'App\\Domains\\Security\\Services\\IpService',
            'App\\Services\\Infrastructure\\CacheService' => 'App\\Infrastructure\\Services\\CacheService',
            'App\\Services\\Infrastructure\\RateLimitService' => 'App\\Infrastructure\\Services\\RateLimitService',
            'App\\Services\\Content\\OutputSanitizer' => 'App\\Infrastructure\\Services\\OutputSanitizer',

            // Security Services
            'App\\Services\\Security\\Core\\AuthorizationService' => 'App\\Domains\\Auth\\Services\\AuthorizationService',
            'App\\Services\\Security\\Core\\XssProtectionService' => 'App\\Domains\\Security\\Services\\Core\\XssProtectionService',
            'App\\Services\\Security\\Core\\CsrfProtectionService' => 'App\\Domains\\Security\\Services\\Core\\CsrfProtectionService',
            'App\\Services\\Security\\Core\\PasswordSecurityService' => 'App\\Domains\\Auth\\Services\\PasswordSecurityService',
            'App\\Services\\Security\\Advanced\\PwnedPasswordService' => 'App\\Domains\\Auth\\Services\\Advanced\\PwnedPasswordService',
            'App\\Services\\Security\\Advanced\\AdvancedRateLimitService' => 'App\\Domains\\Security\\Services\\Advanced\\RateLimitService',
            'App\\Services\\Security\\Session\\SessionSecurityService' => 'App\\Domains\\Auth\\Services\\SessionSecurityService',

            // DTOs
            'App\\DTOs\\Post\\CreatePostDTO' => 'App\\Domains\\Post\\DTOs\\CreatePostDTO',
            'App\\DTOs\\Post\\UpdatePostDTO' => 'App\\Domains\\Post\\DTOs\\UpdatePostDTO',
            'App\\DTOs\\Attachment\\CreateAttachmentDTO' => 'App\\Domains\\Attachment\\DTOs\\CreateAttachmentDTO',
            'App\\DTOs\\Auth\\RegisterUserDTO' => 'App\\Domains\\Auth\\DTOs\\RegisterUserDTO',
            'App\\DTOs\\IpManagement\\CreateIpRuleDTO' => 'App\\Domains\\Security\\DTOs\\CreateIpRuleDTO',
            'App\\DTOs\\BaseDTO' => 'App\\Shared\\DTOs\\BaseDTO',

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
            'App\\Exceptions\\NotFoundException' => 'App\\Shared\\Exceptions\\NotFoundException',
            'App\\Exceptions\\ValidationException' => 'App\\Shared\\Exceptions\\ValidationException',
            'App\\Exceptions\\CsrfTokenException' => 'App\\Shared\\Exceptions\\CsrfTokenException',
            'App\\Exceptions\\StateTransitionException' => 'App\\Shared\\Exceptions\\StateTransitionException',
            'App\\Exceptions\\Validation\\RequestValidationException' => 'App\\Shared\\Exceptions\\Validation\\RequestValidationException',

            // Contracts
            'App\\Contracts\\Repositories\\PostRepositoryInterface' => 'App\\Domains\\Post\\Contracts\\PostRepositoryInterface',
            'App\\Contracts\\Repositories\\AttachmentRepositoryInterface' => 'App\\Domains\\Attachment\\Contracts\\AttachmentRepositoryInterface',
            'App\\Contracts\\Repositories\\UserRepositoryInterface' => 'App\\Domains\\Auth\\Contracts\\UserRepositoryInterface',
            'App\\Contracts\\Repositories\\IpRepositoryInterface' => 'App\\Domains\\Security\\Contracts\\IpRepositoryInterface',
            'App\\Contracts\\Repositories\\RepositoryInterface' => 'App\\Shared\\Contracts\\RepositoryInterface',
            'App\\Contracts\\Services\\PostServiceInterface' => 'App\\Domains\\Post\\Contracts\\PostServiceInterface',
            'App\\Contracts\\Services\\AttachmentServiceInterface' => 'App\\Domains\\Attachment\\Contracts\\AttachmentServiceInterface',
            'App\\Contracts\\Services\\CacheServiceInterface' => 'App\\Shared\\Contracts\\CacheServiceInterface',
            'App\\Contracts\\Validation\\ValidatorInterface' => 'App\\Shared\\Contracts\\ValidatorInterface',

            // Enums
            'App\\Enums\\PostStatus' => 'App\\Domains\\Post\\Enums\\PostStatus',
            'App\\Enums\\FileRules' => 'App\\Domains\\Attachment\\Enums\\FileRules',

            // Validation
            'App\\Validation\\PostValidator' => 'App\\Domains\\Post\\Validation\\PostValidator',
            'App\\Validation\\Validators\\PostValidator' => 'App\\Domains\\Post\\Validation\\Validators\\PostValidator',
            'App\\Validation\\Validator' => 'App\\Shared\\Validation\\Validator',
            'App\\Validation\\ValidationException' => 'App\\Shared\\Validation\\ValidationException',
            'App\\Validation\\ValidationResult' => 'App\\Shared\\Validation\\ValidationResult',
            'App\\Validation\\Factory\\ValidatorFactory' => 'App\\Shared\\Validation\\Factory\\ValidatorFactory',

            // Controllers
            'App\\Controllers\\Api\\V1\\PostController' => 'App\\Application\\Controllers\\Api\\V1\\PostController',
            'App\\Controllers\\Api\\V1\\AttachmentController' => 'App\\Application\\Controllers\\Api\\V1\\AttachmentController',
            'App\\Controllers\\Api\\V1\\AuthController' => 'App\\Application\\Controllers\\Api\\V1\\AuthController',
            'App\\Controllers\\Api\\V1\\IpController' => 'App\\Application\\Controllers\\Api\\V1\\IpController',
            'App\\Controllers\\BaseController' => 'App\\Application\\Controllers\\BaseController',
            'App\\Controllers\\Health\\HealthController' => 'App\\Application\\Controllers\\Health\\HealthController',
            'App\\Controllers\\Security\\CSPReportController' => 'App\\Application\\Controllers\\Security\\CSPReportController',
            'App\\Controllers\\TestController' => 'App\\Application\\Controllers\\TestController',
            'App\\Controllers\\Web\\SwaggerController' => 'App\\Application\\Controllers\\Web\\SwaggerController',

            // Http
            'App\\Http\\ApiResponse' => 'App\\Shared\\Http\\ApiResponse',

            // Cache
            'App\\Cache\\CacheKeys' => 'App\\Infrastructure\\Cache\\CacheKeys',
            'App\\Cache\\CacheManager' => 'App\\Infrastructure\\Cache\\CacheManager',

            // Helpers
            'App\\Helpers\\functions' => 'App\\Shared\\Helpers\\functions',

            // Schemas
            'App\\Schemas\\AuthSchema' => 'App\\Shared\\Schemas\\AuthSchema',
            'App\\Schemas\\PostRequestSchema' => 'App\\Shared\\Schemas\\PostRequestSchema',
            'App\\Schemas\\PostSchema' => 'App\\Shared\\Schemas\\PostSchema',
        ];
    }

    /**
     * 執行修復
     */
    public function run(string $mode = 'validate'): bool
    {
        echo "開始執行測試修復，模式: $mode\n";

        $testFiles = $this->findTestFiles();
        echo "找到 " . count($testFiles) . " 個測試檔案\n";

        foreach ($testFiles as $file) {
            try {
                if ($mode === 'validate') {
                    $this->validateFile($file);
                } else {
                    $this->fixFile($file);
                }
            } catch (Exception $e) {
                $this->errors[] = "處理檔案 $file 時發生錯誤: " . $e->getMessage();
            }
        }

        $this->printSummary();
        return empty($this->errors);
    }

    /**
     * 找到所有測試檔案
     */
    private function findTestFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('tests', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * 驗證檔案
     */
    private function validateFile(string $file): void
    {
        $content = file_get_contents($file);
        if ($content === false) {
            throw new Exception("無法讀取檔案: $file");
        }

        $needsFix = false;
        foreach ($this->namespaceMapping as $old => $new) {
            $oldEscaped = str_replace('\\', '\\\\', $old);
            if (preg_match('/use\s+' . $oldEscaped . '(?:;|\s)/', $content) ||
                preg_match('/' . $oldEscaped . '::/', $content) ||
                preg_match('/new\s+' . $oldEscaped . '\s*\(/', $content)) {
                $needsFix = true;
                break;
            }
        }

        if ($needsFix) {
            echo "檔案需要修復: $file\n";
            $this->processedFiles[] = $file;
        }
    }

    /**
     * 修復檔案
     */
    private function fixFile(string $file): void
    {
        $content = file_get_contents($file);
        if ($content === false) {
            throw new Exception("無法讀取檔案: $file");
        }

        $originalContent = $content;
        $fixed = false;

        foreach ($this->namespaceMapping as $old => $new) {
            $oldEscaped = str_replace('\\', '\\\\', $old);
            $newEscaped = str_replace('\\', '\\\\', $new);

            // 修復 use 語句
            $pattern = '/use\s+' . $oldEscaped . '(;|\s)/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, 'use ' . $new . '$1', $content);
                $fixed = true;
            }

            // 修復靜態調用
            $pattern = '/' . $oldEscaped . '::/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $new . '::', $content);
                $fixed = true;
            }

            // 修復 new 語句
            $pattern = '/new\s+' . $oldEscaped . '\s*\(/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, 'new ' . $new . '(', $content);
                $fixed = true;
            }

            // 修復類型提示
            $pattern = '/:\s*' . $oldEscaped . '(\s|$)/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, ': ' . $new . '$1', $content);
                $fixed = true;
            }

            // 修復參數類型
            $pattern = '/\(\s*' . $oldEscaped . '\s+\$/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '(' . $new . ' $', $content);
                $fixed = true;
            }
        }

        if ($fixed) {
            if (file_put_contents($file, $content) === false) {
                throw new Exception("無法寫入檔案: $file");
            }
            echo "已修復檔案: $file\n";
            $this->fixedCount++;
            $this->processedFiles[] = $file;
        }
    }

    /**
     * 檢查 PHP 語法
     */
    private function isValidPhpSyntax(string $content): bool
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'php_syntax_check');
        file_put_contents($tempFile, $content);

        $output = [];
        $returnCode = 0;
        exec("php -l $tempFile 2>&1", $output, $returnCode);

        unlink($tempFile);

        return $returnCode === 0;
    }

    /**
     * 打印摘要
     */
    private function printSummary(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "執行摘要\n";
        echo str_repeat('=', 50) . "\n";
        echo "處理的檔案數: " . count($this->processedFiles) . "\n";
        echo "修復的檔案數: " . $this->fixedCount . "\n";
        echo "錯誤數: " . count($this->errors) . "\n";

        if (!empty($this->errors)) {
            echo "\n錯誤:\n";
            foreach ($this->errors as $error) {
                echo "- $error\n";
            }
        }

        if (!empty($this->processedFiles)) {
            echo "\n處理的檔案:\n";
            foreach ($this->processedFiles as $file) {
                echo "- $file\n";
            }
        }
    }
}

// 主程式執行
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['mode:']);
    $mode = $options['mode'] ?? 'validate';

    if (!in_array($mode, ['validate', 'execute'])) {
        echo "錯誤: 模式必須是 'validate' 或 'execute'\n";
        echo "使用方式: php scripts/test-fixer.php --mode=validate\n";
        echo "         php scripts/test-fixer.php --mode=execute\n";
        exit(1);
    }

    $fixer = new TestFixer();
    $success = $fixer->run($mode);

    exit($success ? 0 : 1);
}
