<?php

declare(strict_types=1);

/**
 * 語法錯誤分類分析腳本
 *
 * 分析 PHPStan 輸出中的語法錯誤，按類型分類並提供修復建議
 */

class SyntaxErrorClassifier
{
    private array $errorCategories = [];
    private array $fileErrors = [];
    private int $totalErrors = 0;
    private array $errorPatterns = [];

    public function __construct()
    {
        $this->initializeErrorPatterns();
    }

    public function run(): void
    {
        echo "🔍 開始分析 PHPStan 語法錯誤...\n";

        $this->analyzePHPStanOutput();
        $this->categorizeErrors();
        $this->generateReport();
        $this->generateFixingSuggestions();

        echo "\n✅ 語法錯誤分析完成！\n";
    }

    private function initializeErrorPatterns(): void
    {
        $this->errorPatterns = [
            'syntax_unexpected_token' => [
                'patterns' => [
                    '/Syntax error, unexpected .* on line \d+/',
                    '/unexpected token ".*"/',
                    '/unexpected T_.*/',
                ],
                'description' => '意外的語法符號',
                'priority' => 'high',
                'common_causes' => [
                    '缺少分號、括號或大括號',
                    '字串引號不匹配',
                    '變數名錯誤',
                    '關鍵字使用錯誤'
                ]
            ],
            'openapi_attributes' => [
                'patterns' => [
                    '/.*#\[OA\\\\.*/',
                    '/.*OpenApi.*attribute.*/',
                    '/.*@OA\\\\.*/',
                ],
                'description' => 'OpenAPI 屬性語法錯誤',
                'priority' => 'high',
                'common_causes' => [
                    'OpenAPI 屬性語法不正確',
                    '缺少必要的 use 聲明',
                    '屬性參數格式錯誤'
                ]
            ],
            'try_catch_incomplete' => [
                'patterns' => [
                    '/Cannot use try without catch or finally/',
                    '/.*try.*catch.*syntax.*/',
                    '/.*empty try block.*/',
                ],
                'description' => '不完整的 try-catch 語法',
                'priority' => 'high',
                'common_causes' => [
                    '空的 try 塊',
                    '缺少 catch 或 finally 塊',
                    'try-catch 結構不完整'
                ]
            ],
            'string_interpolation' => [
                'patterns' => [
                    '/.*string interpolation.*/',
                    '/.*\{.*\$.*\}.*/',
                    '/.*sprintf.*syntax.*/',
                ],
                'description' => '字串插值語法錯誤',
                'priority' => 'medium',
                'common_causes' => [
                    '複雜的字串插值語法',
                    'sprintf 參數錯誤',
                    '變數在字串中的語法問題'
                ]
            ],
            'ternary_operators' => [
                'patterns' => [
                    '/.*ternary.*operator.*/',
                    '/.*\?.*:.*syntax.*/',
                    '/.*conditional.*expression.*/',
                ],
                'description' => '三元運算子語法錯誤',
                'priority' => 'medium',
                'common_causes' => [
                    '嵌套三元運算子過於複雜',
                    '三元運算子語法不正確',
                    '條件表達式格式錯誤'
                ]
            ],
            'variable_syntax' => [
                'patterns' => [
                    '/.*variable.*syntax.*/',
                    '/.*\$.*syntax.*/',
                    '/.*variable.*name.*/',
                ],
                'description' => '變數語法錯誤',
                'priority' => 'medium',
                'common_causes' => [
                    '變數名包含無效字符',
                    '缺少 $ 符號',
                    '變數解構語法錯誤'
                ]
            ],
            'array_syntax' => [
                'patterns' => [
                    '/.*array.*syntax.*/',
                    '/.*\[.*\].*syntax.*/',
                    '/.*array.*destructuring.*/',
                ],
                'description' => '陣列語法錯誤',
                'priority' => 'medium',
                'common_causes' => [
                    '陣列解構語法錯誤',
                    '陣列元素語法問題',
                    '短陣列語法使用錯誤'
                ]
            ],
            'method_declaration' => [
                'patterns' => [
                    '/.*method.*declaration.*/',
                    '/.*function.*syntax.*/',
                    '/.*method.*signature.*/',
                ],
                'description' => '方法宣告語法錯誤',
                'priority' => 'low',
                'common_causes' => [
                    '方法簽名格式錯誤',
                    '返回類型聲明錯誤',
                    '參數類型聲明錯誤'
                ]
            ],
            'class_structure' => [
                'patterns' => [
                    '/.*class.*syntax.*/',
                    '/.*interface.*syntax.*/',
                    '/.*trait.*syntax.*/',
                ],
                'description' => '類別結構語法錯誤',
                'priority' => 'low',
                'common_causes' => [
                    '類別宣告語法錯誤',
                    '繼承語法問題',
                    '介面實作語法錯誤'
                ]
            ],
            'other_syntax' => [
                'patterns' => [
                    '/.*syntax.*error.*/',
                    '/.*parse.*error.*/',
                ],
                'description' => '其他語法錯誤',
                'priority' => 'medium',
                'common_causes' => [
                    '未分類的語法問題',
                    '解析錯誤',
                    '其他語法規則違反'
                ]
            ]
        ];
    }

    private function analyzePHPStanOutput(): void
    {
        echo "📊 執行 PHPStan 分析...\n";

        // 執行 PHPStan 並獲取輸出
        $command = 'docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G 2>&1';
        $output = shell_exec($command);

        if (!$output) {
            echo "❌ 無法執行 PHPStan 分析\n";
            return;
        }

        $this->parseOutputForSyntaxErrors($output);
    }

    private function parseOutputForSyntaxErrors(string $output): void
    {
        $lines = explode("\n", $output);
        $currentFile = null;
        $errorCount = 0;
        $inErrorSection = false;

        foreach ($lines as $lineIndex => $line) {
            $originalLine = $line;
            $line = trim($line);

            // 跳過進度條和空行
            if (empty($line) || preg_match('/^\d+\/\d+\s*\[/', $line) || preg_match('/^Note:/', $line)) {
                continue;
            }

            // 檢查是否為檔案標題行: " ------ ------- " 之後是 "  Line   app/path/file.php"
            if (preg_match('/^\s*Line\s+(.+\.php)\s*$/', $line, $matches)) {
                $currentFile = $matches[1];
                $this->fileErrors[$currentFile] = [];
                $inErrorSection = true;
                echo "📁 分析檔案: {$currentFile}\n";
                continue;
            }

            // 檢查分隔線
            if (preg_match('/^-+\s*$/', $line)) {
                $inErrorSection = false;
                continue;
            }

            // 在錯誤區段內，檢查錯誤行格式: "  215    Syntax error, unexpected T_PUBLIC on line 215"
            if ($inErrorSection && $currentFile && preg_match('/^\s*(\d+)\s+(.+)$/', $line, $matches)) {
                $lineNumber = (int) $matches[1];
                $errorMessage = trim($matches[2]);

                if ($this->isSyntaxError($errorMessage)) {
                    $this->fileErrors[$currentFile][] = [
                        'line' => $lineNumber,
                        'message' => $errorMessage,
                        'category' => $this->categorizeError($errorMessage)
                    ];
                    $errorCount++;
                    echo "  ⚠️ 第 {$lineNumber} 行: " . substr($errorMessage, 0, 60) . "...\n";
                }
            }

            // 檢查總錯誤數
            if (preg_match('/\[ERROR\]\s*Found (\d+) errors?/', $line, $matches)) {
                $this->totalErrors = (int) $matches[1];
                echo "📊 PHPStan 總錯誤數: {$this->totalErrors}\n";
            }
        }

        echo "📝 共發現 {$errorCount} 個語法錯誤\n";
    }

    private function isSyntaxError(string $errorMessage): bool
    {
        $syntaxKeywords = [
            'Syntax error',
            'Parse error',
            'unexpected token',
            'unexpected T_',
            'unexpected \'"\'',
            'unexpected \'(\'',
            'unexpected \')\'',
            'unexpected \'{\'',
            'unexpected \'}\'',
            'unexpected \';\'',
            'unexpected \',\'',
            'unexpected T_PUBLIC',
            'unexpected T_PRIVATE',
            'unexpected T_PROTECTED',
            'unexpected T_FUNCTION',
            'unexpected T_CLASS',
            'unexpected T_INTERFACE',
            'unexpected T_TRAIT',
            'unexpected T_RETURN',
            'unexpected T_VARIABLE',
            'unexpected T_DOUBLE_ARROW',
            'Cannot use try without catch',
            'Cannot use try without finally',
            'syntax error',
            'parse error'
        ];

        $lowerMessage = strtolower($errorMessage);

        foreach ($syntaxKeywords as $keyword) {
            if (stripos($errorMessage, $keyword) !== false) {
                return true;
            }
        }

        // 檢查其他語法錯誤模式
        if (preg_match('/unexpected .* on line \d+/', $lowerMessage)) {
            return true;
        }

        return false;
    }

    private function categorizeError(string $errorMessage): string
    {
        foreach ($this->errorPatterns as $category => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (preg_match($pattern, $errorMessage)) {
                    return $category;
                }
            }
        }

        return 'other_syntax';
    }

    private function categorizeErrors(): void
    {
        foreach ($this->fileErrors as $file => $errors) {
            foreach ($errors as $error) {
                $category = $error['category'];

                if (!isset($this->errorCategories[$category])) {
                    $this->errorCategories[$category] = [
                        'count' => 0,
                        'files' => [],
                        'examples' => []
                    ];
                }

                $this->errorCategories[$category]['count']++;

                if (!in_array($file, $this->errorCategories[$category]['files'])) {
                    $this->errorCategories[$category]['files'][] = $file;
                }

                if (count($this->errorCategories[$category]['examples']) < 3) {
                    $this->errorCategories[$category]['examples'][] = [
                        'file' => $file,
                        'line' => $error['line'],
                        'message' => $error['message']
                    ];
                }
            }
        }
    }

    private function generateReport(): void
    {
        $reportPath = __DIR__ . '/../syntax-errors-analysis-report.md';

        $content = $this->generateMarkdownReport();

        file_put_contents($reportPath, $content);
        echo "📄 報告已生成：{$reportPath}\n";

        // 同時在控制台顯示摘要
        $this->displayConsoleSummary();
    }

    private function generateMarkdownReport(): string
    {
        $report = "# PHPStan 語法錯誤分析報告\n\n";
        $report .= "> **生成時間**: " . date('Y-m-d H:i:s') . "\n";
        $report .= "> **總錯誤數**: {$this->totalErrors}\n";
        $report .= "> **語法錯誤數**: " . array_sum(array_column($this->errorCategories, 'count')) . "\n\n";

        $report .= "## 📊 語法錯誤分類統計\n\n";

        // 按優先級和數量排序
        $sortedCategories = $this->sortCategoriesByPriority();

        foreach ($sortedCategories as $category => $data) {
            $config = $this->errorPatterns[$category];
            $priorityIcon = $this->getPriorityIcon($config['priority']);

            $report .= "### {$priorityIcon} {$config['description']}\n";
            $report .= "- **錯誤數量**: {$data['count']} 個\n";
            $report .= "- **影響檔案**: " . count($data['files']) . " 個\n";
            $report .= "- **優先級**: {$config['priority']}\n\n";

            $report .= "**常見原因**:\n";
            foreach ($config['common_causes'] as $cause) {
                $report .= "- {$cause}\n";
            }

            $report .= "\n**範例錯誤**:\n";
            foreach ($data['examples'] as $example) {
                $report .= "```\n";
                $report .= "檔案: {$example['file']}\n";
                $report .= "行號: {$example['line']}\n";
                $report .= "錯誤: {$example['message']}\n";
                $report .= "```\n";
            }

            $report .= "\n---\n\n";
        }

        $report .= "## 📈 修復優先級建議\n\n";
        $report .= $this->generatePriorityRecommendations();

        $report .= "## 🛠️ 建議修復工具\n\n";
        $report .= $this->generateToolRecommendations();

        return $report;
    }

    private function sortCategoriesByPriority(): array
    {
        $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];

        $categories = $this->errorCategories;
        uksort($categories, function($a, $b) use ($priorityOrder) {
            $priorityA = $priorityOrder[$this->errorPatterns[$a]['priority']] ?? 4;
            $priorityB = $priorityOrder[$this->errorPatterns[$b]['priority']] ?? 4;

            if ($priorityA === $priorityB) {
                return $categories[$b]['count'] <=> $categories[$a]['count'];
            }

            return $priorityA <=> $priorityB;
        });

        return $categories;
    }

    private function getPriorityIcon(string $priority): string
    {
        return match($priority) {
            'high' => '🔴',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪'
        };
    }

    private function generatePriorityRecommendations(): string
    {
        $recommendations = "### 🔴 高優先級 (立即修復)\n";
        $recommendations .= "1. **OpenAPI 屬性語法錯誤** - 阻擋控制器分析\n";
        $recommendations .= "2. **不完整的 try-catch 語法** - 導致代碼無法執行\n";
        $recommendations .= "3. **意外的語法符號** - 基礎語法問題\n\n";

        $recommendations .= "### 🟡 中優先級 (本週修復)\n";
        $recommendations .= "1. **字串插值語法錯誤** - 影響輸出格式\n";
        $recommendations .= "2. **三元運算子語法錯誤** - 影響邏輯判斷\n";
        $recommendations .= "3. **變數語法錯誤** - 影響變數存取\n\n";

        $recommendations .= "### 🟢 低優先級 (下週修復)\n";
        $recommendations .= "1. **方法宣告語法錯誤** - 優化代碼結構\n";
        $recommendations .= "2. **類別結構語法錯誤** - 改善架構清晰度\n\n";

        return $recommendations;
    }

    private function generateToolRecommendations(): string
    {
        $tools = "### 建議開發的修復工具\n\n";

        foreach ($this->sortCategoriesByPriority() as $category => $data) {
            if ($data['count'] > 5) {
                $config = $this->errorPatterns[$category];
                $toolName = "fix-" . str_replace('_', '-', $category) . ".php";

                $tools .= "#### {$toolName}\n";
                $tools .= "- **目標**: {$config['description']}\n";
                $tools .= "- **修復數量**: {$data['count']} 個錯誤\n";
                $tools .= "- **優先級**: {$config['priority']}\n\n";
            }
        }

        return $tools;
    }

    private function displayConsoleSummary(): void
    {
        echo "\n📊 語法錯誤分類摘要:\n";
        echo str_repeat("=", 50) . "\n";

        $sortedCategories = $this->sortCategoriesByPriority();

        foreach ($sortedCategories as $category => $data) {
            $config = $this->errorPatterns[$category];
            $icon = $this->getPriorityIcon($config['priority']);

            printf(
                "%s %-30s %3d 個錯誤 (%d 檔案)\n",
                $icon,
                $config['description'],
                $data['count'],
                count($data['files'])
            );
        }

        echo str_repeat("=", 50) . "\n";
        echo "總語法錯誤數: " . array_sum(array_column($this->errorCategories, 'count')) . "\n";
        echo "總 PHPStan 錯誤數: {$this->totalErrors}\n";
    }

    private function generateFixingSuggestions(): void
    {
        echo "\n💡 修復建議:\n";

        $highPriorityCount = 0;
        foreach ($this->errorCategories as $category => $data) {
            if ($this->errorPatterns[$category]['priority'] === 'high') {
                $highPriorityCount += $data['count'];
            }
        }

        if ($highPriorityCount > 0) {
            echo "🔴 立即處理 {$highPriorityCount} 個高優先級語法錯誤\n";
            echo "   建議創建專用修復腳本進行批量處理\n";
        }

        echo "📈 預計修復完語法錯誤後，PHPStan 將能完整分析所有代碼\n";
        echo "🧪 語法修復完成後，重新執行測試套件\n";
    }
}

// 執行分析
if (php_sapi_name() === 'cli') {
    $classifier = new SyntaxErrorClassifier();
    $classifier->run();
} else {
    echo "此腳本只能在命令列執行\n";
}
