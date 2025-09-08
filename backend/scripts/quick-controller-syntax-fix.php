<?php

declare(strict_types=1);

/**
 * 快速 Controller 語法修復腳本
 *
 * 此腳本用於修復 Controller 檔案中的常見語法錯誤，包括：
 * - OpenAPI 屬性語法錯誤
 * - 空 try 塊問題
 * - 錯誤的 catch 塊
 * - 語法格式問題
 */

class QuickControllerSyntaxFixer
{
    private int $fixedFiles = 0;
    private int $totalFixes = 0;
    private array $fixedFilesList = [];

    public function __construct()
    {
        echo "🔧 開始快速修復 Controller 語法錯誤...\n\n";
    }

    /**
     * 執行修復
     */
    public function run(): void
    {
        $this->findAndFixControllers();
        $this->printSummary();
    }

    /**
     * 尋找並修復 Controller 檔案
     */
    private function findAndFixControllers(): void
    {
        $controllerDirs = [
            'app/Application/Controllers',
        ];

        foreach ($controllerDirs as $dir) {
            if (is_dir($dir)) {
                $this->processDirectory($dir);
            }
        }
    }

    /**
     * 處理目錄
     */
    private function processDirectory(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' && strpos($file->getFilename(), 'Controller') !== false) {
                $this->processFile($file->getPathname());
            }
        }
    }

    /**
     * 處理單個檔案
     */
    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $fixCount = 0;

        // 修復各種語法錯誤
        $content = $this->fixOpenApiSyntax($content, $fixCount);
        $content = $this->fixEmptyTryBlocks($content, $fixCount);
        $content = $this->fixBrokenCatchBlocks($content, $fixCount);
        $content = $this->fixSyntaxErrors($content, $fixCount);

        if ($fixCount > 0) {
            file_put_contents($filePath, $content);
            $this->fixedFiles++;
            $this->totalFixes += $fixCount;
            $this->fixedFilesList[] = [
                'file' => $filePath,
                'fixes' => $fixCount
            ];
            echo "✅ 修復 {$filePath}: {$fixCount} 個修復\n";
        }
    }

    /**
     * 修復 OpenAPI 屬性語法
     */
    private function fixOpenApiSyntax(string $content, int &$fixCount): string
    {
        // 修復 OpenAPI 屬性中的 => 語法錯誤
        $patterns = [
            '/version => /' => 'version: ',
            '/title => /' => 'title: ',
            '/description => /' => 'description: ',
            '/url => /' => 'url: ',
            '/schema => /' => 'schema: ',
            '/name => /' => 'name: ',
            '/path => /' => 'path: ',
            '/summary => /' => 'summary: ',
            '/tags => /' => 'tags: ',
            '/response => /' => 'response: ',
            '/property => /' => 'property: ',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
            if ($newContent !== null && $count > 0) {
                $content = $newContent;
                $fixCount += $count;
            }
        }

        // 修復 OpenAPI 屬性結尾的逗號問題
        $content = preg_replace('/,(\s*)\)(\s*)\]/', '$1)$2]', $content, -1, $count);
        if ($count > 0) {
            $fixCount += $count;
        }

        return $content;
    }

    /**
     * 修復空 try 塊
     */
    private function fixEmptyTryBlocks(string $content, int &$fixCount): string
    {
        // 修復 try { /* empty */ } 模式
        $content = preg_replace('/try\s*\{\s*\/\*\s*empty\s*\*\/\s*\}/', 'try {', $content, -1, $count);
        if ($count > 0) {
            $fixCount += $count;
        }

        return $content;
    }

    /**
     * 修復錯誤的 catch 塊
     */
    private function fixBrokenCatchBlocks(string $content, int &$fixCount): string
    {
        $lines = explode("\n", $content);
        $modified = false;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // 修復 } // catch block commented out due to syntax error
            if (strpos($line, '} // catch block commented out due to syntax error') !== false) {
                $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
                $lines[$i] = $indent . '} catch (Exception $e) {';

                // 添加基本的錯誤處理
                $errorHandling = [
                    $indent . '    $this->logger?->error(\'操作失敗\', [',
                    $indent . '        \'error\' => $e->getMessage(),',
                    $indent . '    ]);',
                    $indent . '',
                    $indent . '    return $this->json($response, [',
                    $indent . '        \'success\' => false,',
                    $indent . '        \'error\' => [',
                    $indent . '            \'message\' => \'操作失敗\',',
                    $indent . '            \'details\' => $e->getMessage(),',
                    $indent . '        ],',
                    $indent . '        \'timestamp\' => time(),',
                    $indent . '    ], 500);',
                    $indent . '}'
                ];

                array_splice($lines, $i + 1, 0, $errorHandling);
                $modified = true;
                $fixCount++;
                break; // 只修復第一個，避免複雜問題
            }

            // 修復錯誤的內聯 catch 語法
            if (preg_match('/\} catch \(Exception \$e\) \{ return \$this->json\(\$response, \[/', $line)) {
                $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
                $lines[$i] = $indent . '} catch (Exception $e) {';

                $errorHandling = [
                    $indent . '    return $this->json($response, [',
                    $indent . '        \'success\' => false,',
                    $indent . '        \'error\' => [',
                    $indent . '            \'message\' => \'操作失敗\',',
                    $indent . '            \'details\' => $e->getMessage(),',
                    $indent . '        ],',
                    $indent . '        \'timestamp\' => time(),',
                    $indent . '    ], 500);',
                    $indent . '}'
                ];

                array_splice($lines, $i + 1, 0, $errorHandling);
                $modified = true;
                $fixCount++;
                break;
            }
        }

        if ($modified) {
            $content = implode("\n", $lines);
        }

        return $content;
    }

    /**
     * 修復其他語法錯誤
     */
    private function fixSyntaxErrors(string $content, int &$fixCount): string
    {
        // 修復多餘的括號和逗號
        $patterns = [
            // 修復 query_params 後面多餘的括號
            '/\'query_params\' => \$request->getQueryParams\(\)\]\),/' => '\'query_params\' => $request->getQueryParams(),',
            '/\'trace\' => \$e->getTraceAsString\(\)\]\),/' => '\'trace\' => $e->getTraceAsString(),',

            // 修復陣列語法錯誤
            '/\]\)\,/' => '],',
            '/\}\)\,/' => '},',

            // 修復 == == 語法錯誤
            '/== ==/' => '===',

            // 修復缺少分號
            '/\]\)\s*\n/' => ']);\n',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
            if ($newContent !== null && $count > 0) {
                $content = $newContent;
                $fixCount += $count;
            }
        }

        // 修復特殊的語法錯誤模式
        $lines = explode("\n", $content);
        $modified = false;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // 修復錯誤的陣列訪問語法
            if (strpos($line, 'count((array]) ') !== false) {
                $lines[$i] = str_replace('count((array]) ', 'count((array) ', $line);
                $modified = true;
                $fixCount++;
            }

            // 修復錯誤的條件語法
            if (preg_match('/\$[a-zA-Z_]+\s*==\s*==\s*\'/', $line)) {
                $lines[$i] = preg_replace('/==\s*==/', '===', $line);
                $modified = true;
                $fixCount++;
            }
        }

        if ($modified) {
            $content = implode("\n", $lines);
        }

        return $content;
    }

    /**
     * 列印摘要報告
     */
    private function printSummary(): void
    {
        echo "\n📊 修復報告:\n";
        echo "總共修復了 {$this->fixedFiles} 個 Controller 檔案中的 {$this->totalFixes} 個語法錯誤\n\n";

        if (!empty($this->fixedFilesList)) {
            echo "修復詳情:\n";
            foreach ($this->fixedFilesList as $fileInfo) {
                echo "  {$fileInfo['file']}: {$fileInfo['fixes']} 個修復\n";
            }
        }

        echo "\n✅ 修復完成！建議執行 PHPStan 和測試檢查結果。\n";
    }
}

// 執行修復
$fixer = new QuickControllerSyntaxFixer();
$fixer->run();
