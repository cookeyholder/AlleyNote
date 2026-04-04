<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Resources;

use App\Application\Resources\PostResource;
use App\Domains\Post\Models\Post;
use App\Shared\Contracts\OutputSanitizerInterface;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

class PostResourceTest extends UnitTestCase
{
    #[Test]
    public function itShouldTransformPostWithStatsAndSanitizer(): void
    {
        $post = new Post([
            'id' => 1,
            'title' => '<b>Title</b>',
            'content' => '<script>alert(1)</script><p>Body</p>',
            'user_id' => 1,
            'status' => 'published',
            'publish_date' => '2026-04-04 12:00:00',
        ]);

        $sanitizer = Mockery::mock(OutputSanitizerInterface::class);
        $sanitizer->shouldReceive('sanitizeHtml')->andReturn('Title');
        $sanitizer->shouldReceive('sanitizeRichText')->andReturn('<p>Body</p>');

        $resource = new PostResource($post, [
            'sanitizer' => $sanitizer,
            'stats' => ['views' => 12, 'unique_visitors' => 5],
        ]);

        $result = $resource->resolve();

        $this->assertSame('Title', $result['title']);
        $this->assertSame('<p>Body</p>', $result['content']);
        $this->assertSame(12, $result['views']);
        $this->assertSame(5, $result['unique_visitors']);
        $this->assertIsString($result['publish_date']);
        $this->assertStringContainsString('T', $result['publish_date']);
    }
}
