<?php

declare(strict_types=1);

/**
 * 修復測試中的建構子參數錯誤
 */

class TestConstructorFixer
{
    private int $fixCount = 0;
    private array $processedFiles = [];

    public function run(): void
    {
        echo "開始修復測試中的建構子參數錯誤...\n";

        $this->fixAttachmentServiceTests();
        $this->fixPostControllerTests();
        $this->fixAuthControllerTests();

        echo "\n修復完成！\n";
        echo "總修復次數: {$this->fixCount}\n";
        echo "修復的檔案數: " . count($this->processedFiles) . "\n";

        if (!empty($this->processedFiles)) {
            echo "已修復的檔案:\n";
            foreach ($this->processedFiles as $file) {
                echo "  - $file\n";
            }
        }
    }

    private function fixAttachmentServiceTests(): void
    {
        echo "修復 AttachmentService 測試...\n";

        $files = [
            '/var/www/html/tests/Unit/Services/AttachmentServiceTest.php',
            '/var/www/html/tests/Integration/AttachmentUploadTest.php',
            '/var/www/html/tests/Security/FileUploadSecurityTest.php'
        ];

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $content = file_get_contents($file);
            $originalContent = $content;

            // 添加 ActivityLoggingServiceInterface import
            if (strpos($content, 'use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;') === false) {
                $content = preg_replace(
                    '/(use [^;]+;\n)(\n(?:class|final class|abstract class))/s',
                    '$1use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;' . "\n\$2",
                    $content
                );
            }

            // 添加 activityLogger 屬性
            if (strpos($content, 'activityLogger') === false) {
                $content = preg_replace(
                    '/(protected AuthorizationService\|MockInterface \$authService;)/',
                    "$1\n\n    protected ActivityLoggingServiceInterface|MockInterface \$activityLogger;",
                    $content
                );
            }

            // 添加 activityLogger mock 初始化
            $content = preg_replace(
                '/(\$this->authService = Mockery::mock\(AuthorizationService::class\);)/',
                "$1\n        \$this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class);",
                $content
            );

            // 修復 AttachmentService 建構子調用
            $content = preg_replace(
                '/(new AttachmentService\(\s*\$this->attachmentRepo,\s*\$this->postRepo,\s*\$this->authService,)(\s*\$this->uploadDir,?\s*\))/s',
                '$1' . "\n            \$this->activityLogger," . '$2',
                $content
            );

            // 添加 activityLogger mock 期望
            if (strpos($content, '$this->activityLogger->shouldReceive') === false) {
                $content = preg_replace(
                    '/(\$this->attachmentRepo->shouldReceive\(\'delete\'\)[^;]*;)/',
                    "$1\n\n        // 設置 ActivityLoggingService mock 期望\n        \$this->activityLogger->shouldReceive('log')\n            ->andReturn();\n        \$this->activityLogger->shouldReceive('logSuccess')\n            ->andReturn();\n        \$this->activityLogger->shouldReceive('logFailure')\n            ->andReturn();",
                    $content
                );
            }

            if ($content !== $originalContent && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                $relativePath = str_replace('/var/www/html/', '', $file);
                $this->processedFiles[] = $relativePath;
                $this->fixCount++;
                echo "  ✓ 已修復: {$relativePath}\n";
            }
        }
    }

    private function fixPostControllerTests(): void
    {
        echo "修復 PostController 測試...\n";

        $testFiles = glob('/var/www/html/tests/**/PostControllerTest.php') +
            glob('/var/www/html/tests/**/*PostControllerTest.php') +
            glob('/var/www/html/tests/**/Http/PostControllerTest.php');

        foreach ($testFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $content = file_get_contents($file);
            $originalContent = $content;

            // 添加 ActivityLoggingServiceInterface import
            if (strpos($content, 'use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;') === false) {
                $content = preg_replace(
                    '/(use [^;]+;\n)(\n(?:class|final class|abstract class))/s',
                    '$1use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;' . "\n\$2",
                    $content
                );
            }

            // 添加 activityLogger 屬性
            if (strpos($content, 'activityLogger') === false) {
                $content = preg_replace(
                    '/(private OutputSanitizerInterface\|MockInterface \$sanitizer;)/',
                    "$1\n\n    private ActivityLoggingServiceInterface|MockInterface \$activityLogger;",
                    $content
                );
            }

            // 添加 activityLogger mock 初始化
            $content = preg_replace(
                '/(\$this->sanitizer = Mockery::mock\(OutputSanitizerInterface::class\);)/',
                "$1\n        \$this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class);",
                $content
            );

            // 修復 PostController 建構子調用
            $content = preg_replace(
                '/(new PostController\(\s*\$this->postService,\s*\$this->validator,\s*\$this->sanitizer,?)(\s*\))/s',
                '$1' . "\n            \$this->activityLogger," . '$2',
                $content
            );

            // 添加 activityLogger mock 期望
            if (strpos($content, '$this->activityLogger->shouldReceive') === false) {
                $content = preg_replace(
                    '/(\$this->sanitizer->shouldReceive\(\'sanitizeHtml\'\)[^;]*;)/',
                    "$1\n\n        // 設置 ActivityLoggingService mock 期望\n        \$this->activityLogger->shouldReceive('log')\n            ->andReturn();\n        \$this->activityLogger->shouldReceive('logSuccess')\n            ->andReturn();\n        \$this->activityLogger->shouldReceive('logFailure')\n            ->andReturn();",
                    $content
                );
            }

            if ($content !== $originalContent && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                $relativePath = str_replace('/var/www/html/', '', $file);
                $this->processedFiles[] = $relativePath;
                $this->fixCount++;
                echo "  ✓ 已修復: {$relativePath}\n";
            }
        }
    }

    private function fixAuthControllerTests(): void
    {
        echo "修復 AuthController 測試...\n";

        $testFiles = glob('/var/www/html/tests/**/AuthControllerTest.php');

        foreach ($testFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $content = file_get_contents($file);
            $originalContent = $content;

            // 添加正確的 AuthenticationService import
            if (
                strpos($content, 'use AlleyNote\Domains\Auth\Contracts\AuthenticationServiceInterface;') === false &&
                strpos($content, 'use App\Domains\Auth\Contracts\AuthenticationServiceInterface;') === false
            ) {
                $content = preg_replace(
                    '/(use [^;]+;\n)(\n(?:class|final class|abstract class))/s',
                    '$1use App\Domains\Auth\Contracts\AuthenticationServiceInterface;' . "\n\$2",
                    $content
                );
            }

            // 修復錯誤的 namespace
            $content = str_replace(
                'AlleyNote\Domains\Auth\Contracts\AuthenticationServiceInterface',
                'App\Domains\Auth\Contracts\AuthenticationServiceInterface',
                $content
            );

            if ($content !== $originalContent && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                $relativePath = str_replace('/var/www/html/', '', $file);
                $this->processedFiles[] = $relativePath;
                $this->fixCount++;
                echo "  ✓ 已修復: {$relativePath}\n";
            }
        }
    }

    private function isValidPhp(string $code): bool
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_fix_');
        file_put_contents($tempFile, $code);

        $result = shell_exec("php -l $tempFile 2>&1");
        unlink($tempFile);

        return strpos($result, 'No syntax errors detected') !== false;
    }
}

$fixer = new TestConstructorFixer();
$fixer->run();
