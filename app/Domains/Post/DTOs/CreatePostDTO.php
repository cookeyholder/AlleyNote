<?php

declare(strict_types=1);

namespace App\Domains\Post\DTOs;

use App\Domains\Post\Enums\PostStatus;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\DTOs\BaseDTO;
use DateTime;

/**
 * 建立文章的資料傳輸物件.
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
     * @param ValidatorInterface $validator 驗證器實例
     * @param array $data 輸入資料
     * @throws ValidationException 當驗證失敗時
     */
    public function __construct(ValidatorInterface $validator, array $data)
    {
        parent::__construct($validator);

        // 添加文章專用驗證規則
        $this->addPostValidationRules();

        // 預處理狀態值
        if (!isset($data['status']) || empty($data['status'])) {
            $data['status'] = PostStatus::DRAFT->value;
        }

        // 預處理 is_pinned 預設值
        if (!isset($data['is_pinned'])) {
            $data['is_pinned'] = false;
        }

        // 驗證資料
        $validatedData = $this->validate($data);

        // 直接從驗證過的資料設定屬性，無需額外的類型檢查
        $this->title = trim($validatedData['title']);
        $this->content = trim($validatedData['content']);
        $this->userId = (int) $validatedData['user_id'];
        $this->userIp = $validatedData['user_ip'];
        $this->isPinned = (bool) ($validatedData['is_pinned'] ?? false);
        $this->status = PostStatus::from($validatedData['status']);

        // 處理發布日期，空字串轉為 null
        $publishDate = $validatedData['publish_date'] ?? null;
        $this->publishDate = ($publishDate === '') ? null : $publishDate;
    }

    /**
     * 添加文章專用驗證規則.
     */
    private function addPostValidationRules(): void
    {
        // 文章標題驗證規則
        $this->validator->addRule('post_title', function ($value, array $parameters) {
            if (!is_string($value)) {
                return false;
            }

            $title = trim($value);
            $minLength = (int) ($parameters[0] ?? 1);
            $maxLength = (int) ($parameters[1] ?? 255);

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

        // 文章內容驗證規則
        $this->validator->addRule('post_content', function ($value, array $parameters) {
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

        // 使用者 ID 驗證規則
        $this->validator->addRule('user_id', function ($value) {
            return is_numeric($value) && (int) $value > 0;
        });

        // IP 地址驗證規則（別名）
        $this->validator->addRule('ip_address', function ($value) {
            return filter_var($value, FILTER_VALIDATE_IP) !== false;
        });

        // 文章狀態驗證規則
        $this->validator->addRule('post_status', function ($value) {
            if (!is_string($value)) {
                return false;
            }

            $validStatuses = array_map(fn($status) => $status->value, PostStatus::cases());

            return in_array($value, $validStatuses, true);
        });

        // RFC3339 日期時間驗證規則
        $this->validator->addRule('rfc3339_datetime', function ($value) {
            if ($value === null || $value === '') {
                return true; // 允許空值
            }

            if (!is_string($value)) {
                return false;
            }

            // 支援多種 RFC3339 格式
            $formats = [
                DateTime::RFC3339,
                DateTime::RFC3339_EXTENDED,
                'Y-m-d\TH:i:s\Z',
                'Y-m-d\TH:i:sP',
            ];

            foreach ($formats as $format) {
                $date = DateTime::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    return true;
                }
            }

            return false;
        });

        // 添加繁體中文錯誤訊息
        $this->validator->addMessage('post_title', '文章標題長度必須介於 :min 和 :max 個字元之間，且包含有效內容');
        $this->validator->addMessage('post_content', '文章內容長度不能少於 :min 個字元，且必須包含有效內容');
        $this->validator->addMessage('user_id', '使用者 ID 必須是正整數');
        $this->validator->addMessage('ip_address', 'IP 地址格式不正確');
        $this->validator->addMessage('post_status', '文章狀態必須是：draft（草稿）、published（已發布）或 archived（已封存）');
        $this->validator->addMessage('rfc3339_datetime', '發布日期必須是有效的 RFC3339 日期時間格式');
    }

    /**
     * 取得驗證規則.
     */
    protected function getValidationRules(): array
    {
        return [
            'title' => 'required|string|post_title:1,255',
            'content' => 'required|string|post_content:1',
            'user_id' => 'required|user_id',
            'user_ip' => 'required|ip_address',
            'is_pinned' => 'boolean',
            'status' => 'required|string|post_status',
            'publish_date' => 'rfc3339_datetime',
        ];
    }

    /**
     * 轉換為陣列格式（供 Repository 使用）.
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
