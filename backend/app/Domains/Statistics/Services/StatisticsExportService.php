<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use PDO;

/**
 * 統計報表匯出服務
 * 支援 CSV、Excel（通過CSV）、PDF 格式匯出.
 */
class StatisticsExportService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AdvancedAnalyticsService $analyticsService,
    ) {}

    /**
     * 匯出文章瀏覽統計為 CSV.
     *
     * @return string CSV 內容
     */
    public function exportViewsToCSV(?int $postId = null, ?string $startDate = null, ?string $endDate = null): string
    {
        $query = '
            SELECT 
                pv.id,
                pv.post_id,
                p.title as post_title,
                pv.user_id,
                u.username,
                pv.user_ip,
                pv.user_agent,
                pv.referrer,
                pv.view_date
            FROM post_views pv
            LEFT JOIN posts p ON pv.post_id = p.id
            LEFT JOIN users u ON pv.user_id = u.id
            WHERE 1=1
        ';

        $params = [];

        if ($postId !== null) {
            $query .= ' AND pv.post_id = :post_id';
            $params['post_id'] = $postId;
        }

        if ($startDate !== null) {
            $query .= ' AND DATE(pv.view_date) >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate !== null) {
            $query .= ' AND DATE(pv.view_date) >= :end_date';
            $params['end_date'] = $endDate;
        }

        $query .= ' ORDER BY pv.view_date DESC';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 產生 CSV
        $output = fopen('php://temp', 'r+');

        // 寫入標頭
        fputcsv($output, ['ID', '文章ID', '文章標題', '使用者ID', '使用者名稱', 'IP地址', 'User-Agent', '來源', '瀏覽時間']);

        // 寫入資料
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'],
                $row['post_id'],
                $row['post_title'] ?? '',
                $row['user_id'] ?? '',
                $row['username'] ?? '匿名',
                $row['user_ip'],
                $row['user_agent'] ?? '',
                $row['referrer'] ?? '',
                $row['view_date'],
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * 匯出綜合分析報告為 CSV.
     *
     * @return string CSV 內容
     */
    public function exportComprehensiveReportToCSV(?int $postId = null, ?string $startDate = null, ?string $endDate = null): string
    {
        $report = $this->analyticsService->getComprehensiveReport($postId, $startDate, $endDate);

        $output = fopen('php://temp', 'r+');

        // 總覽資訊
        fputcsv($output, ['統計報告']);
        fputcsv($output, ['總瀏覽量', $report['total_views']]);
        fputcsv($output, ['獨立訪客', $report['unique_visitors']]);
        fputcsv($output, []);

        // 裝置類型統計
        fputcsv($output, ['裝置類型統計']);
        fputcsv($output, ['裝置類型', '數量']);
        foreach ($report['device_types'] as $device => $count) {
            fputcsv($output, [$device, $count]);
        }
        fputcsv($output, []);

        // 瀏覽器統計
        fputcsv($output, ['瀏覽器統計']);
        fputcsv($output, ['瀏覽器', '數量']);
        foreach ($report['browsers'] as $browser => $count) {
            fputcsv($output, [$browser, $count]);
        }
        fputcsv($output, []);

        // 操作系統統計
        fputcsv($output, ['操作系統統計']);
        fputcsv($output, ['操作系統', '數量']);
        foreach ($report['operating_systems'] as $os => $count) {
            fputcsv($output, [$os, $count]);
        }
        fputcsv($output, []);

        // 熱門來源
        fputcsv($output, ['熱門來源']);
        fputcsv($output, ['來源', '數量', '百分比']);
        foreach ($report['top_referrers'] as $ref) {
            fputcsv($output, [$ref['referrer'], $ref['count'], $ref['percentage'] . '%']);
        }
        fputcsv($output, []);

        // 時段分布
        fputcsv($output, ['時段分布（0-23小時）']);
        fputcsv($output, ['小時', '瀏覽數']);
        foreach ($report['hourly_distribution'] as $hour => $count) {
            fputcsv($output, [$hour, $count]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * 匯出為 JSON.
     *
     * @return string JSON 內容
     */
    public function exportToJSON(?int $postId = null, ?string $startDate = null, ?string $endDate = null): string
    {
        $report = $this->analyticsService->getComprehensiveReport($postId, $startDate, $endDate);

        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
