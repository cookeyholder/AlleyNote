<?php

declare(strict_types=1);

namespace Tests\Unit\Performance;

use App\Infrastructure\Database\DatabaseConnection;
use DI\ContainerBuilder;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

class PerformanceOptimizationTest extends UnitTestCase
{
    #[Test]
    public function containerCompilationCanBeEnabled(): void
    {
        $cacheDir = sys_get_temp_dir() . '/alleynote_di_test_' . uniqid();
        @mkdir($cacheDir, 0o775, true);

        $builder = new ContainerBuilder();
        $builder->enableCompilation($cacheDir);
        $builder->writeProxiesToFile(true, $cacheDir . '/proxies');

        /** @var array<string, mixed> $containerConfig */
        $containerConfig = require __DIR__ . '/../../../config/container.php';
        $builder->addDefinitions($containerConfig);

        $container = $builder->build();
        $this->assertTrue($container->has(DatabaseConnection::class) || $container->has(PDO::class));

        // 清理暫存目錄
        $files = glob($cacheDir . '/*');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        @rmdir($cacheDir . '/proxies');
        @rmdir($cacheDir);
    }

    #[Test]
    public function postFeedUsesCompositeIndexForQueries(): void
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        DatabaseConnection::applySqlitePragmas($pdo, true);

        $pdo->exec('
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT UNIQUE,
                title TEXT,
                content TEXT,
                status TEXT,
                is_pinned INTEGER DEFAULT 0,
                published_at DATETIME,
                deleted_at DATETIME
            );
            CREATE INDEX idx_posts_feed ON posts(status, deleted_at, is_pinned, published_at);
        ');

        $stmt = $pdo->query("EXPLAIN QUERY PLAN SELECT * FROM posts WHERE status = 'published' AND deleted_at IS NULL ORDER BY is_pinned DESC, published_at DESC LIMIT 20");
        $this->assertNotFalse($stmt);
        $plans = $stmt->fetchAll();

        $details = array_map(static fn(mixed $p): string => is_array($p) && isset($p['detail']) && is_scalar($p['detail']) ? (string) $p['detail'] : '', $plans);
        $combinedDetails = implode(' ', $details);

        $this->assertStringContainsString('USING INDEX idx_posts_feed', $combinedDetails);
        $this->assertStringNotContainsString('USE TEMP B-TREE', $combinedDetails);
    }
}
