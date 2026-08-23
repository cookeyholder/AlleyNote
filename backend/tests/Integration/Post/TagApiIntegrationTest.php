<?php

declare(strict_types=1);

namespace Tests\Integration\Post;

use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\AuthApiIntegrationTestCase;

/**
 * 標籤（Tags）HTTP API 整合測試.
 *
 * 以完整應用程式堆疊覆蓋 /api/tags 端點：
 * - 公開讀取：列表（分頁、搜尋）與單筆查詢
 * - 認證寫入：建立、更新、刪除
 *
 * 每項操作皆同時驗證 HTTP 回應與資料庫狀態。
 */
#[Group('integration')]
#[Group('api')]
#[Group('post')]
#[Group('tag')]
final class TagApiIntegrationTest extends AuthApiIntegrationTestCase
{
    private const USER_EMAIL = 'tag-user@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthUser('taguser', self::USER_EMAIL, ['user']);
    }

    /**
     * 測試無任何標籤時列表回應空集合與分頁資訊.
     */
    public function testIndexReturnsEmptyListWhenNoTagsExist(): void
    {
        $response = $this->request('GET', '/api/tags');
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) ($data['success'] ?? false));
        $this->assertSame([], $data['data'] ?? null);
        $this->assertSame(0, $this->getIntValue($data, 'pagination', 'total'));
        $this->assertSame(1, $this->getIntValue($data, 'pagination', 'page'));
        $this->assertSame(20, $this->getIntValue($data, 'pagination', 'per_page'));
    }

    /**
     * 測試標籤列表分頁與排序.
     */
    public function testIndexPaginatesTags(): void
    {
        $this->insertTag('alpha');
        $this->insertTag('beta');
        $this->insertTag('gamma');

        $response = $this->request('GET', '/api/tags?page=1&per_page=2');
        $this->assertSame(200, $response->getStatusCode());
        $firstPage = $this->getJson($response);

        $this->assertSame(3, $this->getIntValue($firstPage, 'pagination', 'total'));
        $this->assertSame(2, $this->getIntValue($firstPage, 'pagination', 'per_page'));
        $this->assertSame(2, $this->getIntValue($firstPage, 'pagination', 'last_page'));

        $firstName = $this->getStringValue($firstPage, 'data', '0', 'name');
        $secondName = $this->getStringValue($firstPage, 'data', '1', 'name');
        $this->assertSame('alpha', $firstName, '未使用時應依名稱排序');
        $this->assertSame('beta', $secondName);
        $this->assertCount(2, is_array($firstPage['data'] ?? null) ? $firstPage['data'] : []);

        $secondPage = $this->getJson($this->request('GET', '/api/tags?page=2&per_page=2'));
        $this->assertCount(1, is_array($secondPage['data'] ?? null) ? $secondPage['data'] : []);
        $this->assertSame('gamma', $this->getStringValue($secondPage, 'data', '0', 'name'));
    }

    /**
     * 測試標籤列表搜尋過濾名稱、slug 與描述.
     */
    public function testIndexSearchFiltersTags(): void
    {
        $this->insertTag('php-dev', null, 'PHP 相關主題');
        $this->insertTag('javascript');

        $matched = $this->getJson($this->request('GET', '/api/tags?search=php'));
        $names = $this->collectNames($matched);
        $this->assertSame(['php-dev'], $names, '搜尋應比對名稱');

        $byDescription = $this->getJson($this->request('GET', '/api/tags?search=' . urlencode('相關主題')));
        $this->assertSame(['php-dev'], $this->collectNames($byDescription), '搜尋應比對描述');
    }

    /**
     * 測試取得單一標籤.
     */
    public function testShowReturnsRequestedTag(): void
    {
        $tagId = $this->insertTag('release-notes', 'release-notes-slug', '發佈公告用', '#33ccff');

        $response = $this->request('GET', '/api/tags/' . $tagId);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) ($data['success'] ?? false));
        $this->assertSame($tagId, $this->getIntValue($data, 'data', 'id'));
        $this->assertSame('release-notes', $this->getStringValue($data, 'data', 'name'));
        $this->assertSame('release-notes-slug', $this->getStringValue($data, 'data', 'slug'));
        $this->assertSame('#33ccff', $this->getStringValue($data, 'data', 'color'));
    }

    /**
     * 測試查詢不存在的標籤回傳 404.
     */
    public function testShowMissingTagReturns404(): void
    {
        $response = $this->request('GET', '/api/tags/999999');
        $data = $this->assertErrorResponse($response, 404);

        $this->assertStringContainsString('標籤不存在', $this->getStringValue($data, 'message'));
    }

    /**
     * 測試建立標籤會自動產生 slug 並寫入資料庫.
     */
    public function testStoreCreatesTagWithGeneratedSlug(): void
    {
        $response = $this->userRequest('POST', '/api/tags', [
            'name'        => 'Release Notes',
            'description' => '版本發布訊息',
            'color'       => '#00ff88',
        ]);
        $data = $this->getJson($response);

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));
        $this->assertSame('標籤建立成功', $this->getStringValue($data, 'message'));
        $this->assertSame('release-notes', $this->getStringValue($data, 'data', 'slug'));

        $stmt = $this->db->prepare('SELECT name, slug, description, color, usage_count FROM tags WHERE id = :id');
        $stmt->execute(['id' => $this->getIntValue($data, 'data', 'id')]);
        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch();
        $this->assertIsArray($row, '資料庫應存在剛建立的標籤');
        $this->assertSame('Release Notes', self::strOf($row['name'] ?? null));
        $this->assertSame('release-notes', self::strOf($row['slug'] ?? null));
        $this->assertSame('版本發布訊息', self::strOf($row['description'] ?? null));
        $this->assertSame('#00ff88', self::strOf($row['color'] ?? null));
        $this->assertSame(0, self::intOf($row['usage_count'] ?? null));
    }

    /**
     * 測試更新標籤欄位會同步至資料庫.
     */
    public function testUpdatePersistsChangesToDatabase(): void
    {
        $tagId = $this->insertTag('legacy-name', null, '舊描述', '#111111');

        $response = $this->userRequest('PUT', '/api/tags/' . $tagId, [
            'name'        => 'new-name',
            'description' => '新描述',
            'color'       => '#abcdef',
        ]);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));
        $this->assertSame('標籤更新成功', $this->getStringValue($data, 'message'));

        $stmt = $this->db->prepare('SELECT name, slug, description, color FROM tags WHERE id = :id');
        $stmt->execute(['id' => $tagId]);
        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch();
        $this->assertIsArray($row);
        $this->assertSame('new-name', self::strOf($row['name'] ?? null));
        $this->assertSame('new-name', self::strOf($row['slug'] ?? null), '更新名稱時應同步更新 slug');
        $this->assertSame('新描述', self::strOf($row['description'] ?? null));
        $this->assertSame('#abcdef', self::strOf($row['color'] ?? null));
    }

    /**
     * 測試更新不存在的標籤回傳 404.
     */
    public function testUpdateMissingTagReturns404(): void
    {
        $response = $this->userRequest('PUT', '/api/tags/999999', ['name' => 'ghost']);
        $data = $this->assertErrorResponse($response, 404);

        $this->assertStringContainsString('標籤不存在', $this->getStringValue($data, 'message'));
    }

    /**
     * 測試刪除標籤會移除資料列並解除文章關聯.
     */
    public function testDestroyRemovesTagAndPostAssociations(): void
    {
        $tagId = $this->insertTag('doomed');
        $postId = $this->insertTestPost(['title' => '關聯測試公告']);
        $pivotStmt = $this->db->prepare(
            "INSERT INTO post_tags (post_id, tag_id, created_at) VALUES (:post_id, :tag_id, datetime('now'))",
        );
        $pivotStmt->execute(['post_id' => $postId, 'tag_id' => $tagId]);

        $response = $this->userRequest('DELETE', '/api/tags/' . $tagId);
        $data = $this->getJson($response);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue((bool) ($data['success'] ?? false));
        $this->assertSame('標籤刪除成功', $this->getStringValue($data, 'message'));

        $tagStmt = $this->db->prepare('SELECT COUNT(*) FROM tags WHERE id = :id');
        $tagStmt->execute(['id' => $tagId]);
        $this->assertSame(0, (int) $tagStmt->fetchColumn(), '標籤資料列應被移除');

        $pivotCountStmt = $this->db->prepare('SELECT COUNT(*) FROM post_tags WHERE tag_id = :tag_id');
        $pivotCountStmt->execute(['tag_id' => $tagId]);
        $this->assertSame(0, (int) $pivotCountStmt->fetchColumn(), '文章關聯應一併解除');
    }

    /**
     * 測試刪除不存在的標籤回傳 404.
     */
    public function testDestroyMissingTagReturns404(): void
    {
        $response = $this->userRequest('DELETE', '/api/tags/999999');
        $this->assertErrorResponse($response, 404);
    }

    /**
     * 以一般使用者身分發送帶 CSRF 與 Bearer Token 的 JSON 請求.
     *
     * @param array<string, mixed>|null $body
     */
    private function userRequest(string $method, string $path, ?array $body = null): ResponseInterface
    {
        $token = $this->accessTokenOf($this->loginUser(self::USER_EMAIL, ip: $this->uniqueClientIp()));
        $csrf = $this->csrfCredentials(headers: ['Authorization' => 'Bearer ' . $token]);

        return $this->request($method, $path, $body, headers: $csrf['headers'], cookies: $csrf['cookies']);
    }

    /**
     * 直接於資料庫建立標籤並回傳 ID.
     */
    private function insertTag(string $name, ?string $slug = null, ?string $description = null, ?string $color = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tags (name, slug, description, color, usage_count, created_at)
             VALUES (:name, :slug, :description, :color, 0, datetime('now'))",
        );
        $stmt->execute([
            'name'        => $name,
            'slug'        => $slug ?? $name,
            'description' => $description,
            'color'       => $color,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * 從列表回應收集標籤名稱.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string>
     */
    private function collectNames(array $payload): array
    {
        $names = [];
        foreach (is_array($payload['data'] ?? null) ? $payload['data'] : [] as $tag) {
            if (is_array($tag) && isset($tag['name']) && is_string($tag['name'])) {
                $names[] = $tag['name'];
            }
        }

        return $names;
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
