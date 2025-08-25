<?php

declare(strict_types=1);

namespace App\Domains\Post\Services;

/**
 * Post 相關的快取鍵服務
 * 
 * 此服務負責產生 Post Domain 相關的快取鍵，
 * 避免 Domain 層直接依賴 Infrastructure 層的 CacheKeys 類別
 */
class PostCacheKeyService
{
    private const PREFIX = 'alleynote';
    private const SEPARATOR = ':';

    /**
     * 建立快取鍵的通用方法
     */
    private static function buildKey(...$parts): string
    {
        // 過濾空值並轉換為字串
        $cleanParts = array_filter(
            array_map('strval', $parts),
            fn($part) => $part !== '',
        );

        return self::PREFIX . self::SEPARATOR . implode(self::SEPARATOR, $cleanParts);
    }

    /**
     * 建立模式匹配的快取鍵（用於刪除相關快取）
     */
    private static function pattern(...$parts): string
    {
        $pattern = self::buildKey(...$parts);
        return $pattern . '*';
    }

    // Post 相關快取鍵
    public static function post(int $id): string
    {
        return self::buildKey('post', $id);
    }

    public static function postByUuid(string $uuid): string
    {
        return self::buildKey('post', 'uuid', $uuid);
    }

    public static function postList(int $page, string $status = 'published'): string
    {
        return self::buildKey('posts', $status, 'page', $page);
    }

    public static function pinnedPosts(): string
    {
        return self::buildKey('posts', 'pinned');
    }

    public static function postsByCategory(string $category, int $page = 1): string
    {
        return self::buildKey('posts', 'category', $category, 'page', $page);
    }

    public static function postTags(int $postId): string
    {
        return self::buildKey('post', $postId, 'tags');
    }

    public static function postViews(int $postId): string
    {
        return self::buildKey('post', $postId, 'views');
    }

    public static function tagPosts(int $tagId, int $page = 1): string
    {
        return self::buildKey('tag', $tagId, 'posts', 'page', $page);
    }

    // 模式匹配快取鍵
    public static function userPattern(int $userId): string
    {
        return self::pattern('user', $userId);
    }

    public static function postPattern(int $postId): string
    {
        return self::pattern('post', $postId);
    }

    public static function postsListPattern(): string
    {
        return self::pattern('posts');
    }
}
