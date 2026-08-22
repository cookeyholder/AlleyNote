<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use RuntimeException;

class DatabaseConnection
{
    private static ?PDO $instance = null;

    /**
     * @var array<int, mixed>
     */
    private static array $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];

    /**
     * 套用 SQLite3 效能最佳化與安全性 PRAGMA 參數.
     *
     * @param PDO $pdo 資料庫連線實例
     * @param bool $isMemory 是否為記憶體資料庫
     */
    public static function applySqlitePragmas(PDO $pdo, bool $isMemory = false): void
    {
        // 啟用外鍵約束
        $pdo->exec('PRAGMA foreign_keys = ON');

        // 鎖定逾時時間（毫秒）- 避免併發寫入時直接回報 busy
        $pdo->exec('PRAGMA busy_timeout = 5000');

        // 暫存資料表與暫存索引使用記憶體存取
        $pdo->exec('PRAGMA temp_store = MEMORY');

        // 記憶體快取大小：-64000 代表約 64MB (負值單位為 KiB)
        $pdo->exec('PRAGMA cache_size = -64000');

        if (!$isMemory) {
            // WAL 模式支援讀寫併發 (Write-Ahead Logging)
            $pdo->exec('PRAGMA journal_mode = WAL');

            // synchronous = NORMAL 在 WAL 模式下兼具高寫入效能與 ACID 安全性
            $pdo->exec('PRAGMA synchronous = NORMAL');

            // 啟用記憶體映射 I/O (256MB) 加速讀取
            $pdo->exec('PRAGMA mmap_size = 268435456');
        }
    }

    /**
     * @deprecated 使用 DI 容器建立 PDO 實例
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $env = getenv('APP_ENV') ?: 'local';
            $connection = getenv('DB_CONNECTION') ?: 'sqlite';
            $database = getenv('DB_DATABASE');
            if ($env === 'testing' && ($database === ':memory:' || empty($database))) {
                self::$instance = new PDO('sqlite::memory:', null, null, self::$options);
                if ($connection === 'sqlite') {
                    self::applySqlitePragmas(self::$instance, true);
                }
            } else {
                $dsn = match ($connection) {
                    'sqlite' => sprintf('sqlite:%s', $database),
                    default  => throw new RuntimeException('不支援的資料庫類型')
                };
                self::$instance = new PDO($dsn, null, null, self::$options);
                self::applySqlitePragmas(self::$instance, false);
            }
        }

        assert(self::$instance instanceof PDO);

        return self::$instance;
    }

    public static function setInstance(PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
