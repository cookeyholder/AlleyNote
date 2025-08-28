<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Consolidated;

use RuntimeException;

/**
 * 預設腳本執行器實作
 */
final readonly class DefaultScriptExecutor implements ScriptExecutorInterface
{
    public function __construct(
        private string $projectRoot
    ) {}

    public function execute(string $command, array $args = []): ScriptResult
    {
        $startTime = microtime(true);
        $fullCommand = $this->buildCommand($command, $args);

        try {
            $output = shell_exec($fullCommand . ' 2>&1');
            $exitCode = $this->getLastExitCode();

            return new ScriptResult(
                success: $exitCode === 0,
                message: $exitCode === 0 ? '命令執行成功' : '命令執行失敗',
                details: ['command' => $fullCommand, 'output' => $output],
                executionTime: microtime(true) - $startTime,
                exitCode: $exitCode
            );
        } catch (\Throwable $e) {
            return new ScriptResult(
                success: false,
                message: "命令執行異常: {$e->getMessage()}",
                details: ['command' => $fullCommand, 'exception' => $e->getMessage()],
                executionTime: microtime(true) - $startTime,
                exitCode: 1
            );
        }
    }

    public function executeBackground(string $command, array $args = []): string
    {
        $fullCommand = $this->buildCommand($command, $args);
        $pidFile = tempnam(sys_get_temp_dir(), 'script_pid_');

        $backgroundCommand = "cd {$this->projectRoot} && {$fullCommand} > /dev/null 2>&1 & echo \$! > {$pidFile}";
        shell_exec($backgroundCommand);

        $pid = trim(file_get_contents($pidFile));
        unlink($pidFile);

        return $pid;
    }

    private function buildCommand(string $command, array $args): string
    {
        $escapedArgs = array_map('escapeshellarg', $args);
        return "cd {$this->projectRoot} && {$command} " . implode(' ', $escapedArgs);
    }

    private function getLastExitCode(): int
    {
        $exitCode = shell_exec('echo $?');
        return (int) trim($exitCode);
    }
}
