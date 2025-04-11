<?php

declare(strict_types=1);

namespace App\Services\Validators;

use App\Exceptions\ValidationException;
use App\Services\Enums\PostStatus;

class PostValidator
{
    /**
     * 驗證文章資料
     * @param array $data 文章資料
     * @throws ValidationException
     */
    public function validate(array $data): void
    {
        $this->validateTitle($data);
        $this->validateContent($data);
        $this->validatePublishDate($data);
        $this->validateStatus($data);
        $this->validateUserIp($data);
    }

    /**
     * 驗證文章標題
     * @throws ValidationException
     */
    private function validateTitle(array $data): void
    {
        if (empty($data['title'])) {
            throw new ValidationException('文章標題不可為空');
        }
        if (strlen($data['title']) > 255) {
            throw new ValidationException('文章標題不可超過 255 字元');
        }
    }

    /**
     * 驗證文章內容
     * @throws ValidationException
     */
    private function validateContent(array $data): void
    {
        if (empty($data['content'])) {
            throw new ValidationException('文章內容不可為空');
        }
    }

    /**
     * 驗證發布日期格式
     * @throws ValidationException
     */
    private function validatePublishDate(array $data): void
    {
        if (isset($data['publish_date']) && !strtotime($data['publish_date'])) {
            throw new ValidationException('無效的發布日期格式');
        }
    }

    /**
     * 驗證文章狀態
     * @throws ValidationException
     */
    private function validateStatus(array $data): void
    {
        if (isset($data['status'])) {
            try {
                PostStatus::from($data['status']);
            } catch (\ValueError $e) {
                throw new ValidationException('無效的文章狀態');
            }
        }
    }

    /**
     * 驗證 IP 位址
     * @throws ValidationException
     */
    private function validateUserIp(array $data): void
    {
        if (isset($data['user_ip']) && !filter_var($data['user_ip'], FILTER_VALIDATE_IP)) {
            throw new ValidationException('無效的 IP 位址格式');
        }
    }
}
