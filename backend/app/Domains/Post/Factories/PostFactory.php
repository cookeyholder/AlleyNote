<?php

declare(strict_types=1);

namespace App\Domains\Post\Factories;

use App\Domains\Post\Aggregates\PostAggregate;
use App\Domains\Post\ValueObjects\PostContent;
use App\Domains\Post\ValueObjects\PostId;
use App\Domains\Post\ValueObjects\PostTitle;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * Post 工廠類別.
 *
 * 負責建立 Post 聚合根實例，封裝複雜的建立邏輯
 */
final class PostFactory
{
    /**
     * 建立新的草稿文章.
     *
     * @param string $title 文章標題
     * @param string $content 文章內容
     * @param int $authorId 作者 ID
     * @param string|null $creationSource 建立來源
     * @throws InvalidArgumentException 當參數無效時
     */
    public function createDraft(
        string $title,
        string $content,
        int $authorId,
        ?string $creationSource = null,
    ): PostAggregate {
        return PostAggregate::create(
            id: $this->generatePostId(),
            title: PostTitle::fromString($title),
            content: PostContent::fromString($content),
            authorId: $authorId,
            creationSource: $creationSource ?? 'web',
        );
    }

    /**
     * 從請求資料建立草稿文章.
     *
     * @param array<string, mixed> $data 請求資料
     * @throws InvalidArgumentException 當資料無效時
     */
    public function createFromRequest(array $data): PostAggregate
    {
        $this->validateRequestData($data);

        $title = is_string($data['title']) ? $data['title'] : '';
        $content = is_string($data['content']) ? $data['content'] : '';
        $authorId = is_int($data['author_id']) ? $data['author_id'] : 0;
        $creationSource = isset($data['creation_source']) && is_string($data['creation_source'])
            ? $data['creation_source']
            : null;

        return $this->createDraft(
            title: $title,
            content: $content,
            authorId: $authorId,
            creationSource: $creationSource,
        );
    }

    /**
     * 從資料庫資料重建文章聚合.
     *
     * @param array<string, mixed> $data 資料庫資料
     * @throws InvalidArgumentException 當資料無效時
     */
    public function reconstitute(array $data): PostAggregate
    {
        return PostAggregate::reconstitute($data);
    }

    /**
     * 批次從資料庫資料重建文章聚合.
     *
     * @param array<int, array<string, mixed>> $dataList 資料庫資料列表
     * @return array<int, PostAggregate>
     */
    public function reconstituteMany(array $dataList): array
    {
        return array_map(
            fn(array $data): PostAggregate => $this->reconstitute($data),
            $dataList,
        );
    }

    /**
     * 建立文章的副本（用於複製功能）.
     *
     * @param PostAggregate $original 原始文章
     * @param int $newAuthorId 新作者 ID
     */
    public function createCopy(PostAggregate $original, int $newAuthorId): PostAggregate
    {
        $titleSuffix = ' (副本)';
        $newTitle = $original->getTitle()->toString() . $titleSuffix;

        // 確保標題不超過限制
        if (mb_strlen($newTitle, 'UTF-8') > 255) {
            $maxLength = 255 - mb_strlen($titleSuffix, 'UTF-8');
            $newTitle = mb_substr($original->getTitle()->toString(), 0, $maxLength, 'UTF-8') . $titleSuffix;
        }

        return $this->createDraft(
            title: $newTitle,
            content: $original->getContent()->toString(),
            authorId: $newAuthorId,
            creationSource: 'copy',
        );
    }

    /**
     * 建立測試用的文章（僅用於測試環境）.
     *
     * @param string|null $title 標題，null 則自動生成
     * @param string|null $content 內容，null 則自動生成
     * @param int|null $authorId 作者 ID，null 則使用預設值
     */
    public function createForTesting(
        ?string $title = null,
        ?string $content = null,
        ?int $authorId = null,
    ): PostAggregate {
        return $this->createDraft(
            title: $title ?? 'Test Post ' . uniqid(),
            content: $content ?? 'This is a test post content.',
            authorId: $authorId ?? 1,
            creationSource: 'test',
        );
    }

    /**
     * 生成新的 Post ID.
     */
    private function generatePostId(): PostId
    {
        return PostId::fromString(Uuid::uuid4()->toString());
    }

    /**
     * 驗證請求資料.
     *
     * @param array<string, mixed> $data 請求資料
     * @throws InvalidArgumentException 當資料無效時
     */
    private function validateRequestData(array $data): void
    {
        if (!isset($data['title'])) {
            throw new InvalidArgumentException('標題欄位是必需的');
        }

        if (!isset($data['content'])) {
            throw new InvalidArgumentException('內容欄位是必需的');
        }

        if (!isset($data['author_id'])) {
            throw new InvalidArgumentException('作者 ID 欄位是必需的');
        }

        if (!is_string($data['title'])) {
            throw new InvalidArgumentException('標題必須是字串');
        }

        if (!is_string($data['content'])) {
            throw new InvalidArgumentException('內容必須是字串');
        }

        if (!is_int($data['author_id']) || $data['author_id'] <= 0) {
            throw new InvalidArgumentException('作者 ID 必須是大於 0 的整數');
        }
    }
}
