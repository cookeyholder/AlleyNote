<?php

$files = [
    'backend/app/Infrastructure/Routing/RouteDispatcher.php',
    'backend/app/Infrastructure/Services/RateLimitService.php',
    'backend/app/Infrastructure/Statistics/Repositories/StatisticsRepository.php',
    'backend/app/Infrastructure/Statistics/Repositories/UserStatisticsRepository.php',
    'backend/app/Infrastructure/Statistics/Repositories/PostStatisticsRepository.php',
    'backend/app/Infrastructure/Statistics/Services/SlowQueryMonitoringService.php',
    'backend/app/Infrastructure/Statistics/Services/StatisticsPerformanceReportGenerator.php',
    'backend/app/Infrastructure/Statistics/Services/StatisticsMonitoringService.php',
    'backend/app/Infrastructure/Statistics/Commands/StatisticsRecalculationCommand.php',
    'backend/app/Application/Services/Statistics/StatisticsQueryService.php',
    'backend/app/Application/Middleware/JwtAuthorizationMiddleware.php',
    'backend/app/Application/Controllers/Security/CSPReportController.php',
    'backend/app/Application/Controllers/Web/SwaggerController.php',
    'backend/app/Application/Controllers/Health/HealthController.php',
    'backend/app/Application/Controllers/Api/V1/IpController.php',
    'backend/app/Application/Controllers/Api/V1/PostViewController.php',
    'backend/app/Application/Controllers/Api/V1/ActivityLogController.php',
    'backend/app/Application/Controllers/Api/V1/StatisticsChartController.php',
    'backend/app/Application/Controllers/Api/V1/StatisticsController.php',
    'backend/app/Application/Controllers/Admin/CacheMonitorController.php',
    'backend/app/Application/Controllers/Admin/TagManagementController.php',
    'backend/app/Shared/Cache/Strategies/DefaultCacheStrategy.php',
    'backend/app/Shared/Cache/Repositories/RedisTagRepository.php',
    'backend/app/Shared/Cache/Services/DefaultCacheStrategy.php',
    'backend/app/Shared/Cache/Services/TaggedCacheManager.php',
    'backend/app/Shared/Cache/Services/CacheManager.php',
    'backend/app/Shared/Cache/Providers/CacheServiceProvider.php',
    'backend/app/Shared/Config/JwtConfig.php',
    'backend/app/Shared/Config/EnvironmentConfig.php',
    'backend/app/Shared/Monitoring/Services/ErrorTrackerService.php',
    'backend/app/Shared/Monitoring/Services/SystemMonitorService.php',
    'backend/app/Shared/Helpers/TimezoneHelper.php',
    'backend/app/Domains/Security/Repositories/IpRepository.php',
    'backend/app/Domains/Security/Services/IpService.php',
    'backend/app/Domains/Security/Services/Advanced/SecurityTestService.php',
    'backend/app/Domains/Security/Services/Headers/SecurityHeaderService.php',
    'backend/app/Domains/Security/Services/Core/CsrfProtectionService.php',
    'backend/app/Domains/Security/Services/Core/XssProtectionService.php',
    'backend/app/Domains/Attachment/Services/AttachmentService.php',
    'backend/app/Domains/Statistics/Entities/StatisticsSnapshot.php',
    'backend/app/Domains/Shared/ValueObjects/Timestamp.php',
    'backend/app/Domains/Post/Repositories/PostRepository.php',
    'backend/app/Domains/Post/Models/Post.php',
    'backend/app/Domains/Auth/Repositories/RoleRepository.php',
    'backend/app/Domains/Auth/Repositories/UserRepository.php',
    'backend/app/Domains/Auth/Services/AuthService.php',
    'backend/app/Domains/Auth/Services/Advanced/PwnedPasswordService.php',
    'backend/app/Domains/Auth/Services/AuthorizationService.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }

    $content = file_get_contents($file);
    $originalContent = $content;

    // 1. catch (Exception $e) -> catch (Throwable $e)
    $content = preg_replace('/catch\s*\(Exception\s+\$([a-zA-Z0-9_]+)\)/', 'catch (Throwable \$$1)', $content);

    // 2. Type hints: (Exception $e) -> (Throwable $e)
    $content = preg_replace('/(\(|,\s*)Exception\s+\$([a-zA-Z0-9_]+)/', '$1Throwable \$$2', $content);

    // 3. throw new Exception(...) -> throw new \RuntimeException(...)
    $content = preg_replace('/throw\s+new\s+Exception\(/', 'throw new \RuntimeException(', $content);

    // Check if changes were made
    if ($content === $originalContent) {
        // Still check if Throwable is used but Exception was not replaced (maybe already partially refactored)
    }

    // 4. Update imports
    // Only proceed with import management if Throwable is now used in the code
    if (preg_match('/\bThrowable\b/', $content)) {
        
        // Extract all 'use' statements
        preg_match_all('/^use\s+([^;]+);/m', $content, $matches);
        $imports = array_map('trim', $matches[1] ?? []);
        
        $hasThrowableImport = false;
        foreach ($imports as $import) {
            if ($import === 'Throwable') {
                $hasThrowableImport = true;
                break;
            }
        }
        
        if (!$hasThrowableImport) {
            $imports[] = 'Throwable';
        }
        
        // Remove 'Exception' import if Exception is no longer used
        // Check for \bException\b but exclude 'use Exception;' itself and things like 'ValidationException'
        $codeWithoutImports = preg_replace('/^use\s+[^;]+;/m', '', $content);
        if (!preg_match('/\bException\b/', $codeWithoutImports)) {
            $imports = array_filter($imports, function($i) { return trim($i) !== 'Exception'; });
        }
        
        // Deduplicate and Sort
        $imports = array_unique($imports);
        sort($imports, SORT_STRING | SORT_FLAG_CASE);
        
        $importString = "";
        foreach ($imports as $import) {
            $importString .= "use $import;\n";
        }
        
        // Find where to put imports
        if (preg_match('/namespace\s+[^;]+;\n+/', $content, $nsMatches)) {
            // Replace the contiguous block of imports after namespace
            // If no imports yet, we need to insert them
            if (preg_match('/(namespace\s+[^;]+;\n+)(use\s+[^;]+;\n+)+/', $content)) {
                $content = preg_replace('/(namespace\s+[^;]+;\n+)(use\s+[^;]+;\n+)+/', "$1$importString", $content);
            } else {
                $content = preg_replace('/(namespace\s+[^;]+;\n+)/', "$1\n$importString", $content);
            }
        } else {
            // No namespace, find first import block or just prepend
            if (preg_match('/(use\s+[^;]+;\n+)+/', $content)) {
                $content = preg_replace('/(use\s+[^;]+;\n+)+/', "$importString", $content);
            } else {
                $content = "<?php\n\n$importString\n" . ltrim($content, "<?php\n ");
            }
        }
    }

    file_put_contents($file, $content);
    echo "Processed: $file\n";
}
