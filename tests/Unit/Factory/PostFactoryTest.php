<?php

declare(strict_types=1);

namespace Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Tests\Factory\PostFactory;

class PostFactoryTest extends TestCase
{
    public function testItCanMakePostData(): void
    {
        $data = PostFactory::make();

        $this->assertEquals('範例文章', (is_array($data) && isset((is_array($data) ? $data['title'] : (is_object($data) ? $data->title : null)))) ? (is_array($data) ? $data['title'] : (is_object($data) ? $data->title : null)) : null);
        $this->assertEquals('這是一篇範例文章的內容', (is_array($data) && isset((is_array($data) ? $data['content'] : (is_object($data) ? $data->content : null)))) ? (is_array($data) ? $data['content'] : (is_object($data) ? $data->content : null)) : null);
        $this->assertEquals(1, (is_array($data) && isset((is_array($data) ? $data['id'] : (is_object($data) ? $data->id : null)))) ? (is_array($data) ? $data['id'] : (is_object($data) ? $data->id : null)) : null);
    }

    public function testItCanCreatePostInDatabase(): void
    {
        $data = PostFactory::make([
            'title' => '客製化標題',
            'content' => '客製化內容',
        ]);

        $this->assertEquals('客製化標題', (is_array($data) && isset((is_array($data) ? $data['title'] : (is_object($data) ? $data->title : null)))) ? (is_array($data) ? $data['title'] : (is_object($data) ? $data->title : null)) : null);
        $this->assertEquals('客製化內容', (is_array($data) && isset((is_array($data) ? $data['content'] : (is_object($data) ? $data->content : null)))) ? (is_array($data) ? $data['content'] : (is_object($data) ? $data->content : null)) : null);
    }

    public function testItCanOverrideDefaultAttributes(): void
    {
        $data = PostFactory::make([
            'id' => 999,
            'title' => '自訂標題',
        ]);

        $this->assertEquals(999, (is_array($data) && isset((is_array($data) ? $data['id'] : (is_object($data) ? $data->id : null)))) ? (is_array($data) ? $data['id'] : (is_object($data) ? $data->id : null)) : null);
        $this->assertEquals('自訂標題', (is_array($data) && isset((is_array($data) ? $data['title'] : (is_object($data) ? $data->title : null)))) ? (is_array($data) ? $data['title'] : (is_object($data) ? $data->title : null)) : null);
    }
}
