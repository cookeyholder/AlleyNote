<?php

declare(strict_types=1);

namespace App\Shared\Validation\Factory;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Validation\Validator;

/**
 * 驗證器工廠類別.
 *
 * 負責建立和配置驗證器實例，提供統一的驗證器建立介面
 */
class ValidatorFactory
{
    /**
     * 建立標準驗證器實例.
     */
    public function create(): ValidatorInterface
    {
        $validator = new Validator();

        // 設定繁體中文錯誤訊息
        $this->configureChineseMessages($validator);

        // 添加專案特定的驗證規則
        $this->addCustomRules($validator);

        return $validator;
    }

    /**
     * 建立具有自訂配置的驗證器實例.
     *
     * @param array<string, mixed> $config 自訂配置
     */
    public function createWithConfig(array $config): ValidatorInterface
    {
        $validator = $this->create();

        // 應用自訂配置
        if (isset($config['messages'])) {
            foreach ($config['messages'] as $rule => $message) {
                $validator->addMessage($rule, $message);
            }
        }

        if (isset($config['rules'])) {
            foreach ($config['rules'] as $name => $callback) {
                $validator->addRule($name, $callback);
            }
        }

        return $validator;
    }

    /**
     * 設定繁體中文錯誤訊息.
     */
    private function configureChineseMessages(ValidatorInterface $validator): void
    {
        // 基本驗證規則的中文訊息
        $validator->addMessage('required', '欄位 :field 為必填項目');
        $validator->addMessage('string', '欄位 :field 必須是字串');
        $validator->addMessage('integer', '欄位 :field 必須是整數');
        $validator->addMessage('numeric', '欄位 :field 必須是數字');
        $validator->addMessage('boolean', '欄位 :field 必須是布林值');
        $validator->addMessage('email', '欄位 :field 必須是有效的電子郵件地址');
        $validator->addMessage('url', '欄位 :field 必須是有效的 URL');
        $validator->addMessage('ip', '欄位 :field 必須是有效的 IP 地址');
        $validator->addMessage('min', '欄位 :field 的最小值為 :param1');
        $validator->addMessage('max', '欄位 :field 的最大值為 :param1');
        $validator->addMessage('between', '欄位 :field 必須介於 :param1 和 :param2 之間');
        $validator->addMessage('in', '欄位 :field 必須是以下值之一：:param1');
        $validator->addMessage('not_in', '欄位 :field 不能是以下值之一：:param1');
        $validator->addMessage('min_length', '欄位 :field 的最小長度為 :param1 個字元');
        $validator->addMessage('max_length', '欄位 :field 的最大長度為 :param1 個字元');
        $validator->addMessage('length', '欄位 :field 的長度必須是 :param1 個字元');
        $validator->addMessage('regex', '欄位 :field 格式不正確');
        $validator->addMessage('alpha', '欄位 :field 只能包含字母');
        $validator->addMessage('alpha_num', '欄位 :field 只能包含字母和數字');
        $validator->addMessage('alpha_dash', '欄位 :field 只能包含字母、數字、破折號和底線');
        $validator->addMessage('date', '欄位 :field 必須是有效的日期');
        $validator->addMessage('date_format', '欄位 :field 必須符合格式 :param1');
        $validator->addMessage('before', '欄位 :field 必須在 :param1 之前');
        $validator->addMessage('after', '欄位 :field 必須在 :param1 之後');
        $validator->addMessage('file', '欄位 :field 必須是檔案');
        $validator->addMessage('image', '欄位 :field 必須是圖片檔案');
        $validator->addMessage('mimes', '欄位 :field 必須是以下檔案類型之一：:param1');
        $validator->addMessage('size', '欄位 :field 的檔案大小必須是 :param1 KB');
        $validator->addMessage('confirmed', '欄位 :field 確認不相符');
        $validator->addMessage('same', '欄位 :field 和 :param1 必須相同');
        $validator->addMessage('different', '欄位 :field 和 :param1 必須不同');
        $validator->addMessage('unique', '欄位 :field 已存在');
        $validator->addMessage('exists', '選取的 :field 無效');
    }

    /**
     * 添加專案特定的自訂驗證規則.
     */
    private function addCustomRules(ValidatorInterface $validator): void
    {
        // 使用者名稱驗證規則
        $validator->addRule('username', function ($value, array $parameters) {
            if (!is_string($value)) {
                return false;
            }

            $username = trim($value);
            $minLength = (int) ($parameters[0] ?? 3);
            $maxLength = (int) ($parameters[1] ?? 50);

            // 檢查長度
            $length = mb_strlen($username, 'UTF-8');
            if ($length < $minLength || $length > $maxLength) {
                return false;
            }

            // 檢查格式：只允許字母、數字、底線和破折號
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
                return false;
            }

            // 不能以數字開頭
            if (preg_match('/^\d/', $username)) {
                return false;
            }

            return true;
        });

        // 密碼強度驗證規則
        $validator->addRule('password_strength', function ($value, array $parameters) {
            if (!is_string($value)) {
                return false;
            }

            $password = $value;
            $minLength = (int) ($parameters[0] ?? 8);

            // 檢查最小長度
            if (strlen($password) < $minLength) {
                return false;
            }

            // 檢查複雜度：至少包含一個小寫字母、一個大寫字母、一個數字
            if (!preg_match('/[a-z]/', $password)) {
                return false;
            }

            if (!preg_match('/[A-Z]/', $password)) {
                return false;
            }

            if (!preg_match('/\d/', $password)) {
                return false;
            }

            return true;
        });

        // 增強型電子郵件驗證
        $validator->addRule('email_enhanced', function ($value) {
            if (!is_string($value)) {
                return false;
            }

            $email = trim($value);

            // 基本格式檢查
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            // 檢查長度限制
            if (strlen($email) > 254) {
                return false;
            }

            // 檢查域名部分
            $parts = explode('@', $email);
            if (count($parts) !== 2) {
                return false;
            }

            $domain = $parts[1];

            // 檢查域名長度
            if (strlen($domain) > 253) {
                return false;
            }

            // 檢查是否包含危險字元
            if (preg_match('/[<>"\'&]/', $email)) {
                return false;
            }

            return true;
        });

        // IP 地址或 CIDR 格式驗證
        $validator->addRule('ip_or_cidr', function ($value) {
            if (!is_string($value)) {
                return false;
            }

            $ip = trim($value);

            // 檢查是否為 CIDR 格式
            if (strpos($ip, '/') !== false) {
                $parts = explode('/', $ip);
                if (count($parts) !== 2) {
                    return false;
                }

                $ipPart = $parts[0];
                $maskPart = $parts[1];

                // 驗證 IP 部分
                if (!filter_var($ipPart, FILTER_VALIDATE_IP)) {
                    return false;
                }

                // 驗證子網路遮罩
                if (!is_numeric($maskPart)) {
                    return false;
                }

                $mask = (int) $maskPart;
                if (filter_var($ipPart, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    // IPv4：遮罩範圍 0-32
                    return $mask >= 0 && $mask <= 32;
                } elseif (filter_var($ipPart, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    // IPv6：遮罩範圍 0-128
                    return $mask >= 0 && $mask <= 128;
                }

                return false;
            }

            // 檢查是否為單一 IP 地址
            return filter_var($ip, FILTER_VALIDATE_IP) !== false;
        });

        // 檔案名稱驗證規則
        $validator->addRule('filename', function ($value, array $parameters) {
            if (!is_string($value)) {
                return false;
            }

            $filename = trim($value);
            $maxLength = (int) ($parameters[0] ?? 255);

            // 檢查長度
            if (mb_strlen($filename, 'UTF-8') > $maxLength) {
                return false;
            }

            // 檢查是否包含危險字元
            $dangerousChars = ['/', '\\', ':', '*', '?', '"', '<', '>', '|', "\0"];
            foreach ($dangerousChars as $char) {
                if (strpos($filename, $char) !== false) {
                    return false;
                }
            }

            // 不能是 . 或 ..
            if ($filename === '.' || $filename === '..') {
                return false;
            }

            // 不能以點開頭或結尾（Windows 相容性）
            if (strpos($filename, '.') === 0 || substr($filename, -1) === '.') {
                return false;
            }

            return true;
        });

        // 設定對應的中文錯誤訊息
        $validator->addMessage('username', '使用者名稱長度必須介於 :param1 和 :param2 個字元之間，只能包含字母、數字、底線和破折號，且不能以數字開頭');
        $validator->addMessage('password_strength', '密碼長度至少需要 :param1 個字元，且必須包含大寫字母、小寫字母和數字');
        $validator->addMessage('email_enhanced', '電子郵件地址格式不正確');
        $validator->addMessage('ip_or_cidr', 'IP 地址或 CIDR 格式不正確');
        $validator->addMessage('filename', '檔案名稱長度不能超過 :param1 個字元，且不能包含危險字元');
    }

    /**
     * 建立用於 DTO 的驗證器實例.
     */
    public function createForDTO(): ValidatorInterface
    {
        $validator = $this->create();

        // 為 DTO 添加額外的專用規則
        $this->addDTOSpecificRules($validator);

        return $validator;
    }

    /**
     * 添加 DTO 專用的驗證規則.
     */
    private function addDTOSpecificRules(ValidatorInterface $validator): void
    {
        // 這裡可以添加 DTO 專用的驗證規則
        // 例如：跨欄位驗證、複雜的業務邏輯驗證等

        // 密碼確認驗證（用於註冊 DTO）
        $validator->addRule('password_confirmed', function ($value, array $parameters, array $allData = []) {
            if (!is_string($value)) {
                return false;
            }

            $password = $allData['password'] ?? null;

            if ($password === null) {
                return false;
            }

            return $value === $password;
        });

        $validator->addMessage('password_confirmed', '密碼確認不相符');
    }
}
