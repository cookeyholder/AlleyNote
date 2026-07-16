<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Services;

use App\Domains\Statistics\Contracts\BatchExportResult;
use App\Domains\Statistics\Contracts\ExportResult;
use App\Domains\Statistics\Contracts\StatisticsExportServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsFormatterInterface;
use App\Domains\Statistics\DTOs\StatisticsQueryDTO;
use App\Domains\Statistics\Services\StatisticsQueryService;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class StatisticsExportService implements StatisticsExportServiceInterface
{
    /** 預設匯出格式 */
    private const DEFAULT_FORMAT = 'json';

    /** 支援的統計類型 */
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
        private readonly StatisticsQueryService $queryService,
        private readonly array $formatters,
    ) {}

    public function exportOverview(array $options = []): ExportResult
    {
        $startTime = microtime(true);
        $format = $options['format'] ?? self::DEFAULT_FORMAT;
        $this->validateFormat($format);

        try {
            // 準備查詢 DTO
            $queryDTO = $this->buildQueryDTO($options);
            // 取得統計資料
            $overview = $this->queryService->getOverview($queryDTO);
            /** @var array<string, mixed> $data */
            $data = $overview->toArray();
            // 格式化資料
            $formatter = $this->formatters[$format];
            $content = $formatter->format($data, $options);

            // 建立匯出結果
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
            $queryDTO = $this->buildQueryDTO($options);
            $postStats = $this->queryService->getPostStatistics($queryDTO);
            /** @var array<string, mixed> $data */
            $data = $postStats->toArray();
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
            $queryDTO = $this->buildQueryDTO($options);
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
            $queryDTO = $this->buildQueryDTO($options);
            $userStats = $this->queryService->getUserStatistics($queryDTO);
            /** @var array<string, mixed> $data */
            $data = $userStats->toArray();
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
            $queryDTO = $this->buildQueryDTO($options);
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

    /**
     * 驗證匯出格式是否支援.
     */
    private function validateFormat(string $format): void
    {
        if (!isset($this->formatters[$format])) {
            throw new InvalidArgumentException("不支援的匯出格式: {$format}");
        }
    }

    /**
     * 驗證統計類型是否支援.
     */
    private function validateStatisticsType(string $type): void
    {
        if (!in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new InvalidArgumentException("不支援的統計類型: {$type}");
        }
    }

    /**
     * 從選項陣列建構統計查詢 DTO.
     */
    private function buildQueryDTO(array $options): StatisticsQueryDTO
    {
        $startDate = null;
        $endDate = null;

        if (isset($options['period_start'])) {
            $startDate = $options['period_start'] instanceof DateTimeImmutable
                ? $options['period_start']
                : ($options['period_start'] instanceof DateTime
                    ? DateTimeImmutable::createFromMutable($options['period_start'])
                    : null);
        }
        if (isset($options['period_end'])) {
            $endDate = $options['period_end'] instanceof DateTimeImmutable
                ? $options['period_end']
                : ($options['period_end'] instanceof DateTime
                    ? DateTimeImmutable::createFromMutable($options['period_end'])
                    : null);
        }

        return new StatisticsQueryDTO(
            startDate: $startDate,
            endDate: $endDate,
            page: isset($options['page']) && is_int($options['page']) ? max(1, $options['page']) : 1,
            limit: isset($options['limit']) && is_int($options['limit']) ? max(1, min(100, $options['limit'])) : 20,
        );
    }

    /**
     * 計算記錄數.
     */
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

    /**
     * 檢查是否為順序陣列.
     */
    private function isSequentialArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * 建立元資料.
     *
     * @return array<string, mixed>
     */
    private function buildMetadata(array $options, array $data): array
    {
        $metadata = [
            'export_time'    => new DateTime()->format('Y-m-d H:i:s'),
            'format_options' => array_diff_key($options, array_flip(['format'])),
        ];
        // 添加資料相關的元資料
        if (isset($options['include_details'])) {
            $metadata['include_details'] = $options['include_details'];
        }
        if (isset($options['period_start'], $options['period_end'])) {
            $metadata['period'] = [
                'start' => $options['period_start']->format('Y-m-d'),
                'end'   => $options['period_end']->format('Y-m-d'),
            ];
        }

        return $metadata;
    }
}
