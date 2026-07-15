<?php

declare(strict_types=1);

namespace App\Domains\Post\Services;

use App\Domains\Post\Models\Post;
use App\Shared\Contracts\CacheServiceInterface;

class PostCacheInvalidator
{
    public function __construct(
        private readonly CacheServiceInterface $cache,
    ) {}

    public function invalidatePost(Post $post): void
    {
        $this->cache->delete(PostCacheKeyService::post($post->getId()));
        $this->cache->delete(PostCacheKeyService::postByUuid($post->getUuid()));
        $this->cache->delete(PostCacheKeyService::postTags($post->getId()));
        $this->cache->delete(PostCacheKeyService::postViews($post->getId()));
        $this->cache->delete(PostCacheKeyService::pinnedPosts());
        $this->cache->deletePattern(PostCacheKeyService::postsListPattern());

        if ($post->getUserId()) {
            $this->cache->deletePattern(PostCacheKeyService::userPattern($post->getUserId()));
        }
    }

    public function invalidateList(): void
    {
        $this->cache->delete('posts:latest');
    }

    public function invalidatePinned(): void
    {
        $this->cache->delete(PostCacheKeyService::pinnedPosts());
    }

    public function invalidateAnalytics(): void
    {
        $this->cache->deletePattern(PostCacheKeyService::postsListPattern());
    }
}
