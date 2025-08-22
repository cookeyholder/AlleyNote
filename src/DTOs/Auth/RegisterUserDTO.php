<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use App\DTOs\BaseDTO;

/**
 * 使用者註冊的資料傳輸物件
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
     * @param array $data 輸入資料
     * @throws \InvalidArgumentException 當必填欄位缺失或資料格式錯誤時
     */
    public function __construct(array $data)
    {
        // 驗證必填欄位
        $this->validateRequired(['username', 'email', 'password', 'confirm_password', 'user_ip'], $data);

        // 設定屬性
        $this->username = $this->getString($data, 'username');
        $this->email = $this->getString($data, 'email');
        $this->password = $this->getString($data, 'password');
        $this->confirmPassword = $this->getString($data, 'confirm_password');
        $this->userIp = $this->getString($data, 'user_ip');

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
        // 驗證使用者名稱
        if (strlen($this->username) < 3 || strlen($this->username) > 50) {
            throw new \InvalidArgumentException('使用者名稱長度必須在 3-50 字元之間');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $this->username)) {
            throw new \InvalidArgumentException('使用者名稱只能包含英文字母、數字、底線和短橫線');
        }

        // 驗證 email
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('無效的 email 格式');
        }

        // 驗證密碼
        if (strlen($this->password) < 8) {
            throw new \InvalidArgumentException('密碼長度不能少於 8 字元');
        }

        if ($this->password !== $this->confirmPassword) {
            throw new \InvalidArgumentException('密碼與確認密碼不一致');
        }

        // 驗證 IP 位址格式
        if (!filter_var($this->userIp, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('無效的 IP 位址格式');
        }
    }

    /**
     * 轉換為陣列格式（供 Service 使用）
     * 
     * @return array
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
     * 取得用於密碼雜湊的資料
     * 
     * @return array
     */
    public function getPasswordData(): array
    {
        return [
            'password' => $this->password,
            'confirm_password' => $this->confirmPassword,
        ];
    }
}
