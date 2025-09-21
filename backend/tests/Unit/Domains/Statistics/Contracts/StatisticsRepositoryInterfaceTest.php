<?php

declare(strict_types=1);

/** @phpstan-ignore-file */

namespace Tests\Unit\Domains\Statistics\Contracts;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \App\Domains\Statistics\Contracts\StatisticsRepositoryInterface
 */
class StatisticsRepositoryInterfaceTest extends TestCase
{
    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(StatisticsRepositoryInterface::class));
    }

    public function testInterfaceHasExpectedMethods(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        $expectedMethods = [
            'findById',
            'findByUuid',
            'findByTypeAndPeriod',
            'findLatestByType',
            'findByTypeAndDateRange',
            'findExpiredSnapshots',
            'save',
            'update',
            'delete',
            'deleteById',
            'deleteExpiredSnapshots',
            'exists',
            'count',
            'findByTypeWithPagination',
        ];

        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains(
                $expectedMethod,
                $methodNames,
                "Interface should have method: {$expectedMethod}",
            );
        }
    }

    public function testFindByIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('findById');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('int', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('?App\Domains\Statistics\Entities\StatisticsSnapshot', (string) $method->getReturnType());
    }

    public function testFindByUuidMethodSignature(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('findByUuid');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('string', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('?App\Domains\Statistics\Entities\StatisticsSnapshot', (string) $method->getReturnType());
    }

    public function testFindByTypeAndPeriodMethodSignature(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('findByTypeAndPeriod');

        $this->assertEquals(2, $method->getNumberOfParameters());
        $this->assertEquals('string', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[1]->getType());
        $this->assertEquals('?App\Domains\Statistics\Entities\StatisticsSnapshot', (string) $method->getReturnType());
    }

    public function testSaveMethodSignature(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('save');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\Entities\StatisticsSnapshot', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('App\Domains\Statistics\Entities\StatisticsSnapshot', (string) $method->getReturnType());
    }

    public function testUpdateMethodSignature(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('update');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\Entities\StatisticsSnapshot', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('App\Domains\Statistics\Entities\StatisticsSnapshot', (string) $method->getReturnType());
    }

    public function testDeleteMethodSignature(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('delete');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\Entities\StatisticsSnapshot', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('bool', (string) $method->getReturnType());
    }

    public function testFindByTypeAndDateRangeMethodSignature(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('findByTypeAndDateRange');

        $this->assertEquals(3, $method->getNumberOfParameters());
        $this->assertEquals('string', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('DateTimeInterface', (string) $method->getParameters()[1]->getType());
        $this->assertEquals('DateTimeInterface', (string) $method->getParameters()[2]->getType());
        $this->assertEquals('array', (string) $method->getReturnType());
    }

    public function testExistsMethodSignature(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('exists');

        $this->assertEquals(2, $method->getNumberOfParameters());
        $this->assertEquals('string', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[1]->getType());
        $this->assertEquals('bool', (string) $method->getReturnType());
    }

    public function testCountMethodSignature(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('count');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('?string', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('int', (string) $method->getReturnType());
    }

    public function testFindByTypeWithPaginationMethodSignature(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('findByTypeWithPagination');

        $this->assertEquals(5, $method->getNumberOfParameters());
        $this->assertEquals('string', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('int', (string) $method->getParameters()[1]->getType());
        $this->assertEquals('int', (string) $method->getParameters()[2]->getType());
        $this->assertEquals('string', (string) $method->getParameters()[3]->getType());
        $this->assertEquals('string', (string) $method->getParameters()[4]->getType());
        $this->assertEquals('array', (string) $method->getReturnType());
    }

    public function testInterfaceMethodsHaveDocBlocks(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            $docComment = $method->getDocComment();
            $this->assertNotFalse($docComment, "Method {$method->getName()} should have a DocBlock");

            // 檢查參數數量大於 0 的方法應該有 @param 標籤
            if ($method->getNumberOfParameters() > 0) {
                $this->assertStringContainsString('@param', $docComment, "Method {$method->getName()} should document its parameters");
            }

            // 對於複雜回傳類型或有例外的方法，檢查是否有適當的文件
            $this->assertTrue(
                str_contains($docComment, '查找') || str_contains($docComment, '統計') || str_contains($docComment, '儲存')
                || str_contains($docComment, '刪除') || str_contains($docComment, '檢查') || str_contains($docComment, '計算')
                || str_contains($docComment, '更新') || str_contains($docComment, '批量') || str_contains($docComment, '分頁'),
                "Method {$method->getName()} should have appropriate documentation",
            );
        }
    }

    public function testInterfaceFollowsNamingConventions(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);

        // Interface 名稱應以 Interface 結尾
        $this->assertStringEndsWith('Interface', $reflection->getShortName());

        // Interface 應在 Contracts namespace 中
        $this->assertStringContainsString('\\Contracts\\', $reflection->getName());
    }

    public function testMethodNamesFollowDomainLanguage(): void
    {
        $reflection = new ReflectionClass(StatisticsRepositoryInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        // 查詢方法應以 find 開頭
        $findMethods = array_filter($methodNames, fn($name) => strpos($name, 'find') === 0);
        $this->assertNotEmpty($findMethods, 'Interface should have find methods');

        // 儲存方法使用領域語言
        $this->assertContains('save', $methodNames);
        $this->assertContains('update', $methodNames);
        $this->assertContains('delete', $methodNames);
        $this->assertContains('exists', $methodNames);
        $this->assertContains('count', $methodNames);
    }
}
