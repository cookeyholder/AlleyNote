<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Commands;

use App\Domains\Statistics\Services\StatisticsAggregationService;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod as DomainStatisticsPeriod;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * 統計資料回填指令.
 *
 * 用於對歷史資料重新計算並生成統計快照，支援按統計類型和日期範圍進行回填。
 */
final class StatisticsRecalculationCommand extends Command
{
    private const COMMAND_NAME = 'statistics:recalculate';

    private const SUPPORTED_TYPES = [
        'overview' => '總覽統計',
        'posts' => '文章統計',
        'users' => '使用者統計',
        'popular' => '熱門內容統計',
    ];

    private const DEFAULT_BATCH_SIZE = 30; // 預設每30天為一批

    public function __construct(
        private readonly StatisticsAggregationService $aggregationService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('重新計算並回填統計資料快照')
            ->setHelp('此指令用於對歷史資料重新計算統計快照，支援指定統計類型、日期範圍和強制覆蓋選項。')
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                '統計類型 (' . implode(', ', array_keys(self::SUPPORTED_TYPES)) . ')，不指定則回填所有類型',
                null,
            )
            ->addArgument(
                'start_date',
                InputArgument::OPTIONAL,
                '開始日期 (YYYY-MM-DD)，不指定則從30天前開始',
                null,
            )
            ->addArgument(
                'end_date',
                InputArgument::OPTIONAL,
                '結束日期 (YYYY-MM-DD)，不指定則到昨天結束',
                null,
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                '強制覆蓋已存在的快照',
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_REQUIRED,
                '批次處理天數（預設 ' . self::DEFAULT_BATCH_SIZE . ' 天）',
                (string) self::DEFAULT_BATCH_SIZE,
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                '試執行模式，只顯示將要處理的項目，不實際執行',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // 解析輸入參數
            $config = $this->parseInputs($input, $io);
            if (!$config) {
                return Command::FAILURE;
            }

            $io->title('統計資料回填指令');
            $this->displayConfiguration($io, $config);

            // 試執行模式
            if ($config['dry_run']) {
                return $this->performDryRun($io, $config);
            }

            // 確認執行（非互動模式下自動通過）
            if (!$input->isInteractive() || $this->confirmExecution($io, $config)) {
                // 執行回填
                return $this->performRecalculation($io, $config);
            } else {
                $io->warning('操作已取消');

                return Command::SUCCESS;
            }
        } catch (Exception $e) {
            $io->error('指令執行失敗: ' . $e->getMessage());
            $this->logger->error('統計回填指令失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * 解析輸入參數.
     *
     * @return array{types: string[], start_date: DateTimeImmutable, end_date: DateTimeImmutable, force: bool, batch_size: int, dry_run: bool}|null
     */
    private function parseInputs(InputInterface $input, SymfonyStyle $io): ?array
    {
        $type = $input->getArgument('type');
        $startDateStr = $input->getArgument('start_date');
        $endDateStr = $input->getArgument('end_date');
        $force = (bool) $input->getOption('force');
        // 解析批次大小
        $batchSizeOption = $input->getOption('batch-size');
        $batchSize = is_numeric($batchSizeOption) ? (int) $batchSizeOption : 30;
        $dryRun = (bool) $input->getOption('dry-run');

        // 驗證統計類型
        $types = [];
        if (is_string($type)) {
            if (!isset(self::SUPPORTED_TYPES[$type])) {
                $io->error(sprintf(
                    '不支援的統計類型: %s。支援的類型: %s',
                    $type,
                    implode(', ', array_keys(self::SUPPORTED_TYPES)),
                ));

                return null;
            }
            $types = [$type];
        } else {
            $types = array_keys(self::SUPPORTED_TYPES);
        }

        // 解析日期範圍
        try {
            $startDate = is_string($startDateStr)
                ? new DateTimeImmutable($startDateStr)
                : new DateTimeImmutable('-30 days');

            $endDate = is_string($endDateStr)
                ? new DateTimeImmutable($endDateStr)
                : new DateTimeImmutable('-1 day');

            if ($startDate > $endDate) {
                $io->error('開始日期不能晚於結束日期');

                return null;
            }

            if ($endDate >= new DateTimeImmutable('today')) {
                $io->error('結束日期不能是今天或未來日期');

                return null;
            }
        } catch (Exception $e) {
            $io->error('日期格式錯誤: ' . $e->getMessage());

            return null;
        }

        // 驗證批次大小
        if ($batchSize <= 0 || $batchSize > 365) {
            $io->error('批次大小必須在 1-365 天之間');

            return null;
        }

        return [
            'types' => $types,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'force' => $force,
            'batch_size' => $batchSize,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * 顯示配置資訊.
     *
     * @param array{types: string[], start_date: DateTimeImmutable, end_date: DateTimeImmutable, force: bool, batch_size: int, dry_run: bool} $config
     */
    private function displayConfiguration(SymfonyStyle $io, array $config): void
    {
        $typesDisplay = array_map(
            fn(string $type): string => sprintf('%s (%s)', $type, self::SUPPORTED_TYPES[$type]),
            $config['types'],
        );

        $diffResult = $config['start_date']->diff($config['end_date']);
        $totalDays = $diffResult->days !== false ? $diffResult->days + 1 : 1;
        $estimatedBatches = (int) ceil($totalDays / $config['batch_size']);

        $io->definitionList(
            ['統計類型' => implode(', ', $typesDisplay)],
            ['日期範圍' => sprintf(
                '%s 到 %s (%d 天)',
                $config['start_date']->format('Y-m-d'),
                $config['end_date']->format('Y-m-d'),
                $totalDays,
            )],
            ['強制覆蓋' => $config['force'] ? '是' : '否'],
            ['批次大小' => $config['batch_size'] . ' 天'],
            ['預估批次' => $estimatedBatches . ' 個批次'],
            ['試執行模式' => $config['dry_run'] ? '是' : '否'],
        );
    }

    /**
     * 試執行模式.
     *
     * @param array{types: string[], start_date: DateTimeImmutable, end_date: DateTimeImmutable, force: bool, batch_size: int, dry_run: bool} $config
     */
    private function performDryRun(SymfonyStyle $io, array $config): int
    {
        $io->section('試執行模式 - 將要處理的項目：');

        $tasks = $this->generateProcessingTasks($config);

        if (empty($tasks)) {
            $io->warning('沒有需要處理的項目');

            return Command::SUCCESS;
        }

        $table = $io->createTable();
        $table->setHeaders(['統計類型', '開始日期', '結束日期', '狀態']);

        foreach ($tasks as $task) {
            $status = $task['exists'] && !$config['force'] ? '跳過（已存在）' : '將處理';
            $table->addRow([
                self::SUPPORTED_TYPES[$task['type']],
                $task['start_date']->format('Y-m-d'),
                $task['end_date']->format('Y-m-d'),
                $status,
            ]);
        }

        $table->render();

        $willProcess = count(array_filter($tasks, fn(array $task): bool => !$task['exists'] || $config['force']));
        $io->success(sprintf('共 %d 個項目，其中 %d 個將被處理', count($tasks), $willProcess));

        return Command::SUCCESS;
    }

    /**
     * 確認執行.
     *
     * @param array{types: string[], start_date: DateTimeImmutable, end_date: DateTimeImmutable, force: bool, batch_size: int, dry_run: bool} $config
     */
    private function confirmExecution(SymfonyStyle $io, array $config): bool
    {
        $tasks = $this->generateProcessingTasks($config);
        $willProcess = count(array_filter($tasks, fn(array $task): bool => !$task['exists'] || $config['force']));

        if ($willProcess === 0) {
            $io->warning('沒有需要處理的項目');

            return false;
        }

        $warning = [];
        if ($config['force']) {
            $warning[] = '⚠️ 將強制覆蓋已存在的快照';
        }

        $totalDays = $config['start_date']->diff($config['end_date'])->days + 1;
        if ($totalDays > 90) {
            $warning[] = '⚠️ 處理超過 90 天的資料可能需要較長時間';
        }

        if (!empty($warning)) {
            $io->warning($warning);
        }

        return $io->confirm(sprintf('確定要處理 %d 個統計快照嗎？', $willProcess));
    }

    /**
     * 執行回填.
     *
     * @param array{types: string[], start_date: DateTimeImmutable, end_date: DateTimeImmutable, force: bool, batch_size: int, dry_run: bool} $config
     */
    private function performRecalculation(SymfonyStyle $io, array $config): int
    {
        $tasks = $this->generateProcessingTasks($config);
        $willProcess = array_filter($tasks, fn(array $task): bool => !$task['exists'] || $config['force']);

        if (empty($willProcess)) {
            $io->success('沒有需要處理的項目');

            return Command::SUCCESS;
        }

        $io->section('開始執行統計回填');
        $progressBar = $io->createProgressBar(count($willProcess));
        $progressBar->start();

        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($willProcess as $task) {
            try {
                $this->processTask($task, $config);
                $results['success']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = sprintf(
                    '%s [%s - %s]: %s',
                    self::SUPPORTED_TYPES[$task['type']],
                    $task['start_date']->format('Y-m-d'),
                    $task['end_date']->format('Y-m-d'),
                    $e->getMessage(),
                );

                $this->logger->error('統計回填任務失敗', [
                    'type' => $task['type'],
                    'start_date' => $task['start_date']->format('Y-m-d'),
                    'end_date' => $task['end_date']->format('Y-m-d'),
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();

            // 添加小延遲避免資源過度使用
            usleep(100000); // 0.1 秒
        }

        $progressBar->finish();
        $io->newLine(2);

        // 顯示結果
        $this->displayResults($io, $results);

        return $results['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * 產生處理任務清單.
     *
     * @param array{types: string[], start_date: DateTimeImmutable, end_date: DateTimeImmutable, force: bool, batch_size: int, dry_run: bool} $config
     * @return array<int, array{type: string, start_date: DateTimeImmutable, end_date: DateTimeImmutable, exists: bool}>
     */
    private function generateProcessingTasks(array $config): array
    {
        $tasks = [];
        $currentDate = $config['start_date'];
        $endDate = $config['end_date'];
        $batchSize = $config['batch_size'];

        while ($currentDate <= $endDate) {
            $diffResult = $currentDate->diff($endDate);
            $remainingDays = $diffResult->days !== false ? $diffResult->days + 1 : 1;
            $actualBatchSize = min($batchSize, (int) $remainingDays);

            $batchEndDate = $currentDate->modify(sprintf('+%d days', $actualBatchSize - 1));
            if ($batchEndDate > $endDate) {
                $batchEndDate = $endDate;
            }

            foreach ($config['types'] as $type) {
                // 檢查快照是否已存在
                $exists = $this->snapshotExists($type, $currentDate, $batchEndDate);

                $tasks[] = [
                    'type' => $type,
                    'start_date' => $currentDate,
                    'end_date' => $batchEndDate,
                    'exists' => $exists,
                ];
            }

            $currentDate = $batchEndDate->modify('+1 day');
        }

        return $tasks;
    }

    /**
     * 檢查快照是否存在.
     */
    private function snapshotExists(string $type, DateTimeImmutable $startDate, DateTimeImmutable $endDate): bool
    {
        // 這裡應該查詢資料庫檢查快照是否存在
        // 暫時返回 false，表示需要處理
        return false;
    }

    /**
     * 處理單個任務.
     *
     * @param array{type: string, start_date: DateTimeImmutable, end_date: DateTimeImmutable, exists: bool} $task
     * @param array{types: string[], start_date: DateTimeImmutable, end_date: DateTimeImmutable, force: bool, batch_size: int, dry_run: bool} $config
     */
    private function processTask(array $task, array $config): void
    {
        $currentDate = $task['start_date'];
        $endDate = $task['end_date'];

        // 將批次拆分為單日處理
        while ($currentDate <= $endDate) {
            $period = DomainStatisticsPeriod::createDaily($currentDate);

            switch ($task['type']) {
                case 'overview':
                    $this->aggregationService->createOverviewSnapshot($period);
                    break;
                case 'posts':
                    $this->aggregationService->createPostsSnapshot($period);
                    break;
                case 'users':
                    $this->aggregationService->createUsersSnapshot($period);
                    break;
                case 'popular':
                    $this->aggregationService->createPopularSnapshot($period);
                    break;
                default:
                    throw new Exception('不支援的統計類型: ' . $task['type']);
            }

            $currentDate = $currentDate->modify('+1 day');
        }
    }

    /**
     * 顯示執行結果.
     *
     * @param array{success: int, failed: int, skipped: int, errors: string[]} $results
     */
    private function displayResults(SymfonyStyle $io, array $results): void
    {
        $io->section('執行結果');

        $io->definitionList(
            ['成功' => $results['success'] . ' 個'],
            ['失敗' => $results['failed'] . ' 個'],
            ['跳過' => $results['skipped'] . ' 個'],
        );

        if ($results['success'] > 0) {
            $io->success(sprintf('成功處理 %d 個統計快照', $results['success']));
        }

        if (!empty($results['errors'])) {
            $io->error('以下項目處理失敗：');
            foreach ($results['errors'] as $error) {
                $io->writeln('  • ' . $error);
            }
        }

        $this->logger->info('統計回填指令完成', [
            'success' => $results['success'],
            'failed' => $results['failed'],
            'skipped' => $results['skipped'],
        ]);
    }
}
