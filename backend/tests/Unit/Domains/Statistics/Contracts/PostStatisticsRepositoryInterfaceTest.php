<?php

declare(strict_types=1);

/** @phpstan-ignore-file */

namespace Tests\Unit\Domains\Statistics\Contracts;

use App\Domains\Statistics\Contracts\PostStatisticsRepositoryInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PostStatisticsRepositoryInterfaceTest extends TestCase
{
    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(PostStatisticsRepositoryInterface::class));
    }

    public function testInterfaceHasExpectedMethods(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        $expectedMethods = [
            'getTotalPostsCount',
            'getPostsCountByStatus',
            'getPostsCountBySource',
            'getPostsCountBySourceType',
            'getPostViewsStatistics',
            'getPopularPosts',
            'getPostsCountByUser',
            'getPostsPublishTimeDistribution',
            'getPostsGrowthTrend',
            'getPostsLengthStatistics',
            'getPostsCountByLengthRange',
            'getPinnedPostsStatistics',
            'hasDataForPeriod',
            'getPostActivitySummary',
        ];

        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains(
                $expectedMethod,
                $methodNames,
                "Interface should have method: {$expectedMethod}",
            );
        }
    }

    public function testGetTotalPostsCountMethodSignature(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getTotalPostsCount');

        $this->assertEquals(2, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('?string', (string) $method->getParameters()[1]->getType());
        $this->assertEquals('int', (string) $method->getReturnType());
    }

    public function testGetPostsCountByStatusMethodSignature(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getPostsCountByStatus');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('array', (string) $method->getReturnType());
    }

    public function testGetPostsCountBySourceMethodSignature(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getPostsCountBySource');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('array', (string) $method->getReturnType());
    }

    public function testGetPostsCountBySourceTypeMethodSignature(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getPostsCountBySourceType');

        $this->assertEquals(3, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\SourceType', (string) $method->getParameters()[1]->getType());
        $this->assertEquals('?string', (string) $method->getParameters()[2]->getType());
        $this->assertEquals('int', (string) $method->getReturnType());
    }

    public function testGetPopularPostsMethodSignature(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getPopularPosts');

        $this->assertEquals(3, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('int', (string) $method->getParameters()[1]->getType());
        $this->assertEquals('string', (string) $method->getParameters()[2]->getType());
        $this->assertEquals('array', (string) $method->getReturnType());
    }

    public function testGetPostsGrowthTrendMethodSignature(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getPostsGrowthTrend');

        $this->assertEquals(2, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[1]->getType());
        $this->assertEquals('array', (string) $method->getReturnType());
    }

    public function testHasDataForPeriodMethodSignature(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('hasDataForPeriod');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('bool', (string) $method->getReturnType());
    }

    public function testInterfaceMethodsHaveDocBlocks(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            $docComment = $method->getDocComment();
            $this->assertNotFalse($docComment, "Method {$method->getName()} should have a DocBlock");

            // 檢查參數數量大於 0 的方法應該有 @param 標籤
            if ($method->getNumberOfParameters() > 0) {
                $this->assertStringContainsString('@param', $docComment, "Method {$method->getName()} should document its parameters");
            }

            // 檢查文章統計相關的方法文件
            $this->assertTrue(
                str_contains($docComment, '文章') || str_contains($docComment, 'Post') || str_contains($docComment, '熱門') || str_contains($docComment, '置頂') || str_contains($docComment, '檢查'),
                "Method {$method->getName()} should have appropriate documentation",
            );
        }
    }

    public function testInterfaceFollowsNamingConventions(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);

        // Interface 名稱應以 Interface 結尾
        $this->assertStringEndsWith('Interface', $reflection->getShortName());

        // Interface 應在 Contracts namespace 中
        $this->assertStringContainsString('\\Contracts\\', $reflection->getName());

        // Interface 名稱應表達其專門處理文章統計
        $this->assertStringContainsString('Post', $reflection->getShortName());
        $this->assertStringContainsString('Statistics', $reflection->getShortName());
    }

    public function testMethodNamesFollowDomainLanguage(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        // 統計方法應以 get 開頭
        $getMethods = array_filter($methodNames, fn($name) => strpos($name, 'get') === 0);
        $this->assertGreaterThan(0, count($getMethods), 'Interface should have get methods for statistics');

        // 檢查方法應以 has 開頭
        $hasMethods = array_filter($methodNames, fn($name) => strpos($name, 'has') === 0);
        $this->assertGreaterThan(0, count($hasMethods), 'Interface should have has methods for checks');

        // 方法名稱應包含 Posts 來表達專門處理文章
        $postMethods = array_filter($methodNames, fn($name) => strpos($name, 'Post') !== false);
        $this->assertGreaterThan(0, count($postMethods), 'Interface methods should relate to Posts');
    }

    public function testInterfaceFollowsISPPrinciple(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);
        $methods = $reflection->getMethods();

        // 介面應該專注於文章統計，不應該有非文章相關的方法
        foreach ($methods as $method) {
            $methodName = $method->getName();
            $docComment = $method->getDocComment();

            // 檢查方法名稱或文件註解是否與文章統計相關
            $isPostRelated = strpos($methodName, 'Post') !== false
                           || strpos($methodName, 'Popular') !== false
                           || strpos($methodName, 'Pinned') !== false
                           || strpos($methodName, 'hasData') !== false
                           || strpos($methodName, 'Summary') !== false;

            $this->assertTrue($isPostRelated, "Method {$methodName} should be related to post statistics");
        }
    }

    public function testMethodsReturnAppropriateTypes(): void
    {
        $reflection = new ReflectionClass(PostStatisticsRepositoryInterface::class);

        // 計數方法應回傳 int
        $countMethods = ['getTotalPostsCount', 'getPostsCountBySourceType'];
        foreach ($countMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertEquals(
                'int',
                (string) $method->getReturnType(),
                "Method {$methodName} should return int",
            );
        }

        // 分組統計方法應回傳 array
        $arrayMethods = ['getPostsCountByStatus', 'getPostsCountBySource', 'getPopularPosts'];
        foreach ($arrayMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertEquals(
                'array',
                (string) $method->getReturnType(),
                "Method {$methodName} should return array",
            );
        }

        // 檢查方法應回傳 bool
        $boolMethods = ['hasDataForPeriod'];
        foreach ($boolMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertEquals(
                'bool',
                (string) $method->getReturnType(),
                "Method {$methodName} should return bool",
            );
        }
    }
}
