<?php

declare(strict_types=1);

/** @phpstan-ignore-file */

namespace Tests\Unit\Domains\Statistics\Contracts;

use App\Domains\Statistics\Contracts\UserStatisticsRepositoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(UserStatisticsRepositoryInterface::class)]
class UserStatisticsRepositoryInterfaceTest extends TestCase
{
    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(UserStatisticsRepositoryInterface::class));
    }

    public function testInterfaceHasExpectedMethods(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        $expectedMethods = [
            'getActiveUsersCount',
            'getNewUsersCount',
            'getTotalUsersCount',
            'getActiveUsersByActivityType',
            'getMostActiveUsers',
            'getUserLoginActivity',
            'getUserRegistrationTrend',
            'getUserActivityTimeDistribution',
            'getUserRetentionAnalysis',
            'getUsersCountByRole',
            'getUserEngagementStatistics',
            'getUserRegistrationSources',
            'getUserGeographicalDistribution',
            'hasDataForPeriod',
            'getUserActivitySummary',
        ];

        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains(
                $expectedMethod,
                $methodNames,
                "Interface should have method: {$expectedMethod}",
            );
        }
    }

    public function testGetActiveUsersCountMethodSignature(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getActiveUsersCount');

        $this->assertEquals(2, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('string', (string) $method->getParameters()[1]->getType());
        $this->assertEquals('int', (string) $method->getReturnType());
    }

    public function testGetNewUsersCountMethodSignature(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getNewUsersCount');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('int', (string) $method->getReturnType());
    }

    public function testGetTotalUsersCountMethodSignature(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getTotalUsersCount');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('int', (string) $method->getReturnType());
    }

    public function testGetActiveUsersByActivityTypeMethodSignature(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getActiveUsersByActivityType');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('array', (string) $method->getReturnType());
    }

    public function testGetMostActiveUsersMethodSignature(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getMostActiveUsers');

        $this->assertEquals(3, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('int', (string) $method->getParameters()[1]->getType());
        $this->assertEquals('string', (string) $method->getParameters()[2]->getType());
        $this->assertEquals('array', (string) $method->getReturnType());
    }

    public function testGetUserRegistrationTrendMethodSignature(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getUserRegistrationTrend');

        $this->assertEquals(2, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[1]->getType());
        $this->assertEquals('array', (string) $method->getReturnType());
    }

    public function testGetUserRetentionAnalysisMethodSignature(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('getUserRetentionAnalysis');

        $this->assertEquals(2, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('int', (string) $method->getParameters()[1]->getType());
        $this->assertEquals('array', (string) $method->getReturnType());
    }

    public function testHasDataForPeriodMethodSignature(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $method = $reflection->getMethod('hasDataForPeriod');

        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('App\Domains\Statistics\ValueObjects\StatisticsPeriod', (string) $method->getParameters()[0]->getType());
        $this->assertEquals('bool', (string) $method->getReturnType());
    }

    public function testInterfaceMethodsHaveDocBlocks(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            $docComment = $method->getDocComment();
            $this->assertNotFalse($docComment, "Method {$method->getName()} should have a DocBlock");

            // 檢查參數數量大於 0 的方法應該有 @param 標籤
            if ($method->getNumberOfParameters() > 0) {
                $this->assertStringContainsString('@param', $docComment, "Method {$method->getName()} should document its parameters");
            }

            // 檢查使用者統計相關的方法文件
            $this->assertTrue(
                str_contains($docComment, '使用者') || str_contains($docComment, 'User') || str_contains($docComment, '活躍') || str_contains($docComment, '註冊') || str_contains($docComment, '檢查'),
                "Method {$method->getName()} should have appropriate documentation",
            );
        }
    }

    public function testInterfaceFollowsNamingConventions(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);

        // Interface 名稱應以 Interface 結尾
        $this->assertStringEndsWith('Interface', $reflection->getShortName());

        // Interface 應在 Contracts namespace 中
        $this->assertStringContainsString('\\Contracts\\', $reflection->getName());

        // Interface 名稱應表達其專門處理使用者統計
        $this->assertStringContainsString('User', $reflection->getShortName());
        $this->assertStringContainsString('Statistics', $reflection->getShortName());
    }

    public function testMethodNamesFollowDomainLanguage(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        // 統計方法應以 get 開頭
        $getMethods = array_filter($methodNames, fn($name) => strpos($name, 'get') === 0);
        $this->assertGreaterThan(0, count($getMethods), 'Interface should have get methods for statistics');

        // 檢查方法應以 has 開頭
        $hasMethods = array_filter($methodNames, fn($name) => strpos($name, 'has') === 0);
        $this->assertGreaterThan(0, count($hasMethods), 'Interface should have has methods for checks');

        // 方法名稱應包含 User 來表達專門處理使用者
        $userMethods = array_filter($methodNames, fn($name) => strpos($name, 'User') !== false);
        $this->assertGreaterThan(0, count($userMethods), 'Interface methods should relate to Users');
    }

    public function testInterfaceFollowsISPPrinciple(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $methods = $reflection->getMethods();

        // 介面應該專注於使用者統計，不應該有非使用者相關的方法
        foreach ($methods as $method) {
            $methodName = $method->getName();

            // 檢查方法名稱是否與使用者統計相關
            $isUserRelated = strpos($methodName, 'User') !== false
                           || strpos($methodName, 'Active') !== false
                           || strpos($methodName, 'Registration') !== false
                           || strpos($methodName, 'Login') !== false
                           || strpos($methodName, 'Retention') !== false
                           || strpos($methodName, 'Engagement') !== false
                           || strpos($methodName, 'Geographical') !== false
                           || strpos($methodName, 'hasData') !== false
                           || strpos($methodName, 'Summary') !== false;

            $this->assertTrue($isUserRelated, "Method {$methodName} should be related to user statistics");
        }
    }

    public function testMethodsReturnAppropriateTypes(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);

        // 計數方法應回傳 int
        $countMethods = ['getActiveUsersCount', 'getNewUsersCount', 'getTotalUsersCount'];
        foreach ($countMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertEquals(
                'int',
                (string) $method->getReturnType(),
                "Method {$methodName} should return int",
            );
        }

        // 統計分析方法應回傳 array
        $arrayMethods = ['getActiveUsersByActivityType', 'getMostActiveUsers', 'getUserLoginActivity', 'getUserRegistrationTrend'];
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

    public function testInterfaceSupportsComprehensiveUserAnalysis(): void
    {
        $reflection = new ReflectionClass(UserStatisticsRepositoryInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        // 應該支援使用者活躍度分析
        $this->assertContains('getActiveUsersCount', $methodNames);
        $this->assertContains('getMostActiveUsers', $methodNames);
        $this->assertContains('getUserEngagementStatistics', $methodNames);

        // 應該支援使用者成長分析
        $this->assertContains('getNewUsersCount', $methodNames);
        $this->assertContains('getUserRegistrationTrend', $methodNames);
        $this->assertContains('getUserRetentionAnalysis', $methodNames);

        // 應該支援使用者行為分析
        $this->assertContains('getUserLoginActivity', $methodNames);
        $this->assertContains('getUserActivityTimeDistribution', $methodNames);

        // 應該支援使用者分群分析
        $this->assertContains('getUsersCountByRole', $methodNames);
        $this->assertContains('getUserGeographicalDistribution', $methodNames);
    }
}
