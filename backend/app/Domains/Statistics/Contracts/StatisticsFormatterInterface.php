<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

/**
 * 統計資料格式化器介面.
 *
 * 定義統計資料格式化的標準操作。
 */
interface StatisticsFormatterInterface
{
    /**
     * 取得支援的格式名稱.
     */
    public function getFormat(): string;

    /**
     * 取得檔案副檔名.
     */
    public function getFileExtension(): string;

    /**
     * 取得 MIME 類型.
     */
    public function getMimeType(): string;

    /**
     * 格式化統計資料.
     *
     * @param array<string, mixed> $data 統計資料
     * @param array<string, mixed> $options 格式化選項
     */
    public function format(array $data, array $options = []): string;

    /**
     * 是否支援大量資料.
     */
    public function supportsLargeData(): bool;

    /**
     * 取得建議的檔案名稱.
     */
    public function getRecommendedFilename(string $type, array $options = []): string;
}
