<?php

declare(strict_types=1);

/**
 * 修復 Commands 和 Console 層 mixed 型別問題
 */

class CommandsConsoleFixer
{
    public function run(): void
    {
        echo "開始修復 Commands 和 Console 層 mixed 型別問題...\n";

        $this->fixStatisticsCalculationCommand();
        $this->fixStatisticsCalculationConsole();

        echo "Commands 和 Console 層修復完成!\n";
    }

    private function fixStatisticsCalculationCommand(): void
    {
        echo "修復 StatisticsCalculationCommand...\n";
        $file = __DIR__ . '/../app/Domains/Statistics/Commands/StatisticsCalculationCommand.php';

        if (!file_exists($file)) {
            echo "  - 檔案不存在: $file\n";
            return;
        }

        $content = file_get_contents($file);

        // 1. 修復 $periodType->value mixed 問題
        $oldPeriodValue = '$periodType->value';
        $newPeriodValue = '(string) $periodType->value';
        $content = str_replace($oldPeriodValue, $newPeriodValue, $content);

        // 2. 修復 match 表達式中重複的 case
        $oldMatch = 'match ($periodType) {
            PeriodType::DAILY => \'daily\',
            PeriodType::WEEKLY => \'weekly\',
            PeriodType::MONTHLY => \'monthly\',
            PeriodType::YEARLY => \'yearly\',
            PeriodType::YEARLY => \'yearly\', // 移除重複的 case
        }';
        
        $newMatch = 'match ($periodType) {
            PeriodType::DAILY => \'daily\',
            PeriodType::WEEKLY => \'weekly\',
            PeriodType::MONTHLY => \'monthly\',
            PeriodType::YEARLY => \'yearly\',
        }';
        
        $content = str_replace($oldMatch, $newMatch, $content);

        // 3. 修復 foreach 型別問題 - 檢查是否為陣列
        $oldForeach = 'foreach ($result as $item) {';
        $newForeach = 'if (is_array($result)) {
            foreach ($result as $item) {';
        
        // 找到所有 foreach 並修復
        $content = preg_replace_callback(
            '/foreach\s*\(\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*as\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\)\s*\{/',
            function ($matches) {
                $arrayVar = $matches[1];
                $itemVar = $matches[2];
                return "if (is_array(\${$arrayVar})) {\n            foreach (\${$arrayVar} as \${$itemVar}) {";
            },
            $content
        );

        // 4. 修復陣列存取的 mixed 型別問題
        $arrayAccessFixes = [
            // $result 的各種存取
            "\$result['total_periods']" => 
            "is_numeric(\$result['total_periods'] ?? null) ? \$result['total_periods'] : 0",
            
            "\$result['success_count']" => 
            "is_numeric(\$result['success_count'] ?? null) ? \$result['success_count'] : 0",
            
            "\$result['failure_count']" => 
            "is_numeric(\$result['failure_count'] ?? null) ? \$result['failure_count'] : 0",
            
            // $periodResult 的各種存取
            "\$periodResult['success']" => 
            "(\$periodResult['success'] ?? false)",
            
            "\$periodResult['duration']" => 
            "is_numeric(\$periodResult['duration'] ?? null) ? (float) \$periodResult['duration'] : 0.0",
            
            "\$periodResult['cached']" => 
            "(\$periodResult['cached'] ?? false)",
            
            "\$periodResult['snapshot_id']" => 
            "is_string(\$periodResult['snapshot_id'] ?? null) ? \$periodResult['snapshot_id'] : ''",
            
            "\$periodResult['error']" => 
            "is_string(\$periodResult['error'] ?? null) ? \$periodResult['error'] : ''",
            
            "\$periodResult['skipped']" => 
            "(\$periodResult['skipped'] ?? false)",
            
            // $status 的各種存取
            "\$status['lock_timeout']" => 
            "is_numeric(\$status['lock_timeout'] ?? null) ? \$status['lock_timeout'] : 0",
            
            "\$status['max_retries']" => 
            "is_numeric(\$status['max_retries'] ?? null) ? \$status['max_retries'] : 0",
            
            "\$status['retry_delay']" => 
            "is_numeric(\$status['retry_delay'] ?? null) ? \$status['retry_delay'] : 0",
        ];

        foreach ($arrayAccessFixes as $old => $new) {
            $content = str_replace($old, $new, $content);
        }

        // 5. 修復字串插值中的 mixed 型別
        $stringInterpolationFixes = [
            'echo "錯誤: {$error} (週期: {$period})" . PHP_EOL;' => 
            'echo "錯誤: " . (is_string($error) ? $error : "未知錯誤") . " (週期: " . (is_string($period) ? $period : "未知週期") . ")" . PHP_EOL;',
            
            'echo "跳過: {$error} (週期: {$period})" . PHP_EOL;' => 
            'echo "跳過: " . (is_string($error) ? $error : "未知錯誤") . " (週期: " . (is_string($period) ? $period : "未知週期") . ")" . PHP_EOL;',
        ];

        foreach ($stringInterpolationFixes as $old => $new) {
            $content = str_replace($old, $new, $content);
        }

        // 6. 修復 count() 參數型別
        $oldCount = 'count($periods)';
        $newCount = 'count(is_array($periods) ? $periods : [])';
        $content = str_replace($oldCount, $newCount, $content);

        file_put_contents($file, $content);
        echo "  - StatisticsCalculationCommand 修復完成\n";
    }

    private function fixStatisticsCalculationConsole(): void
    {
        echo "修復 StatisticsCalculationConsole...\n";
        $file = __DIR__ . '/../app/Domains/Statistics/Console/StatisticsCalculationConsole.php';

        if (!file_exists($file)) {
            echo "  - 檔案不存在: $file\n";
            return;
        }

        $content = file_get_contents($file);

        // 1. 修復 mixed 參數型別問題
        $mixedParameterFixes = [
            // handleInvalidCommand 參數
            'private function handleInvalidCommand($command): void' => 
            '/**
     * @param mixed $command
     */
    private function handleInvalidCommand($command): void',
            
            // 在方法內部進行型別檢查
            'echo "無效的指令: {$command}" . PHP_EOL;' => 
            'echo "無效的指令: " . (is_string($command) ? $command : "未知指令") . PHP_EOL;',
        ];

        foreach ($mixedParameterFixes as $old => $new) {
            $content = str_replace($old, $new, $content);
        }

        // 2. 修復 implode 參數型別
        $oldImplode = 'implode(\', \', $availableCommands)';
        $newImplode = 'implode(\', \', is_array($availableCommands) ? $availableCommands : [])';
        $content = str_replace($oldImplode, $newImplode, $content);

        // 3. 修復 execute 方法參數傳遞
        $oldExecuteCall = '$this->command->execute($periods, $force, $skipCache)';
        $newExecuteCall = '$this->command->execute(
            is_array($periods) ? $periods : [],
            is_bool($force) ? $force : false,
            is_bool($skipCache) ? $skipCache : false
        )';
        $content = str_replace($oldExecuteCall, $newExecuteCall, $content);

        // 4. 修復 explode 參數型別
        $oldExplode = 'explode(\',\', $input)';
        $newExplode = 'explode(\',\', is_string($input) ? $input : \'\')';
        $content = str_replace($oldExplode, $newExplode, $content);

        // 5. 修復 str_starts_with 參數型別
        $oldStrStarts = 'str_starts_with($input, \'--\')';
        $newStrStarts = 'str_starts_with(is_string($input) ? $input : \'\', \'--\')';
        $content = str_replace($oldStrStarts, $newStrStarts, $content);

        // 6. 修復 number_format 參數型別
        $oldNumberFormat = 'number_format($duration, 2)';
        $newNumberFormat = 'number_format(is_numeric($duration) ? (float) $duration : 0.0, 2)';
        $content = str_replace($oldNumberFormat, $newNumberFormat, $content);

        // 7. 修復從 $_SERVER 存取的型別問題
        $serverAccessFixes = [
            '$_SERVER[\'argv\']' => 
            '(array) ($_SERVER[\'argv\'] ?? [])',
        ];

        foreach ($serverAccessFixes as $old => $new) {
            $content = str_replace($old, $new, $content);
        }

        // 8. 在所有變數使用前加入型別檢查
        $variableTypeFixes = [
            'if ($input === \'help\' || $input === \'--help\' || $input === \'-h\') {' => 
            '$inputStr = is_string($input) ? $input : \'\';
        if ($inputStr === \'help\' || $inputStr === \'--help\' || $inputStr === \'-h\') {',
            
            'if ($input === \'exit\' || $input === \'quit\' || $input === \'q\') {' => 
            'if ($inputStr === \'exit\' || $inputStr === \'quit\' || $inputStr === \'q\') {',
        ];

        foreach ($variableTypeFixes as $old => $new) {
            $content = str_replace($old, $new, $content);
        }

        file_put_contents($file, $content);
        echo "  - StatisticsCalculationConsole 修復完成\n";
    }
}

// 執行修復
$fixer = new CommandsConsoleFixer();
$fixer->run();
