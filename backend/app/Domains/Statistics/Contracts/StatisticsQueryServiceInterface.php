<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use DateTime;

/**
 * 統計查詢服務介面.
 *
 * 為匯出服務提供統計資料查詢的標準介面。
 */
interface StatisticsQueryServiceInterface
{
    /**
     * 取得統計概覽資料.
     *
     * @param array{
     *     period_start?: DateTime,
     *     period_end?: DateTime,
     *     include_details?: bool
     * } $options
     * @return array<string, mixed>
     */
    public function getOverview(array $options = []): array;

    /**
     * 取得文章統計資料.
     *
     * @param array{
     *     period_start?: DateTime,
     *     period_end?: DateTime,
     *     limit?: int,
     *     offset?: int
     * } $options
     * @return array<string, mixed>
     */
    public function getPostStatistics(array $options = []): array;

    /**
     * 取得來源分布統計.
     *
     * @param array{
     *     period_start?: DateTime,
     *     period_end?: DateTime,
     *     group_by_detail?: bool
     * } $options
     * @return array<string, mixed>
     */
    public function getSourceDistribution(array $options = []): array;

    /**
     * 取得使用者統計資料.
     *
     * @param array{
     *     period_start?: DateTime,
     *     period_end?: DateTime,
     *     include_inactive?: bool
     * } $options
     * @return array<string, mixed>
     */
    public function getUserStatistics(array $options = []): array;

    /**
     * 取得熱門內容統計.
     *
     * @param array{
     *     period_start?: DateTime,
     *     period_end?: DateTime,
     *     limit?: int
     * } $options
     * @return array<string, mixed>
     */
    public function getPopularContent(array $options = []): array;
}
