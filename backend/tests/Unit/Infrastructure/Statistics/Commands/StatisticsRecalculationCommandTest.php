<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Commands;

use App\Domains\Statistics\Services\StatisticsAggregationService;
use App\Infrastructure\Statistics\Commands\StatisticsRecalculationCommand;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * StatisticsRecalculationCommand 單元測試.
 */
final class StatisticsRecalculationCommandTest extends TestCase
{
    private StatisticsRecalculationCommand $command;

    /** @var MockObject&StatisticsAggregationService */
    private MockObject $mockAggregationService;

    /** @var MockObject&LoggerInterface */
    private MockObject $mockLogger;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAggregationService = $this->createMock(StatisticsAggregationService::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->command = new StatisticsRecalculationCommand(
            $this->mockAggregationService,
            $this->mockLogger,
        );

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommandCanBeInstantiated(): void
    {
        $this->assertInstanceOf(StatisticsRecalculationCommand::class, $this->command);
    }

    public function testCommandHasCorrectName(): void
    {
        $this->assertEquals('statistics:recalculate', $this->command->getName());
    }

    public function testCommandHasCorrectDescription(): void
    {
        $this->assertEquals('重新計算並回填統計資料快照', $this->command->getDescription());
    }

    public function testDryRunModeShowsTasksWithoutExecution(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--dry-run' => true,
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-03',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('試執行模式', $output);
        $this->assertStringContainsString('將要處理的項目', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testCommandWithSpecificType(): void
    {
        $this->mockAggregationService
            ->expects($this->atLeastOnce())
            ->method('createOverviewSnapshot');

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'type' => 'overview',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-01',
            '--force' => true,
        ], ['interactive' => false]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testCommandWithInvalidType(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'type' => 'invalid_type',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('不支援的統計類型', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testCommandWithInvalidDateFormat(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'start_date' => 'invalid-date',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('日期格式錯誤', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testCommandWithStartDateAfterEndDate(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'start_date' => '2023-01-10',
            'end_date' => '2023-01-01',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('開始日期不能晚於結束日期', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testCommandWithFutureEndDate(): void
    {
        $futureDate = date('Y-m-d', strtotime('+1 day'));

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'start_date' => '2023-01-01',
            'end_date' => $futureDate,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('結束日期不能是今天或未來日期', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testCommandWithInvalidBatchSize(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--batch-size' => '0',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('批次大小必須在 1-365 天之間', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testCommandWithLargeBatchSize(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--batch-size' => '400',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('批次大小必須在 1-365 天之間', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testCommandProcessesOverviewType(): void
    {
        $this->mockAggregationService
            ->expects($this->once())
            ->method('createOverviewSnapshot');

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'type' => 'overview',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-01',
            '--force' => true,
        ], ['interactive' => false]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testCommandProcessesPostsType(): void
    {
        $this->mockAggregationService
            ->expects($this->once())
            ->method('createPostsSnapshot');

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'type' => 'posts',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-01',
            '--force' => true,
        ], ['interactive' => false]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testCommandProcessesUsersType(): void
    {
        $this->mockAggregationService
            ->expects($this->once())
            ->method('createUsersSnapshot');

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'type' => 'users',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-01',
            '--force' => true,
        ], ['interactive' => false]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testCommandProcessesPopularType(): void
    {
        $this->mockAggregationService
            ->expects($this->once())
            ->method('createPopularSnapshot');

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'type' => 'popular',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-01',
            '--force' => true,
        ], ['interactive' => false]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testCommandProcessesAllTypesWhenNoTypeSpecified(): void
    {
        $this->mockAggregationService
            ->expects($this->once())
            ->method('createOverviewSnapshot');

        $this->mockAggregationService
            ->expects($this->once())
            ->method('createPostsSnapshot');

        $this->mockAggregationService
            ->expects($this->once())
            ->method('createUsersSnapshot');

        $this->mockAggregationService
            ->expects($this->once())
            ->method('createPopularSnapshot');

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-01',
            '--force' => true,
        ], ['interactive' => false]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testCommandHandlesExceptionsDuringProcessing(): void
    {
        $this->mockAggregationService
            ->expects($this->once())
            ->method('createOverviewSnapshot')
            ->willThrowException(new Exception('Test exception'));

        $this->mockLogger
            ->expects($this->once())
            ->method('error');

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'type' => 'overview',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-01',
            '--force' => true,
        ], ['interactive' => false]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('處理失敗', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testCommandShowsProgressBar(): void
    {
        $this->mockAggregationService
            ->method('createOverviewSnapshot');

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'type' => 'overview',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02',
            '--force' => true,
            '--batch-size' => '1',
        ], ['interactive' => false]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('開始執行統計回填', $output);
        $this->assertStringContainsString('成功', $output);
    }

    public function testCommandWithCustomBatchSize(): void
    {
        $this->mockAggregationService
            ->method('createOverviewSnapshot');

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'type' => 'overview',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-05',
            '--force' => true,
            '--batch-size' => '2',
        ], ['interactive' => false]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('批次大小', $output);
        $this->assertStringContainsString('2 天', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testCommandDisplaysConfiguration(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'type' => 'overview',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-03',
            '--batch-size' => '5',
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('統計類型', $output);
        $this->assertStringContainsString('日期範圍', $output);
        $this->assertStringContainsString('批次大小', $output);
        $this->assertStringContainsString('5 天', $output);
        $this->assertStringContainsString('試執行模式', $output);
        $this->assertStringContainsString('是', $output);
    }

    public function testCommandWithoutArgumentsUsesDefaults(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('30 天', $output); // 預設範圍
        $this->assertStringContainsString('試執行模式', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testCommandLogsSuccessfulCompletion(): void
    {
        $this->mockAggregationService
            ->method('createOverviewSnapshot');

        $this->mockLogger
            ->expects($this->once())
            ->method('info')
            ->with('統計回填指令完成', $this->callback(function (array $context): bool {
                return isset($context['success'])
                    && isset($context['failed'])
                    && isset($context['skipped']);
            }));

        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'type' => 'overview',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-01',
            '--force' => true,
        ], ['interactive' => false]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }
}
