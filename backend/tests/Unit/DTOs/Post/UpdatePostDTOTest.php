<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\Post;

use App\Domains\Post\DTOs\UpdatePostDTO;
use App\Domains\Post\Enums\PostStatus;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\Validator;
use PHPUnit\Framework\TestCase;

class UpdatePostDTOTest extends TestCase



{
    private Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }

    public function testCanCreateDTOWithFullUpdate(): void
    {
        $data = [
            'title' => '更新的標題',
            'content' => '更新的內容',
            'is_pinned' => true,
            'status' => 'published',
            'publish_date' => '2024-12-01T10 => 30:00+00:00',
        ];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertEquals('更新的標題', $dto->title);
        $this->assertEquals('更新的內容', $dto->content);
        $this->assertTrue($dto->isPinned);
        $this->assertEquals(PostStatus::PUBLISHED, $dto->status);
        $this->assertEquals('2024-12-01T10:30:00+00:00', $dto->publishDate);
        $this->assertTrue($dto->hasChanges());
    }

    public function testCanCreateDTOWithPartialUpdate(): void
    {
        $data = ['title' => '只更新標題'];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertEquals('只更新標題', $dto->title);
        $this->assertNull($dto->content);
        $this->assertNull($dto->isPinned);
        $this->assertNull($dto->status);
        $this->assertNull($dto->publishDate);
        $this->assertTrue($dto->hasChanges());
    }

    public function testCanCreateDTOWithContentOnlyUpdate(): void
    {
        $data = ['content' => '只更新內容'];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertNull($dto->title);
        $this->assertEquals('只更新內容', $dto->content);
        $this->assertNull($dto->isPinned);
        $this->assertNull($dto->status);
        $this->assertNull($dto->publishDate);
        $this->assertTrue($dto->hasChanges());
    }

    public function testCanCreateDTOWithStatusOnlyUpdate(): void
    {
        $data = ['status' => 'archived'];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertNull($dto->title);
        $this->assertNull($dto->content);
        $this->assertNull($dto->isPinned);
        $this->assertEquals(PostStatus::ARCHIVED, $dto->status);
        $this->assertNull($dto->publishDate);
        $this->assertTrue($dto->hasChanges());
    }

    public function testCanCreateDTOWithPinnedOnlyUpdate(): void
    {
        $data = ['is_pinned' => false];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertNull($dto->title);
        $this->assertNull($dto->content);
        $this->assertFalse($dto->isPinned);
        $this->assertNull($dto->status);
        $this->assertNull($dto->publishDate);
        $this->assertTrue($dto->hasChanges());
    }

    public function testCanCreateDTOWithPublishDateOnlyUpdate(): void
    {
        $publishDate = '2024-12-01T15:30:00+00:00';
        $data = ['publish_date' => $publishDate];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertNull($dto->title);
        $this->assertNull($dto->content);
        $this->assertNull($dto->isPinned);
        $this->assertNull($dto->status);
        $this->assertEquals($publishDate, $dto->publishDate);
        $this->assertTrue($dto->hasChanges());
    }

    public function testCanCreateEmptyDTOWithNoData(): void
    {
        $data = [];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertNull($dto->title);
        $this->assertNull($dto->content);
        $this->assertNull($dto->isPinned);
        $this->assertNull($dto->status);
        $this->assertNull($dto->publishDate);
        $this->assertFalse($dto->hasChanges());
    }

    public function testCanCreateEmptyDTOWithOnlyNullValues(): void
    {
        $data = [
            'title' => null,
            'content' => null,
            'is_pinned' => null,
            'status' => null,
            'publish_date' => null,
        ];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertNull($dto->title);
        $this->assertNull($dto->content);
        $this->assertNull($dto->isPinned);
        $this->assertNull($dto->status);
        $this->assertNull($dto->publishDate);
        $this->assertFalse($dto->hasChanges());
    }

    public function testCanCreateEmptyDTOWithOnlyEmptyStrings(): void
    {
        $data = [
            'title' => '',
            'content' => '',
            'status' => '',
            'publish_date' => '',
        ];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertNull($dto->title);
        $this->assertNull($dto->content);
        $this->assertNull($dto->isPinned);
        $this->assertNull($dto->status);
        $this->assertNull($dto->publishDate);
        $this->assertFalse($dto->hasChanges());
    }

    public function testShouldThrowExceptionForInvalidTitle(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => str_repeat('a', 256), // 太長的標題
        ];

        new UpdatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForEmptyTitleContent(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'title' => '   ', // 只有空白的標題
        ];

        new UpdatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForEmptyContentContent(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'content' => '   ', // 只有空白的內容
        ];

        new UpdatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForInvalidStatus(): void
    {
        $this->expectException(ValidationException::class);

        $data = ['status' => 'invalid_status'];

        new UpdatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForInvalidPublishDate(): void
    {
        $this->expectException(ValidationException::class);

        $data = ['publish_date' => 'invalid-date-format'];

        new UpdatePostDTO($this->validator, $data);
    }

    public function testShouldThrowExceptionForInvalidBooleanValue(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'is_pinned' => 'maybe', // 無效的布林值
        ];

        new UpdatePostDTO($this->validator, $data);
    }

    public function testAcceptsAllValidPostStatuses(): void
    {
        $validStatuses = ['draft', 'published', 'archived'];

        foreach ($validStatuses as $status) {
            $data = ['status' => $status];

            $dto = new UpdatePostDTO($this->validator, $data);
            $this->assertEquals(PostStatus::from($status), $dto->status);
        }
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
            ['false', false],
            ['on', true],
            ['yes', true],
        ];

        foreach ($testCases as [$input, $expected]) {
            $data = ['is_pinned' => $input];

            $dto = new UpdatePostDTO($this->validator, $data);
            $this->assertEquals($expected, $dto->isPinned, 'Failed for input: ' . var_export($input, true));
        }
    }

    public function testToArrayReturnsOnlyChangedFields(): void
    {
        $data = [
            'title' => '新標題',
            'is_pinned' => true,
        ];

        $dto = new UpdatePostDTO($this->validator, $data);
        $array = $dto->toArray();

        $expected = [
            'title' => '新標題',
            'is_pinned' => true,
        ];

        $this->assertEquals($expected, $array);
        $this->assertArrayNotHasKey('content', $array);
        $this->assertArrayNotHasKey('status', $array);
        $this->assertArrayNotHasKey('publish_date', $array);
    }

    public function testToArrayReturnsEmptyArrayWhenNoChanges(): void
    {
        $data = [];

        $dto = new UpdatePostDTO($this->validator, $data);
        $array = $dto->toArray();

        $this->assertEquals([], $array);
    }

    public function testToArrayWithAllFields(): void
    {
        $data = [
            'title' => '完整更新',
            'content' => '新內容',
            'is_pinned' => false,
            'status' => 'published',
            'publish_date' => '2024-12-01T10 => 30:00Z',
        ];

        $dto = new UpdatePostDTO($this->validator, $data);
        $array = $dto->toArray();

        $expected = [
            'title' => '完整更新',
            'content' => '新內容',
            'is_pinned' => false,
            'status' => 'published',
            'publish_date' => '2024-12-01T10 => 30:00Z',
        ];

        $this->assertEquals($expected, $array);
    }

    public function testHasChangesReturnsTrueWhenDataExists(): void
    {
        $data = ['title' => '有變更'];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertTrue($dto->hasChanges());
    }

    public function testHasChangesReturnsFalseWhenNoData(): void
    {
        $data = [];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertFalse($dto->hasChanges());
    }

    public function testJsonSerializationWorks(): void
    {
        $data = [
            'title' => '測試序列化',
            'content' => '測試內容',
            'is_pinned' => true,
        ];

        $dto = new UpdatePostDTO($this->validator, $data);
        $json = json_encode($dto);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals($dto->toArray(), $decoded);
    }

    public function testJsonSerializationWithEmptyDTO(): void
    {
        $data = [];

        $dto = new UpdatePostDTO($this->validator, $data);
        $json = json_encode($dto);

        $this->assertJson($json);
        $this->assertEquals('[]', $json);
    }

    public function testAcceptsValidRFC3339DateFormats(): void
    {
        $validDates = [
            '2024-12-01T10 => 30:00+00:00',
            '2024-12-01T10:30:00Z',
            '2024-12-01T10:30:00+08:00',
            '2024-12-01T10:30:00-05:00',
        ];

        foreach ($validDates as $date) {
            $data = ['publish_date' => $date];

            $dto = new UpdatePostDTO($this->validator, $data);
            $this->assertEquals($date, $dto->publishDate, "Failed for date: {$date}");
        }

    }
    public function testHandlesWhitespaceInStringFields(): void
    {
        $data = [
            'title' => '  標題有空白  ',
            'content' => '  內容有空白  ',
        ];

        $dto = new UpdatePostDTO($this->validator, $data);

        // 由於 BaseDTO 的 getString 方法會 trim 字串
        $this->assertEquals('標題有空白', $dto->title);
        $this->assertEquals('內容有空白', $dto->content);
    }

    public function testGetUpdatedFields(): void
    {
        $data = [
            'title' => '新標題',
            'status' => 'published',
        ];

        $dto = new UpdatePostDTO($this->validator, $data);
        $updatedFields = $dto->getUpdatedFields();

        $this->assertEquals(['title', 'status'], $updatedFields);
    }

    public function testHasUpdatedField(): void
    {
        $data = [
            'title' => '新標題',
            'is_pinned' => true,
        ];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertTrue($dto->hasUpdatedField('title'));
        $this->assertTrue($dto->hasUpdatedField('is_pinned'));
        $this->assertFalse($dto->hasUpdatedField('content'));
        $this->assertFalse($dto->hasUpdatedField('status'));
    }

    public function testHandlesEmptyPublishDate(): void
    {
        $data = [
            'title' => '測試文章',
            'publish_date' => '',
        ];

        $dto = new UpdatePostDTO($this->validator, $data);
        $this->assertEquals('測試文章', $dto->title);
        $this->assertNull($dto->publishDate);
    }

    public function testValidatesUnicodeContent(): void
    {
        $data = [
            'title' => '測試標題 🚀 with emoji',
            'content' => '這是包含 emoji 的內容 🎉 和各種字符',
        ];

        $dto = new UpdatePostDTO($this->validator, $data);

        $this->assertEquals('測試標題 🚀 with emoji', $dto->title);
        $this->assertEquals('這是包含 emoji 的內容 🎉 和各種字符', $dto->content);
    }
}
