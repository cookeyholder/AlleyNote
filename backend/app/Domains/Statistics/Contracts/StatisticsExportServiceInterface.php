<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use DateTime;

/**
 * 統計資料匯出服務合約介面.
 *
 * 定義統計資料匯出的標準操作，支援多種格式和篩選條件。
 */
interface StatisticsExportServiceInterface
{
    /**
     * 匯出統計概覽資料.
     *
     * @param array{
     *     format?: string,
     *     period_start?: DateTime,
     *     period_end?: DateTime,
     *     include_details?: bool
     * } $options
     */
    public function exportOverview(array $options = []): ExportResult;

    /**
     * 匯出文章統計資料.
     *
     * @param array{
     *     format?: string,
     *     period_start?: DateTime,
     *     period_end?: DateTime,
     *     limit?: int,
     *     offset?: int
     * } $options
     */
    public function exportPostStatistics(array $options = []): ExportResult;

    /**
     * 匯出來源分布統計資料.
     *
     * @param array{
     *     format?: string,
     *     period_start?: DateTime,
     *     period_end?: DateTime,
     *     group_by_detail?: bool
     * } $options
     */
    public function exportSourceDistribution(array $options = []): ExportResult;

    /**
     * 匯出使用者活動統計資料.
     *
     * @param array{
     *     format?: string,
     *     period_start?: DateTime,
     *     period_end?: DateTime,
     *     include_inactive?: bool
     * } $options
     */
    public function exportUserStatistics(array $options = []): ExportResult;

    /**
     * 匯出熱門內容統計資料.
     *
     * @param array{
     *     format?: string,
     *     period_start?: DateTime,
     *     period_end?: DateTime,
     *     limit?: int
     * } $options
     */
    public function exportPopularContent(array $options = []): ExportResult;

    /**
     * 批次匯出多種統計資料.
     *
     * @param array<string> $types 要匯出的統計類型
     * @param array{
     *     format?: string,
     *     period_start?: DateTime,
     *     period_end?: DateTime,
     *     output_dir?: string
     * } $options
     */
    public function exportBatch(array $types, array $options = []): BatchExportResult;

    /**
     * 取得支援的匯出格式.
     *
     * @return array<string>
     */
    public function getSupportedFormats(): array;

    /**
     * 取得支援的統計類型.
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array;
}
