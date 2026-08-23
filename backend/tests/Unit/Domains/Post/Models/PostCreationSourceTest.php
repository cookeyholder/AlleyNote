<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Models;

use App\Domains\Post\Models\Post;
use Tests\Support\UnitTestCase;

/**
 * Post 模型建立來源與作者資訊測試.
 */
final class PostCreationSourceTest extends UnitTestCase
{
    public function test_建立來源與作者資訊可正確讀取(): void
    {
        $post = Post::fromArray([
            'id'                     => 21,
            'uuid'                   => 'uuid-21',
            'title'                  => '來源測試文章',
            'content'                => '來源測試內容',
            'user_id'                => 3,
            'status'                 => 'published',
            'creation_source'        => 'admin_panel',
            'creation_source_detail' => '由管理後台建立',
            'author'                 => 'cookey',
        ]);

        $this->assertSame('admin_panel', $post->getCreationSource());
        $this->assertSame('由管理後台建立', $post->getCreationSourceDetail());
        $this->assertSame('cookey', $post->getAuthor());
    }

    public function test_未提供來源與作者時為null(): void
    {
        $post = Post::fromArray([
            'id'      => 22,
            'title'   => '預設來源文章',
            'content' => '未指定來源',
            'user_id' => 4,
        ]);

        $this->assertNull($post->getCreationSource());
        $this->assertNull($post->getCreationSourceDetail());
        $this->assertNull($post->getAuthor());
    }

    public function test_toArray包含來源與作者欄位(): void
    {
        $post = Post::fromArray([
            'id'                     => 23,
            'title'                  => '陣列輸出測試',
            'content'                => '陣列輸出內容',
            'user_id'                => 5,
            'creation_source'        => 'api',
            'creation_source_detail' => null,
            'author'                 => 'alice',
        ]);

        $array = $post->toArray();

        $this->assertSame('api', $array['creation_source']);
        $this->assertNull($array['creation_source_detail']);
        $this->assertSame('alice', $array['author']);
    }
}
