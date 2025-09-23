<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

/**
 * 批次匯出結果值物件.
 *
 * 封裝批次統計資料匯出的結果資訊。
 */
final readonly class BatchExportResult
{
    /**
     * @param array<string, ExportResult> $results 各統計類型的匯出結果
     * @param array<string, string> $errors 匯出失敗的錯誤資訊
     */
    public function __construct(
        public array $results,
        public array $errors,
        public float $totalExecutionTime,
        public int $successCount,
        public int $failureCount,
        public string $batchId,
        public array $metadata = [],
    ) {}

    /**
     * 取得總記錄數.
     */
    public function getTotalRecordCount(): int
    {
        return array_sum(array_map(fn($result) => $result->recordCount, $this->results));
    }

    /**
     * 取得總檔案大小.
     */
    public function getTotalFileSize(): int
    {
        return array_sum(array_map(fn($result) => $result->fileSize, $this->results));
    }

    /**
     * 取得成功率.
     */
    public function getSuccessRate(): float
    {
        $total = $this->successCount + $this->failureCount;

        return $total > 0 ? ($this->successCount / $total) * 100 : 0.0;
    }

    /**
     * 是否全部成功.
     */
    public function isAllSuccessful(): bool
    {
        return $this->failureCount === 0 && $this->successCount > 0;
    }

    /**
     * 是否有失敗.
     */
    public function hasFailures(): bool
    {
        return $this->failureCount > 0;
    }

    /**
     * 取得格式化的總檔案大小.
     */
    public function getFormattedTotalFileSize(): string
    {
        $totalSize = $this->getTotalFileSize();
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = (int) floor(log($totalSize, 1024));

        return sprintf('%.2f %s', $totalSize / (1024 ** $factor), $units[$factor] ?? 'TB');
    }

    /**
     * 轉換為陣列.
     *
     * @return array{
     *     batch_id: string,
     *     success_count: int,
     *     failure_count: int,
     *     success_rate: float,
     *     total_execution_time: float,
     *     total_record_count: int,
     *     total_file_size: int,
     *     formatted_total_file_size: string,
     *     results: array,
     *     errors: array,
     *     metadata: array
     * }
     */
    public function toArray(): array
    {
        return [
            'batch_id' => $this->batchId,
            'success_count' => $this->successCount,
            'failure_count' => $this->failureCount,
            'success_rate' => $this->getSuccessRate(),
            'total_execution_time' => $this->totalExecutionTime,
            'total_record_count' => $this->getTotalRecordCount(),
            'total_file_size' => $this->getTotalFileSize(),
            'formatted_total_file_size' => $this->getFormattedTotalFileSize(),
            'results' => array_map(fn($result) => $result->toArray(), $this->results),
            'errors' => $this->errors,
            'metadata' => $this->metadata,
        ];
    }
}
