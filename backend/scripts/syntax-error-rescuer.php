<?php

declare(strict_types=1);

/**
 * 語法錯誤救援器
 *
 * 針對自動化工具引入的語法錯誤進行救援修復
 */

class SyntaxErrorRescuer
{
    private int $totalFixed = 0;
    private array $fixedFiles = [];

    public function rescueSyntaxErrors(): void
    {
        echo "🚑 啟動語法錯誤救援器...\n\n";

        $phpFiles = $this->findPhpFiles();

        foreach ($phpFiles as $file) {
            $this->rescueFile($file);
        }

        $this->printSummary();
    }

    private function findPhpFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../app')
        );

        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        return $phpFiles;
    }

    private function rescueFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $fixes = 0;

        // 救援 1: 修復破壞的變數引用
        $variableRescuePatterns = [
            // 修復 "$a->" 變成 "$this->"
            '/\$[a-z]\s*->/' => '$this->',

            // 修復 "$a[" 變成 "$this["
            '/\$[a-z]\s*\[/' => '$this[',

            // 修復破壞的參數
            '/\(\s*\$[a-z]\s*,/' => '($param,',
            '/,\s*\$[a-z]\s*\)/' => ', $param)',
            '/,\s*\$[a-z]\s*,/' => ', $param,',
        ];

        foreach ($variableRescuePatterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        // 救援 2: 修復破壞的陣列存取
        $arrayRescuePatterns = [
            // 修復 "0s['key']" 或類似的錯誤
            '/[0-9]+s\[\'([^\']+)\'\]/' => '$this[\'$1\']',
            '/[0-9]+s\["([^"]+)"\]/' => '$this["$1"]',

            // 修復破壞的陣列語法
            '/\[\s*[a-z]\s*\]/' => '[\'param\']',
        ];

        foreach ($arrayRescuePatterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        // 救援 3: 修復三元運算子括號問題
        $ternaryRescuePatterns = [
            // 為嵌套三元運算子加括號
            '/(\w+\s*\?\s*[^:]+\s*:\s*[^?:]+\s*\?\s*[^:]+\s*:[^;)]+)/' => '($1)',
        ];

        foreach ($ternaryRescuePatterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        // 救援 4: 修復破壞的方法呼叫
        $methodRescuePatterns = [
            // 修復 "->a" 這種破壞的方法名
            '/->([a-z])([^a-zA-Z_0-9])/' => '->get' . ucfirst('$1') . '$2',

            // 修復多餘的逗號
            '/,\s*,+/' => ',',
            '/\(\s*,/' => '(',
            '/,\s*\)/' => ')',

            // 修復破壞的物件操作
            '/\.\s*->/' => '->',
        ];

        foreach ($methodRescuePatterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        // 救援 5: 修復特定語法錯誤
        $specificRescuePatterns = [
            // 修復 "unexpected identifier"
            '/(\w+)\s+([a-z])\s*([;,\)])/' => '$1 $param$3',

            // 修復破壞的字串
            '/\'([a-z])\'\s*,/' => '\'param\',',

            // 修復破壞的關鍵字
            '/\s+([a-z])\s*=/' => ' $param =',

            // 修復不完整的語句
            '/;\s*([a-z])\s*;/' => '; $param;',
        ];

        foreach ($specificRescuePatterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        // 救援 6: 修復常見的語法結構錯誤
        $structureRescuePatterns = [
            // 修復空白問題
            '/\$\s+/' => '$',
            '/\s+->\s+/' => '->',
            '/\s+::\s+/' => '::',

            // 修復破壞的空白字元
            '/\s{2,}/' => ' ',
            '/\n\s*\n\s*\n/' => "\n\n",
        ];

        foreach ($structureRescuePatterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        // 如果有修復，保存檔案
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->totalFixed += $fixes;
            $this->fixedFiles[] = [
                'file' => str_replace(__DIR__ . '/../', '', $filePath),
                'fixes' => $fixes
            ];

            echo "🚑 救援: " . str_replace(__DIR__ . '/../', '', $filePath) . " ({$fixes} 個修復)\n";
        }
    }

    private function printSummary(): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📋 語法錯誤救援報告\n";
        echo str_repeat("=", 50) . "\n";
        echo "總修復數量: {$this->totalFixed}\n";
        echo "修復檔案數: " . count($this->fixedFiles) . "\n\n";

        if (!empty($this->fixedFiles)) {
            echo "救援詳情:\n";
            foreach ($this->fixedFiles as $file) {
                echo "  🚑 {$file['file']}: {$file['fixes']} 個修復\n";
            }
        }

        echo "\n✅ 語法錯誤救援完成！\n";
        echo "💡 建議再次執行語法檢查確認救援效果\n";
    }
}

// 執行語法錯誤救援
$rescuer = new SyntaxErrorRescuer();
$rescuer->rescueSyntaxErrors();
