<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use RuntimeException;

class DatabaseConnection
{
    private static ?PDO $instance = null;

    private static array $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $env = getenv('APP_ENV') ?: 'local';
            $connection = getenv('DB_CONNECTION') ?: 'sqlite';
            $database = getenv('DB_DATABASE');

            // 如果測試環境指定了具體的資料庫檔案，使用該檔案，否則使用記憶體資料庫
            if ($env === 'testing' && ($database === ':memory:' || empty($database))) {
                self::$instance = new PDO('sqlite::memory:', null, null, self::$options);
                self::$instance->exec('PRAGMA foreign_keys = ON');
            } else {
                $dsn = match ($connection) {
                    'sqlite' => sprintf('sqlite:%s', $database),
                    default => throw new RuntimeException('不支援的資料庫類型')
                };
                self::$instance = new PDO($dsn, null, null, self::$options);
                if ($connection === 'sqlite') {
                    self::$instance->exec('PRAGMA foreign_keys = ON');
                }
            }
        }

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
