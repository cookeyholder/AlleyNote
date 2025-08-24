<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use App\Infrastructure\Cache\CacheKeys;
use PHPUnit\Framework\TestCase;

class CacheKeysTest extends TestCase
{
    public function testPostCacheKey(): void
    {
        $id = 123;
        $key = CacheKeys::post($id);

        $this->assertEquals('alleynote:post:123', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testPostByUuidCacheKey(): void
    {
        $uuid = 'abc-123-def-456';
        $key = CacheKeys::postByUuid($uuid);

        $this->assertEquals('alleynote:post:uuid:abc-123-def-456', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testPostListCacheKey(): void
    {
        $page = 2;
        $status = 'published';
        $key = CacheKeys::postList($page, $status);

        $this->assertEquals('alleynote:posts:published:page:2', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testPinnedPostsCacheKey(): void
    {
        $key = CacheKeys::pinnedPosts();

        $this->assertEquals('alleynote:posts:pinned', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testPostsByCategoryCacheKey(): void
    {
        $category = 'announcement';
        $page = 3;
        $key = CacheKeys::postsByCategory($category, $page);

        $this->assertEquals('alleynote:posts:category:announcement:page:3', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testUserPostsCacheKey(): void
    {
        $userId = 456;
        $page = 1;
        $key = CacheKeys::userPosts($userId, $page);

        $this->assertEquals('alleynote:user:456:posts:page:1', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testPostTagsCacheKey(): void
    {
        $postId = 789;
        $key = CacheKeys::postTags($postId);

        $this->assertEquals('alleynote:post:789:tags', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testPostViewsCacheKey(): void
    {
        $postId = 101;
        $key = CacheKeys::postViews($postId);

        $this->assertEquals('alleynote:post:101:views', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testUserCacheKey(): void
    {
        $userId = 555;
        $key = CacheKeys::user($userId);

        $this->assertEquals('alleynote:user:555', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testUserByEmailCacheKey(): void
    {
        $email = 'test@example.com';
        $key = CacheKeys::userByEmail($email);

        $expectedHash = md5($email);
        $this->assertEquals("alleynote:user:email:{$expectedHash}", $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testSystemConfigCacheKey(): void
    {
        $key = CacheKeys::systemConfig();

        $this->assertEquals('alleynote:system:config', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testTagPostsCacheKey(): void
    {
        $tagId = 42;
        $page = 2;
        $key = CacheKeys::tagPosts($tagId, $page);

        $this->assertEquals('alleynote:tag:42:posts:page:2', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testSearchResultsCacheKey(): void
    {
        $query = 'test search';
        $page = 1;
        $key = CacheKeys::searchResults($query, $page);

        $expectedHash = md5($query);
        $this->assertEquals("alleynote:search:{$expectedHash}:page:1", $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testRateLimitByIpCacheKey(): void
    {
        $ip = '192.168.1.1';
        $action = 'login';
        $key = CacheKeys::rateLimitByIp($ip, $action);

        $expectedHash = md5($ip);
        $this->assertEquals("alleynote:rate_limit:ip:{$expectedHash}:login", $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testRateLimitByUserCacheKey(): void
    {
        $userId = 123;
        $action = 'post_create';
        $key = CacheKeys::rateLimitByUser($userId, $action);

        $this->assertEquals('alleynote:rate_limit:user:123:post_create', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testGetPrefix(): void
    {
        $prefix = CacheKeys::getPrefix();
        $this->assertEquals('alleynote', $prefix);
    }

    public function testGetSeparator(): void
    {
        $separator = CacheKeys::getSeparator();
        $this->assertEquals(':', $separator);
    }

    public function testIsValidKeyWithValidKey(): void
    {
        $validKey = 'alleynote:post:123';
        $this->assertTrue(CacheKeys::isValidKey($validKey));
    }

    public function testIsValidKeyWithInvalidKey(): void
    {
        $invalidKey = 'other:post:123';
        $this->assertFalse(CacheKeys::isValidKey($invalidKey));
    }

    public function testParseValidKey(): void
    {
        $key = 'alleynote:post:123:tags';
        $parts = CacheKeys::parseKey($key);

        $this->assertEquals(['post', '123', 'tags'], $parts);
    }

    public function testParseInvalidKey(): void
    {
        $invalidKey = 'other:post:123';
        $parts = CacheKeys::parseKey($invalidKey);

        $this->assertEquals([], $parts);
    }

    public function testPatternGeneration(): void
    {
        $pattern = CacheKeys::pattern('post', 123);
        $this->assertEquals('alleynote:post:123*', $pattern);
    }

    public function testUserPattern(): void
    {
        $userId = 456;
        $pattern = CacheKeys::userPattern($userId);

        $this->assertEquals('alleynote:user:456*', $pattern);
    }

    public function testPostPattern(): void
    {
        $postId = 789;
        $pattern = CacheKeys::postPattern($postId);

        $this->assertEquals('alleynote:post:789*', $pattern);
    }

    public function testPostsListPattern(): void
    {
        $pattern = CacheKeys::postsListPattern();
        $this->assertEquals('alleynote:posts*', $pattern);
    }

    public function testStatsPattern(): void
    {
        $pattern = CacheKeys::statsPattern();
        $this->assertEquals('alleynote:stats*', $pattern);
    }

    public function testDailyStatsCacheKey(): void
    {
        $date = '2023-12-01';
        $key = CacheKeys::dailyStats($date);

        $this->assertEquals('alleynote:stats:daily:2023-12-01', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testMonthlyStatsCacheKey(): void
    {
        $yearMonth = '2023-12';
        $key = CacheKeys::monthlyStats($yearMonth);

        $this->assertEquals('alleynote:stats:monthly:2023-12', $key);
        $this->assertTrue(CacheKeys::isValidKey($key));
    }

    public function testCacheKeyConsistency(): void
    {
        // 測試相同參數產生相同的快取鍵
        $postId = 123;
        $key1 = CacheKeys::post($postId);
        $key2 = CacheKeys::post($postId);

        $this->assertEquals($key1, $key2);
    }

    public function testCacheKeyUniqueness(): void
    {
        // 測試不同參數產生不同的快取鍵
        $key1 = CacheKeys::post(123);
        $key2 = CacheKeys::post(456);

        $this->assertNotEquals($key1, $key2);
    }

    public function testEmptyParameterHandling(): void
    {
        // 測試空字串參數不會影響快取鍵
        $key = CacheKeys::postsByCategory('', 1);

        // 應該排除空值
        $this->assertStringNotContainsString('::', $key);
    }
}
