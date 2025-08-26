<?php

declare(strict_types=1);

/**
 * 現代化測試失敗分析工具
 * 
 * 基於最新 PHPUnit CLI 選項和輸出格式的智能測試分析工具
 * 支援 PHPUnit 11.5+ 的現代輸出格式和詳細錯誤分類
 */

class ModernTestFailureAnalyzer
{
    private array $errorTypes = [];
    private array $failurePatterns = [];
    private array $suggestions = [];
    private array $statistics = [];
    private array $phpunitConfig = [];

    public function __construct()
    {
        $this->initializeModernPatterns();
        $this->detectPhpunitConfiguration();
    }

    /**
     * 檢測 PHPUnit 設定
     */
    private function detectPhpunitConfiguration(): void
    {
        // 檢測 PHPUnit 版本和設定
        $phpunitXml = '/var/www/html/phpunit.xml';
        if (file_exists($phpunitXml)) {
            $xml = simplexml_load_file($phpunitXml);
            $this->phpunitConfig = [
                'configFile' => $phpunitXml,
                'version' => $this->getPhpunitVersion(),
                'hasBootstrap' => isset($xml['bootstrap']),
                'testSuites' => isset($xml->testsuites) ? count($xml->testsuites->testsuite) : 0
            ];
        }
    }

    /**
     * 取得 PHPUnit 版本
     */
    private function getPhpunitVersion(): string
    {
        $output = shell_exec('cd /var/www/html && ./vendor/bin/phpunit --version 2>&1');
        if (preg_match('/PHPUnit (\d+\.\d+\.\d+)/', $output, $matches)) {
            return $matches[1];
        }
        return 'unknown';
    }

    /**
     * 初始化現代化錯誤模式識別規則
     */
    private function initializeModernPatterns(): void
    {
        $this->failurePatterns = [
            // JWT 相關問題（更精確的模式）
            'jwt_token_generation' => [
                'patterns' => [
                    '/Failed to generate.*token.*encoding process failed/i',
                    '/TokenGenerationException.*encoding process failed/i',
                    '/OpenSSL unable to sign data/i',
                    '/Invalid key supplied/i'
                ],
                'category' => 'JWT Configuration',
                'priority' => 'HIGH',
                'suggestion' => 'Check JWT private key format, ensure OpenSSL extension is loaded, and validate key permissions'
            ],

            // 現代 PHPUnit Deprecations（區分 PHP 和 PHPUnit deprecations）
            'phpunit_deprecations' => [
                'patterns' => [
                    '/\d+ tests? triggered \d+ PHPUnit deprecations?:/i',
                    '/PHPUnit deprecations? triggered by:/i',
                    '/Use of.*deprecated.*PHPUnit/i'
                ],
                'category' => 'PHPUnit Deprecations',
                'priority' => 'MEDIUM',
                'suggestion' => 'Migrate from doc-comment annotations to PHP attributes using #[Test], #[DataProvider], etc.'
            ],

            // PHP Deprecations（與 PHPUnit deprecations 分開）
            'php_deprecations' => [
                'patterns' => [
                    '/\d+ tests? triggered \d+ PHP deprecations?:/i',
                    '/Deprecated:.*in.*on line/i',
                    '/PHP deprecations? triggered by:/i'
                ],
                'category' => 'PHP Deprecations',
                'priority' => 'MEDIUM',
                'suggestion' => 'Update deprecated PHP functions and features for PHP 8.4+ compatibility'
            ],

            // 資料庫結構問題（增強檢測）
            'database_structure' => [
                'patterns' => [
                    '/table.*has no column named/i',
                    '/SQLSTATE.*General error.*table.*column/i',
                    '/Unknown column.*in.*list/i',
                    '/Table.*doesn\'t exist/i'
                ],
                'category' => 'Database Schema',
                'priority' => 'HIGH',
                'suggestion' => 'Run database migrations: vendor/bin/phinx migrate -e testing, or check entity-table structure consistency'
            ],

            // 型別錯誤（增強模式識別）
            'type_error' => [
                'patterns' => [
                    '/TypeError.*Argument.*must be of type/i',
                    '/must be of type.*given/i',
                    '/Cannot use.*as.*because the name is already in use/i',
                    '/Class.*not found/i'
                ],
                'category' => 'Type Mismatch',
                'priority' => 'MEDIUM',
                'suggestion' => 'Check method signatures, parameter types, use statements, and autoloader configuration'
            ],

            // Mock 相關問題（更詳細的分類）
            'mock_issues' => [
                'patterns' => [
                    '/Mockery.*given.*called in/i',
                    '/Mock.*Interface.*given/i',
                    '/No matching handler found/i',
                    '/received unexpected.*call/i'
                ],
                'category' => 'Mock Configuration',
                'priority' => 'MEDIUM',
                'suggestion' => 'Review mock object setup, constructor parameters, and expected method calls'
            ],

            // 驗證錯誤（新增更多模式）
            'validation_error' => [
                'patterns' => [
                    '/InvalidArgumentException.*must be one of/i',
                    '/Platform must be one of/i',
                    '/Expected.*but got/i',
                    '/Invalid.*format/i'
                ],
                'category' => 'Validation Rules',
                'priority' => 'LOW',
                'suggestion' => 'Review input validation rules, allowed values, and data format requirements'
            ],

            // 新增：HTTP 客戶端問題
            'http_client_issues' => [
                'patterns' => [
                    '/HTTP.*400.*Bad Request/i',
                    '/HTTP.*401.*Unauthorized/i',
                    '/HTTP.*404.*Not Found/i',
                    '/HTTP.*500.*Internal Server Error/i',
                    '/cURL error.*Connection refused/i'
                ],
                'category' => 'HTTP Client',
                'priority' => 'HIGH',
                'suggestion' => 'Check HTTP client configuration, authentication, and server availability'
            ],

            // 新增：記憶體和效能問題
            'performance_issues' => [
                'patterns' => [
                    '/Fatal error.*Maximum execution time.*exceeded/i',
                    '/Fatal error.*Allowed memory size.*exhausted/i',
                    '/Process exceeded the timeout/i'
                ],
                'category' => 'Performance',
                'priority' => 'HIGH',
                'suggestion' => 'Increase memory_limit, set_time_limit, or optimize test performance'
            ],

            // 文件格式問題（保持原有）
            'documentation' => [
                'patterns' => [
                    '/Failed asserting that.*contains.*@package/i',
                    '/Interface has correct documentation/i',
                    '/Missing.*annotation/i'
                ],
                'category' => 'Documentation',
                'priority' => 'LOW',
                'suggestion' => 'Update PHPDoc comments and migrate to PHP 8+ attributes where appropriate'
            ]
        ];
    }

    /**
     * 分析測試輸出（現代化版本）
     */
    public function analyze(string $testOutput): array
    {
        $lines = explode("\n", $testOutput);
        $this->statistics = [
            'total_tests' => 0,
            'total_assertions' => 0,
            'errors' => 0,
            'failures' => 0,
            'skipped' => 0,
            'incomplete' => 0,
            'risky' => 0,
            'warnings' => 0,
            'deprecations' => 0,
            'phpunit_deprecations' => 0,
            'php_notices' => 0
        ];

        $issues = [];
        $currentError = null;

        foreach ($lines as $line) {
            // 提取現代化統計資訊（支援更多指標）
            if (preg_match('/Tests: (\d+), Assertions: (\d+)(?:, Errors: (\d+))?(?:, Failures: (\d+))?(?:, Warnings: (\d+))?(?:, Skipped: (\d+))?(?:, Incomplete: (\d+))?(?:, Risky: (\d+))?(?:, Deprecations: (\d+))?(?:, PHPUnit Deprecations: (\d+))?(?:, PHPUnit Notices: (\d+))?/', $line, $matches)) {
                $this->statistics['total_tests'] = (int)$matches[1];
                $this->statistics['total_assertions'] = (int)$matches[2];
                $this->statistics['errors'] = isset($matches[3]) ? (int)$matches[3] : 0;
                $this->statistics['failures'] = isset($matches[4]) ? (int)$matches[4] : 0;
                $this->statistics['warnings'] = isset($matches[5]) ? (int)$matches[5] : 0;
                $this->statistics['skipped'] = isset($matches[6]) ? (int)$matches[6] : 0;
                $this->statistics['incomplete'] = isset($matches[7]) ? (int)$matches[7] : 0;
                $this->statistics['risky'] = isset($matches[8]) ? (int)$matches[8] : 0;
                $this->statistics['deprecations'] = isset($matches[9]) ? (int)$matches[9] : 0;
                $this->statistics['phpunit_deprecations'] = isset($matches[10]) ? (int)$matches[10] : 0;
                $this->statistics['php_notices'] = isset($matches[11]) ? (int)$matches[11] : 0;
            }

            // 識別錯誤類型
            foreach ($this->failurePatterns as $errorType => $config) {
                foreach ($config['patterns'] as $pattern) {
                    if (preg_match($pattern, $line)) {
                        if (!isset($this->errorTypes[$errorType])) {
                            $this->errorTypes[$errorType] = [
                                'count' => 0,
                                'examples' => [],
                                'config' => $config
                            ];
                        }
                        $this->errorTypes[$errorType]['count']++;
                        if (count($this->errorTypes[$errorType]['examples']) < 5) { // 增加範例數量
                            $this->errorTypes[$errorType]['examples'][] = trim($line);
                        }
                        break 2;
                    }
                }
            }
        }

        return $this->generateModernReport();
    }

    /**
     * 產生現代化分析報告
     */
    private function generateModernReport(): array
    {
        $report = [
            'phpunit_info' => $this->phpunitConfig,
            'statistics' => $this->statistics,
            'error_categories' => [],
            'recommendations' => [],
            'severity_summary' => $this->calculateSeveritySummary()
        ];

        // 按優先級和影響度排序錯誤類型
        uasort($this->errorTypes, function ($a, $b) {
            $priorityOrder = ['HIGH' => 3, 'MEDIUM' => 2, 'LOW' => 1];
            $aPriority = $priorityOrder[$a['config']['priority']] ?? 0;
            $bPriority = $priorityOrder[$b['config']['priority']] ?? 0;

            if ($aPriority === $bPriority) {
                return $b['count'] - $a['count']; // 按數量降序
            }
            return $bPriority - $aPriority; // 按優先級降序
        });

        foreach ($this->errorTypes as $errorType => $data) {
            $report['error_categories'][] = [
                'type' => $errorType,
                'category' => $data['config']['category'],
                'count' => $data['count'],
                'priority' => $data['config']['priority'],
                'suggestion' => $data['config']['suggestion'],
                'examples' => $data['examples'],
                'impact_score' => $this->calculateImpactScore($data)
            ];
        }

        // 生成現代化具體建議
        $report['recommendations'] = $this->generateModernRecommendations();

        return $report;
    }

    /**
     * 計算嚴重性摘要
     */
    private function calculateSeveritySummary(): array
    {
        $summary = ['HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0];

        foreach ($this->errorTypes as $data) {
            $priority = $data['config']['priority'];
            $summary[$priority] += $data['count'];
        }

        return $summary;
    }

    /**
     * 計算影響分數
     */
    private function calculateImpactScore(array $data): int
    {
        $priorityScores = ['HIGH' => 10, 'MEDIUM' => 5, 'LOW' => 2];
        $baseScore = $priorityScores[$data['config']['priority']] ?? 1;

        // 考慮發生頻率
        $frequencyMultiplier = min($data['count'] / 10, 3); // 最大3倍

        return (int)($baseScore * (1 + $frequencyMultiplier));
    }

    /**
     * 根據錯誤類型產生現代化修復建議
     */
    private function generateModernRecommendations(): array
    {
        $recommendations = [];

        foreach ($this->errorTypes as $errorType => $data) {
            switch ($errorType) {
                case 'jwt_token_generation':
                    $recommendations[] = [
                        'priority' => 'HIGH',
                        'title' => 'Fix JWT Configuration',
                        'impact_score' => $this->calculateImpactScore($data),
                        'actions' => [
                            'Verify .env file contains valid RSA key pair',
                            'Check JWT_PRIVATE_KEY and JWT_PUBLIC_KEY format (must include -----BEGIN/END-----)',
                            'Ensure OpenSSL extension is loaded: php -m | grep openssl',
                            'Test key permissions: ls -la storage/keys/',
                            'Use composer audit to check firebase/php-jwt package'
                        ],
                        'commands' => [
                            'docker compose exec -T web php -r "echo extension_loaded(\'openssl\') ? \'OpenSSL OK\' : \'OpenSSL Missing\';"',
                            'docker compose exec -T web composer show firebase/php-jwt'
                        ]
                    ];
                    break;

                case 'database_structure':
                    $recommendations[] = [
                        'priority' => 'HIGH',
                        'title' => 'Fix Database Schema',
                        'impact_score' => $this->calculateImpactScore($data),
                        'actions' => [
                            'Run pending migrations: vendor/bin/phinx migrate -e testing',
                            'Check database status: vendor/bin/phinx status -e testing',
                            'Verify Entity properties match database columns',
                            'Check foreign key constraints and references',
                            'Consider creating rollback migration if needed'
                        ],
                        'commands' => [
                            'docker compose exec -T web php vendor/bin/phinx status -e testing',
                            'docker compose exec -T web php vendor/bin/phinx migrate -e testing'
                        ]
                    ];
                    break;

                case 'phpunit_deprecations':
                    $recommendations[] = [
                        'priority' => 'MEDIUM',
                        'title' => 'Migrate PHPUnit Annotations to Attributes',
                        'impact_score' => $this->calculateImpactScore($data),
                        'actions' => [
                            'Replace @test with #[Test] attribute',
                            'Replace @dataProvider with #[DataProvider] attribute',
                            'Replace @depends with #[Depends] attribute',
                            'Replace @group with #[Group] attribute',
                            'Use rector/rector or manual migration tools'
                        ],
                        'commands' => [
                            'composer require --dev rector/rector',
                            'vendor/bin/rector init',
                            'vendor/bin/rector process tests/ --dry-run'
                        ]
                    ];
                    break;

                case 'mock_issues':
                    $recommendations[] = [
                        'priority' => 'MEDIUM',
                        'title' => 'Fix Mock Configuration',
                        'impact_score' => $this->calculateImpactScore($data),
                        'actions' => [
                            'Check constructor parameter order and types',
                            'Verify mock interfaces match actual implementations',
                            'Update test constructor calls to match current signatures',
                            'Use PHPUnit createMock() or createStub() instead of Mockery',
                            'Review test setup and tearDown methods'
                        ],
                        'commands' => [
                            'docker compose exec -T web ./vendor/bin/phpunit --testdox --filter="Mock"'
                        ]
                    ];
                    break;

                case 'http_client_issues':
                    $recommendations[] = [
                        'priority' => 'HIGH',
                        'title' => 'Fix HTTP Client Issues',
                        'impact_score' => $this->calculateImpactScore($data),
                        'actions' => [
                            'Check server configuration and availability',
                            'Verify authentication credentials and tokens',
                            'Review API endpoints and request format',
                            'Check network connectivity and firewall rules',
                            'Use HTTP client debugging options'
                        ],
                        'commands' => [
                            'docker compose exec -T web curl -I http://localhost',
                            'docker compose logs web'
                        ]
                    ];
                    break;

                case 'performance_issues':
                    $recommendations[] = [
                        'priority' => 'HIGH',
                        'title' => 'Fix Performance Issues',
                        'impact_score' => $this->calculateImpactScore($data),
                        'actions' => [
                            'Increase memory_limit in php.ini or docker config',
                            'Optimize test data and fixtures',
                            'Use setUp/tearDown methods efficiently',
                            'Consider parallel test execution',
                            'Profile slow tests and optimize queries'
                        ],
                        'commands' => [
                            'docker compose exec -T web php -r "echo ini_get(\'memory_limit\');"',
                            'docker compose exec -T web ./vendor/bin/phpunit --order-by=duration'
                        ]
                    ];
                    break;

                default:
                    // 基本建議
                    $recommendations[] = [
                        'priority' => $data['config']['priority'],
                        'title' => "Fix {$data['config']['category']} Issues",
                        'impact_score' => $this->calculateImpactScore($data),
                        'actions' => [
                            $data['config']['suggestion'],
                            'Review error examples and stack traces',
                            'Check related documentation and best practices'
                        ]
                    ];
                    break;
            }
        }

        return $recommendations;
    }

    /**
     * 輸出現代化彩色控制台報告
     */
    public function printModernColoredReport(array $report): void
    {
        echo "\n" . $this->colorize("=== 📊 現代化測試失敗分析報告 ===", 'cyan') . "\n\n";

        // PHPUnit 資訊
        if (!empty($report['phpunit_info'])) {
            echo $this->colorize("� PHPUnit 資訊:", 'yellow') . "\n";
            echo "  版本: {$report['phpunit_info']['version']}\n";
            echo "  設定檔: " . basename($report['phpunit_info']['configFile'] ?? 'N/A') . "\n";
            echo "  測試套件數: {$report['phpunit_info']['testSuites']}\n\n";
        }

        // 統計資訊
        echo $this->colorize("📊 測試統計:", 'yellow') . "\n";
        $stats = $report['statistics'];
        echo "  總測試數: {$stats['total_tests']}\n";
        echo "  總斷言數: {$stats['total_assertions']}\n";

        $totalIssues = $stats['errors'] + $stats['failures'] + $stats['warnings'] +
            $stats['deprecations'] + $stats['phpunit_deprecations'];

        echo "  問題總數: " . $this->colorize((string)$totalIssues, $totalIssues > 0 ? 'red' : 'green') . "\n";
        echo "    ├─ 錯誤: " . $this->colorize((string)$stats['errors'], 'red') . "\n";
        echo "    ├─ 失敗: " . $this->colorize((string)$stats['failures'], 'red') . "\n";
        echo "    ├─ 警告: " . $this->colorize((string)$stats['warnings'], 'yellow') . "\n";
        echo "    ├─ PHP Deprecations: " . $this->colorize((string)$stats['deprecations'], 'yellow') . "\n";
        echo "    └─ PHPUnit Deprecations: " . $this->colorize((string)$stats['phpunit_deprecations'], 'yellow') . "\n";

        if ($stats['skipped'] > 0 || $stats['risky'] > 0 || $stats['incomplete'] > 0) {
            echo "  其他狀態:\n";
            if ($stats['skipped'] > 0) echo "    ├─ 跳過: {$stats['skipped']}\n";
            if ($stats['risky'] > 0) echo "    ├─ 風險: {$stats['risky']}\n";
            if ($stats['incomplete'] > 0) echo "    └─ 未完成: {$stats['incomplete']}\n";
        }
        echo "\n";

        // 嚴重性摘要
        if (!empty($report['severity_summary'])) {
            echo $this->colorize("🎯 問題嚴重性分佈:", 'yellow') . "\n";
            $severity = $report['severity_summary'];
            echo "  高優先級: " . $this->colorize((string)$severity['HIGH'], 'red') . " 個問題\n";
            echo "  中優先級: " . $this->colorize((string)$severity['MEDIUM'], 'yellow') . " 個問題\n";
            echo "  低優先級: " . $this->colorize((string)$severity['LOW'], 'green') . " 個問題\n\n";
        }

        // 錯誤分類
        echo $this->colorize("🔍 錯誤分類 (按優先級和影響度排序):", 'yellow') . "\n";
        foreach ($report['error_categories'] as $i => $category) {
            $priorityColor = $category['priority'] === 'HIGH' ? 'red' : ($category['priority'] === 'MEDIUM' ? 'yellow' : 'green');

            echo ($i + 1) . ". " . $this->colorize($category['category'], 'white') .
                " (" . $this->colorize($category['priority'], $priorityColor) .
                ", 影響分數: " . $this->colorize((string)$category['impact_score'], $priorityColor) .
                "): " . $this->colorize((string)$category['count'], 'red') . " 個問題\n";
            echo "    建議: {$category['suggestion']}\n";

            if (!empty($category['examples']) && count($category['examples']) > 0) {
                echo "    範例:\n";
                foreach (array_slice($category['examples'], 0, 2) as $example) {
                    echo "      • " . $this->colorize($example, 'gray') . "\n";
                }
                if (count($category['examples']) > 2) {
                    echo "      ... 還有 " . (count($category['examples']) - 2) . " 個範例\n";
                }
            }
            echo "\n";
        }

        // 修復建議
        echo $this->colorize("🔧 詳細修復建議 (按影響分數排序):", 'yellow') . "\n";

        // 按影響分數排序建議
        $sortedRecommendations = $report['recommendations'];
        usort($sortedRecommendations, function ($a, $b) {
            return ($b['impact_score'] ?? 0) - ($a['impact_score'] ?? 0);
        });

        foreach ($sortedRecommendations as $i => $rec) {
            $priorityColor = $rec['priority'] === 'HIGH' ? 'red' : ($rec['priority'] === 'MEDIUM' ? 'yellow' : 'green');

            echo ($i + 1) . ". " . $this->colorize($rec['title'], 'white') .
                " (" . $this->colorize($rec['priority'], $priorityColor);

            if (isset($rec['impact_score'])) {
                echo ", 影響分數: " . $this->colorize((string)$rec['impact_score'], $priorityColor);
            }
            echo ")\n";

            foreach ($rec['actions'] as $action) {
                echo "   ✓ {$action}\n";
            }

            // 顯示可執行的指令
            if (!empty($rec['commands'])) {
                echo "   " . $this->colorize("💻 建議執行指令:", 'cyan') . "\n";
                foreach ($rec['commands'] as $cmd) {
                    echo "     \$ " . $this->colorize($cmd, 'blue') . "\n";
                }
            }
            echo "\n";
        }

        // 下一步建議
        echo $this->colorize("🎯 建議執行順序:", 'yellow') . "\n";
        echo "  1. 先處理高優先級問題（特別是 JWT 和資料庫問題）\n";
        echo "  2. 執行建議的指令進行驗證\n";
        echo "  3. 重新執行測試確認修復效果\n";
        echo "  4. 處理中等優先級問題（如 PHPUnit deprecations）\n";
        echo "  5. 最後處理低優先級問題\n\n";

        echo $this->colorize("📝 提示: 使用 --display-all-issues 選項可以看到所有問題的詳細資訊", 'cyan') . "\n";
    }

    /**
     * 輸出彩色文字
     */
    private function colorize(string $text, string $color): string
    {
        $colors = [
            'red' => '31',
            'green' => '32',
            'yellow' => '33',
            'blue' => '34',
            'magenta' => '35',
            'cyan' => '36',
            'white' => '37',
            'gray' => '90'
        ];

        $colorCode = $colors[$color] ?? '37';
        return "\033[{$colorCode}m{$text}\033[0m";
    }
}

// 主程式
if ($argc < 2) {
    echo "現代化測試失敗分析工具 v2.0\n";
    echo "用法: php test-failure-analyzer.php <test-output-file>\n";
    echo "  或: php test-failure-analyzer.php --live [--modern-output]\n";
    echo "選項:\n";
    echo "  --live              即時分析模式\n";
    echo "  --modern-output     使用現代化 PHPUnit 輸出選項\n";
    echo "  --json              產生 JSON 報告\n";
    echo "範例:\n";
    echo "  php test-failure-analyzer.php --live --modern-output\n";
    echo "  php test-failure-analyzer.php output.txt --json\n";
    exit(1);
}

$analyzer = new ModernTestFailureAnalyzer();

if ($argv[1] === '--live') {
    // 即時分析模式 - 使用現代化選項
    echo "執行測試並進行現代化即時分析...\n";

    $modernOutput = in_array('--modern-output', $argv);
    $phpunitOptions = '--testdox --display-all-issues --display-deprecations --display-phpunit-deprecations';

    if ($modernOutput) {
        $phpunitOptions .= ' --display-warnings --display-errors --display-notices';
    }

    $testCommand = "cd /var/www/html && ./vendor/bin/phpunit {$phpunitOptions} 2>&1";

    // 檢查是否在容器內部執行
    if (getenv('INSIDE_DOCKER') || file_exists('/.dockerenv')) {
        // 在容器內部，直接執行 PHPUnit
        $testOutput = shell_exec($testCommand);
    } else {
        // 在主機上，通過 Docker Compose 執行
        $testOutput = shell_exec("sudo docker compose exec -T web bash -c \"$testCommand\"");
    }

    if (!$testOutput) {
        echo "⚠️  無法執行測試或取得輸出。檢查 Docker 服務是否正常運行。\n";
        exit(1);
    }
} else {
    // 檔案分析模式
    if (!file_exists($argv[1])) {
        echo "錯誤: 找不到檔案 {$argv[1]}\n";
        exit(1);
    }
    $testOutput = file_get_contents($argv[1]);
}

if (!$testOutput) {
    echo "錯誤: 無法讀取測試輸出\n";
    exit(1);
}

$report = $analyzer->analyze($testOutput);

// 檢查是否要產生 JSON 輸出
if (in_array('--json', $argv)) {
    $jsonFile = 'modern-test-analysis-report.json';
    file_put_contents($jsonFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "✅ 現代化 JSON 報告已儲存到 {$jsonFile}\n";
}

$analyzer->printModernColoredReport($report);
