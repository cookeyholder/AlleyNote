<?php

declare(strict_types=1);

namespace App\Domains\Post\Validation\Validators;

use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;
use PDO;

class PostValidator
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * 驗證新建文章的完整資料.
     * @param array $data 文章資料
     * @throws ValidationException
     */
    public function validateForCreation(array $data): void
    {
        $this->validateRequiredFields($data);
        $this->validateDataTypes($data);
        $this->validateFieldLengths($data);
        $this->validateStatus($data);
        $this->validateUserIp($data);
        $this->validatePublishDate($data);
    }

    /**
     * 驗證更新文章的資料（部分欄位）.
     * @param array $data 文章資料
     * @throws ValidationException
     */
    public function validateForUpdate(array $data): void
    {
        // 只驗證有提供的欄位
        $validationData = array_intersect_key($data, [
            'title' => true,
            'content' => true,
            'user_id' => true,
            'user_ip' => true,
            'is_pinned' => true,
            'status' => true,
            'publish_date' => true,
        ]);

        if (!empty($validationData)) {
            $this->validateDataTypes($validationData);
            $this->validateFieldLengths($validationData);
            if (isset($validationData['status'])) {
                $this->validateStatus($validationData);
            }
            if (isset($validationData['user_ip'])) {
                $this->validateUserIp($validationData);
            }
            if (isset($validationData['publish_date'])) {
                $this->validatePublishDate($validationData);
            }
        }
    }

    /**
     * 驗證標籤指派.
     * @param array $tagIds 標籤 ID 陣列
     * @throws ValidationException
     */
    public function validateTagAssignment(array $tagIds): void
    {
        if (empty($tagIds)) {
            return;
        }

        // 檢查標籤 ID 是否都是正整數
        foreach ($tagIds as $tagId) {
            if (!is_int($tagId) || $tagId <= 0) {
                throw ValidationException::fromSingleError('tag_id', '標籤 ID 必須是正整數');
            }
        }

        // 檢查標籤是否存在
        if (!$this->tagsExist($tagIds)) {
            throw ValidationException::fromSingleError('tags', '某些標籤不存在');
        }
    }

    /**
     * 驗證必要欄位.
     */
    private function validateRequiredFields(array $data): void
    {
        $errors = [];

        // 檢查必要欄位
        if (empty($data['title'])) {
            $errors[] = '標題不能為空';
        }
        if (empty($data['content'])) {
            $errors[] = '內容不能為空';
        }
        if (empty($data['user_id'])) {
            $errors[] = '使用者 ID 不能為空';
        }

        if (!empty($errors)) {
            throw ValidationException::fromErrors(['required_fields' => $errors], implode(', ', $errors));
        }
    }

    /**
     * 驗證欄位長度.
     */
    private function validateFieldLengths(array $data): void
    {
        $errors = [];

        // 檢查欄位長度
        if (isset($data['title']) && mb_strlen($data['title']) > 100) {
            $errors[] = '標題長度不能超過 100 個字';
        }
        if (isset($data['content']) && mb_strlen($data['content']) > 10000) {
            $errors[] = '內容長度不能超過 10000 個字';
        }

        if (!empty($errors)) {
            throw ValidationException::fromErrors(['length_validation' => $errors], implode(', ', $errors));
        }
    }

    /**
     * 驗證資料型別.
     */
    private function validateDataTypes(array $data): void
    {
        $errors = [];

        // 檢查資料型別
        if (isset($data['user_id']) && !is_numeric($data['user_id'])) {
            $errors[] = '使用者 ID 必須是數字';
        }
        if (isset($data['is_pinned']) && !is_bool($data['is_pinned'])) {
            $errors[] = '置頂標記必須是布林值';
        }

        if (!empty($errors)) {
            throw ValidationException::fromErrors(['data_type' => $errors], implode(', ', $errors));
        }
    }

    /**
     * 驗證文章狀態.
     * @throws ValidationException
     */
    private function validateStatus(array $data): void
    {
        if (isset($data['status']) && !in_array($data['status'], ['draft', 'published', 'archived'], true)) {
            throw ValidationException::fromSingleError('status', '狀態值必須是 draft、published 或 archived');
        }
    }

    /**
     * 驗證發布日期格式.
     * @throws ValidationException
     */
    private function validatePublishDate(array $data): void
    {
        if (isset($data['publish_date'])) {
            $date = \DateTime::createFromFormat(\DateTime::RFC3339, $data['publish_date']);
            if (!$date || $date->format(\DateTime::RFC3339) !== $data['publish_date']) {
                throw ValidationException::fromSingleError('publish_date', '發布日期格式必須是 RFC 3339');
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
            throw ValidationException::fromSingleError('ip_address', 'IP 位址格式無效');
        }
    }

    /**
     * 檢查標籤是否存在.
     */
    private function tagsExist(array $tagIds): bool
    {
        if (empty($tagIds)) {
            return true;
        }

        $placeholders = str_repeat('?,', count($tagIds) - 1) . '?';
        $sql = "SELECT COUNT(*) FROM tags WHERE id IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($tagIds);

        $count = (int) $stmt->fetchColumn();

        return $count === count($tagIds);
    }
}