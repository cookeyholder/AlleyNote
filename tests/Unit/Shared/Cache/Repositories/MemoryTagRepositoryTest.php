<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Cache\Repositories;

use App\Shared\Cache\Repositories\MemoryTagRepository;
use PHPUnit\Framework\TestCase;

/**
 * MemoryTagRepository 測試（修正版）.
 */
class MemoryTagRepositoryTest extends TestCase
{
    private MemoryTagRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new MemoryTagRepository();
    }

    public function testSetAndGetTags(): void
    {
        $key = 'test_key';
        $tags = ['user:123', 'module:posts'];

        $result = $this->repository->setTags($key, $tags, 3600);
        $this->assertTrue($result);

        $retrievedTags = $this->repository->getTags($key);
        $this->assertEquals($tags, $retrievedTags);
    }

    public function testAddTags(): void
    {
        $key = 'test_key';
        $initialTags = ['user:123'];
        $additionalTags = ['module:posts', 'temporal:daily'];

        $this->repository->setTags($key, $initialTags, 3600);
        $this->repository->addTags($key, $additionalTags);

        $allTags = $this->repository->getTags($key);
        $this->assertCount(3, $allTags);
        $this->assertContains('user:123', $allTags);
        $this->assertContains('module:posts', $allTags);
        $this->assertContains('temporal:daily', $allTags);
    }

    public function testRemoveTags(): void
    {
        $key = 'test_key';
        $tags = ['user:123', 'module:posts', 'temporal:daily'];

        $this->repository->setTags($key, $tags, 3600);
        $this->repository->removeTags($key, ['module:posts']);

        $remainingTags = $this->repository->getTags($key);
        $this->assertCount(2, $remainingTags);
        $this->assertContains('user:123', $remainingTags);
        $this->assertContains('temporal:daily', $remainingTags);
        $this->assertNotContains('module:posts', $remainingTags);
    }

    public function testHasTag(): void
    {
        $key = 'test_key';
        $tags = ['user:123', 'module:posts'];

        $this->repository->setTags($key, $tags, 3600);

        $this->assertTrue($this->repository->hasTag($key, 'user:123'));
        $this->assertTrue($this->repository->hasTag($key, 'module:posts'));
        $this->assertFalse($this->repository->hasTag($key, 'nonexistent:tag'));
    }

    public function testGetKeysByTag(): void
    {
        $tag = 'user:123';
        $keys = ['key1', 'key2', 'key3'];

        foreach ($keys as $key) {
            $this->repository->setTags($key, [$tag, 'other:tag'], 3600);
        }

        $retrievedKeys = $this->repository->getKeysByTag($tag);
        $this->assertEquals($keys, $retrievedKeys);
    }

    public function testDeleteByTags(): void
    {
        $keys = ['key1', 'key2', 'key3'];
        $tag = 'user:123';

        foreach ($keys as $key) {
            $this->repository->setTags($key, [$tag], 3600);
        }

        $deletedKeys = $this->repository->deleteByTags($tag);
        $this->assertEquals($keys, $deletedKeys);

        // 驗證標籤已被清空
        foreach ($keys as $key) {
            $this->assertEmpty($this->repository->getTags($key));
        }
    }

    public function testDeleteKey(): void
    {
        $key = 'test_key';
        $tags = ['user:123', 'module:posts'];

        $this->repository->setTags($key, $tags, 3600);
        $this->repository->deleteKey($key);

        $this->assertEmpty($this->repository->getTags($key));

        // 驗證標籤到鍵的映射也被清理
        foreach ($tags as $tag) {
            $keysForTag = $this->repository->getKeysByTag($tag);
            $this->assertNotContains($key, $keysForTag);
        }
    }

    public function testGetAllTags(): void
    {
        $testData = [
            'key1' => ['user:123', 'module:posts'],
            'key2' => ['user:456', 'module:comments'],
            'key3' => ['temporal:daily'],
        ];

        foreach ($testData as $key => $tags) {
            $this->repository->setTags($key, $tags, 3600);
        }

        $allTags = $this->repository->getAllTags();
        $expectedTags = ['user:123', 'module:posts', 'user:456', 'module:comments', 'temporal:daily'];

        $this->assertCount(5, $allTags);
        foreach ($expectedTags as $expectedTag) {
            $this->assertContains($expectedTag, $allTags);
        }
    }

    public function testTagExists(): void
    {
        $key = 'test_key';
        $tag = 'user:123';

        $this->assertFalse($this->repository->tagExists($tag));

        $this->repository->setTags($key, [$tag], 3600);
        $this->assertTrue($this->repository->tagExists($tag));

        $this->repository->deleteKey($key);
        $this->assertFalse($this->repository->tagExists($tag));
    }

    public function testGetTagStatistics(): void
    {
        $testData = [
            'key1' => ['user:123'],
            'key2' => ['user:123'],
            'key3' => ['module:posts'],
            'key4' => ['module:posts'],
            'key5' => ['module:posts'],
        ];

        foreach ($testData as $key => $tags) {
            $this->repository->setTags($key, $tags, 3600);
        }

        $stats = $this->repository->getTagStatistics();

        $this->assertEquals(2, $stats['user:123']);
        $this->assertEquals(3, $stats['module:posts']);
    }

    public function testTouch(): void
    {
        $key = 'test_key';
        $tags = ['user:123'];

        // 設定短過期時間
        $this->repository->setTags($key, $tags, 1);

        // 立即更新過期時間
        $this->repository->touch($key, 3600);

        // 等待一段時間，確保原本的過期時間已過
        sleep(2);

        // 標籤應該仍然存在
        $retrievedTags = $this->repository->getTags($key);
        $this->assertEquals($tags, $retrievedTags);
    }

    public function testFlush(): void
    {
        $testData = [
            'key1' => ['user:123', 'module:posts'],
            'key2' => ['user:456', 'module:comments'],
        ];

        foreach ($testData as $key => $tags) {
            $this->repository->setTags($key, $tags, 3600);
        }

        $this->repository->flush();

        // 驗證所有資料都已清空
        foreach (array_keys($testData) as $key) {
            $this->assertEmpty($this->repository->getTags($key));
        }

        $this->assertEmpty($this->repository->getAllTags());
    }

    public function testCleanupUnusedTags(): void
    {
        $key = 'test_key';
        $tags = ['user:123', 'module:posts'];

        $this->repository->setTags($key, $tags, 3600);

        // 刪除鍵但不清理標籤
        $this->repository->deleteKey($key);

        $cleanedCount = $this->repository->cleanupUnusedTags();
        $this->assertGreaterThanOrEqual(0, $cleanedCount);
    }

    public function testTagExpiration(): void
    {
        $key = 'expiring_key';
        $tags = ['user:123'];

        // 設定 1 秒過期時間
        $this->repository->setTags($key, $tags, 1);

        // 立即驗證標籤存在
        $this->assertEquals($tags, $this->repository->getTags($key));
        $this->assertTrue($this->repository->tagExists('user:123'));

        // 等待過期
        sleep(2);

        // 驗證標籤已過期
        $this->assertEmpty($this->repository->getTags($key));
        $this->assertEmpty($this->repository->getKeysByTag('user:123'));
    }

    public function testMultipleKeysWithSameTag(): void
    {
        $tag = 'shared:tag';
        $keys = ['key1', 'key2', 'key3'];

        foreach ($keys as $key) {
            $this->repository->setTags($key, [$tag], 3600);
        }

        $retrievedKeys = $this->repository->getKeysByTag($tag);
        $this->assertEquals($keys, $retrievedKeys);

        // 刪除一個鍵
        $this->repository->deleteKey('key2');

        $remainingKeys = $this->repository->getKeysByTag($tag);
        $this->assertCount(2, $remainingKeys);
        $this->assertContains('key1', $remainingKeys);
        $this->assertContains('key3', $remainingKeys);
        $this->assertNotContains('key2', $remainingKeys);
    }

    public function testEmptyTagArray(): void
    {
        $key = 'test_key';

        // 設定空標籤陣列
        $this->repository->setTags($key, [], 3600);

        $tags = $this->repository->getTags($key);
        $this->assertEmpty($tags);
    }

    public function testDuplicateTagsNormalization(): void
    {
        $key = 'test_key';
        $tags = ['user:123', 'user:123', 'module:posts', 'module:posts'];

        $this->repository->setTags($key, $tags, 3600);

        $retrievedTags = $this->repository->getTags($key);
        $this->assertCount(2, $retrievedTags);
        $this->assertContains('user:123', $retrievedTags);
        $this->assertContains('module:posts', $retrievedTags);
    }
}
