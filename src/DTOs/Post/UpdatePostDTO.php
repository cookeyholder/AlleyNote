<?php

declare(strict_types=1);

namespace App\DTOs\Post;

use App\DTOs\BaseDTO;
use App\Services\Enums\PostStatus;

/**
 * 更新文章的資料傳輸物件
 * 
 * 用於安全地傳輸更新文章所需的資料，防止巨量賦值攻擊
 */
class UpdatePostDTO extends BaseDTO
{
    public readonly ?string $title;
    public readonly ?string $content;
    public readonly ?bool $isPinned;
    public readonly ?PostStatus $status;
    public readonly ?string $publishDate;

    /**
     * @param array $data 輸入資料
     * @throws \InvalidArgumentException 當資料格式錯誤時
     */
    public function __construct(array $data)
    {
        // 設定屬性（全部都是可選的）
        $this->title = $this->getString($data, 'title');
        $this->content = $this->getString($data, 'content');
        $this->isPinned = $this->getBool($data, 'is_pinned');

        // 處理狀態
        $statusValue = $this->getString($data, 'status');
        if ($statusValue !== null) {
            $this->status = PostStatus::from($statusValue);
        } else {
            $this->status = null;
        }

        // 發布日期（可選）
        $this->publishDate = $this->getString($data, 'publish_date');

        // 驗證資料
        $this->validate();
    }

    /**
     * 驗證資料完整性
     * 
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        // 驗證標題長度（如果有提供）
        if ($this->title !== null && (strlen($this->title) < 1 || strlen($this->title) > 255)) {
            throw new \InvalidArgumentException('標題長度必須在 1-255 字元之間');
        }

        // 驗證內容長度（如果有提供）
        if ($this->content !== null && strlen($this->content) < 1) {
            throw new \InvalidArgumentException('內容不能為空');
        }

        // 驗證發布日期格式（如果有提供）
        if ($this->publishDate !== null) {
            $dateTime = \DateTimeImmutable::createFromFormat(\DateTimeImmutable::RFC3339, $this->publishDate);
            if ($dateTime === false) {
                throw new \InvalidArgumentException('無效的發布日期格式，請使用 RFC3339 格式');
            }
        }
    }

    /**
     * 轉換為陣列格式（供 Repository 使用）
     * 只包含有值的欄位
     * 
     * @return array
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->content !== null) {
            $data['content'] = $this->content;
        }

        if ($this->isPinned !== null) {
            $data['is_pinned'] = $this->isPinned;
        }

        if ($this->status !== null) {
            $data['status'] = $this->status->value;
        }

        if ($this->publishDate !== null) {
            $data['publish_date'] = $this->publishDate;
        }

        return $data;
    }

    /**
     * 檢查是否有任何資料需要更新
     * 
     * @return bool
     */
    public function hasChanges(): bool
    {
        return !empty($this->toArray());
    }
}
