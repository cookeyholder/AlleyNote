<?php

namespace Tests\Unit\Factory;

use App\Database\DatabaseConnection;
use PHPUnit\Framework\TestCase;
use Tests\Factory\PostFactory;

class PostFactoryTest extends TestCase
{
    private PostFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        DatabaseConnection::reset();
        $this->factory = new PostFactory();
    }

    /** @test */
    public function it_can_make_post_data(): void
    {
        $post = $this->factory->make();

        $this->assertArrayHasKey('uuid', $post);
        $this->assertArrayHasKey('title', $post);
        $this->assertArrayHasKey('content', $post);
        $this->assertEquals('測試文章標題', $post['title']);
    }

    /** @test */
    public function it_can_create_post_in_database(): void
    {
        $post = $this->factory->create([
            'title' => '自訂標題',
            'content' => '自訂內容'
        ]);

        $this->assertArrayHasKey('id', $post);
        $this->assertEquals('自訂標題', $post['title']);

        // 驗證資料確實存入資料庫
        $db = DatabaseConnection::getInstance();
        $stmt = $db->prepare('SELECT * FROM posts WHERE id = ?');
        $stmt->execute([$post['id']]);
        $result = $stmt->fetch();

        $this->assertEquals('自訂標題', $result['title']);
        $this->assertEquals('自訂內容', $result['content']);
    }

    /** @test */
    public function it_can_override_default_attributes(): void
    {
        $post = $this->factory->make([
            'title' => '新標題',
            'is_pinned' => 1
        ]);

        $this->assertEquals('新標題', $post['title']);
        $this->assertEquals(1, $post['is_pinned']);
    }

    protected function tearDown(): void
    {
        DatabaseConnection::reset();
        parent::tearDown();
    }
}
