<?php

declare(strict_types=1);

namespace Tests\Integration\Post;

use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\AuthApiIntegrationTestCase;

/**
 * Post 生命週期 HTTP API 整合測試.
 *
 * 以完整應用程式堆疊（路由、中介軟體、控制器、領域服務、SQLite 資料庫）覆蓋：
 * - 建立公告（成功、預設草稿狀態、未認證、驗證失敗）
 * - 更新公告（內容變更、標籤關聯替換、不存在、無欄位可更新）
 * - 刪除公告（204 後資料列移除且後續讀取 404）
 *
 * 每項操作皆同時驗證 HTTP 回應與資料庫狀態。
 */
#[Group('integration')]
#[Group('api')]
#[Group('post')]
final class PostLifecycleApiIntegrationTest extends AuthApiIntegrationTestCase
{
    private const AUTHOR_EMAIL = 'post-author@example.com';

    private const CLIENT_IP = '203.0.113.7';

    private int $authorId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authorId = $this->createAuthUser('postauthor', self::AUTHOR_EMAIL, ['user']);
    }

    /**
     * 測試建立公告回傳 201 並寫入資料庫.
     */
    public function testStoreReturns201AndPersistsPost(): void
    {
        $response = $this->authorRequest('POST', '/api/posts', [
            'title'        => '系統維護公告',
            'content'      => '<p>將於週六凌晨進行系統維護。</p>',
            'status'       => 'published',
            'publish_date' => $this->rfc3339(time() - 60),
        ]);
        $data = $this->getJson($response);

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));
        $this->assertSame('貼文建立成功', $this->getStringValue($data, 'message'));

        $postId = $this->getIntValue($data, 'data', 'id');
        $uuid = $this->getStringValue($data, 'data', 'uuid');
        $this->assertGreaterThan(0, $postId);
        $this->assertNotSame('', $uuid);
        $this->assertSame('系統維護公告', $this->getStringValue($data, 'data', 'title'));
        $this->assertSame('published', $this->getStringValue($data, 'data', 'status'));
        $this->assertSame($this->authorId, $this->getIntValue($data, 'data', 'user_id'));
        $this->assertSame(self::CLIENT_IP, $this->getStringValue($data, 'data', 'user_ip'));

        $row = $this->fetchPostRow($postId);
        $this->assertNotNull($row, '資料庫應存在剛建立的公告');
        $this->assertSame($uuid, self::strOf($row['uuid'] ?? null));
        $this->assertSame('系統維護公告', self::strOf($row['title'] ?? null));
        $this->assertSame('published', self::strOf($row['status'] ?? null));
        $this->assertSame($this->authorId, self::intOf($row['user_id'] ?? null));
        $this->assertSame(self::CLIENT_IP, self::strOf($row['user_ip'] ?? null));
        $this->assertNull($row['deleted_at'] ?? null, '新建立的公告不應被刪除');
    }

    /**
     * 測試未指定狀態時預設建立草稿.
     */
    public function testStoreDefaultsToDraftStatus(): void
    {
        $response = $this->authorRequest('POST', '/api/posts', [
            'title'        => '尚未發布的草稿',
            'content'      => '<p>這是一篇草稿。</p>',
            'publish_date' => $this->rfc3339(time() - 60),
        ]);
        $data = $this->getJson($response);

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('draft', $this->getStringValue($data, 'data', 'status'));

        $row = $this->fetchPostRow($this->getIntValue($data, 'data', 'id'));
        $this->assertNotNull($row);
        $this->assertSame('draft', self::strOf($row['status'] ?? null));
    }

    /**
     * 測試未認證建立公告回傳 401 且不寫入資料庫.
     */
    public function testStoreWithoutTokenReturns401AndPersistsNothing(): void
    {
        $csrf = $this->csrfCredentials();
        $response = $this->request('POST', '/api/posts', [
            'title'   => '未認證的公告',
            'content' => '<p>不應該被建立。</p>',
        ], headers: $csrf['headers'], cookies: $csrf['cookies']);
        $this->assertErrorResponse($response, 401);

        $this->assertSame(0, $this->countPosts(), '未認證請求不應寫入任何公告');
    }

    /**
     * 測試缺少標題時回傳 400 且不寫入資料庫.
     */
    public function testStoreWithBlankTitleReturns400AndPersistsNothing(): void
    {
        $response = $this->authorRequest('POST', '/api/posts', [
            'title'        => '   ',
            'content'      => '<p>內容存在但標題空白。</p>',
            'publish_date' => $this->rfc3339(time() - 60),
        ]);
        $data = $this->assertErrorResponse($response, 400);

        $errors = $this->getArrayValue($data, 'errors');
        $this->assertNotEmpty($errors, '驗證失敗應回傳錯誤欄位資訊');
        $this->assertSame(0, $this->countPosts());
    }

    /**
     * 測試更新公告會同步至 HTTP 回應與資料庫.
     */
    public function testUpdatePersistsChangesToDatabase(): void
    {
        $postId = $this->createPostViaApi([
            'title'        => '原始標題',
            'content'      => '<p>原始內容</p>',
            'publish_date' => $this->rfc3339(time() - 120),
        ]);

        $before = $this->fetchPostRow($postId);
        $this->assertNotNull($before);

        $response = $this->authorRequest('PUT', '/api/posts/' . $postId, [
            'title'     => '更新後的標題',
            'content'   => '<p>更新後的內容</p>',
            'status'    => 'published',
            'is_pinned' => true,
        ]);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));
        $this->assertSame('貼文更新成功', $this->getStringValue($data, 'message'));
        $this->assertSame('更新後的標題', $this->getStringValue($data, 'data', 'title'));
        $this->assertSame('published', $this->getStringValue($data, 'data', 'status'));
        $this->assertTrue((bool) $this->getNestedValue($data, 'data', 'is_pinned'));

        $after = $this->fetchPostRow($postId);
        $this->assertNotNull($after);
        $this->assertSame('更新後的標題', self::strOf($after['title'] ?? null));
        $this->assertSame('<p>更新後的內容</p>', self::strOf($after['content'] ?? null));
        $this->assertSame('published', self::strOf($after['status'] ?? null));
        $this->assertSame(1, self::intOf($after['is_pinned'] ?? null), '置頂狀態應更新為 1');
        $this->assertGreaterThanOrEqual(
            self::strOf($before['updated_at'] ?? null),
            self::strOf($after['updated_at'] ?? null),
            'updated_at 應不早於更新前',
        );
    }

    /**
     * 測試以 tag_ids 更新公告會替換關聯並調整使用次數.
     */
    public function testUpdateReplacesTagAssociations(): void
    {
        $firstTagId = $this->insertTag('php');
        $secondTagId = $this->insertTag('mysql');

        $postId = $this->createPostViaApi([
            'title'        => '含標籤的公告',
            'content'      => '<p>標籤整合測試</p>',
            'tag_ids'      => [$firstTagId],
            'publish_date' => $this->rfc3339(time() - 60),
        ]);
        $this->assertSame([$firstTagId], $this->pivotTagIdsOf($postId));
        $this->assertSame(1, $this->tagUsageCount($firstTagId));

        $response = $this->authorRequest('PUT', '/api/posts/' . $postId, [
            'tag_ids' => [$secondTagId],
        ]);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        $this->assertSame([$secondTagId], $this->pivotTagIdsOf($postId), '舊標籤應被替換');
        $this->assertSame(0, $this->tagUsageCount($firstTagId), '移除後原標籤使用次數應歸零');
        $this->assertSame(1, $this->tagUsageCount($secondTagId));
    }

    /**
     * 測試更新不存在的公告回傳 404.
     */
    public function testUpdateNonExistentPostReturns404(): void
    {
        $response = $this->authorRequest('PUT', '/api/posts/999999', [
            'title' => '不存在的公告',
        ]);
        $this->assertErrorResponse($response, 404);
    }

    /**
     * 測試沒有任何可更新欄位時回傳 400.
     */
    public function testUpdateWithEmptyPayloadReturns400(): void
    {
        $postId = $this->createPostViaApi([
            'title'        => '空更新測試',
            'content'      => '<p>內容</p>',
            'publish_date' => $this->rfc3339(time() - 60),
        ]);

        $response = $this->authorRequest('PUT', '/api/posts/' . $postId, []);
        $data = $this->assertErrorResponse($response, 400);

        $this->assertSame('沒有要更新的欄位', $this->getStringValue($data, 'error', 'message'));
    }

    /**
     * 測試刪除公告回傳 204、移除資料列，且後續讀取回傳 404.
     */
    public function testDestroyRemovesRowAndSubsequentReadsReturn404(): void
    {
        $postId = $this->createPostViaApi([
            'title'        => '即將刪除的公告',
            'content'      => '<p>刪除測試</p>',
            'publish_date' => $this->rfc3339(time() - 60),
        ]);

        $response = $this->authorRequest('DELETE', '/api/posts/' . $postId);
        $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('', (string) $response->getBody(), '204 回應不應有內容');
        $this->assertSame(0, $this->countPosts(), '刪除後資料列不應存在');

        $showResponse = $this->request('GET', '/api/posts/' . $postId);
        $this->assertErrorResponse($showResponse, 404);

        $secondDelete = $this->authorRequest('DELETE', '/api/posts/' . $postId);
        $this->assertErrorResponse($secondDelete, 404);
    }

    /**
     * 以作者身分發送帶 CSRF 與 Bearer Token 的 JSON 請求.
     *
     * @param array<string, mixed>|null $body
     */
    private function authorRequest(string $method, string $path, ?array $body = null): ResponseInterface
    {
        return $this->authedRequest($method, $path, $body, self::AUTHOR_EMAIL);
    }

    /**
     * 登入並發送帶 CSRF 與 Authorization 的請求.
     *
     * @param array<string, mixed>|null $body
     */
    private function authedRequest(string $method, string $path, ?array $body, string $email): ResponseInterface
    {
        $token = $this->accessTokenOf($this->loginUser($email, ip: $this->uniqueClientIp()));
        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $token]);

        return $this->request($method, $path, $body, headers: $csrf['headers'], cookies: $csrf['cookies'], ip: self::CLIENT_IP);
    }

    /**
     * 透過 API 建立公告並回傳其 ID.
     *
     * @param array<string, mixed> $overrides
     */
    private function createPostViaApi(array $overrides = []): int
    {
        $payload = array_merge([
            'title'        => '生命週期測試公告 ' . $this->generateRandomString(5),
            'content'      => '<p>自動建立的測試內容</p>',
            'publish_date' => $this->rfc3339(time() - 60),
        ], $overrides);

        $response = $this->authorRequest('POST', '/api/posts', $payload);
        $data = $this->getJson($response);
        $this->assertSame(201, $response->getStatusCode(), '前置步驟：建立公告應成功：' . $response->getBody());

        return $this->getIntValue($data, 'data', 'id');
    }

    /**
     * 由資料庫取得公告資料列.
     *
     * @return array<string, mixed>|null
     */
    private function fetchPostRow(int $postId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = :id');
        $stmt->execute(['id' => $postId]);
        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * 統計 posts 資料列數量.
     */
    private function countPosts(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM posts');

        return (int) ($stmt !== false ? $stmt->fetchColumn() : 0);
    }

    /**
     * 取得公告關聯的標籤 ID 列表（依插入順序）.
     *
     * @return array<int>
     */
    private function pivotTagIdsOf(int $postId): array
    {
        $stmt = $this->db->prepare('SELECT tag_id FROM post_tags WHERE post_id = :post_id ORDER BY created_at, tag_id');
        $stmt->execute(['post_id' => $postId]);
        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        $ids = [];
        foreach ($rows as $row) {
            if (isset($row['tag_id']) && is_numeric($row['tag_id'])) {
                $ids[] = (int) $row['tag_id'];
            }
        }

        return $ids;
    }

    /**
     * 取得標籤目前的使用次數.
     */
    private function tagUsageCount(int $tagId): int
    {
        $stmt = $this->db->prepare('SELECT usage_count FROM tags WHERE id = :id');
        $stmt->execute(['id' => $tagId]);
        $count = $stmt->fetchColumn();

        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * 直接於資料庫建立標籤.
     */
    private function insertTag(string $name): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tags (name, slug, usage_count, created_at) VALUES (:name, :slug, 0, datetime('now'))",
        );
        $stmt->execute(['name' => $name, 'slug' => $name]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * 將時間戳記轉為 RFC3339 字串.
     */
    private function rfc3339(int $timestamp): string
    {
        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }

    /**
     * 將資料庫欄位值安全轉為字串.
     */
    private static function strOf(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (string) $value;
        }

        return '';
    }

    /**
     * 將資料庫欄位值安全轉為整數.
     */
    private static function intOf(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }

        return 0;
    }
}
