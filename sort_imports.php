<?php
$dir = __DIR__ . '/backend/app';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($iterator as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    $content = file_get_contents($path);
    if (!preg_match('/namespace\s+[^;]+;/', $content)) continue;

    // Extract use statements
    if (preg_match_all('/^use\s+([^;]+);/m', $content, $matches)) {
        $uses = $matches[0];
        $original = implode("\n", $uses);
        
        // Split into classes, functions, and constants for PHP CS alpha sort
        $classes = []; $functions = []; $consts = [];
        foreach ($matches[1] as $u) {
            if (str_starts_with($u, 'function ')) $functions[] = "use $u;";
            elseif (str_starts_with($u, 'const ')) $consts[] = "use $u;";
            else $classes[] = "use $u;";
        }
        sort($classes, SORT_STRING | SORT_FLAG_CASE);
        sort($functions, SORT_STRING | SORT_FLAG_CASE);
        sort($consts, SORT_STRING | SORT_FLAG_CASE);
        
        $sorted = implode("\n", array_merge($classes, $functions, $consts));
        
        if ($original !== $sorted) {
            // Find the first and last use statement block
            $first = $uses[0];
            $last = end($uses);
            $startPos = strpos($content, $first);
            $endPos = strpos($content, $last) + strlen($last);
            
            $newContent = substr($content, 0, $startPos) . $sorted . substr($content, $endPos);
            file_put_contents($path, $newContent);
            echo "Sorted imports in $path\n";
        }
    }
}
