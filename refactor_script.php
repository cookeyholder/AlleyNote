<?php
// Simple import sorter and refactor helper
$dir = __DIR__ . '/backend/app';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($iterator as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    $content = file_get_contents($path);
    
    // 1. Ensure use RuntimeException if thrown
    if (str_contains($content, 'throw new RuntimeException') && !str_contains($content, 'use RuntimeException;')) {
        $content = preg_replace('/namespace\s+[^;]+;/', "$0\n\nuse RuntimeException;", $content, 1);
    }
    
    // 2. Sort use statements
    if (preg_match_all('/^use\s+([^;]+);/m', $content, $matches)) {
        $uses = $matches[0];
        $classes = []; $functions = []; $consts = [];
        foreach ($matches[1] as $u) {
            $u = trim($u);
            if (str_starts_with($u, 'function ')) $functions[] = "use $u;";
            elseif (str_starts_with($u, 'const ')) $consts[] = "use $u;";
            else $classes[] = "use $u;";
        }
        sort($classes, SORT_STRING | SORT_FLAG_CASE);
        sort($functions, SORT_STRING | SORT_FLAG_CASE);
        sort($consts, SORT_STRING | SORT_FLAG_CASE);
        
        $sorted = implode("\n", array_merge($classes, $functions, $consts));
        
        $first = $uses[0];
        $last = end($uses);
        $startPos = strpos($content, $first);
        $endPos = strpos($content, $last) + strlen($last);
        
        $content = substr($content, 0, $startPos) . $sorted . substr($content, $endPos);
    }
    
    // 3. Spacing fixes
    $content = str_replace('catch (Throwable )', 'catch (Throwable)', $content);
    $content = str_replace('string $driverName ,', 'string $driverName,', $content);
    $content = preg_replace('/->with\(Mockery::any\(\)\);\s+$/m', '->with(Mockery::any());', $content);
    
    file_put_contents($path, $content);
}
echo "Refactored all files.\n";
