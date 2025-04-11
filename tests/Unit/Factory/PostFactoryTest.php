<?php

declare(strict_types=1);

namespace Tests\Unit\Factory;

use Tests\Factory\PostFactory;
use PHPUnit\Framework\TestCase;

class PostFactoryTest extends TestCase
{
    public function testItCanMakePostData(): void
    {
        $data = PostFactory::make();

        $this->assertEquals('範例文章', $data['title']);
        $this->assertEquals('這是一篇範例文章的內容', $data['content']);
        $this->assertEquals(1, $data['id']);
    }

    public function testItCanCreatePostInDatabase(): void
    {
        $data = PostFactory::make([
            'title' => '客製化標題',
            'content' => '客製化內容'
        ]);

        $this->assertEquals('客製化標題', $data['title']);
        $this->assertEquals('客製化內容', $data['content']);
    }

    public function testItCanOverrideDefaultAttributes(): void
    {
        $data = PostFactory::make([
            'id' => 999,
            'title' => '自訂標題'
        ]);

        $this->assertEquals(999, $data['id']);
        $this->assertEquals('自訂標題', $data['title']);
    }
}
