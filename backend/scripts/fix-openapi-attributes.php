<?php

declare(strict_types=1);

/**
 * OpenAPI 屬性語法修復腳本
 *
 * 修復 OpenAPI 屬性中的語法錯誤，包括：
 * - schema => 'name' 改為 schema: 'name'
 * - property => 'name' 改為 property: 'name'
 * - 其他屬性參數格式錯誤
 */

class OpenApiAttributesFixer
{
    private array $fixedFiles = [];
    private int $totalFixes = 0;
    private array $fixPatterns = [];

    public function __construct()
    {
        $this->initializeFixPatterns();
    }

    public function run(): void
    {
        echo "🔧 修復 OpenAPI 屬性語法錯誤...\n";

        $this->processDirectory('app');
        $this->generateReport();

        echo "\n✅ OpenAPI 屬性語法修復完成！\n";
    }

    private function initializeFixPatterns(): void
    {
        $this->fixPatterns = [
            // 修復 schema => 'name' 為 schema: 'name'
            [
                'pattern' => '/schema\s*=>\s*([\'"][^\'"]*)([\'"])/i',
                'replacement' => 'schema: $1$2',
                'description' => 'schema 屬性箭頭語法'
            ],
            // 修復 property => 'name' 為 property: 'name'
            [
                'pattern' => '/property\s*=>\s*([\'"][^\'"]*)([\'"])/i',
                'replacement' => 'property: $1$2',
                'description' => 'property 屬性箭頭語法'
            ],
            // 修復 type => 'string' 為 type: 'string'
            [
                'pattern' => '/type\s*=>\s*([\'"][^\'"]*)([\'"])/i',
                'replacement' => 'type: $1$2',
                'description' => 'type 屬性箭頭語法'
            ],
            // 修復 description => 'text' 為 description: 'text'
            [
                'pattern' => '/description\s*=>\s*([\'"][^\'"]*)([\'"])/i',
                'replacement' => 'description: $1$2',
                'description' => 'description 屬性箭頭語法'
            ],
            // 修復 title => 'text' 為 title: 'text'
            [
                'pattern' => '/title\s*=>\s*([\'"][^\'"]*)([\'"])/i',
                'replacement' => 'title: $1$2',
                'description' => 'title 屬性箭頭語法'
            ],
            // 修復 example => 'value' 為 example: 'value'
            [
                'pattern' => '/example\s*=>\s*([\'"][^\'"]*)([\'"])/i',
                'replacement' => 'example: $1$2',
                'description' => 'example 屬性箭頭語法'
            ],
            // 修復 default => 'value' 為 default: 'value'
            [
                'pattern' => '/default\s*=>\s*([\'"][^\'"]*)([\'"])/i',
                'replacement' => 'default: $1$2',
                'description' => 'default 屬性箭頭語法'
            ],
            // 修復 required => [...] 為 required: [...]
            [
                'pattern' => '/required\s*=>\s*(\[.*?\])/s',
                'replacement' => 'required: $1',
                'description' => 'required 陣列屬性箭頭語法'
            ],
            // 修復 properties => [...] 為 properties: [...]
            [
                'pattern' => '/properties\s*=>\s*(\[)/i',
                'replacement' => 'properties: $1',
                'description' => 'properties 陣列屬性箭頭語法'
            ],
            // 修復 enum => [...] 為 enum: [...]
            [
                'pattern' => '/enum\s*=>\s*(\[.*?\])/s',
                'replacement' => 'enum: $1',
                'description' => 'enum 陣列屬性箭頭語法'
            ],
            // 修復數值屬性的箭頭語法
            [
                'pattern' => '/minLength\s*=>\s*(\d+)/i',
                'replacement' => 'minLength: $1',
                'description' => 'minLength 數值屬性箭頭語法'
            ],
            [
                'pattern' => '/maxLength\s*=>\s*(\d+)/i',
                'replacement' => 'maxLength: $1',
                'description' => 'maxLength 數值屬性箭頭語法'
            ],
            [
                'pattern' => '/minimum\s*=>\s*(\d+)/i',
                'replacement' => 'minimum: $1',
                'description' => 'minimum 數值屬性箭頭語法'
            ],
            [
                'pattern' => '/maximum\s*=>\s*(\d+)/i',
                'replacement' => 'maximum: $1',
                'description' => 'maximum 數值屬性箭頭語法'
            ],
            // 修復 format => 'value' 為 format: 'value'
            [
                'pattern' => '/format\s*=>\s*([\'"][^\'"]*)([\'"])/i',
                'replacement' => 'format: $1$2',
                'description' => 'format 屬性箭頭語法'
            ],
            // 修復 nullable => true/false 為 nullable: true/false
            [
                'pattern' => '/nullable\s*=>\s*(true|false)/i',
                'replacement' => 'nullable: $1',
                'description' => 'nullable 布林屬性箭頭語法'
            ],
            // 修復 readOnly => true/false 為 readOnly: true/false
            [
                'pattern' => '/readOnly\s*=>\s*(true|false)/i',
                'replacement' => 'readOnly: $1',
                'description' => 'readOnly 布林屬性箭頭語法'
            ],
            // 修復 writeOnly => true/false 為 writeOnly: true/false
            [
                'pattern' => '/writeOnly\s*=>\s*(true|false)/i',
                'replacement' => 'writeOnly: $1',
                'description' => 'writeOnly 布林屬性箭頭語法'
            ]
        ];
    }

    private function processDirectory(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->processFile($file->getPathname());
            }
        }
    }

    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $fileFixCount = 0;
        $appliedFixes = [];

        // 只處理包含 OpenAPI 屬性的文件
        if (!preg_match('/use\s+OpenApi\\\\Attributes|#\[OA\\\\/i', $content)) {
            return;
        }

        foreach ($this->fixPatterns as $pattern) {
            $matches = [];
            $count = preg_match_all($pattern['pattern'], $content, $matches);

            if ($count > 0) {
                $content = preg_replace($pattern['pattern'], $pattern['replacement'], $content);
                $fileFixCount += $count;
                $appliedFixes[] = $pattern['description'] . ": {$count} 次";
            }
        }

        // 如果有修復，寫入文件
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);

            $relativePath = str_replace(getcwd() . '/', '', $filePath);
            $this->fixedFiles[$relativePath] = [
                'fixes' => $fileFixCount,
                'patterns' => $appliedFixes
            ];
            $this->totalFixes += $fileFixCount;

            echo "  修復 {$fileFixCount} 個 OpenAPI 屬性語法錯誤在 " . basename($filePath) . "\n";
            foreach ($appliedFixes as $fix) {
                echo "    - {$fix}\n";
            }
        }
    }

    private function generateReport(): void
    {
        echo "\n📊 OpenAPI 屬性語法修復摘要:\n";
        echo str_repeat("=", 50) . "\n";
        echo "修復的檔案數: " . count($this->fixedFiles) . "\n";
        echo "總修復數量: {$this->totalFixes}\n\n";

        if (!empty($this->fixedFiles)) {
            echo "📁 修復的檔案:\n";
            foreach ($this->fixedFiles as $file => $info) {
                echo "  - " . basename($file) . " ({$info['fixes']} 個修復)\n";
            }

            echo "\n💡 修復完成後建議:\n";
            echo "  1. 執行 PHPStan 檢查修復效果\n";
            echo "  2. 檢查 OpenAPI 文檔生成是否正常\n";
            echo "  3. 運行測試確保功能正常\n";
            echo "  4. 檢查 Swagger UI 是否能正確顯示\n\n";

            echo "📈 預期改善:\n";
            echo "  - 減少 'unexpected T_DOUBLE_ARROW' 錯誤\n";
            echo "  - 減少 'unexpected \'=>\'' 錯誤\n";
            echo "  - 修復 OpenAPI 屬性語法符合規範\n";
            echo "  - 恢復 Swagger 文檔正常生成\n\n";

            echo "🔍 修復的語法模式:\n";
            echo "  - schema => 'name' → schema: 'name'\n";
            echo "  - property => 'name' → property: 'name'\n";
            echo "  - type => 'string' → type: 'string'\n";
            echo "  - description => 'text' → description: 'text'\n";
            echo "  - 其他 OpenAPI 屬性箭頭語法修復\n";
        }
    }
}

// 執行修復
$fixer = new OpenApiAttributesFixer();
$fixer->run();
