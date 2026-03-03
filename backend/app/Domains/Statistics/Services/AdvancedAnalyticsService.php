<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use PDO;

/**
 * 進階分析服務
 * 提供裝置類型、瀏覽器、來源等進階統計分析.
 */
class AdvancedAnalyticsService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly UserAgentParserService $userAgentParser,
    ) {}

    /**
     * 獲取裝置類型統計.
     *
     * @param int|null $postId 指定文章ID，null表示全站統計
     * @param string|null $startDate 開始日期 (Y-m-d)
     * @param string|null $endDate 結束日期 (Y-m-d)
     * @return array<string, int>
     */
    public function getDeviceTypeStats(?int $postId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = 'SELECT user_agent FROM post_views WHERE 1=1';
        $params = [];

        if ($postId !== null) {
            $query .= ' AND post_id = :post_id';
            $params['post_id'] = $postId;
        }

        if ($startDate !== null) {
            $query .= ' AND DATE(view_date) >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate !== null) {
            $query .= ' AND DATE(view_date) <= :end_date';
            $params['end_date'] = $endDate;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $userAgents = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $deviceStats = [
            'Desktop' => 0,
            'Mobile' => 0,
            'Tablet' => 0,
            'Unknown' => 0,
        ];

        foreach ($userAgents as $ua) {
            $parsed = $this->userAgentParser->parse($ua);
            $deviceType = $parsed['device_type'];
            $deviceStats[$deviceType] = ($deviceStats[$deviceType] ?? 0) + 1;
        }

        return $deviceStats;
    }

    /**
     * 獲取瀏覽器統計.
     *
     * @return array<string, int>
     */
    public function getBrowserStats(?int $postId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = 'SELECT user_agent FROM post_views WHERE 1=1';
        $params = [];

        if ($postId !== null) {
            $query .= ' AND post_id = :post_id';
            $params['post_id'] = $postId;
        }

        if ($startDate !== null) {
            $query .= ' AND DATE(view_date) >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate !== null) {
            $query .= ' AND DATE(view_date) <= :end_date';
            $params['end_date'] = $endDate;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $userAgents = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $browserStats = [];

        foreach ($userAgents as $ua) {
            $parsed = $this->userAgentParser->parse($ua);
            $browser = $parsed['browser'];
            $browserStats[$browser] = ($browserStats[$browser] ?? 0) + 1;
        }

        arsort($browserStats);

        return $browserStats;
    }

    /**
     * 獲取操作系統統計.
     *
     * @return array<string, int>
     */
    public function getOSStats(?int $postId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = 'SELECT user_agent FROM post_views WHERE 1=1';
        $params = [];

        if ($postId !== null) {
            $query .= ' AND post_id = :post_id';
            $params['post_id'] = $postId;
        }

        if ($startDate !== null) {
            $query .= ' AND DATE(view_date) >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate !== null) {
            $query .= ' AND DATE(view_date) <= :end_date';
            $params['end_date'] = $endDate;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $userAgents = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $osStats = [];

        foreach ($userAgents as $ua) {
            $parsed = $this->userAgentParser->parse($ua);
            $os = $parsed['os'];
            $osStats[$os] = ($osStats[$os] ?? 0) + 1;
        }

        arsort($osStats);

        return $osStats;
    }

    /**
     * 獲取來源統計（Referrer）.
     *
     * @return array<array{referrer: string, count: int, percentage: float}>
     */
    public function getReferrerStats(?int $postId = null, ?string $startDate = null, ?string $endDate = null, int $limit = 10): array
    {
        $query = 'SELECT referrer, COUNT(*) as count FROM post_views WHERE referrer IS NOT NULL AND referrer != ""';
        $params = [];

        if ($postId !== null) {
            $query .= ' AND post_id = :post_id';
            $params['post_id'] = $postId;
        }

        if ($startDate !== null) {
            $query .= ' AND DATE(view_date) >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate !== null) {
            $query .= ' AND DATE(view_date) <= :end_date';
            $params['end_date'] = $endDate;
        }

        $query .= ' GROUP BY referrer ORDER BY count DESC LIMIT :limit';

        $stmt = $this->pdo->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 計算總數以計算百分比
        $totalQuery = 'SELECT COUNT(*) FROM post_views WHERE referrer IS NOT NULL AND referrer != ""';
        $totalParams = [];

        if ($postId !== null) {
            $totalQuery .= ' AND post_id = :post_id';
            $totalParams['post_id'] = $postId;
        }

        if ($startDate !== null) {
            $totalQuery .= ' AND DATE(view_date) >= :start_date';
            $totalParams['start_date'] = $startDate;
        }

        if ($endDate !== null) {
            $totalQuery .= ' AND DATE(view_date) <= :end_date';
            $totalParams['end_date'] = $endDate;
        }

        $totalStmt = $this->pdo->prepare($totalQuery);
        $totalStmt->execute($totalParams);
        $total = (int) $totalStmt->fetchColumn();

        $stats = [];
        foreach ($results as $row) {
            $stats[] = [
                'referrer' => $row['referrer'],
                'count' => (int) $row['count'],
                'percentage' => $total > 0 ? round(((int) $row['count'] / $total) * 100, 2) : 0.0,
            ];
        }

        return $stats;
    }

    /**
     * 獲取時段分布統計（按小時）.
     *
     * @return array<int, int> 小時(0-23)為鍵，瀏覽數為值
     */
    public function getHourlyDistribution(?int $postId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = "
            SELECT 
                CAST(strftime('%H', view_date) AS INTEGER) as hour,
                COUNT(*) as count
            FROM post_views
            WHERE 1=1
        ";
        $params = [];

        if ($postId !== null) {
            $query .= ' AND post_id = :post_id';
            $params['post_id'] = $postId;
        }

        if ($startDate !== null) {
            $query .= ' AND DATE(view_date) >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate !== null) {
            $query .= ' AND DATE(view_date) <= :end_date';
            $params['end_date'] = $endDate;
        }

        $query .= ' GROUP BY hour ORDER BY hour';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 初始化所有小時為0
        $distribution = array_fill(0, 24, 0);

        foreach ($results as $row) {
            $distribution[(int) $row['hour']] = (int) $row['count'];
        }

        return $distribution;
    }

    /**
     * 獲取綜合分析報告.
     *
     * @return array{
     *     device_types: array<string, int>,
     *     browsers: array<string, int>,
     *     operating_systems: array<string, int>,
     *     top_referrers: array<array{referrer: string, count: int, percentage: float}>,
     *     hourly_distribution: array<int, int>,
     *     total_views: int,
     *     unique_visitors: int
     * }
     */
    public function getComprehensiveReport(?int $postId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        // 獲取總瀏覽量和獨立訪客
        $query = 'SELECT COUNT(*) as total_views, COUNT(DISTINCT user_ip) as unique_visitors FROM post_views WHERE 1=1';
        $params = [];

        if ($postId !== null) {
            $query .= ' AND post_id = :post_id';
            $params['post_id'] = $postId;
        }

        if ($startDate !== null) {
            $query .= ' AND DATE(view_date) >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate !== null) {
            $query .= ' AND DATE(view_date) <= :end_date';
            $params['end_date'] = $endDate;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'device_types' => $this->getDeviceTypeStats($postId, $startDate, $endDate),
            'browsers' => $this->getBrowserStats($postId, $startDate, $endDate),
            'operating_systems' => $this->getOSStats($postId, $startDate, $endDate),
            'top_referrers' => $this->getReferrerStats($postId, $startDate, $endDate, 10),
            'hourly_distribution' => $this->getHourlyDistribution($postId, $startDate, $endDate),
            'total_views' => (int) ($totals['total_views'] ?? 0),
            'unique_visitors' => (int) ($totals['unique_visitors'] ?? 0),
        ];
    }
}
