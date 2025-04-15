<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Post;
use Tests\TestCase;
use Tests\Factory\PostFactory;

class PostTest extends TestCase
{
    /** @test */
    public function correctlyInitializesWithValidData(): void
    {
        $data = PostFactory::make([
            'uuid' => 'test-uuid',
            'seq_number' => '202504001',
            'title' => 'Test Title',
            'content' => 'Test Content',
            'user_id' => 1,
            'user_ip' => '127.0.0.1'
        ]);

        $post = new Post($data);

        $this->assertEquals($data['uuid'], $post->getUuid());
        $this->assertEquals($data['seq_number'], $post->getSeqNumber());
        $this->assertEquals(htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8'), $post->getTitle());
        $this->assertEquals(htmlspecialchars($data['content'], ENT_QUOTES, 'UTF-8'), $post->getContent());
        $this->assertEquals($data['user_id'], $post->getUserId());
        $this->assertEquals($data['user_ip'], $post->getUserIp());
    }

    /** @test */
    public function handlesNullableFieldsCorrectly(): void
    {
        $data = PostFactory::make([
            'uuid' => 'test-uuid',
            'seq_number' => null,
            'user_ip' => null,
            'publish_date' => null
        ]);

        $post = new Post($data);

        $this->assertNull($post->getSeqNumber());
        $this->assertNull($post->getUserIp());
        $this->assertNull($post->getPublishDate());
    }

    /** @test */
    public function setsDefaultValuesCorrectly(): void
    {
        $data = PostFactory::make([
            'uuid' => 'test-uuid',
            'title' => 'Test Title',
            'content' => 'Test Content',
            'user_id' => 1
        ]);

        // 移除 id，這樣才能測試預設值
        unset($data['id']);

        $post = new Post($data);

        $this->assertEquals(0, $post->getId());
        $this->assertEquals(0, $post->getViewCount());
        $this->assertEquals('draft', $post->getStatus());
        $this->assertFalse($post->isPinned());
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[\+\-]\d{2}:\d{2}$/', $post->getCreatedAt());
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[\+\-]\d{2}:\d{2}$/', $post->getUpdatedAt());
    }

    /** @test */
    public function properlyEscapesHtmlInTitleAndContent(): void
    {
        $data = PostFactory::make([
            'uuid' => 'test-uuid',
            'title' => '<script>alert("XSS")</script>',
            'content' => '<p onclick="alert(\'XSS\')">Test</p>',
            'user_id' => 1
        ]);

        $post = new Post($data);

        $this->assertEquals(
            htmlspecialchars('<script>alert("XSS")</script>', ENT_QUOTES, 'UTF-8'),
            $post->getTitle()
        );
        $this->assertEquals(
            htmlspecialchars('<p onclick="alert(\'XSS\')">Test</p>', ENT_QUOTES, 'UTF-8'),
            $post->getContent()
        );
    }
}
