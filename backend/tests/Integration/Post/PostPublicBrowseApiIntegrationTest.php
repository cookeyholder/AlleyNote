<?php

declare(strict_types=1);

namespace Tests\Integration\Post;

use PHPUnit\Framework\Attributes\Group;
use Tests\Support\AuthApiIntegrationTestCase;

/**
 * 公開瀏覽 Post HTTP API 整合測試.
 *
 * 以完整應用程式堆疊覆蓋不需認證的公告瀏覽行為：
 * - 列表：分頁（page/limit 與上下限）、搜尋篩選、置頂排序
 * - 詳情：單筆取得與 404
 * - 瀏覽數：已發布文章計數、草稿不計數
 */
#[Group('integration')]
#[Group('api')]
#[Group('post')]
final class PostPublicBrowseApiIntegrationTest extends AuthApiIntegrationTestCase
{
    private static int $seqNumber = 70001;

    protected function setUp(): void
    {
        parent::setUp();
        $this->alignPostViewsSchema();
    }

    /**
     * 測試列表分頁回傳正確總數、排序與頁面內容.
     */
    public function testIndexPaginatesPublishedPostsNewestFirst(): void
    {
        $ids = [];
        foreach ([50, 40, 30, 20, 10] as $minutesAgo) {
            $ids[] = $this->insertPublishedPost('分頁公告 ' . $minutesAgo, minutesAgo: $minutesAgo);
        }

        $firstResponse = $this->request('GET', '/api/posts?page=1&limit=3');
        $this->assertSame(200, $firstResponse->getStatusCode());
        $firstPage = $this->getJson($firstResponse);
        $this->assertTrue((bool) ($firstPage['success'] ?? false));
        $this->assertSame(5, $this->getIntValue($firstPage, 'pagination', 'total'));
        $this->assertSame(1, $this->getIntValue($firstPage, 'pagination', 'page'));
        $this->assertSame(3, $this->getIntValue($firstPage, 'pagination', 'per_page'));
        // publish_date 越新越前面：50 分鐘前的文章最舊，10 分鐘前最新
        $this->assertSame(
            [$ids[4], $ids[3], $ids[2]],
            $this->collectIds($firstPage),
            '第一頁應為最新的三篇',
        );

        $secondPage = $this->getJson($this->request('GET', '/api/posts?page=2&limit=3'));
        $this->assertSame(2, $this->getIntValue($secondPage, 'pagination', 'page'));
        $this->assertSame([$ids[1], $ids[0]], $this->collectIds($secondPage), '第二頁應為其餘兩篇');
    }

    /**
     * 測試 limit 參數會被限制在 1 到 100 之間.
     */
    public function testIndexClampsLimitToBounds(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->insertPublishedPost('上限測試公告 ' . $i);
        }

        $tooLarge = $this->getJson($this->request('GET', '/api/posts?limit=500'));
        $this->assertSame(100, $this->getIntValue($tooLarge, 'pagination', 'per_page'), '超過上限應設為 100');
        $this->assertSame(3, $this->getIntValue($tooLarge, 'pagination', 'total'));

        $tooSmall = $this->getJson($this->request('GET', '/api/posts?limit=0'));
        $this->assertSame(1, $this->getIntValue($tooSmall, 'pagination', 'per_page'), '低於下限應設為 1');
        $this->assertCount(1, $this->collectIds($tooSmall));
    }

    /**
     * 測試 search 篩選僅回傳標題或內容符合的文章.
     */
    public function testIndexSearchFiltersByTitleAndContent(): void
    {
        $alphaId = $this->insertPublishedPost('Alpha 系統維護公告', '<p>例行維護作業</p>');
        $betaId = $this->insertPublishedPost('系統升級公告', '<p>gamma 平台升級完成</p>');

        $byTitle = $this->getJson($this->request('GET', '/api/posts?search=' . urlencode('Alpha')));
        $this->assertSame([$alphaId], $this->collectIds($byTitle), '應比對標題關鍵字');

        $byContent = $this->getJson($this->request('GET', '/api/posts?search=' . urlencode('gamma')));
        $this->assertSame([$betaId], $this->collectIds($byContent), '應比對內容關鍵字');

        $noMatch = $this->getJson($this->request('GET', '/api/posts?search=' . urlencode('不存在的關鍵詞組')));
        $this->assertSame(0, $this->getIntValue($noMatch, 'pagination', 'total'));
        $this->assertSame([], $noMatch['data'] ?? null);
    }

    /**
     * 測試置頂文章排在列表最前面.
     */
    public function testIndexRanksPinnedPostsFirst(): void
    {
        $pinnedId = $this->insertPublishedPost('較舊但置頂', minutesAgo: 60, pinned: true);
        $newerId = $this->insertPublishedPost('較新未置頂', minutesAgo: 5);

        $data = $this->getJson($this->request('GET', '/api/posts'));

        $this->assertSame([$pinnedId, $newerId], $this->collectIds($data), '置頂文章應優先於時間排序');
    }

    /**
     * 測試詳情頁取得文章並累加瀏覽統計.
     */
    public function testShowReturnsDetailAndRecordsViewCount(): void
    {
        $postId = $this->insertPublishedPost('瀏覽數測試公告');

        $firstResponse = $this->request('GET', '/api/posts/' . $postId, ip: '198.51.100.11');
        $this->assertSame(200, $firstResponse->getStatusCode(), (string) $firstResponse->getBody());
        $firstData = $this->getJson($firstResponse);

        $this->assertTrue((bool) ($firstData['success'] ?? false));
        $this->assertSame($postId, $this->getIntValue($firstData, 'data', 'id'));
        $this->assertSame('瀏覽數測試公告', $this->getStringValue($firstData, 'data', 'title'));
        $this->assertSame(1, $this->getIntValue($firstData, 'data', 'views'), '首次瀏覽後 views 應為 1');
        $this->assertSame(1, $this->getIntValue($firstData, 'data', 'unique_visitors'));

        $secondData = $this->getJson(
            $this->request('GET', '/api/posts/' . $postId, ip: '198.51.100.12'),
        );
        $this->assertSame(2, $this->getIntValue($secondData, 'data', 'views'), '第二次瀏覽應累加');
        $this->assertSame(2, $this->getIntValue($secondData, 'data', 'unique_visitors'));

        $stmt = $this->db->prepare('SELECT views FROM posts WHERE id = :id');
        $stmt->execute(['id' => $postId]);
        $this->assertSame(2, (int) $stmt->fetchColumn(), 'posts.views 欄位應同步累加');

        $viewStmt = $this->db->prepare('SELECT COUNT(*) FROM post_views WHERE post_id = :id');
        $viewStmt->execute(['id' => $postId]);
        $this->assertSame(2, (int) $viewStmt->fetchColumn(), 'post_views 應有兩筆瀏覽記錄');
    }

    /**
     * 測試草稿不會被計算瀏覽次數.
     */
    public function testShowDoesNotCountViewsForDraftPosts(): void
    {
        $postId = $this->insertTestPost([
            'title'        => '未發布的草稿',
            'status'       => 'draft',
            'seq_number'   => self::$seqNumber++,
            'publish_date' => gmdate('Y-m-d H:i:s'),
        ]);

        $response = $this->request('GET', '/api/posts/' . $postId, ip: '198.51.100.50');
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, $this->getIntValue($data, 'data', 'views'), '草稿不應計入瀏覽數');
        $this->assertSame(0, $this->getIntValue($data, 'data', 'unique_visitors'));

        $stmt = $this->db->prepare('SELECT views FROM posts WHERE id = :id');
        $stmt->execute(['id' => $postId]);
        $this->assertSame(0, (int) $stmt->fetchColumn());

        $viewStmt = $this->db->prepare('SELECT COUNT(*) FROM post_views WHERE post_id = :id');
        $viewStmt->execute(['id' => $postId]);
        $this->assertSame(0, (int) $viewStmt->fetchColumn());
    }

    /**
     * 測試查詢不存在的文章回傳 404.
     */
    public function testShowMissingPostReturns404(): void
    {
        $response = $this->request('GET', '/api/posts/999999');
        $data = $this->assertErrorResponse($response, 404);

        $this->assertStringContainsString('找不到', $this->getStringValue($data, 'error', 'message'));
    }

    /**
     * 將 post_views 調整為與正式 migration 一致的結構.
     *
     * DatabaseTestTrait 的 post_views 缺少 incrementViews 所需的
     * uuid / user_ip / view_date 欄位，需重建以符合正式結構。
     */
    private function alignPostViewsSchema(): void
    {
        $this->db->exec('PRAGMA foreign_keys = OFF');
        $this->db->exec('DROP TABLE IF EXISTS post_views');
        $this->db->exec('
            CREATE TABLE post_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                post_id INTEGER NOT NULL,
                user_id INTEGER,
                user_ip TEXT NOT NULL,
                view_date TEXT NOT NULL,
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
            )
        ');
        $this->db->exec('PRAGMA foreign_keys = ON');
    }

    /**
     * 插入一篇已發布的測試文章並回傳 ID.
     */
    private function insertPublishedPost(string $title, string $content = '<p>公開瀏覽測試內容</p>', int $minutesAgo = 30, bool $pinned = false): int
    {
        return $this->insertTestPost([
            'title'        => $title,
            'content'      => $content,
            'status'       => 'published',
            'is_pinned'    => $pinned ? 1 : 0,
            'views'        => 0,
            'seq_number'   => self::$seqNumber++,
            'publish_date' => gmdate('Y-m-d H:i:s', time() - $minutesAgo * 60),
            'created_at'   => gmdate('Y-m-d H:i:s', time() - $minutesAgo * 60),
            'updated_at'   => gmdate('Y-m-d H:i:s', time() - $minutesAgo * 60),
        ]);
    }

    /**
     * 從列表回應收集文章 ID.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<int>
     */
    private function collectIds(array $payload): array
    {
        $ids = [];
        foreach (is_array($payload['data'] ?? null) ? $payload['data'] : [] as $item) {
            if (is_array($item) && isset($item['id']) && is_numeric($item['id'])) {
                $ids[] = (int) $item['id'];
            }
        }

        return $ids;
    }
}
