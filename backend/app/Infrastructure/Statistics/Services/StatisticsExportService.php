<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Services;

use App\Application\Services\Statistics\DTOs\StatisticsQueryDTO;
use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Domains\Statistics\Contracts\BatchExportResult;
use App\Domains\Statistics\Contracts\ExportResult;
use App\Domains\Statistics\Contracts\StatisticsExportServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsFormatterInterface;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class StatisticsExportService implements StatisticsExportServiceInterface
{
    private const DEFAULT_FORMAT = 'json';

    private const SUPPORTED_TYPES = [
        'overview',
        'posts',
        'sources',
        'users',
        'popular',
    ];

    /**
     * @param array<string, StatisticsFormatterInterface> $formatters 格式化器陣列
     */
    public function __construct(
        private readonly StatisticsApplicationService $queryService,
        private readonly array $formatters,
    ) {}

    public function exportOverview(array $options = []): ExportResult
    {
        $startTime = microtime(true);
        $format = $options['format'] ?? self::DEFAULT_FORMAT;
        $this->validateFormat($format);

        try {
            $queryDTO = $this->prepareQueryDTO($options);
            $overview = $this->queryService->getOverview($queryDTO);
            /** @var array<string, mixed> $data */
            $data = $overview->toArray();
            $formatter = $this->formatters[$format];
            $content = $formatter->format($data, $options);

            return new ExportResult(
                format: $format,
                filename: $formatter->getRecommendedFilename('overview', $options),
                content: $content,
                recordCount: $this->countRecords($data),
                executionTime: microtime(true) - $startTime,
                fileSize: strlen($content),
                metadata: $this->buildMetadata($options, $data),
            );
        } catch (Throwable $e) {
            throw new RuntimeException("匯出概覽統計失敗: {$e->getMessage()}", 0, $e);
        }
    }

    public function exportPostStatistics(array $options = []): ExportResult
    {
        $startTime = microtime(true);
        $format = $options['format'] ?? self::DEFAULT_FORMAT;
        $this->validateFormat($format);

        try {
            $queryDTO = $this->prepareQueryDTO($options);
            $paginated = $this->queryService->getPostStatistics($queryDTO);
            /** @var array<string, mixed> $data */
            $data = $paginated->toArray();
            $formatter = $this->formatters[$format];
            $content = $formatter->format($data, $options);

            return new ExportResult(
                format: $format,
                filename: $formatter->getRecommendedFilename('posts', $options),
                content: $content,
                recordCount: $this->countRecords($data),
                executionTime: microtime(true) - $startTime,
                fileSize: strlen($content),
                metadata: $this->buildMetadata($options, $data),
            );
        } catch (Throwable $e) {
            throw new RuntimeException("匯出文章統計失敗: {$e->getMessage()}", 0, $e);
        }
    }

    public function exportSourceDistribution(array $options = []): ExportResult
    {
        $startTime = microtime(true);
        $format = $options['format'] ?? self::DEFAULT_FORMAT;
        $this->validateFormat($format);

        try {
            $queryDTO = $this->prepareQueryDTO($options);
            /** @var array<string, mixed> $data */
            $data = $this->queryService->getSourceDistribution($queryDTO);
            $formatter = $this->formatters[$format];
            $content = $formatter->format($data, $options);

            return new ExportResult(
                format: $format,
                filename: $formatter->getRecommendedFilename('sources', $options),
                content: $content,
                recordCount: $this->countRecords($data),
                executionTime: microtime(true) - $startTime,
                fileSize: strlen($content),
                metadata: $this->buildMetadata($options, $data),
            );
        } catch (Throwable $e) {
            throw new RuntimeException("匯出來源分布統計失敗: {$e->getMessage()}", 0, $e);
        }
    }

    public function exportUserStatistics(array $options = []): ExportResult
    {
        $startTime = microtime(true);
        $format = $options['format'] ?? self::DEFAULT_FORMAT;
        $this->validateFormat($format);

        try {
            $queryDTO = $this->prepareQueryDTO($options);
            $paginated = $this->queryService->getUserStatistics($queryDTO);
            /** @var array<string, mixed> $data */
            $data = $paginated->toArray();
            $formatter = $this->formatters[$format];
            $content = $formatter->format($data, $options);

            return new ExportResult(
                format: $format,
                filename: $formatter->getRecommendedFilename('users', $options),
                content: $content,
                recordCount: $this->countRecords($data),
                executionTime: microtime(true) - $startTime,
                fileSize: strlen($content),
                metadata: $this->buildMetadata($options, $data),
            );
        } catch (Throwable $e) {
            throw new RuntimeException("匯出使用者統計失敗: {$e->getMessage()}", 0, $e);
        }
    }

    public function exportPopularContent(array $options = []): ExportResult
    {
        $startTime = microtime(true);
        $format = $options['format'] ?? self::DEFAULT_FORMAT;
        $this->validateFormat($format);

        try {
            $queryDTO = $this->prepareQueryDTO($options);
            /** @var array<string, mixed> $data */
            $data = $this->queryService->getPopularContent($queryDTO);
            $formatter = $this->formatters[$format];
            $content = $formatter->format($data, $options);

            return new ExportResult(
                format: $format,
                filename: $formatter->getRecommendedFilename('popular', $options),
                content: $content,
                recordCount: $this->countRecords($data),
                executionTime: microtime(true) - $startTime,
                fileSize: strlen($content),
                metadata: $this->buildMetadata($options, $data),
            );
        } catch (Throwable $e) {
            throw new RuntimeException("匯出熱門內容統計失敗: {$e->getMessage()}", 0, $e);
        }
    }

    public function exportBatch(array $types, array $options = []): BatchExportResult
    {
        $startTime = microtime(true);
        $batchId = uniqid('batch_', true);
        $results = [];
        $errors = [];
        $successCount = 0;
        $failureCount = 0;
        foreach ($types as $type) {
            try {
                $this->validateStatisticsType($type);
                $result = match ($type) {
                    'overview' => $this->exportOverview($options),
                    'posts'    => $this->exportPostStatistics($options),
                    'sources'  => $this->exportSourceDistribution($options),
                    'users'    => $this->exportUserStatistics($options),
                    'popular'  => $this->exportPopularContent($options),
                    default    => throw new InvalidArgumentException("不支援的統計類型: {$type}"),
                };
                $results[$type] = $result;
                $successCount++;
            } catch (Throwable $e) {
                $errors[$type] = $e->getMessage();
                $failureCount++;
            }
        }

        return new BatchExportResult(
            results: $results,
            errors: $errors,
            totalExecutionTime: microtime(true) - $startTime,
            successCount: $successCount,
            failureCount: $failureCount,
            batchId: $batchId,
            metadata: [
                'requested_types' => $types,
                'options'         => $options,
                'export_time'     => new DateTime()->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function getSupportedFormats(): array
    {
        return array_keys($this->formatters);
    }

    public function getSupportedTypes(): array
    {
        return self::SUPPORTED_TYPES;
    }

    private function validateFormat(string $format): void
    {
        if (!isset($this->formatters[$format])) {
            throw new InvalidArgumentException("不支援的匯出格式: {$format}");
        }
    }

    private function validateStatisticsType(string $type): void
    {
        if (!in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new InvalidArgumentException("不支援的統計類型: {$type}");
        }
    }

    /**
     * 將匯出選項轉換為 StatisticsQueryDTO.
     */
    private function prepareQueryDTO(array $options): StatisticsQueryDTO
    {
        $startDate = null;
        $endDate = null;

        if (isset($options['period_start']) && $options['period_start'] instanceof DateTimeImmutable) {
            $startDate = $options['period_start'];
        } elseif (isset($options['period_start']) && $options['period_start'] instanceof DateTime) {
            $startDate = DateTimeImmutable::createFromMutable($options['period_start']);
        }

        if (isset($options['period_end']) && $options['period_end'] instanceof DateTimeImmutable) {
            $endDate = $options['period_end'];
        } elseif (isset($options['period_end']) && $options['period_end'] instanceof DateTime) {
            $endDate = DateTimeImmutable::createFromMutable($options['period_end']);
        }

        $page = 1;
        $limit = 20;
        if (isset($options['limit']) && is_numeric($options['limit'])) {
            $limit = min(100, max(1, (int) $options['limit']));
        }
        if (isset($options['offset']) && is_numeric($options['offset'])) {
            $page = (int) floor((int) $options['offset'] / $limit) + 1;
        }

        return new StatisticsQueryDTO(
            startDate: $startDate,
            endDate: $endDate,
            page: $page,
            limit: $limit,
        );
    }

    private function countRecords(array $data): int
    {
        $count = 0;
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->isSequentialArray($value)) {
                    $count += count($value);
                } else {
                    $count += $this->countRecords($value);
                }
            } else {
                $count = max($count, 1);
            }
        }

        return $count;
    }

    private function isSequentialArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMetadata(array $options, array $data): array
    {
        $metadata = [
            'export_time'    => new DateTime()->format('Y-m-d H:i:s'),
            'format_options' => array_diff_key($options, array_flip(['format'])),
        ];
        if (isset($options['include_details'])) {
            $metadata['include_details'] = $options['include_details'];
        }
        if (isset($options['period_start'], $options['period_end'])) {
            $start = $options['period_start'];
            $end = $options['period_end'];
            /** @var string $startStr */
            $startStr = $start instanceof DateTime || $start instanceof DateTimeImmutable
                ? $start->format('Y-m-d')
                : (is_string($start) ? $start : '');
            /** @var string $endStr */
            $endStr = $end instanceof DateTime || $end instanceof DateTimeImmutable
                ? $end->format('Y-m-d')
                : (is_string($end) ? $end : '');
            $metadata['period'] = [
                'start' => $startStr,
                'end'   => $endStr,
            ];
        }

        return $metadata;
    }
}
