<?php

declare(strict_types=1);

namespace App\Domains\Post\DTOs;

use App\Shared\DTOs\BaseDTO;
use App\Domains\Post\Enums\PostStatus;
use App\Shared\Contracts\ValidatorInterface;

/**
 * 更新文章的資料傳輸物件.
 *
 * 用於安全地傳輸更新文章所需的資料，防止巨量賦值攻擊
 * 支援部分更新，只驗證和處理提供的欄位
 */
class UpdatePostDTO extends BaseDTO
{
    public readonly ?string $title;

    public readonly ?string $content;

    public readonly ?bool $isPinned;

    public readonly ?PostStatus $status;

    public readonly ?string $publishDate;

    /**
     * @param ValidatorInterface $validator 驗證器實例
     * @param array $data 輸入資料
     * @throws ValidationException 當驗證失敗時
     */
    public function __construct(ValidatorInterface $validator, array $data)
    {
        parent::__construct($validator);

        // 添加文章專用驗證規則
        $this->addPostValidationRules();

        // 過濾空值和空字串，只保留有意義的更新資料
        $filteredData = [];
        foreach ($data as $key => $value) {
            // 保留 false 和 0，但排除 null 和空字串
            if ($value !== null && $value !== '') {
                $filteredData[$key] = $value;
            }
            // 特別處理布林值的情況
            if ($key === 'is_pinned' && ($value === false || $value === 0 || $value === '0')) {
                $filteredData[$key] = $value;
            }
        }

        // 如果沒有任何資料需要更新，建立空的 DTO
        if (empty($filteredData)) {
            $this->title = null;
            $this->content = null;
            $this->isPinned = null;
            $this->status = null;
            $this->publishDate = null;
            return;
        }

        // 動態驗證資料（只驗證提供的欄位）
        $validatedData = $this->validatePartialData($filteredData);

        // 設定屬性（全部都是可選的）
        $this->title = isset($validatedData['title']) ? trim($validatedData['title']) : null;
        $this->content = isset($validatedData['content']) ? trim($validatedData['content']) : null;
        $this->isPinned = isset($validatedData['is_pinned']) ? $this->convertToBoolean($validatedData['is_pinned']) : null;

        // 處理狀態
        if (isset($validatedData['status'])) {
            $this->status = PostStatus::from($validatedData['status']);
        } else {
            $this->status = null;
        }

        // 處理發布日期，空字串轉為 null
        if (isset($validatedData['publish_date'])) {
            $publishDate = $validatedData['publish_date'];
            $this->publishDate = ($publishDate === '') ? null : $publishDate;
        } else {
            $this->publishDate = null;
        }
    }

    /**
     * 添加文章專用驗證規則
     */
    private function addPostValidationRules(): void
    {
        // 文章標題驗證規則（更新版本，允許空值）
        $this->validator->addRule('post_title_update', function ($value, array $parameters) {
            if ($value === null || $value === '') {
                return true; // 更新時允許空值
            }

            if (!is_string($value)) {
                return false;
            }

            $title = trim($value);
            $minLength = $parameters[0] ?? 1;
            $maxLength = $parameters[1] ?? 255;

            // 檢查長度
            $length = mb_strlen($title, 'UTF-8');
            if ($length < $minLength || $length > $maxLength) {
                return false;
            }

            // 檢查是否包含有效內容（不只是空白字元或特殊字符）
            if (!preg_match('/[\p{L}\p{N}]/u', $title)) {
                return false;
            }

            return true;
        });

        // 文章內容驗證規則（更新版本，允許空值）
        $this->validator->addRule('post_content_update', function ($value, array $parameters) {
            if ($value === null || $value === '') {
                return true; // 更新時允許空值
            }

            if (!is_string($value)) {
                return false;
            }

            $content = trim($value);
            $minLength = $parameters[0] ?? 1;

            // 檢查最小長度
            $length = mb_strlen($content, 'UTF-8');
            if ($length < $minLength) {
                return false;
            }

            // 檢查是否包含有效內容
            if (!preg_match('/[\p{L}\p{N}]/u', $content)) {
                return false;
            }

            return true;
        });

        // 文章狀態驗證規則
        $this->validator->addRule('post_status', function ($value) {
            if ($value === null || $value === '') {
                return true; // 更新時允許空值
            }

            if (!is_string($value)) {
                return false;
            }

            $validStatuses = array_map(fn($status) => $status->value, PostStatus::cases());
            return in_array($value, $validStatuses, true);
        });

        // RFC3339 日期時間驗證規則
        $this->validator->addRule('rfc3339_datetime', function ($value) {
            if ($value === null || $value === '') {
                return true; // 更新時允許空值
            }

            if (!is_string($value)) {
                return false;
            }

            // 支援多種 RFC3339 格式
            $formats = [
                \DateTime::RFC3339,
                \DateTime::RFC3339_EXTENDED,
                'Y-m-d\TH:i:s\Z',
                'Y-m-d\TH:i:sP'
            ];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    return true;
                }
            }

            return false;
        });

        // 添加繁體中文錯誤訊息
        $this->validator->addMessage('post_title_update', '文章標題長度必須介於 :min 和 :max 個字元之間，且包含有效內容');
        $this->validator->addMessage('post_content_update', '文章內容長度不能少於 :min 個字元，且必須包含有效內容');
        $this->validator->addMessage('post_status', '文章狀態必須是：draft（草稿）、published（已發布）或 archived（已封存）');
        $this->validator->addMessage('rfc3339_datetime', '發布日期必須是有效的 RFC3339 日期時間格式');
    }

    /**
     * 取得驗證規則（基礎方法，但 UpdatePostDTO 使用動態驗證）
     *
     * @return array
     */
    protected function getValidationRules(): array
    {
        // UpdatePostDTO 使用動態驗證規則，此方法不直接使用
        return [
            'title' => 'string|post_title_update:1,255',
            'content' => 'string|post_content_update:1',
            'is_pinned' => 'boolean',
            'status' => 'string|post_status',
            'publish_date' => 'rfc3339_datetime',
        ];
    }

    /**
     * 動態驗證資料（只驗證提供的欄位）
     *
     * @param array $data 要驗證的資料
     * @return array 驗證通過的資料
     * @throws ValidationException 當驗證失敗時
     */
    protected function validatePartialData(array $data): array
    {
        $rules = [];
        $availableRules = $this->getValidationRules();

        // 只為提供的欄位添加驗證規則
        foreach ($data as $field => $value) {
            if (isset($availableRules[$field])) {
                $rules[$field] = $availableRules[$field];
            }
        }

        // 如果沒有需要驗證的規則，直接返回原資料
        if (empty($rules)) {
            return $data;
        }

        return $this->validator->validateOrFail($data, $rules);
    }

    /**
     * 轉換為陣列格式（供 Repository 使用）
     * 只包含有值的欄位.
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
     * 檢查是否有任何資料需要更新.
     *
     * @return bool
     */
    public function hasChanges(): bool
    {
        return !empty($this->toArray());
    }

    /**
     * 取得更新的欄位名稱列表
     *
     * @return array
     */
    public function getUpdatedFields(): array
    {
        return array_keys($this->toArray());
    }

    /**
     * 檢查是否更新了特定欄位
     *
     * @param string $field 欄位名稱
     * @return bool
     */
    public function hasUpdatedField(string $field): bool
    {
        return in_array($field, $this->getUpdatedFields(), true);
    }

    /**
     * 正確轉換布林值
     *
     * @param mixed $value
     * @return bool
     */
    private function convertToBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['1', 'true', 'on', 'yes'], true);
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return false;
    }
}
