<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\DTOs\BaseDTO;

/**
 * 使用者註冊的資料傳輸物件.
 *
 * 用於安全地傳輸使用者註冊所需的資料，防止巨量賦值攻擊
 */
class RegisterUserDTO extends BaseDTO
{
    public readonly string $username;

    public readonly string $email;

    public readonly string $password;

    public readonly string $confirmPassword;

    public readonly string $userIp;

    /**
     * @param ValidatorInterface $validator 驗證器實例
     * @param array $data 輸入資料
     * @throws ValidationException 當驗證失敗時
     */
    public function __construct(ValidatorInterface $validator, array $data)
    {
        parent::__construct($validator);

        // 添加使用者註冊專用驗證規則
        $this->addUserValidationRules();

        // 驗證資料
        $validatedData = $this->validate($data);

        // 設定屬性
        $this->username = trim($validatedData['username']);
        $this->email = trim(strtolower($validatedData['email']));
        $this->password = $validatedData['password'];
        $this->confirmPassword = $validatedData['confirm_password'];
        $this->userIp = $validatedData['user_ip'];
    }

    /**
     * 添加使用者註冊專用驗證規則.
     */
    private function addUserValidationRules(): void
    {
        // 使用者名稱驗證規則
        $this->validator->addRule('username', function ($value, array $parameters) {
            if (!is_string($value)) {
                return false;
            }

            $username = trim($value);
            $minLength = $parameters[0] ?? 3;
            $maxLength = $parameters[1] ?? 50;

            // 檢查長度
            $length = strlen($username);
            if ($length < $minLength || $length > $maxLength) {
                return false;
            }

            // 檢查字元組成（只允許英文字母、數字、底線和短橫線）
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
                return false;
            }

            // 不能以數字開頭
            if (preg_match('/^[0-9]/', $username)) {
                return false;
            }

            // 不能只是底線或短橫線
            if (preg_match('/^[_-]+$/', $username)) {
                return false;
            }

            return true;
        });

        // 密碼強度驗證規則
        $this->validator->addRule('password_strength', function ($value, array $parameters) {
            if (!is_string($value)) {
                return false;
            }

            $password = $value;
            $minLength = $parameters[0] ?? 8;

            // 檢查最小長度
            if (strlen($password) < $minLength) {
                return false;
            }

            // 檢查是否包含至少一個數字
            if (!preg_match('/[0-9]/', $password)) {
                return false;
            }

            // 檢查是否包含至少一個字母
            if (!preg_match('/[a-zA-Z]/', $password)) {
                return false;
            }

            // 檢查不能是常見弱密碼
            $weakPasswords = [
                'password', '12345678', 'qwerty123', 'abc12345',
                '11111111', 'password123', '123456789', 'qwertyui',
            ];

            if (in_array(strtolower($password), $weakPasswords, true)) {
                return false;
            }

            return true;
        });

        // 密碼確認驗證規則
        $this->validator->addRule('password_confirmed', function ($value, array $parameters, array $allData = []) {
            if (!is_string($value)) {
                return false;
            }

            // 需要與 password 欄位比較
            $password = $allData['password'] ?? null;

            return $password === $value;
        });

        // 使用者 IP 驗證規則（擴展版本）
        $this->validator->addRule('user_ip', function ($value) {
            if (!is_string($value)) {
                return false;
            }

            // 檢查是否為有效的 IP 地址
            if (filter_var($value, FILTER_VALIDATE_IP) === false) {
                return false;
            }

            // 檢查是否為私有 IP（可能需要特殊處理）
            if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                // 私有 IP 在開發環境中是允許的，這裡只是記錄，不阻止
            }

            return true;
        });

        // 電子郵件格式增強驗證
        $this->validator->addRule('email_enhanced', function ($value) {
            if (!is_string($value)) {
                return false;
            }

            $email = trim(strtolower($value));

            // 基本格式檢查
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            // 檢查長度限制
            if (strlen($email) > 320) { // RFC 5321 限制
                return false;
            }

            // 檢查 @ 符號數量
            $parts = explode('@', $email);
            if (count($parts) !== 2) {
                return false;
            }

            [$localPart, $domain] = $parts;

            // 檢查本地部分是否包含不當字元
            if (preg_match('/[<>()[\]\\;:\s"]/', $localPart)) {
                return false;
            }

            // 檢查是否為常見的一次性郵箱域名
            $disposableEmailDomains = [
                '10minutemail.com', 'tempmail.org', 'guerrillamail.com',
                'mailinator.com', 'yopmail.com', 'throwaway.email',
            ];

            $domain = substr(strrchr($email, '@'), 1);
            if (in_array($domain, $disposableEmailDomains, true)) {
                return false;
            }

            return true;
        });

        // 添加繁體中文錯誤訊息
        $this->validator->addMessage('username', '使用者名稱長度必須在 :min 到 :max 字元之間，只能包含英文字母、數字、底線和短橫線，且不能以數字開頭');
        $this->validator->addMessage('email_enhanced', '請輸入有效的電子郵件地址，不支援一次性郵箱');
        $this->validator->addMessage('password_strength', '密碼長度不能少於 :min 字元，且必須包含至少一個數字和一個字母，不能使用常見弱密碼');
        $this->validator->addMessage('password_confirmed', '密碼與確認密碼不一致');
        $this->validator->addMessage('user_ip', 'IP 地址格式不正確');
    }

    /**
     * 取得驗證規則.
     */
    protected function getValidationRules(): array
    {
        return [
            'username' => 'required|string|username:3,50',
            'email' => 'required|string|email_enhanced',
            'password' => 'required|string|password_strength:8',
            'confirm_password' => 'required|string|password_confirmed',
            'user_ip' => 'required|user_ip',
        ];
    }

    /**
     * 覆寫驗證方法以支援跨欄位驗證.
     *
     * @param array $data 輸入資料
     * @return array 驗證通過的資料
     * @throws ValidationException 當驗證失敗時
     */
    protected function validate(array $data): array
    {
        // 為跨欄位驗證規則提供完整資料
        $this->validator->addRule('password_confirmed', function ($value, array $parameters) use ($data) {
            if (!is_string($value)) {
                return false;
            }

            $password = $data['password'] ?? null;

            return $password === $value;
        });

        return parent::validate($data);
    }

    /**
     * 轉換為陣列格式（供 Service 使用）.
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
            'user_ip' => $this->userIp,
        ];
    }

    /**
     * 取得用於密碼雜湊的資料.
     */
    public function getPasswordData(): array
    {
        return [
            'password' => $this->password,
            'confirm_password' => $this->confirmPassword,
        ];
    }

    /**
     * 檢查密碼強度等級.
     *
     * @return string 密碼強度等級：weak, medium, strong
     */
    public function getPasswordStrength(): string
    {
        $password = $this->password;
        $score = 0;

        // 長度檢查
        if (strlen($password) >= 8) {
            $score++;
        }
        if (strlen($password) >= 12) {
            $score++;
        }

        // 字元類型檢查
        if (preg_match('/[a-z]/', $password)) {
            $score++;
        }
        if (preg_match('/[A-Z]/', $password)) {
            $score++;
        }
        if (preg_match('/[0-9]/', $password)) {
            $score++;
        }
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $score++;
        }

        // 複雜度檢查
        if (preg_match('/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])/', $password)) {
            $score++;
        }

        if ($score >= 6) {
            return 'strong';
        }
        if ($score >= 4) {
            return 'medium';
        }

        return 'weak';
    }

    /**
     * 取得清理後的使用者名稱（用於顯示）.
     */
    public function getDisplayUsername(): string
    {
        return $this->username;
    }

    /**
     * 取得電子郵件的域名部分.
     */
    public function getEmailDomain(): string
    {
        return substr(strrchr($this->email, '@'), 1);
    }

    /**
     * 檢查是否為企業郵箱.
     */
    public function isBusinessEmail(): bool
    {
        $businessDomains = [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
            'qq.com', '163.com', '126.com', 'sina.com',
        ];

        $domain = $this->getEmailDomain();

        return !in_array($domain, $businessDomains, true);
    }
}
