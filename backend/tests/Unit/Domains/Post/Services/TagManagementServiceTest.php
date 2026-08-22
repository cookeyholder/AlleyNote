<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Services;

use App\Domains\Post\Contracts\TagRepositoryInterface;
use App\Domains\Post\DTOs\CreateTagDTO;
use App\Domains\Post\DTOs\UpdateTagDTO;
use App\Domains\Post\Models\Tag;
use App\Domains\Post\Services\TagManagementService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * TagManagementService 單元測試.
 */
#[CoversClass(TagManagementService::class)]
final class TagManagementServiceTest extends UnitTestCase
{
    private TagManagementService $service;

    private TagRepositoryInterface&MockInterface $tagRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tagRepository = Mockery::mock(TagRepositoryInterface::class);
        $this->service = new TagManagementService($this->tagRepository);
    }

    private function makeTag(int $id, string $name, ?string $slug = null): Tag
    {
        return new Tag(
            id: $id,
            name: $name,
            slug: $slug,
            usageCount: 2,
            createdAt: new DateTimeImmutable('2026-01-01 08:00:00'),
            updatedAt: new DateTimeImmutable('2026-01-02 09:00:00'),
        );
    }

    #[Test]
    public function test_listTags回傳分頁結構(): void
    {
        $tags = [$this->makeTag(1, '公告', 'announcement')];

        $this->tagRepository
            ->shouldReceive('list')
            ->once()
            ->with(2, 10, ['search' => '公'])
            ->andReturn(['items' => $tags, 'total' => 21]);

        $result = $this->service->listTags(2, 10, ['search' => '公']);

        $this->assertSame(21, $result['total']);
        $this->assertSame(2, $result['page']);
        $this->assertSame(10, $result['per_page']);
        $this->assertSame(3, $result['last_page']);
        $this->assertCount(1, $result['items']);
        $this->assertSame('公告', $result['items'][0]['name']);
    }

    #[Test]
    public function test_getTag成功(): void
    {
        $this->tagRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($this->makeTag(1, '公告'));

        $result = $this->service->getTag(1);

        $this->assertSame('公告', $result['name']);
    }

    #[Test]
    public function test_getTag不存在時拋出例外(): void
    {
        $this->tagRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('標籤不存在 (ID: 999)');

        $this->service->getTag(999);
    }

    #[Test]
    public function test_createTag成功且自動產生slug(): void
    {
        $this->tagRepository->shouldReceive('findByName')->once()->with('PHP 開發')->andReturn(null);
        $this->tagRepository->shouldReceive('findBySlug')->once()->with('php-開發')->andReturn(null);
        $this->tagRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(static fn(array $data): bool => $data['name'] === 'PHP 開發'
                && $data['slug'] === 'php-開發'
                && $data['usage_count'] === 0))
            ->andReturn($this->makeTag(10, 'PHP 開發', 'php-開發'));

        $result = $this->service->createTag(new CreateTagDTO(
            name: 'PHP 開發',
            description: '技術文章',
            color: '#3399ff',
        ));

        $this->assertSame(10, $result['id']);
        $this->assertSame('php-開發', $result['slug']);
    }

    #[Test]
    public function test_createTag使用指定slug(): void
    {
        $this->tagRepository->shouldReceive('findByName')->andReturn(null);
        $this->tagRepository->shouldReceive('findBySlug')->once()->with('custom-slug')->andReturn(null);
        $this->tagRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(static fn(array $data): bool => $data['slug'] === 'custom-slug'))
            ->andReturn($this->makeTag(11, '標籤', 'custom-slug'));

        $result = $this->service->createTag(new CreateTagDTO(name: '標籤', slug: 'custom-slug'));

        $this->assertSame('custom-slug', $result['slug']);
    }

    #[Test]
    public function test_createTag名稱為空時驗證失敗(): void
    {
        // 名稱為空仍會以自動產生的 slug 檢查重複
        $this->tagRepository->shouldReceive('findBySlug')->andReturn(null);

        $this->expectException(ValidationException::class);

        $this->service->createTag(new CreateTagDTO(name: ''));
    }

    #[Test]
    public function test_createTag名稱超過五十字元時驗證失敗(): void
    {
        $this->tagRepository->shouldReceive('findByName')->andReturn(null);
        $this->tagRepository->shouldReceive('findBySlug')->andReturn(null);
        $this->tagRepository->shouldNotReceive('create');

        $this->expectException(ValidationException::class);

        $this->service->createTag(new CreateTagDTO(name: str_repeat('長', 51)));
    }

    #[Test]
    public function test_createTag名稱重複時驗證失敗(): void
    {
        $this->tagRepository->shouldReceive('findByName')->andReturn($this->makeTag(1, '既有'));
        $this->tagRepository->shouldReceive('findBySlug')->andReturn(null);
        $this->tagRepository->shouldNotReceive('create');

        $this->expectException(ValidationException::class);

        $this->service->createTag(new CreateTagDTO(name: '既有'));
    }

    #[Test]
    public function test_createTag_slug重複時驗證失敗(): void
    {
        $this->tagRepository->shouldReceive('findByName')->andReturn(null);
        $this->tagRepository->shouldReceive('findBySlug')->andReturn($this->makeTag(2, '其他', 'dup'));
        $this->tagRepository->shouldNotReceive('create');

        $this->expectException(ValidationException::class);

        $this->service->createTag(new CreateTagDTO(name: '新標籤', slug: 'dup'));
    }

    #[Test]
    public function test_updateTag更新描述與顏色(): void
    {
        $this->tagRepository->shouldReceive('findById')->once()->with(5)->andReturn($this->makeTag(5, '原標籤'));
        $this->tagRepository
            ->shouldReceive('update')
            ->once()
            ->with(5, ['description' => '新描述', 'color' => '#000000'])
            ->andReturn($this->makeTag(5, '原標籤'));

        $result = $this->service->updateTag(new UpdateTagDTO(
            id: 5,
            description: '新描述',
            color: '#000000',
        ));

        $this->assertSame('原標籤', $result['name']);
    }

    #[Test]
    public function test_updateTag改名時自動重新產生slug(): void
    {
        $this->tagRepository->shouldReceive('findById')->andReturn($this->makeTag(5, '舊名稱', 'old-name'));
        $this->tagRepository->shouldReceive('findByName')->once()->with('New Name')->andReturn(null);
        $this->tagRepository
            ->shouldReceive('update')
            ->once()
            ->with(5, ['name' => 'New Name', 'slug' => 'new-name'])
            ->andReturn($this->makeTag(5, 'New Name', 'new-name'));

        $result = $this->service->updateTag(new UpdateTagDTO(id: 5, name: 'New Name'));

        $this->assertSame('new-name', $result['slug']);
    }

    #[Test]
    public function test_updateTag指定slug時使用指定值(): void
    {
        $this->tagRepository->shouldReceive('findById')->andReturn($this->makeTag(5, '舊名稱', 'old'));
        $this->tagRepository->shouldReceive('findByName')->andReturn(null);
        $this->tagRepository->shouldReceive('findBySlug')->once()->with('kept-slug')->andReturn(null);
        $this->tagRepository
            ->shouldReceive('update')
            ->once()
            ->with(5, ['name' => 'Another Name', 'slug' => 'kept-slug'])
            ->andReturn($this->makeTag(5, 'Another Name', 'kept-slug'));

        $result = $this->service->updateTag(new UpdateTagDTO(id: 5, name: 'Another Name', slug: 'kept-slug'));

        $this->assertSame('kept-slug', $result['slug']);
    }

    #[Test]
    public function test_updateTag不存在時拋出例外(): void
    {
        $this->tagRepository->shouldReceive('findById')->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('標籤不存在 (ID: 77)');

        $this->service->updateTag(new UpdateTagDTO(id: 77, name: '任何名稱'));
    }

    #[Test]
    public function test_updateTag名稱被其他標籤使用時驗證失敗(): void
    {
        $this->tagRepository->shouldReceive('findById')->andReturn($this->makeTag(5, '自己'));
        $this->tagRepository->shouldReceive('findByName')->andReturn($this->makeTag(9, '別人'));
        $this->tagRepository->shouldNotReceive('update');

        $this->expectException(ValidationException::class);

        $this->service->updateTag(new UpdateTagDTO(id: 5, name: '別人'));
    }

    #[Test]
    public function test_updateTag_slug被其他標籤使用時驗證失敗(): void
    {
        $this->tagRepository->shouldReceive('findById')->andReturn($this->makeTag(5, '自己', 'mine'));
        $this->tagRepository->shouldReceive('findBySlug')->andReturn($this->makeTag(9, '別人', 'taken'));
        $this->tagRepository->shouldNotReceive('update');

        $this->expectException(ValidationException::class);

        $this->service->updateTag(new UpdateTagDTO(id: 5, slug: 'taken'));
    }

    #[Test]
    public function test_updateTag同名同ID視為本人允許更新(): void
    {
        $self = $this->makeTag(5, '本人', 'self');
        $this->tagRepository->shouldReceive('findById')->andReturn($self);
        $this->tagRepository->shouldReceive('findByName')->andReturn($self);
        $this->tagRepository->shouldReceive('findBySlug')->once()->with('renamed-self')->andReturn(null);
        $this->tagRepository
            ->shouldReceive('update')
            ->once()
            ->with(5, ['name' => '本人', 'slug' => 'renamed-self'])
            ->andReturn($self);

        $result = $this->service->updateTag(new UpdateTagDTO(id: 5, name: '本人', slug: 'renamed-self'));

        $this->assertSame('本人', $result['name']);
    }

    #[Test]
    public function test_deleteTag成功時先解除關聯再刪除(): void
    {
        $this->tagRepository->shouldReceive('findById')->once()->with(3)->andReturn($this->makeTag(3, '待刪除'));
        $order = [];
        $this->tagRepository->shouldReceive('detachFromAllPosts')->once()->with(3)->andReturnUsing(static function () use (&$order): void { $order[] = 'detach'; });
        $this->tagRepository->shouldReceive('delete')->once()->with(3)->andReturnUsing(static function () use (&$order): bool {
            $order[] = 'delete';

            return true;
        });

        $this->service->deleteTag(3);

        $this->assertSame(['detach', 'delete'], $order);
    }

    #[Test]
    public function test_deleteTag不存在時拋出例外(): void
    {
        $this->tagRepository->shouldReceive('findById')->andReturn(null);
        $this->tagRepository->shouldNotReceive('detachFromAllPosts');
        $this->tagRepository->shouldNotReceive('delete');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('標籤不存在 (ID: 404)');

        $this->service->deleteTag(404);
    }
}
