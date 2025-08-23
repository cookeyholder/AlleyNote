<?php

declare(strict_types=1);

/**
 * 控制台輸出幫助類
 * 提供格式化的控制台訊息輸出功能
 */
class ConsoleOutput
{
    // ANSI 顏色代碼
    private const COLORS = [
        'reset' => "\033[0m",
        'black' => "\033[0;30m",
        'red' => "\033[0;31m",
        'green' => "\033[0;32m",
        'yellow' => "\033[0;33m",
        'blue' => "\033[0;34m",
        'magenta' => "\033[0;35m",
        'cyan' => "\033[0;36m",
        'white' => "\033[0;37m",
        'bright_red' => "\033[1;31m",
        'bright_green' => "\033[1;32m",
        'bright_yellow' => "\033[1;33m",
        'bright_blue' => "\033[1;34m",
        'bright_magenta' => "\033[1;35m",
        'bright_cyan' => "\033[1;36m",
        'bright_white' => "\033[1;37m"
    ];

    // 背景顏色代碼
    private const BG_COLORS = [
        'bg_black' => "\033[40m",
        'bg_red' => "\033[41m",
        'bg_green' => "\033[42m",
        'bg_yellow' => "\033[43m",
        'bg_blue' => "\033[44m",
        'bg_magenta' => "\033[45m",
        'bg_cyan' => "\033[46m",
        'bg_white' => "\033[47m"
    ];

    // 文字樣式
    private const STYLES = [
        'bold' => "\033[1m",
        'dim' => "\033[2m",
        'italic' => "\033[3m",
        'underline' => "\033[4m",
        'blink' => "\033[5m",
        'reverse' => "\033[7m",
        'strikethrough' => "\033[9m"
    ];

    private bool $colorSupport;
    private int $verbosity;

    // 詳細程度常數
    public const VERBOSITY_QUIET = 0;
    public const VERBOSITY_NORMAL = 1;
    public const VERBOSITY_VERBOSE = 2;
    public const VERBOSITY_VERY_VERBOSE = 3;
    public const VERBOSITY_DEBUG = 4;

    public function __construct(int $verbosity = self::VERBOSITY_NORMAL)
    {
        $this->colorSupport = $this->hasColorSupport();
        $this->verbosity = $verbosity;
    }

    /**
     * 檢測是否支援顏色輸出
     */
    private function hasColorSupport(): bool
    {
        // Windows 系統檢測
        if (DIRECTORY_SEPARATOR === '\\') {
            return false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM');
        }

        // Unix/Linux 系統檢測
        return function_exists('posix_isatty') && @posix_isatty(STDOUT);
    }

    /**
     * 應用顏色和樣式到文字
     */
    private function colorize(string $text, array $styles): string
    {
        if (!$this->colorSupport) {
            return $text;
        }

        $codes = [];
        foreach ($styles as $style) {
            if (isset(self::COLORS[$style])) {
                $codes[] = self::COLORS[$style];
            } elseif (isset(self::BG_COLORS[$style])) {
                $codes[] = self::BG_COLORS[$style];
            } elseif (isset(self::STYLES[$style])) {
                $codes[] = self::STYLES[$style];
            }
        }

        if (empty($codes)) {
            return $text;
        }

        return implode('', $codes) . $text . self::COLORS['reset'];
    }

    /**
     * 輸出訊息
     */
    private function write(string $message, array $styles = [], int $verbosity = self::VERBOSITY_NORMAL): void
    {
        if ($this->verbosity < $verbosity) {
            return;
        }

        $styledMessage = $this->colorize($message, $styles);
        echo $styledMessage . "\n";
    }

    /**
     * 輸出成功訊息
     */
    public function success(string $message, int $verbosity = self::VERBOSITY_NORMAL): void
    {
        $this->write("✓ {$message}", ['bright_green'], $verbosity);
    }

    /**
     * 輸出錯誤訊息
     */
    public function error(string $message, int $verbosity = self::VERBOSITY_NORMAL): void
    {
        $this->write("✗ {$message}", ['bright_red'], $verbosity);
    }

    /**
     * 輸出警告訊息
     */
    public function warning(string $message, int $verbosity = self::VERBOSITY_NORMAL): void
    {
        $this->write("⚠ {$message}", ['bright_yellow'], $verbosity);
    }

    /**
     * 輸出資訊訊息
     */
    public function info(string $message, int $verbosity = self::VERBOSITY_NORMAL): void
    {
        $this->write("ℹ {$message}", ['bright_blue'], $verbosity);
    }

    /**
     * 輸出除錯訊息
     */
    public function debug(string $message): void
    {
        $this->write("🐛 {$message}", ['dim'], self::VERBOSITY_DEBUG);
    }

    /**
     * 輸出一般訊息
     */
    public function line(string $message = '', int $verbosity = self::VERBOSITY_NORMAL): void
    {
        $this->write($message, [], $verbosity);
    }

    /**
     * 輸出標題
     */
    public function title(string $title): void
    {
        $length = strlen($title);
        $border = str_repeat('=', $length + 4);

        $this->line();
        $this->write($border, ['bright_cyan']);
        $this->write("  {$title}  ", ['bright_cyan', 'bold']);
        $this->write($border, ['bright_cyan']);
        $this->line();
    }

    /**
     * 輸出副標題
     */
    public function subtitle(string $subtitle): void
    {
        $this->write("--- {$subtitle} ---", ['bright_magenta']);
    }

    /**
     * 輸出列表項目
     */
    public function listItem(string $item, int $indent = 0): void
    {
        $prefix = str_repeat('  ', $indent) . '• ';
        $this->write($prefix . $item, ['cyan']);
    }

    /**
     * 輸出表格標頭
     */
    public function tableHeader(array $headers): void
    {
        $row = '| ' . implode(' | ', $headers) . ' |';
        $separator = '+' . str_repeat('-', strlen($row) - 2) . '+';

        $this->write($separator, ['bright_white']);
        $this->write($row, ['bright_white', 'bold']);
        $this->write($separator, ['bright_white']);
    }

    /**
     * 輸出表格行
     */
    public function tableRow(array $cells): void
    {
        $row = '| ' . implode(' | ', $cells) . ' |';
        $this->write($row, ['white']);
    }

    /**
     * 輸出進度條
     */
    public function progressBar(int $current, int $total, string $label = ''): void
    {
        if ($this->verbosity < self::VERBOSITY_NORMAL) {
            return;
        }

        $percentage = $total > 0 ? ($current / $total) * 100 : 0;
        $barLength = 50;
        $filledLength = (int) round(($barLength * $current) / $total);

        $bar = str_repeat('█', $filledLength) . str_repeat('░', $barLength - $filledLength);
        $progressText = sprintf("[%s] %3.1f%% (%d/%d)", $bar, $percentage, $current, $total);

        if (!empty($label)) {
            $progressText .= " - {$label}";
        }

        // 使用 \r 來覆寫同一行
        echo "\r" . $this->colorize($progressText, ['bright_blue']);

        // 如果完成，換行
        if ($current >= $total) {
            echo "\n";
        }
    }

    /**
     * 詢問使用者輸入
     */
    public function ask(string $question, string $default = ''): string
    {
        $prompt = $this->colorize($question, ['bright_yellow']);
        if (!empty($default)) {
            $prompt .= $this->colorize(" [{$default}]", ['yellow']);
        }
        $prompt .= $this->colorize(': ', ['bright_yellow']);

        echo $prompt;
        $input = trim(fgets(STDIN));

        return $input === '' ? $default : $input;
    }

    /**
     * 詢問是否確認
     */
    public function confirm(string $question, bool $default = true): bool
    {
        $options = $default ? '[Y/n]' : '[y/N]';
        $prompt = $this->colorize($question . " {$options}: ", ['bright_yellow']);

        echo $prompt;
        $input = strtolower(trim(fgets(STDIN)));

        if ($input === '') {
            return $default;
        }

        return in_array($input, ['y', 'yes', '1', 'true']);
    }

    /**
     * 輸出分隔線
     */
    public function separator(string $char = '-', int $length = 60): void
    {
        $this->write(str_repeat($char, $length), ['dim']);
    }

    /**
     * 輸出空行
     */
    public function newLine(int $count = 1): void
    {
        echo str_repeat("\n", $count);
    }

    /**
     * 設定詳細程度
     */
    public function setVerbosity(int $verbosity): void
    {
        $this->verbosity = $verbosity;
    }

    /**
     * 獲取詳細程度
     */
    public function getVerbosity(): int
    {
        return $this->verbosity;
    }

    /**
     * 檢查是否為安靜模式
     */
    public function isQuiet(): bool
    {
        return $this->verbosity <= self::VERBOSITY_QUIET;
    }

    /**
     * 檢查是否為詳細模式
     */
    public function isVerbose(): bool
    {
        return $this->verbosity >= self::VERBOSITY_VERBOSE;
    }

    /**
     * 檢查是否為除錯模式
     */
    public function isDebug(): bool
    {
        return $this->verbosity >= self::VERBOSITY_DEBUG;
    }
}
