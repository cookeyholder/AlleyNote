<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

/**
 * 匯出結果值物件.
 *
 * 封裝統計資料匯出的結果資訊。
 */
final readonly class ExportResult
{
    public function __construct(
        public string $format,
        public string $filename,
        public string $content,
        public int $recordCount,
        public float $executionTime,
        public int $fileSize,
        public array $metadata = [],
    ) {}

    /**
     * 取得格式化的檔案大小.
     */
    public function getFormattedFileSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = (int) floor(log($this->fileSize, 1024));

        return sprintf('%.2f %s', $this->fileSize / (1024 ** $factor), $units[$factor] ?? 'TB');
    }

    /**
     * 是否為大型檔案.
     */
    public function isLargeFile(): bool
    {
        return $this->fileSize > 10 * 1024 * 1024; // 10MB
    }

    /**
     * 轉換為陣列.
     *
     * @return array{
     *     format: string,
     *     filename: string,
     *     record_count: int,
     *     execution_time: float,
     *     file_size: int,
     *     formatted_file_size: string,
     *     metadata: array
     * }
     */
    public function toArray(): array
    {
        return [
            'format' => $this->format,
            'filename' => $this->filename,
            'record_count' => $this->recordCount,
            'execution_time' => $this->executionTime,
            'file_size' => $this->fileSize,
            'formatted_file_size' => $this->getFormattedFileSize(),
            'metadata' => $this->metadata,
        ];
    }
}
