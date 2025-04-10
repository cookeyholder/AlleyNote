<?php

namespace App\Database;

use PDO;

class DatabaseConnection
{
    private static ?PDO $instance = null;
    private static array $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = 'sqlite::memory:';
            self::$instance = new PDO($dsn, null, null, self::$options);
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
