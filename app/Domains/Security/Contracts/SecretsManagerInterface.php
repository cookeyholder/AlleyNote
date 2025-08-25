<?php

declare(strict_types=1);

namespace App\Domains\Security\Contracts;

use App\Shared\Exceptions\ValidationException;

interface SecretsManagerInterface
{
    /**
     * 載入所有秘密設定.
     */
    public function load(): void;

    /**
     * 取得設定值
     *
     * @param string $key 設定鍵名
     * @param mixed $default 預設值
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * 設定秘密值
     *
     * @param string $key 設定鍵名
     * @param string $value 設定值
     */
    public function set(string $key, string $value): void;

    /**
     * 檢查設定是否存在.
     *
     * @param string $key 設定鍵名
     */
    public function has(string $key): bool;

    /**
     * 取得必需的設定值
     *
     * @param string $key 設定鍵名
     * @throws ValidationException 如果設定不存在
     */
    public function getRequired(string $key): string;

    /**
     * 驗證必需的秘密設定.
     *
     * @param array $requiredKeys 必需的設定鍵名陣列
     * @throws ValidationException 如果有缺少的設定
     */
    public function validateRequiredSecrets(array $requiredKeys): void;

    /**
     * 檢查是否為正式環境.
     */
    public function isProduction(): bool;

    /**
     * 檢查是否為開發環境.
     */
    public function isDevelopment(): bool;

    /**
     * 取得秘密設定摘要（敏感資料會被遮蔽）.
     */
    public function getSecretsSummary(): array;

    /**
     * 產生安全的隨機秘密.
     *
     * @param int $length 長度
     */
    public function generateSecret(int $length = 32): string;

    /**
     * 驗證 .env 檔案的安全性.
     *
     * @param string $filePath .env 檔案路徑
     * @return array 發現的問題陣列
     */
    public function validateEnvFile(string $filePath = ''): array;
}
