<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

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

        $this->assertEquals((is_array($data) && isset((is_array($data) ? $data['uuid'] : (is_object($data) ? $data->uuid : null)))) ? (is_array($data) ? $data['uuid'] : (is_object($data) ? $data->uuid : null)) : null, $post->getUuid());
        $this->assertEquals((is_array($data) && isset((is_array($data) ? $data['seq_number'] : (is_object($data) ? $data->seq_number : null)))) ? (is_array($data) ? $data['seq_number'] : (is_object($data) ? $data->seq_number : null)) : null, $post->getSeqNumber());
        $this->assertEquals(htmlspecialchars((is_array($data) && isset((is_array($data) ? $data['title'] : (is_object($data) ? $data->title : null)))) ? (is_array($data) ? $data['title'] : (is_object($data) ? $data->title : null)) : null, ENT_QUOTES, 'UTF-8'), $post->getTitle());
        $this->assertEquals(htmlspecialchars((is_array($data) && isset((is_array($data) ? $data['content'] : (is_object($data) ? $data->content : null)))) ? (is_array($data) ? $data['content'] : (is_object($data) ? $data->content : null)) : null, ENT_QUOTES, 'UTF-8'), $post->getContent());
        $this->assertEquals((is_array($data) && isset((is_array($data) ? $data['user_id'] : (is_object($data) ? $data->user_id : null)))) ? (is_array($data) ? $data['user_id'] : (is_object($data) ? $data->user_id : null)) : null, $post->getUserId());
        $this->assertEquals((is_array($data) && isset((is_array($data) ? $data['user_ip'] : (is_object($data) ? $data->user_ip : null)))) ? (is_array($data) ? $data['user_ip'] : (is_object($data) ? $data->user_ip : null)) : null, $post->getUserIp());
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
            'uuid' => 'test-uuid',
            'title' => 'Test Title',
            'content' => 'Test Content',
            'user_id' => 1,
        ]);

        // 移除 id，這樣才能測試預設值
        unset((is_array($data) ? $data['id'] : (is_object($data) ? $data->id : null)));

        $post = new Post($data);

        $this->assertEquals(0, $post->getId());
        $this->assertEquals(0, $post->getViewCount());
        $this->assertEquals('draft', $post->getStatus());
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
            'content' => '<p onclick="alert(\'XSS\')">Test',
            'user_id' => 1,
        ]);

        $post = new Post($data);

        // Model 應該存儲原始資料，HTML 轉義在視圖層處理
        $this->assertEquals(
            '<script>alert("XSS")</script>',
            $post->getTitle(),
        );
        $this->assertEquals(
            '<p onclick="alert(\'XSS\')">Test',
            $post->getContent(),
        );
    }
}
