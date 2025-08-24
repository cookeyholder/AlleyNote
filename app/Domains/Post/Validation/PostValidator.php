<?php

declare(strict_types=1);

namespace App\Domains\Post\Validation;

use App\Services\Enums\PostStatus;
use App\Shared\Contracts\ValidatorInterface;

/**
 * Post 專用驗證器
 *
 * 擴展基本驗證器，添加 Post 相關的自訂驗證規則
 */
class PostValidator extends Validator
{
    public function __construct()
    {
        $this->addPostSpecificRules();
        $this->addPostSpecificMessages();
    }

    /**
     * 添加 Post 相關的自訂驗證規則
     */
    private function addPostSpecificRules(): void
    {
        // PostStatus 枚舉驗證
        $this->addRule('post_status', function (mixed $value, array $parameters): bool {
            if ($value === null || $value === '') {
                return true; // 允許空值，由 required 規則處理
            }

            if (!is_string($value)) {
                return false;
            }

            return in_array($value, array_column(PostStatus::cases(), 'value'), true);
        });

        // RFC3339 日期時間格式驗證
        $this->addRule('rfc3339_datetime', function (mixed $value, array $parameters): bool {
            if ($value === null || $value === '') {
                return true; // 允許空值，由 required 規則處理
            }

            if (!is_string($value)) {
                return false;
            }

            // 支援多種 RFC3339 格式
            $formats = [
                \DateTime::RFC3339,
                \DateTime::RFC3339_EXTENDED,
                'Y-m-d\TH:i:s\Z',
                'Y-m-d\TH:i:s.u\Z',
                'Y-m-d\TH:i:sP',
                'Y-m-d\TH:i:s.uP',
            ];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    return true;
                }
            }

            return false;
        });

        // 文章標題驗證（去除 HTML 標籤，檢查實際內容長度）
        $this->addRule('post_title', function (mixed $value, array $parameters): bool {
            if (!is_string($value)) {
                return false;
            }

            // 去除 HTML 標籤和多餘空白
            $cleanTitle = trim(strip_tags($value));

            if (empty($cleanTitle)) {
                return false;
            }

            $minLength = isset($parameters[0]) ? (int) $parameters[0] : 1;
            $maxLength = isset($parameters[1]) ? (int) $parameters[1] : 255;

            $length = mb_strlen($cleanTitle);
            return $length >= $minLength && $length <= $maxLength;
        });

        // 文章內容驗證（去除 HTML 標籤，檢查實際內容長度）
        $this->addRule('post_content', function (mixed $value, array $parameters): bool {
            if (!is_string($value)) {
                return false;
            }

            // 去除 HTML 標籤和多餘空白
            $cleanContent = trim(strip_tags($value));

            if (empty($cleanContent)) {
                return false;
            }

            $minLength = isset($parameters[0]) ? (int) $parameters[0] : 1;
            $maxLength = isset($parameters[1]) ? (int) $parameters[1] : null;

            $length = mb_strlen($cleanContent);

            if ($length < $minLength) {
                return false;
            }

            if ($maxLength !== null && $length > $maxLength) {
                return false;
            }

            return true;
        });

        // 使用者 ID 驗證（必須是正整數）
        $this->addRule('user_id', function (mixed $value, array $parameters): bool {
            if ($value === null || $value === '') {
                return false;
            }

            if (!is_numeric($value)) {
                return false;
            }

            $userId = (int) $value;
            return $userId > 0;
        });

        // IP 地址驗證（支援 IPv4 和 IPv6）
        $this->addRule('ip_address', function (mixed $value, array $parameters): bool {
            if (!is_string($value)) {
                return false;
            }

            // 檢查是否為有效的 IP 地址
            if (filter_var($value, FILTER_VALIDATE_IP) === false) {
                return false;
            }

            // 如果有指定版本限制
            if (!empty($parameters)) {
                $version = strtolower($parameters[0]);
                if ($version === 'ipv4') {
                    return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
                } elseif ($version === 'ipv6') {
                    return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
                }
            }

            return true;
        });

        // 發布日期驗證（不能是過去的日期，除非是草稿）
        $this->addRule('publish_date_future', function (mixed $value, array $parameters): bool {
            if ($value === null || $value === '') {
                return true; // 允許空值
            }

            if (!is_string($value)) {
                return false;
            }

            // 首先檢查是否為有效的日期格式
            $date = \DateTime::createFromFormat(\DateTime::RFC3339, $value);
            if (!$date) {
                return false;
            }

            // 如果狀態是草稿，允許任何日期
            $status = $parameters[0] ?? null;
            if ($status === PostStatus::DRAFT->value) {
                return true;
            }

            // 對於非草稿狀態，發布日期不能早於當前時間
            $now = new \DateTime();
            return $date >= $now;
        });
    }

    /**
     * 添加 Post 相關的自訂錯誤訊息
     */
    private function addPostSpecificMessages(): void
    {
        $this->addMessage('post_status', '文章狀態必須是有效的值（draft, published, archived）');
        $this->addMessage('rfc3339_datetime', '日期時間格式必須符合 RFC3339 標準（例如：2023-12-01T10:30:00Z）');
        $this->addMessage('post_title', '文章標題長度必須在 :min 到 :max 個字元之間，且不能為空');
        $this->addMessage('post_content', '文章內容不能為空，且長度必須至少 :min 個字元');
        $this->addMessage('user_id', '使用者 ID 必須是正整數');
        $this->addMessage('ip_address', 'IP 地址格式不正確');
        $this->addMessage('publish_date_future', '發布日期不能早於當前時間');
    }

    /**
     * 建立 Post 建立時的驗證規則
     *
     * @return array
     */
    public static function getCreatePostRules(): array
    {
        return [
            'title' => 'required|post_title:1,255',
            'content' => 'required|post_content:1',
            'user_id' => 'required|user_id',
            'user_ip' => 'required|ip_address',
            'is_pinned' => 'boolean',
            'status' => 'post_status',
            'publish_date' => 'rfc3339_datetime',
        ];
    }

    /**
     * 建立 Post 更新時的驗證規則
     *
     * @return array
     */
    public static function getUpdatePostRules(): array
    {
        return [
            'title' => 'post_title:1,255',
            'content' => 'post_content:1',
            'is_pinned' => 'boolean',
            'status' => 'post_status',
            'publish_date' => 'rfc3339_datetime',
        ];
    }

    /**
     * 建立動態的更新驗證規則（只驗證提供的欄位）
     *
     * @param array $data 要驗證的資料
     * @return array
     */
    public static function getDynamicUpdateRules(array $data): array
    {
        $rules = [];
        $availableRules = self::getUpdatePostRules();

        foreach ($data as $field => $value) {
            if (isset($availableRules[$field])) {
                $rules[$field] = $availableRules[$field];
            }
        }

        return $rules;
    }

    /**
     * 驗證 Post 資料的特殊邏輯
     *
     * @param array $data 要驗證的資料
     * @param bool $isUpdate 是否為更新操作
     * @return ValidationResult
     */
    public function validatePostData(array $data, bool $isUpdate = false): ValidationResult
    {
        $rules = $isUpdate ? self::getUpdatePostRules() : self::getCreatePostRules();

        // 執行基本驗證
        $result = $this->validate($data, $rules);

        // 如果基本驗證失敗，直接返回結果
        if ($result->isInvalid()) {
            return $result;
        }

        // 執行額外的業務邏輯驗證
        $validatedData = $result->getValidatedData();

        // 檢查發布日期與狀態的一致性
        if (isset($validatedData['publish_date']) && isset($validatedData['status'])) {
            $publishDate = $validatedData['publish_date'];
            $status = $validatedData['status'];

            if ($publishDate && !$this->checkRule($publishDate, 'publish_date_future', [$status])) {
                $result->addError('publish_date', '發布日期不能早於當前時間');
                $result->addFailedRule('publish_date', 'publish_date_future');
            }
        }

        return $result;
    }
}
