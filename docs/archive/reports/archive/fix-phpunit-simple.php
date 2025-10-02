<?php
declare(strict_types=1);

echo "開始修復 PHPUnit deprecations...\n";

$testsDirectory = '/var/www/html/tests';
$files = [
    '/var/www/html/tests/Unit/Application/Services/Statistics/StatisticsQueryServiceTest.php',
    '/var/www/html/tests/Unit/Domains/Statistics/Contracts/PostStatisticsRepositoryInterfaceTest.php',
    '/var/www/html/tests/Unit/Domains/Statistics/Contracts/StatisticsRepositoryInterfaceTest.php',
    '/var/www/html/tests/Unit/Domains/Statistics/Contracts/UserStatisticsRepositoryInterfaceTest.php',
    '/var/www/html/tests/Unit/Domains/Statistics/Entities/StatisticsSnapshotTest.php',
    '/var/www/html/tests/Unit/Domains/Statistics/Models/StatisticsSnapshotTest.php',
    '/var/www/html/tests/Unit/Infrastructure/Statistics/Adapters/StatisticsDatabaseAdapterFactoryTest.php',
    '/var/www/html/tests/Unit/Infrastructure/Statistics/Commands/StatisticsCalculationCommandTest.php',
    '/var/www/html/tests/Unit/Infrastructure/Statistics/Repositories/StatisticsRepositoryTest.php',
    '/var/www/html/tests/Unit/Infrastructure/Statistics/Repositories/UserStatisticsRepositoryTest.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if ($content !== false) {
            // 替換 @covers annotation
            if (preg_match('/\* @covers (.+)/', $content, $matches)) {
                $class = trim($matches[1]);
                $content = preg_replace('/\* @covers .+\n/', '', $content);
                $content = preg_replace('/(final class \w+Test extends TestCase)/', "#[CoversClass($class)]\n$1", $content);
                $content = preg_replace('/(use PHPUnit\\\\Framework\\\\TestCase;)/', "$1\nuse PHPUnit\\Framework\\Attributes\\CoversClass;", $content);

                file_put_contents($file, $content);
                echo "已修正 $file\n";
            }
        }
    }
}

echo "完成!\n";
