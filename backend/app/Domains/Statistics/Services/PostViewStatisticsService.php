<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Services;

use PDO;
use Throwable;

/**
 * 文章瀏覽統計服務.
 */
class PostViewStatisticsService
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * 取得單篇文章的瀏覽統計.
     *
     * @return array{views: int, unique_visitors: int}
     */
    public function getPostViewStats(int $postId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                COUNT(*) as views,
                COUNT(DISTINCT user_ip) as unique_visitors
            FROM post_views
            WHERE post_id = :post_id
        ');

        $stmt->execute(['post_id' => $postId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($result)) {
            $result = [];
        }

        return [
            'views' => $this->toInt($result['views'] ?? 0),
            'unique_visitors' => $this->toInt($result['unique_visitors'] ?? 0),
        ];
    }

    /**
     * 取得多篇文章的瀏覽統計（批量查詢）.
     *
     * @param array<int> $postIds
     * @return array<int, array{views: int, unique_visitors: int}>
     */
    public function getBatchPostViewStats(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postIds), '?'));

        $stmt = $this->pdo->prepare("
            SELECT
                post_id,
                COUNT(*) as views,
                COUNT(DISTINCT user_ip) as unique_visitors
            FROM post_views
            WHERE post_id IN ({$placeholders})
            GROUP BY post_id
        ");

        $stmt->execute($postIds);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($results)) {
            $results = [];
        }

        $stats = [];
        foreach ($results as $row) {
            if (!is_array($row) || !isset($row['post_id'])) {
                continue;
            }

            $postId = $this->toInt($row['post_id']);

            $stats[$postId] = [
                'views' => $this->toInt($row['views'] ?? 0),
                'unique_visitors' => $this->toInt($row['unique_visitors'] ?? 0),
            ];
        }

        // 填充沒有瀏覽記錄的文章
        foreach ($postIds as $postId) {
            if (!isset($stats[$postId])) {
                $stats[$postId] = [
                    'views' => 0,
                    'unique_visitors' => 0,
                ];
            }
        }

        return $stats;
    }

    /**
     * 記錄文章瀏覽.
     */
    public function recordView(int $postId, ?int $userId, string $userIp, ?string $userAgent = null, ?string $referrer = null): bool
    {
        $uuid = $this->generateUuid();

        // 開始交易
        $this->pdo->beginTransaction();

        try {
            // 1. 記錄瀏覽事件到 post_views 表
            $stmt = $this->pdo->prepare('
                INSERT INTO post_views (uuid, post_id, user_id, user_ip, user_agent, referrer, view_date)
                VALUES (:uuid, :post_id, :user_id, :user_ip, :user_agent, :referrer, :view_date)
            ');

            $stmt->execute([
                'uuid' => $uuid,
                'post_id' => $postId,
                'user_id' => $userId,
                'user_ip' => $userIp,
                'user_agent' => $userAgent,
                'referrer' => $referrer,
                'view_date' => date('Y-m-d H:i:s'),
            ]);

            // 2. 更新文章的瀏覽次數
            $updateStmt = $this->pdo->prepare('
                UPDATE posts
                SET views = views + 1
                WHERE id = :post_id
            ');

            $updateStmt->execute(['post_id' => $postId]);

            // 提交交易
            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {
            // 發生錯誤時回滾
            $this->pdo->rollBack();

            throw $e;
        }
    }

    /**
     * 生成 UUID.
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
        );
    }

    private function toInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int) $value;
        }

        return 0;
    }
}
