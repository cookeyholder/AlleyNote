<?php
/**
 * 修復 PHPUnit 11.5 的 deprecation warnings
 * 將 doc-comment 中的 @covers, @dataProvider 等 metadata 轉換為 PHPUnit Attributes
 */

declare(strict_types=1);

$testsDirectory = dirname(__DIR__) . '/backend/tests';

function processTestFile(string $filePath): void
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "無法讀取檔案: $filePath\n";
        return;
    }

    $originalContent = $content;
    $modified = false;

    // 處理 @covers annotation
    if (preg_match('/^\s*\*\s*@covers\s+(.+)$/m', $content, $matches)) {
        $coverageClass = trim($matches[1]);

        // 移除 @covers annotation
        $content = preg_replace('/^\s*\*\s*@covers\s+.+$/m', '', $content);

        // 在 class 之前加上 #[CoversClass] attribute
        $content = preg_replace(
            '/(final class [a-zA-Z_][a-zA-Z0-9_]* extends TestCase)/m',
            "#[CoversClass($coverageClass)]\nfinal class",
            $content
        );

        // 確保引用 CoversClass
        if (strpos($content, 'use PHPUnit\Framework\Attributes\CoversClass;') === false) {
            $content = preg_replace(
                '/(use PHPUnit\\\\Framework\\\\TestCase;)/m',
                "$1\nuse PHPUnit\\Framework\\Attributes\\CoversClass;",
                $content
            );
        }

        $modified = true;
        echo "修正 @covers in $filePath\n";
    }

    // 處理 @dataProvider annotation
    while (preg_match('/^\s*\*\s*@dataProvider\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*$/m', $content, $matches)) {
        $providerMethod = trim($matches[1]);

        // 找到對應的測試方法
        $pattern = '/(\s*\/\*\*.*?\*\s*@dataProvider\s+' . preg_quote($providerMethod) . '\s*.*?\*\/\s*)(public function\s+[a-zA-Z_][a-zA-Z0-9_]*\s*\()/s';
        if (preg_match($pattern, $content, $methodMatches)) {
            // 移除 @dataProvider annotation
            $cleanedComment = preg_replace('/^\s*\*\s*@dataProvider\s+.+$/m', '', $methodMatches[1]);

            // 如果 comment 只剩下空行，就完全移除
            if (preg_match('/^\s*\/\*\*\s*(\*\s*)*\*\/\s*$/s', $cleanedComment)) {
                $cleanedComment = '';
            }

            // 在方法前加上 #[DataProvider] attribute
            $replacement = $cleanedComment . "#[DataProvider('$providerMethod')]\n    " . $methodMatches[2];
            $content = str_replace($methodMatches[0], $replacement, $content);

            // 確保引用 DataProvider
            if (strpos($content, 'use PHPUnit\Framework\Attributes\DataProvider;') === false) {
                $content = preg_replace(
                    '/(use PHPUnit\\\\Framework\\\\TestCase;)/m',
                    "$1\nuse PHPUnit\\Framework\\Attributes\\DataProvider;",
                    $content
                );
            }

            $modified = true;
            echo "修正 @dataProvider $providerMethod in $filePath\n";
        }
    }

    // 清理空的 doc-comments
    $content = preg_replace('/\s*\/\*\*\s*\*\s*\*\/\s*\n/', "\n", $content);
    $content = preg_replace('/\s*\/\*\*\s*\*\/\s*\n/', "\n", $content);

    if ($modified && $content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "已更新檔案: $filePath\n";
    }
}

function scanDirectory(string $directory): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php' && str_ends_with($file->getFilename(), 'Test.php')) {
            processTestFile($file->getPathname());
        }
    }
}

echo "開始修復 PHPUnit deprecations...\n";
scanDirectory($testsDirectory);
echo "完成!\n";
