<?php

declare(strict_types=1);

namespace Tests\Integration\Support;

use PDO;
use Tests\Support\IntegrationTestCase;

/**
 * 展示整合測試架構使用方式的範例測試.
 */
class IntegrationTestExampleTest extends IntegrationTestCase
{
    public function testDatabaseIntegration(): void
    {
        // 測試資料庫功能
        $this->assertInstanceOf(PDO::class, $this->db);

        // 插入測試資料
        $postId = $this->insertTestPost([
            'title' => '測試貼文標題',
            'content' => '測試貼文內容',
        ]);

        $this->assertIsInt($postId);
        $this->assertGreaterThan(0, $postId);

        // 驗證資料是否正確插入
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = ?');
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        $this->assertIsArray($post);
        $this->assertEquals('測試貼文標題', $post['title']);
        $this->assertEquals('測試貼文內容', $post['content']);
    }

    public function testCacheIntegration(): void
    {
        // 測試快取功能
        $this->assertCacheIsEmpty();

        // 設定快取值
        $this->setCacheValue('test_key', 'test_value');
        $this->assertCacheHasKey('test_key');
        $this->assertCacheValue('test_key', 'test_value');

        // 測試快取模擬物件
        $result = $this->cache->get('test_key');
        $this->assertEquals('test_value', $result);

        // 測試 remember 方法
        $remembered = $this->cache->remember('computed_value', function () {
            return 'computed_result';
        });
        $this->assertEquals('computed_result', $remembered);
    }

    public function testHttpResponseHelpers(): void
    {
        // 測試 HTTP 回應輔助方法
        $response = $this->createResponseMock();
        $this->assertResponseStatus($response, 200);

        // 測試 JSON 回應
        $data = ['status' => 'success', 'message' => 'OK'];
        $jsonResponse = $this->createJsonResponseMock($data, 201);
        $this->assertResponseStatus($jsonResponse, 201);
        $this->assertJsonResponseHasKey($jsonResponse, 'status');
        $this->assertJsonResponseValue($jsonResponse, 'status', 'success');
    }

    public function testComprehensiveIntegration(): void
    {
        // 整合多種功能的測試場景

        // 1. 建立測試使用者
        $userId = $this->insertTestUser([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        // 2. 快取使用者資訊
        $this->setCacheValue("user:{$userId}", [
            'id' => $userId,
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        // 3. 建立該使用者的貼文
        $postId = $this->insertTestPost([
            'title' => '使用者的測試貼文',
            'content' => '這是一篇測試貼文',
            'user_id' => $userId,
        ]);

        // 4. 驗證整合結果
        $stmt = $this->db->prepare('
            SELECT p.*, u.username 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.id = ?
        ');
        $stmt->execute([$postId]);
        $result = $stmt->fetch();

        $this->assertEquals('testuser', $result['username']);
        $this->assertEquals('使用者的測試貼文', $result['title']);

        // 5. 檢查快取
        $cachedUser = $this->cache->get("user:{$userId}");
        $this->assertEquals('testuser', $cachedUser['username']);

        // 6. 建立成功回應
        $response = $this->createJsonResponseMock([
            'post_id' => $postId,
            'user' => $cachedUser,
            'message' => 'Post created successfully',
        ]);

        $this->assertJsonResponseHasKey($response, 'post_id');
        $this->assertJsonResponseValue($response, 'post_id', $postId);
    }
}
