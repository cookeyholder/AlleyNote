<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\Models\Post;
use PHPUnit\Framework\Attributes\Test;
use Tests\Factory\PostFactory;
use Tests\TestCase;

class PostTest extends TestCase
{
    #[Test]
    public function correctlyInitializesWithValidData(): void
    {
        $data = PostFactory::make([
            'uuid' => 'test-uuid',
            'seq_number' => '202504001',
            'title' => 'Test Title',
            'content' => 'Test Content',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
        ]);

        $post = new Post($data);

        $this->assertEquals($data['uuid'], $post->getUuid());
        $this->assertEquals($data['seq_number'], $post->getSeqNumber());
        $this->assertEquals($data['title'], $post->getTitle());
        $this->assertEquals($data['content'], $post->getContent());
        $this->assertEquals($data['user_id'], $post->getUserId());
        $this->assertEquals($data['user_ip'], $post->getUserIp());
    }

    #[Test]
    public function handlesNullableFieldsCorrectly(): void
    {
        $data = PostFactory::make([
            'uuid' => 'test-uuid',
            'seq_number' => null,
            'user_ip' => null,
            'publish_date' => null,
        ]);

        $post = new Post($data);

        $this->assertNull($post->getSeqNumber());
        $this->assertNull($post->getUserIp());
        $this->assertNull($post->getPublishDate());
    }

    #[Test]
    public function setsDefaultValuesCorrectly(): void
    {
        $data = PostFactory::make([
            'id' => 0,
            'uuid' => 'test-uuid',
            'title' => 'Test Title',
            'content' => 'Test Content',
            'user_id' => 1,
        ]);

        $post = new Post($data);

        $this->assertEquals(0, $post->getId());
        $this->assertEquals(0, $post->getViewCount());
        $this->assertEquals(PostStatus::DRAFT, $post->getStatus());
        $this->assertFalse($post->isPinned());
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[\+\-]\d{2}:\d{2}$/', $post->getCreatedAt());
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[\+\-]\d{2}:\d{2}$/', $post->getUpdatedAt());
    }

    #[Test]
    public function storesRawHtmlInTitleAndContent(): void
    {
        $data = PostFactory::make([
            'uuid' => 'test-uuid',
            'title' => '<script>alert("XSS")</script>',
            'content' => '<p onclick="alert(\'XSS\')">Test</p>',
            'user_id' => 1,
        ]);

        $post = new Post($data);

        // Model 應該存儲原始資料，HTML 轉義在視圖層處理
        $this->assertEquals(
            '<script>alert("XSS")</script>',
            $post->getTitle(),
        );
        $this->assertEquals(
            '<p onclick="alert(\'XSS\')">Test</p>',
            $post->getContent(),
        );
    }
}
