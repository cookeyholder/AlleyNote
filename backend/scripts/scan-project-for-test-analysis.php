<?php

declare(strict_types=1);

/**
 * AlleyNote 專案測試錯誤分析掃描器
 *
 * 專門針對 PHPStan 測試錯誤分析，產生詳細的快照包括：
 * - 測試檔案與原始檔案的對應關係
 * - PHPStan 錯誤類型分析
 * - 測試模式與反模式檢測
 * - 型別安全問題識別
 * - 測試重構建議
 */

class TestErrorAnalysisScanner
{
    private string $projectRoot;
    private array $snapshot = [];
    private array $testFiles = [];
    private array $sourceFiles = [];
    private array $testToSourceMap = [];
    private array $phpstanErrors = [];
    private array $errorPatterns = [];
    private array $refactoringSuggestions = [];

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/');
    }

    public function generateTestAnalysis(): void
    {
        echo "🧪 開始掃描 AlleyNote 測試錯誤分析...\n\n";

        // 1. 掃描測試檔案結構
        $this->scanTestStructure();

        // 2. 分析測試檔案與原始檔案的關係
        $this->analyzeTestSourceMapping();

        // 3. 收集 PHPStan 錯誤
        $this->collectPHPStanErrors();

        // 4. 分析錯誤模式
        $this->analyzeErrorPatterns();

        // 5. 檢測測試反模式
        $this->detectTestAntiPatterns();

        // 6. 分析型別安全問題
        $this->analyzeTypeSafetyIssues();

        // 7. 生成重構建議
        $this->generateRefactoringSuggestions();

        // 8. 生成測試分析報告
        $this->generateTestReport();

        echo "\n✅ 測試錯誤分析快照已生成！\n";
    }

    private function scanTestStructure(): void
    {
        echo "📁 掃描測試檔案結構...\n";

        $this->snapshot['scan_info'] = [
            'project' => 'AlleyNote',
            'scan_time' => date('Y-m-d H:i:s'),
            'scan_type' => 'test_error_analysis',
            'phpstan_level' => 10
        ];

        // 掃描測試目錄
        $testPath = $this->projectRoot . '/tests';
        if (is_dir($testPath)) {
            $this->scanTestDirectory($testPath);
        }

        // 掃描主要原始碼目錄
        $appPath = $this->projectRoot . '/app';
        if (is_dir($appPath)) {
            $this->scanSourceDirectory($appPath);
        }

        echo "   - 發現 " . count($this->testFiles) . " 個測試檔案\n";
        echo "   - 發現 " . count($this->sourceFiles) . " 個原始檔案\n";
    }

    private function scanTestDirectory(string $path): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $relativePath = str_replace($this->projectRoot . '/', '', $file->getPathname());
                $this->testFiles[$relativePath] = $this->analyzeTestFile($file->getPathname());
            }
        }
    }

    private function scanSourceDirectory(string $path): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $relativePath = str_replace($this->projectRoot . '/', '', $file->getPathname());
                $this->sourceFiles[$relativePath] = $this->analyzeSourceFile($file->getPathname());
            }
        }
    }

    private function analyzeTestFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if (!$content) return [];

        return [
            'type' => 'test',
            'namespace' => $this->extractNamespace($content),
            'test_class' => $this->extractTestClass($content),
            'test_methods' => $this->extractTestMethods($content),
            'assertions' => $this->extractAssertions($content),
            'mocks' => $this->extractMockUsage($content),
            'data_providers' => $this->extractDataProviders($content),
            'dependencies' => $this->extractUseStatements($content),
            'phpunit_attributes' => $this->extractPHPUnitAttributes($content),
            'complexity_indicators' => $this->analyzeTestComplexity($content),
            'potential_issues' => $this->identifyPotentialTestIssues($content)
        ];
    }

    private function analyzeSourceFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if (!$content) return [];

        return [
            'type' => 'source',
            'namespace' => $this->extractNamespace($content),
            'classes' => $this->extractClasses($content),
            'interfaces' => $this->extractInterfaces($content),
            'public_methods' => $this->extractPublicMethods($content),
            'dependencies' => $this->extractUseStatements($content),
            'type_hints' => $this->extractTypeHints($content),
            'testability_score' => $this->calculateTestabilityScore($content)
        ];
    }

    private function extractTestClass(string $content): ?string
    {
        if (preg_match('/class\s+(\w*Test\w*)/i', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function extractTestMethods(string $content): array
    {
        preg_match_all('/(?:public\s+)?function\s+(test\w+|.*Test)\s*\(/i', $content, $matches);
        $methods = $matches[1] ?? [];

        // 也尋找有 @test 註解的方法
        preg_match_all('/@test[^\/]*function\s+(\w+)/i', $content, $testAnnotationMatches);
        if (!empty($testAnnotationMatches[1])) {
            $methods = array_merge($methods, $testAnnotationMatches[1]);
        }

        return array_unique($methods);
    }

    private function extractAssertions(string $content): array
    {
        $assertions = [];

        // 常見的 PHPUnit 斷言
        $assertionPatterns = [
            'assertEquals', 'assertSame', 'assertTrue', 'assertFalse',
            'assertNull', 'assertNotNull', 'assertEmpty', 'assertNotEmpty',
            'assertInstanceOf', 'assertIsArray', 'assertIsString', 'assertIsInt',
            'assertGreaterThan', 'assertLessThan', 'assertContains', 'assertCount'
        ];

        foreach ($assertionPatterns as $assertion) {
            $count = preg_match_all('/\$this->' . $assertion . '\s*\(/i', $content);
            if ($count > 0) {
                $assertions[$assertion] = $count;
            }
        }

        return $assertions;
    }

    private function extractMockUsage(string $content): array
    {
        $mocks = [];

        // 檢測 mock 的使用
        if (preg_match_all('/createMock\s*\(\s*([^)]+)\s*\)/i', $content, $matches)) {
            $mocks['createMock'] = $matches[1];
        }

        if (preg_match_all('/getMockBuilder\s*\(\s*([^)]+)\s*\)/i', $content, $matches)) {
            $mocks['getMockBuilder'] = $matches[1];
        }

        if (preg_match_all('/Mockery::mock\s*\(\s*([^)]+)\s*\)/i', $content, $matches)) {
            $mocks['mockery'] = $matches[1];
        }

        return $mocks;
    }

    private function extractDataProviders(string $content): array
    {
        preg_match_all('/@dataProvider\s+(\w+)/i', $content, $matches);
        return $matches[1] ?? [];
    }

    private function extractPHPUnitAttributes(string $content): array
    {
        $attributes = [];

        // PHP 8 attributes
        if (preg_match_all('/#\[(\w+)/i', $content, $matches)) {
            $attributes['php8_attributes'] = array_unique($matches[1]);
        }

        // DocBlock annotations
        if (preg_match_all('/@(\w+)/i', $content, $matches)) {
            $attributes['docblock_annotations'] = array_unique($matches[1]);
        }

        return $attributes;
    }

    private function analyzeTestComplexity(string $content): array
    {
        return [
            'line_count' => substr_count($content, "\n") + 1,
            'method_count' => count($this->extractTestMethods($content)),
            'assertion_count' => array_sum($this->extractAssertions($content)),
            'cyclomatic_complexity' => $this->estimateCyclomaticComplexity($content),
            'nested_level' => $this->calculateMaxNestingLevel($content)
        ];
    }

    private function identifyPotentialTestIssues(string $content): array
    {
        $issues = [];

        // 檢查常見的測試問題
        if (strpos($content, 'sleep(') !== false) {
            $issues[] = 'uses_sleep_in_test';
        }

        if (preg_match('/is_array\s*\(\s*\$\w+\s*\)/', $content)) {
            $issues[] = 'redundant_is_array_check';
        }

        if (preg_match('/isset\s*\(\s*\$\w+\[/', $content)) {
            $issues[] = 'potentially_redundant_isset';
        }

        if (strpos($content, '@expectedException') !== false) {
            $issues[] = 'deprecated_expectedException_annotation';
        }

        if (preg_match('/assertSame\s*\(\s*true\s*,/', $content)) {
            $issues[] = 'should_use_assertTrue';
        }

        if (preg_match('/assertSame\s*\(\s*false\s*,/', $content)) {
            $issues[] = 'should_use_assertFalse';
        }

        return $issues;
    }

    private function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    private function extractClasses(string $content): array
    {
        preg_match_all('/(?:abstract\s+)?class\s+(\w+)/i', $content, $matches);
        return $matches[1] ?? [];
    }

    private function extractInterfaces(string $content): array
    {
        preg_match_all('/interface\s+(\w+)/i', $content, $matches);
        return $matches[1] ?? [];
    }

    private function extractPublicMethods(string $content): array
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(/i', $content, $matches);
        return $matches[1] ?? [];
    }

    private function extractUseStatements(string $content): array
    {
        preg_match_all('/use\s+([^;]+);/', $content, $matches);
        $uses = [];
        foreach ($matches[1] as $use) {
            $uses[] = trim($use);
        }
        return $uses;
    }

    private function extractTypeHints(string $content): array
    {
        $typeHints = [];

        // 方法參數型別提示
        if (preg_match_all('/function\s+\w+\s*\([^)]*(\w+)\s+\$\w+/i', $content, $matches)) {
            $typeHints['parameter_types'] = array_unique($matches[1]);
        }

        // 回傳型別提示
        if (preg_match_all('/\):\s*(\w+|\?[\w\\\\]+|[\w\\\\]+)/i', $content, $matches)) {
            $typeHints['return_types'] = array_unique($matches[1]);
        }

        return $typeHints;
    }

    private function calculateTestabilityScore(string $content): int
    {
        $score = 100;

        // 靜態方法使用 (降低可測試性)
        $staticCalls = preg_match_all('/\w+::\w+\s*\(/i', $content);
        $score -= min($staticCalls * 5, 30);

        // 全域變數使用
        $globalUsage = preg_match_all('/\$GLOBALS|\$_GET|\$_POST|\$_SESSION/i', $content);
        $score -= min($globalUsage * 10, 40);

        // new 關鍵字使用 (如果沒有依賴注入)
        $newUsage = preg_match_all('/new\s+\w+\s*\(/i', $content);
        $score -= min($newUsage * 3, 25);

        // 建構子注入 (增加可測試性)
        if (strpos($content, '__construct') !== false && strpos($content, '$') !== false) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    private function estimateCyclomaticComplexity(string $content): int
    {
        $complexity = 1; // 基準複雜度

        // 計算決策點
        $complexity += preg_match_all('/\bif\s*\(/i', $content);
        $complexity += preg_match_all('/\belse\s*\(/i', $content);
        $complexity += preg_match_all('/\belseif\s*\(/i', $content);
        $complexity += preg_match_all('/\bfor\s*\(/i', $content);
        $complexity += preg_match_all('/\bforeach\s*\(/i', $content);
        $complexity += preg_match_all('/\bwhile\s*\(/i', $content);
        $complexity += preg_match_all('/\bcase\s+/i', $content);
        $complexity += preg_match_all('/\bcatch\s*\(/i', $content);
        $complexity += preg_match_all('/\&\&|\|\|/i', $content);

        return $complexity;
    }

    private function calculateMaxNestingLevel(string $content): int
    {
        $maxLevel = 0;
        $currentLevel = 0;

        for ($i = 0; $i < strlen($content); $i++) {
            if ($content[$i] === '{') {
                $currentLevel++;
                $maxLevel = max($maxLevel, $currentLevel);
            } elseif ($content[$i] === '}') {
                $currentLevel--;
            }
        }

        return $maxLevel;
    }

    private function analyzeTestSourceMapping(): void
    {
        echo "🔗 分析測試與原始檔案對應關係...\n";

        foreach ($this->testFiles as $testFile => $testData) {
            $potentialSources = $this->findPotentialSourceFiles($testFile, $testData);
            $this->testToSourceMap[$testFile] = $potentialSources;
        }

        echo "   - 建立了 " . count($this->testToSourceMap) . " 個測試對應關係\n";
    }

    private function findPotentialSourceFiles(string $testFile, array $testData): array
    {
        $potentialSources = [];

        // 基於檔案名稱模式匹配
        $testClassName = $testData['test_class'] ?? '';
        if ($testClassName) {
            $sourceClassName = preg_replace('/Test$/', '', $testClassName);

            foreach ($this->sourceFiles as $sourceFile => $sourceData) {
                $sourceClasses = $sourceData['classes'] ?? [];
                if (in_array($sourceClassName, $sourceClasses)) {
                    $potentialSources[] = [
                        'file' => $sourceFile,
                        'match_type' => 'class_name',
                        'confidence' => 90
                    ];
                }
            }
        }

        // 基於命名空間匹配
        $testNamespace = $testData['namespace'] ?? '';
        if ($testNamespace) {
            $sourceNamespace = str_replace('\\Tests\\', '\\', $testNamespace);
            $sourceNamespace = preg_replace('/\\\\Test$/', '', $sourceNamespace);

            foreach ($this->sourceFiles as $sourceFile => $sourceData) {
                $sourceDataNamespace = $sourceData['namespace'] ?? '';
                if ($sourceDataNamespace === $sourceNamespace) {
                    $potentialSources[] = [
                        'file' => $sourceFile,
                        'match_type' => 'namespace',
                        'confidence' => 75
                    ];
                }
            }
        }

        return $potentialSources;
    }

    private function collectPHPStanErrors(): void
    {
        echo "🔍 收集 PHPStan 錯誤...\n";

        // 先嘗試收集整個專案的 PHPStan 錯誤 (JSON 格式)
        $command = './vendor/bin/phpstan analyse --memory-limit=1G --error-format=json';
        $output = shell_exec("cd /var/www/html && $command 2>/dev/null");

        if ($output) {
            $phpstanResult = json_decode($output, true);
            if ($phpstanResult && isset($phpstanResult['files'])) {
                // 轉換 PHPStan JSON 格式為我們的格式
                foreach ($phpstanResult['files'] as $filePath => $fileData) {
                    if (isset($fileData['messages']) && !empty($fileData['messages'])) {
                        $this->phpstanErrors[$filePath] = $fileData['messages'];
                    }
                }
                echo "   - 成功從 JSON 格式收集錯誤\n";
            }
        }

        // 如果 JSON 格式失敗，嘗試收集純文字格式
        if (empty($this->phpstanErrors)) {
            echo "   - JSON 格式失敗，嘗試純文字格式...\n";
            $this->collectPHPStanErrorsText();
        }

        $totalErrors = 0;
        foreach ($this->phpstanErrors as $errors) {
            $totalErrors += count($errors);
        }

        echo "   - 收集了來自 " . count($this->phpstanErrors) . " 個檔案的 {$totalErrors} 個錯誤\n";
    }

    private function collectPHPStanErrorsText(): void
    {
        // 收集整個專案的 PHPStan 錯誤 (純文字格式)
        $command = './vendor/bin/phpstan analyse --memory-limit=1G';
        $output = shell_exec("cd /var/www/html && $command 2>&1");

        if ($output) {
            $this->parsePhpstanTextOutput($output);
            echo "   - 從純文字格式解析錯誤\n";
        } else {
            echo "   - 無法執行 PHPStan 或沒有輸出\n";
        }
    }

    private function parsePhpstanTextOutput(string $output): void
    {
        $lines = explode("\n", $output);
        $currentFile = null;
        $currentLine = null;
        $errors = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // 檢測錯誤統計行 (如: Found 3723 errors)
            if (preg_match('/Found (\d+) errors?/i', $line, $matches)) {
                echo "   - 找到總共 {$matches[1]} 個錯誤\n";
                continue;
            }

            // 跳過進度條和其他資訊行
            if (preg_match('/^\s*\d+\/\d+\s*\[/', $line) ||
                strpos($line, 'Note: Using configuration file') !== false ||
                strpos($line, '🪪') !== false ||
                strpos($line, '💡') !== false ||
                empty($line)) {
                continue;
            }

            // 檢測檔案路徑和行號的行 (格式: /path/to/file.php:123)
            if (preg_match('/^(.+\.php):(\d+)$/', $line, $matches)) {
                $currentFile = $matches[1];
                $currentLine = (int)$matches[2];
                continue;
            }

            // 檢測錯誤訊息行 (通常有縮排並包含錯誤描述)
            if ($currentFile && $currentLine && preg_match('/^\s+(.+)$/', $line, $matches)) {
                $message = trim($matches[1]);

                // 跳過空行和非錯誤訊息的行
                if (empty($message)) {
                    continue;
                }

                // 如果訊息包含 'tip:' 或 'identifier:' 則可能是額外資訊，也要記錄
                if (!isset($errors[$currentFile])) {
                    $errors[$currentFile] = [];
                }

                $errors[$currentFile][] = [
                    'line' => $currentLine,
                    'message' => $message
                ];

                // 重置當前行，準備下一個錯誤
                $currentLine = null;
            }
        }

        $this->phpstanErrors = $errors;
    }

    private function analyzeErrorPatterns(): void
    {
        echo "📊 分析錯誤模式...\n";

        $errorTypeCount = [];
        $errorsByFile = [];

        foreach ($this->phpstanErrors as $file => $errors) {
            $errorsByFile[$file] = count($errors);

            foreach ($errors as $error) {
                $message = $error['message'] ?? '';
                $errorType = $this->categorizeError($message);
                $errorTypeCount[$errorType] = ($errorTypeCount[$errorType] ?? 0) + 1;
            }
        }

        $this->errorPatterns = [
            'error_type_distribution' => $errorTypeCount,
            'errors_by_file' => $errorsByFile,
            'most_common_errors' => $this->findMostCommonErrors(),
            'test_specific_patterns' => $this->identifyTestSpecificPatterns()
        ];

        echo "   - 識別了 " . count($errorTypeCount) . " 種錯誤類型\n";
    }

    private function categorizeError(string $message): string
    {
        // PHPStan Level 10 常見錯誤類型分類
        if (strpos($message, 'no value type specified in iterable type') !== false) {
            return 'missing_iterable_value_type';
        }

        if (strpos($message, 'is_array()') !== false && strpos($message, 'will always evaluate to true') !== false) {
            return 'redundant_is_array_check';
        }

        if (strpos($message, 'isset()') !== false && strpos($message, 'always exists') !== false) {
            return 'redundant_isset_check';
        }

        if (strpos($message, 'will always evaluate to true') !== false) {
            return 'always_true_condition';
        }

        if (strpos($message, 'will always evaluate to false') !== false) {
            return 'always_false_condition';
        }

        if (strpos($message, 'alreadyNarrowedType') !== false) {
            return 'already_narrowed_type';
        }

        if (strpos($message, 'impossibleType') !== false) {
            return 'impossible_type';
        }

        if (strpos($message, 'expects') !== false && strpos($message, 'given') !== false) {
            return 'type_mismatch';
        }

        if (strpos($message, 'should return') !== false && strpos($message, 'but returns') !== false) {
            return 'return_type_mismatch';
        }

        if (strpos($message, 'does not accept') !== false) {
            return 'parameter_type_mismatch';
        }

        if (strpos($message, 'Cannot cast') !== false) {
            return 'unsafe_type_cast';
        }

        if (strpos($message, 'Offset') !== false && strpos($message, 'might not exist') !== false) {
            return 'potential_undefined_offset';
        }

        if (strpos($message, 'Property') !== false && strpos($message, 'does not accept') !== false) {
            return 'property_type_mismatch';
        }

        if (strpos($message, 'Method') !== false && strpos($message, 'should return') !== false) {
            return 'method_return_type_issue';
        }

        return 'other';
    }

    private function findMostCommonErrors(): array
    {
        $errorMessages = [];

        foreach ($this->phpstanErrors as $file => $errors) {
            foreach ($errors as $error) {
                $message = $error['message'] ?? '';
                // 正規化錯誤訊息以找出模式
                $normalizedMessage = $this->normalizeErrorMessage($message);
                $errorMessages[$normalizedMessage] = ($errorMessages[$normalizedMessage] ?? 0) + 1;
            }
        }

        arsort($errorMessages);
        return array_slice($errorMessages, 0, 10, true);
    }

    private function normalizeErrorMessage(string $message): string
    {
        // 移除變數名稱和具體的值，保留錯誤模式
        $normalized = preg_replace('/\$\w+/', '$VAR', $message);
        $normalized = preg_replace('/\b\d+\b/', 'NUM', $normalized);
        $normalized = preg_replace('/\'[^\']*\'/', 'STRING', $normalized);
        $normalized = preg_replace('/[A-Z][a-zA-Z0-9_\\\\]*::[a-zA-Z0-9_]+/', 'CLASS::METHOD', $normalized);

        return $normalized;
    }

    private function identifyTestSpecificPatterns(): array
    {
        return [
            'phpunit_assertion_issues' => $this->findPhpunitAssertionIssues(),
            'mock_related_errors' => $this->findMockRelatedErrors(),
            'data_provider_issues' => $this->findDataProviderIssues(),
            'test_double_problems' => $this->findTestDoubleProblems()
        ];
    }

    private function findPhpunitAssertionIssues(): array
    {
        $issues = [];

        foreach ($this->phpstanErrors as $file => $errors) {
            foreach ($errors as $error) {
                $message = $error['message'] ?? '';
                if (strpos($message, 'Assert::') !== false || strpos($message, 'assert') !== false) {
                    $issues[] = [
                        'file' => $file,
                        'line' => $error['line'] ?? 0,
                        'message' => $message
                    ];
                }
            }
        }

        return $issues;
    }

    private function findMockRelatedErrors(): array
    {
        $issues = [];

        foreach ($this->phpstanErrors as $file => $errors) {
            foreach ($errors as $error) {
                $message = $error['message'] ?? '';
                if (strpos($message, 'Mock') !== false || strpos($message, 'Stub') !== false) {
                    $issues[] = [
                        'file' => $file,
                        'line' => $error['line'] ?? 0,
                        'message' => $message
                    ];
                }
            }
        }

        return $issues;
    }

    private function findDataProviderIssues(): array
    {
        $issues = [];

        foreach ($this->testFiles as $file => $testData) {
            if (!empty($testData['data_providers'])) {
                foreach ($this->phpstanErrors[$file] ?? [] as $error) {
                    $message = $error['message'] ?? '';
                    if (strpos($message, 'dataProvider') !== false || strpos($message, 'array') !== false) {
                        $issues[] = [
                            'file' => $file,
                            'line' => $error['line'] ?? 0,
                            'message' => $message,
                            'data_providers' => $testData['data_providers']
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    private function findTestDoubleProblems(): array
    {
        $issues = [];

        foreach ($this->testFiles as $file => $testData) {
            if (!empty($testData['mocks'])) {
                foreach ($this->phpstanErrors[$file] ?? [] as $error) {
                    $issues[] = [
                        'file' => $file,
                        'line' => $error['line'] ?? 0,
                        'message' => $error['message'] ?? '',
                        'mock_usage' => $testData['mocks']
                    ];
                }
            }
        }

        return $issues;
    }

    private function detectTestAntiPatterns(): void
    {
        echo "🚨 檢測測試反模式...\n";

        $antiPatterns = [];

        foreach ($this->testFiles as $file => $testData) {
            $fileAntiPatterns = [];

            // 檢測各種測試反模式
            $fileAntiPatterns = array_merge($fileAntiPatterns, $this->checkAssertionAntiPatterns($testData));
            $fileAntiPatterns = array_merge($fileAntiPatterns, $this->checkTestStructureAntiPatterns($testData));
            $fileAntiPatterns = array_merge($fileAntiPatterns, $this->checkMockAntiPatterns($testData));
            $fileAntiPatterns = array_merge($fileAntiPatterns, $this->checkDataProviderAntiPatterns($testData));

            if (!empty($fileAntiPatterns)) {
                $antiPatterns[$file] = $fileAntiPatterns;
            }
        }

        $this->snapshot['anti_patterns'] = $antiPatterns;
        echo "   - 在 " . count($antiPatterns) . " 個檔案中發現反模式\n";
    }

    private function checkAssertionAntiPatterns(array $testData): array
    {
        $patterns = [];

        // 檢查過度使用特定斷言
        $assertions = $testData['assertions'] ?? [];
        foreach ($assertions as $assertion => $count) {
            if ($count > 50) {
                $patterns[] = [
                    'type' => 'excessive_assertion_usage',
                    'assertion' => $assertion,
                    'count' => $count,
                    'severity' => 'medium'
                ];
            }
        }

        // 檢查潛在的斷言問題
        if (in_array('redundant_is_array_check', $testData['potential_issues'] ?? [])) {
            $patterns[] = [
                'type' => 'redundant_type_check',
                'description' => 'Using is_array() on known arrays',
                'severity' => 'low'
            ];
        }

        return $patterns;
    }

    private function checkTestStructureAntiPatterns(array $testData): array
    {
        $patterns = [];

        $complexity = $testData['complexity_indicators'] ?? [];

        // 測試方法過多
        if (($complexity['method_count'] ?? 0) > 20) {
            $patterns[] = [
                'type' => 'too_many_test_methods',
                'count' => $complexity['method_count'],
                'severity' => 'medium'
            ];
        }

        // 循環複雜度過高
        if (($complexity['cyclomatic_complexity'] ?? 0) > 10) {
            $patterns[] = [
                'type' => 'high_cyclomatic_complexity',
                'complexity' => $complexity['cyclomatic_complexity'],
                'severity' => 'high'
            ];
        }

        // 巢狀層級過深
        if (($complexity['nested_level'] ?? 0) > 5) {
            $patterns[] = [
                'type' => 'deep_nesting',
                'level' => $complexity['nested_level'],
                'severity' => 'medium'
            ];
        }

        return $patterns;
    }

    private function checkMockAntiPatterns(array $testData): array
    {
        $patterns = [];

        $mocks = $testData['mocks'] ?? [];

        // 過度使用 mock
        $totalMocks = 0;
        foreach ($mocks as $mockType => $mockList) {
            $totalMocks += count($mockList);
        }

        if ($totalMocks > 10) {
            $patterns[] = [
                'type' => 'excessive_mocking',
                'mock_count' => $totalMocks,
                'severity' => 'medium'
            ];
        }

        return $patterns;
    }

    private function checkDataProviderAntiPatterns(array $testData): array
    {
        $patterns = [];

        $dataProviders = $testData['data_providers'] ?? [];

        // 過多的 data provider
        if (count($dataProviders) > 5) {
            $patterns[] = [
                'type' => 'too_many_data_providers',
                'count' => count($dataProviders),
                'severity' => 'low'
            ];
        }

        return $patterns;
    }

    private function analyzeTypeSafetyIssues(): void
    {
        echo "🔒 分析型別安全問題...\n";

        $typeSafetyIssues = [];

        foreach ($this->phpstanErrors as $file => $errors) {
            $fileIssues = [];

            foreach ($errors as $error) {
                $message = $error['message'] ?? '';
                $safetyIssue = $this->categorizeTypeSafetyIssue($message);

                if ($safetyIssue) {
                    $fileIssues[] = [
                        'line' => $error['line'] ?? 0,
                        'message' => $message,
                        'category' => $safetyIssue,
                        'fix_suggestion' => $this->generateFixSuggestion($safetyIssue, $message)
                    ];
                }
            }

            if (!empty($fileIssues)) {
                $typeSafetyIssues[$file] = $fileIssues;
            }
        }

        $this->snapshot['type_safety_issues'] = $typeSafetyIssues;
        echo "   - 在 " . count($typeSafetyIssues) . " 個檔案中發現型別安全問題\n";
    }

    private function categorizeTypeSafetyIssue(string $message): ?string
    {
        if (strpos($message, 'alreadyNarrowedType') !== false) {
            return 'redundant_type_check';
        }

        if (strpos($message, 'impossibleType') !== false) {
            return 'impossible_condition';
        }

        if (strpos($message, 'expects') !== false && strpos($message, 'given') !== false) {
            return 'type_mismatch';
        }

        if (strpos($message, 'always exists and is not nullable') !== false) {
            return 'redundant_null_check';
        }

        if (strpos($message, 'will always evaluate to') !== false) {
            return 'constant_condition';
        }

        return null;
    }

    private function generateFixSuggestion(string $category, string $message): string
    {
        switch ($category) {
            case 'redundant_type_check':
                return '移除多餘的型別檢查，因為型別已經被確定';

            case 'impossible_condition':
                return '檢查邏輯條件，這個條件永遠不會為真';

            case 'type_mismatch':
                return '確保傳入的參數型別與期望的型別匹配';

            case 'redundant_null_check':
                return '移除多餘的 null 檢查，因為值已經確定存在';

            case 'constant_condition':
                return '檢查條件邏輯，這個條件結果是常數';

            default:
                return '檢查型別註解和使用方式';
        }
    }

    private function generateRefactoringSuggestions(): void
    {
        echo "💡 生成重構建議...\n";

        $suggestions = [];

        // 基於錯誤模式生成建議
        foreach ($this->errorPatterns['error_type_distribution'] as $errorType => $count) {
            if ($count > 5) { // 如果某種錯誤出現超過 5 次
                $suggestions[] = $this->generateSuggestionForErrorType($errorType, $count);
            }
        }

        // 基於反模式生成建議
        $suggestions = array_merge($suggestions, $this->generateAntiPatternSuggestions());

        // 基於測試品質生成建議
        $suggestions = array_merge($suggestions, $this->generateTestQualitySuggestions());

        $this->refactoringSuggestions = array_filter($suggestions);
        echo "   - 生成了 " . count($this->refactoringSuggestions) . " 個重構建議\n";
    }

    private function generateSuggestionForErrorType(string $errorType, int $count): array
    {
        $suggestion = [
            'type' => $errorType,
            'frequency' => $count,
            'priority' => $this->calculatePriority($errorType, $count)
        ];

        switch ($errorType) {
            case 'missing_iterable_value_type':
                $suggestion['title'] = '添加陣列泛型型別註解';
                $suggestion['description'] = "在 {$count} 個地方缺少陣列值型別規範。PHPStan Level 10 要求所有可迭代型別指定值型別。";
                $suggestion['action'] = '使用 @return array<string, mixed> 或 @param array<int, string> 等泛型註解指定陣列元素型別。';
                $suggestion['fix_example'] = "/**\n * @return array<string, mixed>\n */\npublic function getData(): array";
                break;

            case 'redundant_is_array_check':
                $suggestion['title'] = '移除多餘的 is_array() 檢查';
                $suggestion['description'] = "在 {$count} 個地方發現多餘的 is_array() 檢查。建議移除這些檢查，因為變數型別已經確定為陣列。";
                $suggestion['action'] = '使用更精確的型別提示和 PHPDoc 註解，避免不必要的執行時型別檢查。';
                $suggestion['fix_example'] = "// 移除: if (is_array(\$data)) { ... }\n// 因為 \$data 已經是已知的陣列型別";
                break;

            case 'redundant_isset_check':
                $suggestion['title'] = '移除多餘的 isset() 檢查';
                $suggestion['description'] = "在 {$count} 個地方發現多餘的 isset() 檢查。建議移除這些檢查，因為陣列鍵值已經確定存在。";
                $suggestion['action'] = '重新檢查陣列結構定義，確保型別註解正確反映實際結構。';
                $suggestion['fix_example'] = "// 移除: if (isset(\$array['key'])) { ... }\n// 當 \$array 結構已確定包含 'key'";
                break;

            case 'type_mismatch':
                $suggestion['title'] = '修正型別不匹配問題';
                $suggestion['description'] = "在 {$count} 個地方發現型別不匹配。方法期望的參數型別與實際傳入的型別不符。";
                $suggestion['action'] = '檢查方法簽名，確保傳入的參數型別正確，或添加型別轉換。';
                $suggestion['fix_example'] = "// 添加型別註解:\n/** @var array<string, mixed> \$data */\n\$data = \$this->getData();";
                break;

            case 'return_type_mismatch':
                $suggestion['title'] = '修正回傳型別不匹配';
                $suggestion['description'] = "在 {$count} 個地方發現方法回傳型別與宣告不符。";
                $suggestion['action'] = '添加正確的回傳型別註解或修正實際回傳的資料型別。';
                $suggestion['fix_example'] = "/**\n * @return array<string, mixed>\n */\npublic function process(): array";
                break;

            case 'parameter_type_mismatch':
                $suggestion['title'] = '修正參數型別不匹配';
                $suggestion['description'] = "在 {$count} 個地方發現參數型別不匹配。";
                $suggestion['action'] = '檢查方法調用，確保傳入的參數符合方法簽名的型別要求。';
                $suggestion['fix_example'] = "// 添加型別轉換或斷言:\nassert(\$value instanceof ExpectedType);\n\$result = \$this->method(\$value);";
                break;

            case 'property_type_mismatch':
                $suggestion['title'] = '修正屬性型別不匹配';
                $suggestion['description'] = "在 {$count} 個地方發現屬性型別不匹配。";
                $suggestion['action'] = '檢查屬性賦值，確保賦值的型別符合屬性宣告的型別。';
                $suggestion['fix_example'] = "// 添加型別斷言:\n\$obj = \$container->get(MyClass::class);\nassert(\$obj instanceof MyClass);\n\$this->property = \$obj;";
                break;

            case 'always_true_condition':
                $suggestion['title'] = '移除永遠為真的條件';
                $suggestion['description'] = "在 {$count} 個地方發現永遠為真的條件判斷。";
                $suggestion['action'] = '檢查測試邏輯，移除不必要的條件判斷或修正測試資料。';
                $suggestion['fix_example'] = "// 移除不必要的條件:\n// if (true) { ... } -> 直接執行程式碼";
                break;

            case 'always_false_condition':
                $suggestion['title'] = '修正永遠為假的條件';
                $suggestion['description'] = "在 {$count} 個地方發現永遠為假的條件判斷。";
                $suggestion['action'] = '檢查測試邏輯是否有錯誤，可能需要修正條件或測試資料。';
                $suggestion['fix_example'] = "// 檢查邏輯錯誤:\n// if (false) { ... } -> 可能條件寫錯了";
                break;

            default:
                $suggestion['title'] = "修正 {$errorType} 錯誤";
                $suggestion['description'] = "發現 {$count} 個 {$errorType} 型別的錯誤。";
                $suggestion['action'] = '請檢查相關程式碼並進行必要的修正。';
                $suggestion['fix_example'] = '根據具體錯誤訊息進行修正。';
        }

        return $suggestion;
    }

    private function calculatePriority(string $errorType, int $count): string
    {
        if ($count > 20) return 'high';
        if ($count > 10) return 'medium';
        return 'low';
    }

    private function generateAntiPatternSuggestions(): array
    {
        $suggestions = [];

        foreach ($this->snapshot['anti_patterns'] ?? [] as $file => $patterns) {
            foreach ($patterns as $pattern) {
                if ($pattern['severity'] === 'high') {
                    $suggestions[] = [
                        'type' => 'anti_pattern',
                        'file' => $file,
                        'pattern' => $pattern['type'],
                        'title' => '修正測試反模式',
                        'description' => "在 {$file} 中發現 {$pattern['type']} 反模式",
                        'priority' => 'high',
                        'action' => $this->getAntiPatternAction($pattern['type'])
                    ];
                }
            }
        }

        return $suggestions;
    }

    private function getAntiPatternAction(string $patternType): string
    {
        switch ($patternType) {
            case 'high_cyclomatic_complexity':
                return '將複雜的測試分割成多個較簡單的測試方法';

            case 'too_many_test_methods':
                return '考慮將測試類別分割成多個較小的測試類別';

            case 'deep_nesting':
                return '重構測試方法，減少巢狀層級，提高可讀性';

            case 'excessive_mocking':
                return '檢查是否過度使用 mock，考慮使用真實物件或測試替身';

            default:
                return '請檢查相關的測試反模式並進行改善';
        }
    }

    private function generateTestQualitySuggestions(): array
    {
        $suggestions = [];

        // 計算整體測試品質指標
        $totalTestFiles = count($this->testFiles);
        $totalSourceFiles = count($this->sourceFiles);
        $testCoverage = $totalSourceFiles > 0 ? ($totalTestFiles / $totalSourceFiles) * 100 : 0;

        if ($testCoverage < 50) {
            $suggestions[] = [
                'type' => 'test_coverage',
                'title' => '提高測試覆蓋率',
                'description' => "目前預估測試覆蓋率為 {$testCoverage}%，建議增加更多測試",
                'priority' => 'medium',
                'action' => '為未測試的類別和方法增加單元測試'
            ];
        }

        return $suggestions;
    }

    private function generateTestReport(): void
    {
        echo "📝 生成測試分析報告...\n";

        $this->snapshot['test_files'] = $this->testFiles;
        $this->snapshot['source_files'] = $this->sourceFiles;
        $this->snapshot['test_source_mapping'] = $this->testToSourceMap;
        $this->snapshot['phpstan_errors'] = $this->phpstanErrors;
        $this->snapshot['error_patterns'] = $this->errorPatterns;
        $this->snapshot['refactoring_suggestions'] = $this->refactoringSuggestions;

        // 計算摘要統計
        $this->snapshot['summary'] = $this->generateSummaryStatistics();

        // 儲存 JSON 報告
        $jsonOutput = json_encode($this->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->projectRoot . '/test-error-analysis-snapshot.json', $jsonOutput);

        // 生成人類可讀的報告
        $this->generateHumanReadableTestReport();
    }

    private function generateSummaryStatistics(): array
    {
        $totalErrors = 0;
        foreach ($this->phpstanErrors as $errors) {
            $totalErrors += count($errors);
        }

        return [
            'total_test_files' => count($this->testFiles),
            'total_source_files' => count($this->sourceFiles),
            'total_phpstan_errors' => $totalErrors,
            'files_with_errors' => count($this->phpstanErrors),
            'most_common_error_type' => $this->findMostCommonErrorType(),
            'estimated_fix_time' => $this->estimateFixTime($totalErrors),
            'test_quality_score' => $this->calculateTestQualityScore()
        ];
    }

    private function findMostCommonErrorType(): string
    {
        $distribution = $this->errorPatterns['error_type_distribution'] ?? [];
        if (empty($distribution)) return 'none';

        return array_keys($distribution)[0]; // 第一個是最常見的
    }

    private function estimateFixTime(int $errorCount): array
    {
        // 估算修復時間 (分鐘) - 根據錯誤類型調整
        $easyErrors = [
            'missing_iterable_value_type', 'redundant_is_array_check',
            'redundant_isset_check', 'always_true_condition', 'always_false_condition'
        ];
        $mediumErrors = [
            'type_mismatch', 'return_type_mismatch', 'parameter_type_mismatch',
            'already_narrowed_type'
        ];
        $hardErrors = [
            'impossible_type', 'unsafe_type_cast', 'property_type_mismatch',
            'method_return_type_issue', 'potential_undefined_offset'
        ];

        $distribution = $this->errorPatterns['error_type_distribution'] ?? [];

        $easyCount = 0;
        $mediumCount = 0;
        $hardCount = 0;

        foreach ($distribution as $type => $count) {
            if (in_array($type, $easyErrors)) {
                $easyCount += $count;
            } elseif (in_array($type, $mediumErrors)) {
                $mediumCount += $count;
            } else {
                $hardCount += $count;
            }
        }

        // 調整時間估算：簡單錯誤 1-2 分鐘，中等 3-8 分鐘，困難 10-20 分鐘
        $estimatedMinutes = ($easyCount * 1.5) + ($mediumCount * 5) + ($hardCount * 15);

        return [
            'total_minutes' => (int)$estimatedMinutes,
            'total_hours' => round($estimatedMinutes / 60, 1),
            'easy_fixes' => $easyCount,
            'medium_fixes' => $mediumCount,
            'hard_fixes' => $hardCount,
            'batch_fix_potential' => $this->calculateBatchFixPotential($distribution)
        ];
    }

    private function calculateBatchFixPotential(array $distribution): array
    {
        $batchable = [];

        // 可以批量修復的錯誤類型
        $batchableTypes = [
            'missing_iterable_value_type' => 'high',
            'redundant_is_array_check' => 'high',
            'redundant_isset_check' => 'high',
            'type_mismatch' => 'medium',
            'return_type_mismatch' => 'medium'
        ];

        foreach ($batchableTypes as $type => $potential) {
            if (isset($distribution[$type]) && $distribution[$type] > 3) {
                $batchable[] = [
                    'type' => $type,
                    'count' => $distribution[$type],
                    'potential' => $potential,
                    'time_saved_percent' => $potential === 'high' ? 60 : 30
                ];
            }
        }

        return $batchable;
    }

    private function calculateTestQualityScore(): int
    {
        $score = 100;

        // 根據錯誤數量扣分
        $totalErrors = $this->snapshot['summary']['total_phpstan_errors'] ?? 0;
        $score -= min($totalErrors * 0.5, 50);

        // 根據反模式扣分
        $antiPatternCount = 0;
        foreach ($this->snapshot['anti_patterns'] ?? [] as $patterns) {
            $antiPatternCount += count($patterns);
        }
        $score -= min($antiPatternCount * 2, 30);

        // 根據測試覆蓋率調整
        $testCoverage = count($this->testFiles) > 0 && count($this->sourceFiles) > 0
            ? (count($this->testFiles) / count($this->sourceFiles)) * 100
            : 0;

        if ($testCoverage < 50) {
            $score -= 20;
        } elseif ($testCoverage > 80) {
            $score += 10;
        }

        return max(0, min(100, (int)$score));
    }

    private function generateHumanReadableTestReport(): void
    {
        $summary = $this->snapshot['summary'];

        $report = "# AlleyNote 測試錯誤分析報告\n\n";
        $report .= "生成時間：" . $this->snapshot['scan_info']['scan_time'] . "\n";
        $report .= "PHPStan 等級：Level " . $this->snapshot['scan_info']['phpstan_level'] . "\n\n";

        $report .= "## 📊 摘要統計\n";
        $report .= "- 測試檔案數：" . $summary['total_test_files'] . "\n";
        $report .= "- 原始檔案數：" . $summary['total_source_files'] . "\n";
        $report .= "- PHPStan 錯誤總數：" . $summary['total_phpstan_errors'] . "\n";
        $report .= "- 有錯誤的檔案數：" . $summary['files_with_errors'] . "\n";
        $report .= "- 最常見錯誤類型：" . $summary['most_common_error_type'] . "\n";
        $report .= "- 測試品質評分：" . $summary['test_quality_score'] . "/100\n\n";

        $report .= "## ⏱️ 預估修復時間\n";
        $fixTime = $summary['estimated_fix_time'];
        $report .= "- 總預估時間：" . $fixTime['total_minutes'] . " 分鐘 (" . $fixTime['total_hours'] . " 小時)\n";
        $report .= "- 簡單修復：" . $fixTime['easy_fixes'] . " 個 (約 " . ($fixTime['easy_fixes'] * 1.5) . " 分鐘)\n";
        $report .= "- 中等修復：" . $fixTime['medium_fixes'] . " 個 (約 " . ($fixTime['medium_fixes'] * 5) . " 分鐘)\n";
        $report .= "- 困難修復：" . $fixTime['hard_fixes'] . " 個 (約 " . ($fixTime['hard_fixes'] * 15) . " 分鐘)\n\n";

        // 批量修復潛力分析
        if (!empty($fixTime['batch_fix_potential'])) {
            $report .= "### 🔄 批量修復機會\n";
            foreach ($fixTime['batch_fix_potential'] as $batch) {
                $report .= "- **{$batch['type']}**: {$batch['count']} 個錯誤，可節省 {$batch['time_saved_percent']}% 時間\n";
            }
            $report .= "\n";
        }

        $report .= "## 🔍 錯誤類型分佈\n";
        foreach ($this->errorPatterns['error_type_distribution'] as $type => $count) {
            $report .= "- {$type}：{$count} 個\n";
        }
        $report .= "\n";

        $report .= "## 💡 重構建議\n";
        foreach ($this->refactoringSuggestions as $suggestion) {
            $priority = $suggestion['priority'] ?? 'medium';
            $priorityIcon = $priority === 'high' ? '🔴' : ($priority === 'medium' ? '🟡' : '🟢');

            $report .= "### {$priorityIcon} " . $suggestion['title'] . "\n";
            $report .= "**優先級：** " . ucfirst($priority) . "\n";
            $report .= "**頻率：** " . ($suggestion['frequency'] ?? 'N/A') . " 次\n";
            $report .= "**描述：** " . $suggestion['description'] . "\n";
            $report .= "**建議行動：** " . $suggestion['action'] . "\n";

            if (isset($suggestion['fix_example'])) {
                $report .= "**修復範例：**\n```php\n" . $suggestion['fix_example'] . "\n```\n";
            }

            $report .= "\n";
        }

        $report .= "## 🚨 檢測到的反模式\n";
        $antiPatternCount = 0;
        foreach ($this->snapshot['anti_patterns'] ?? [] as $file => $patterns) {
            if (!empty($patterns)) {
                $report .= "### " . basename($file) . "\n";
                foreach ($patterns as $pattern) {
                    $antiPatternCount++;
                    $report .= "- " . $pattern['type'] . " (嚴重程度：" . $pattern['severity'] . ")\n";
                }
                $report .= "\n";
            }
        }

        if ($antiPatternCount === 0) {
            $report .= "✅ 未檢測到明顯的測試反模式\n\n";
        }

        $report .= "## 📁 錯誤最多的檔案 (Top 10)\n";
        $errorsByFile = $this->errorPatterns['errors_by_file'] ?? [];
        arsort($errorsByFile);
        $topFiles = array_slice($errorsByFile, 0, 10, true);

        foreach ($topFiles as $file => $errorCount) {
            $report .= "- " . basename($file) . "：{$errorCount} 個錯誤\n";
        }
        $report .= "\n";

        $report .= "## 🎯 下一步行動建議\n";
        $report .= "1. 優先修復高優先級的重構建議\n";
        $report .= "2. 從錯誤數量最多的檔案開始修復\n";
        $report .= "3. 專注於最常見的錯誤類型進行批量修復\n";
        $report .= "4. 建立測試重構的 checklist 避免回歸\n";
        $report .= "5. 考慮設定 PHPStan 基線檔案來管理逐步修復過程\n\n";

        $report .= "---\n";
        $report .= "*此報告由 AlleyNote 測試錯誤分析掃描器自動生成*\n";

        file_put_contents($this->projectRoot . '/test-error-analysis-report.md', $report);
    }
}

// 執行掃描
if (isset($argv[1])) {
    $projectRoot = $argv[1];
} else {
    $projectRoot = dirname(__DIR__);
}

$scanner = new TestErrorAnalysisScanner($projectRoot);
$scanner->generateTestAnalysis();

echo "\n📄 測試錯誤分析報告已生成：\n";
echo "- JSON 詳細報告：test-error-analysis-snapshot.json\n";
echo "- Markdown 報告：test-error-analysis-report.md\n\n";
echo "🎯 使用這些報告來系統性地修復測試中的 PHPStan Level 10 錯誤！\n";
