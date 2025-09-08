<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\Post;

use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\Enums\PostStatus;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\Validator;
use PHPUnit\Framework\TestCase;

class CreatePostDTOTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }

    public function testCanCreateDTOWithValidData(): void
    {
        $data = [
            'title' => '測試文章',
            'content' => '這是測試內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'is_pinned' => false,
            'status' => 'draft',
        ];

        $dto = new CreatePostDTO($this->validator, $data);

        $this->assertEquals('測試文章', $dto->title);
        $this->assertEquals('這是測試內容', $dto->content);
        $this->assertEquals(1, $dto->userId);
        $this->assertEquals('127.0.0.1', $dto->userIp);
        $this->assertFalse($dto->isPinned);
        $this->assertEquals(PostStatus::DRAFT, $dto->status);
        $this->assertNull($dto->publishDate);
    }

    public function testCanCreateDTOWithMinimalData(): void
    {
        $data = [
            'title' => '標題',
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
        ];

        $dto = new CreatePostDTO($this->validator, $data);

        $this->assertEquals('標題', $dto->title);
        $this->assertEquals('內容', $dto->content);
        $this->assertEquals(1, $dto->userId);
        $this->assertEquals('127.0.0.1', $dto->userIp);
        $this->assertFalse($dto->isPinned); // 預設值
        $this->assertEquals(PostStatus::DRAFT, $dto->status); // 預設值
        $this->assertNull($dto->publishDate);
    }

    public function testCanCreateDTOWithPublishDate(): void
    {
        $publishDate = '2024-12-01T10:30:00+00:00';
        $data = [
            'title' => '測試文章',
            'content' => '這是測試內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'status' => 'published',
            'publish_date' => $publishDate,
        ];

        $dto = new CreatePostDTO($this->validator, $data);

        $this->assertEquals($publishDate, $dto->publishDate);
        $this->assertEquals(PostStatus::PUBLISHED, $dto->status);
    }

    public function testCanCreateDTOWithPinnedPost(): void
    {
        $data = [
            'title' => '置頂文章',
            'content' => '這是置頂內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'is_pinned' => true,
            'status' => 'published',
        ];

        $dto = new CreatePostDTO($this->validator, $data);

        $this->assertTrue($dto->isPinned);
        $this->assertEquals(PostStatus::PUBLISHED, $dto->status);
    }

    public function testShouldThrowExceptionForMissingTitle(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForEmptyTitle(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => '',
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForTitleTooLong(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => str_repeat('a', 256),
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForTitleWithOnlyWhitespace(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => '   　　　   ', // 包含中英文空白
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForMissingContent(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => '標題',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForEmptyContent(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => '標題',
            'content' => '',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForContentWithOnlyWhitespace(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => '標題',
            'content' => '   　　　   ', // 包含中英文空白
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForInvalidUserId(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => '標題',
            'content' => '內容',
            'user_id' => 0,
            'user_ip' => '127.0.0.1',
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForMissingUserId(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => '標題',
            'content' => '內容',
            'user_ip' => '127.0.0.1',
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForInvalidIP(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => '標題',
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => 'invalid-ip',
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForMissingIP(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => '標題',
            'content' => '內容',
            'user_id' => 1,
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForInvalidStatus(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => '標題',
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'status' => 'invalid_status',
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForInvalidPublishDate(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => '標題',
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'publish_date' => 'invalid-date',
        ];

        new CreatePostDTO($this->validator, $data);
    }

    public function testAcceptsValidIPv6Address(): void
    {
        $data = [
            'title' => '測試文章',
            'content' => '這是測試內容',
            'user_id' => 1,
            'user_ip' => '2001 => 0db8:85a3:0000:0000:8a2e:0370:7334',
        ];

        $dto = new CreatePostDTO($this->validator, $data);

        $this->assertEquals('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $dto->userIp);
    }

    public function testAcceptsAllValidPostStatuses(): void
    {
        $validStatuses = ['draft', 'published', 'archived'];

        foreach ($validStatuses as $status) {
            $data = [
                'title' => '測試文章',
                'content' => '這是測試內容',
                'user_id' => 1,
                'user_ip' => '127.0.0.1',
                'status' => $status,
            ];

            $dto = new CreatePostDTO($this->validator, $data);
            $this->assertEquals(PostStatus::from($status), $dto->status);
        }

    public function testHandlesBooleanValues(): void
    {
        $testCases = [
            [true, true],
            [false, false],
            ['1', true],
            ['0', false],
            [1, true],
            [0, false],
            ['true', true],
            ['on', true],
            ['yes', true],
        ];

        foreach ($testCases as [$input, $expected]) {
            $data = [
                'title' => '測試文章',
                'content' => '這是測試內容',
                'user_id' => 1,
                'user_ip' => '127.0.0.1',
                'is_pinned' => $input,
            ];

            $dto = new CreatePostDTO($this->validator, $data);
            $this->assertEquals($expected, $dto->isPinned, 'Failed for input: ' . var_export($input, true));
        }

    public function testToArrayReturnsCorrectFormat(): void
    {
        $data = [
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'is_pinned' => true,
            'status' => 'published',
        ];

        $dto = new CreatePostDTO($this->validator, $data);
        $array = $dto->toArray();

        $expected = [
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'is_pinned' => true,
            'status' => 'published',
            'publish_date' => null,
        ];

        $this->assertEquals($expected, $array);
    }

    public function testJsonSerializationWorks(): void
    {
        $data = [
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'is_pinned' => false,
            'status' => 'draft',
        ];

        $dto = new CreatePostDTO($this->validator, $data);
        $json = json_encode($dto);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals($dto->toArray(), $decoded);
    }

    public function testAcceptsRFC3339DateTimeFormats(): void
    {
        $validDates = [
            '2024-12-01T10 => 30:00+00:00',
            '2024-12-01T10:30:00Z',
            '2024-12-01T10:30:00+08:00',
            '2024-12-01T10:30:00-05:00',
        ];

        foreach ($validDates as $date) {
            $data = [
                'title' => '測試文章',
                'content' => '測試內容',
                'user_id' => 1,
                'user_ip' => '127.0.0.1',
                'status' => 'published',
                'publish_date' => $date,
            ];

            $dto = new CreatePostDTO($this->validator, $data);
            $this->assertEquals($date, $dto->publishDate, "Failed for date: {$date}");
        }

    public function testTrimsTitleAndContent(): void
    {
        $data = [
            'title' => '  測試文章  ',
            'content' => '  測試內容  ',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
        ];

        $dto = new CreatePostDTO($this->validator, $data);

        $this->assertEquals('測試文章', $dto->title);
        $this->assertEquals('測試內容', $dto->content);
    }

    public function testHandlesEmptyPublishDate(): void
    {
        $data = [
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'publish_date' => '',
        ];

        $dto = new CreatePostDTO($this->validator, $data);
        $this->assertNull($dto->publishDate);
    }

    public function testValidatesUnicodeContent(): void
    {
        $data = [
            'title' => '測試標題 🚀 with emoji',
            'content' => '這是包含 emoji 的內容 🎉 和各種字符',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
        ];

        $dto = new CreatePostDTO($this->validator, $data);

        $this->assertEquals('測試標題 🚀 with emoji', $dto->title);
        $this->assertEquals('這是包含 emoji 的內容 🎉 和各種字符', $dto->content);
    }
}
