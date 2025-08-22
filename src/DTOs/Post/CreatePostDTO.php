<?php

declare(strict_types=1);

namespace App\DTOs\Post;

use App\DTOs\BaseDTO;
use App\Services\Enums\PostStatus;

/**
 * 建立文章的資料傳輸物件
 * 
 * 用於安全地傳輸建立文章所需的資料，防止巨量賦值攻擊
 */
class CreatePostDTO extends BaseDTO
{
    public readonly string $title;
    public readonly string $content;
    public readonly int $userId;
    public readonly string $userIp;
    public readonly bool $isPinned;
    public readonly PostStatus $status;
    public readonly ?string $publishDate;

    /**
     * @param array $data 輸入資料
     * @throws \InvalidArgumentException 當必填欄位缺失或資料格式錯誤時
     */
    public function __construct(array $data)
    {
        // 驗證必填欄位
        $this->validateRequired(['title', 'content', 'user_id', 'user_ip'], $data);

        // 設定屬性
        $this->title = $this->getString($data, 'title');
        $this->content = $this->getString($data, 'content');
        $this->userId = $this->getInt($data, 'user_id');
        $this->userIp = $this->getString($data, 'user_ip');
        $this->isPinned = $this->getBool($data, 'is_pinned', false);

        // 處理狀態
        $statusValue = $this->getString($data, 'status');
        if ($statusValue !== null) {
            $this->status = PostStatus::from($statusValue);
        } else {
            $this->status = PostStatus::DRAFT;
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
        // 驗證標題長度
        if (strlen($this->title) < 1 || strlen($this->title) > 255) {
            throw new \InvalidArgumentException('標題長度必須在 1-255 字元之間');
        }

        // 驗證內容長度
        if (strlen($this->content) < 1) {
            throw new \InvalidArgumentException('內容不能為空');
        }

        // 驗證 IP 位址格式
        if (!filter_var($this->userIp, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('無效的 IP 位址格式');
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
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'user_id' => $this->userId,
            'user_ip' => $this->userIp,
            'is_pinned' => $this->isPinned,
            'status' => $this->status->value,
            'publish_date' => $this->publishDate,
        ];
    }
}
