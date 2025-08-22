<?php

declare(strict_types=1);

namespace App\DTOs\IpManagement;

use App\DTOs\BaseDTO;

/**
 * 建立 IP 規則的資料傳輸物件
 * 
 * 用於安全地傳輸建立 IP 規則所需的資料，防止巨量賦值攻擊
 */
class CreateIpRuleDTO extends BaseDTO
{
    public readonly string $ipAddress;
    public readonly string $action;
    public readonly ?string $reason;
    public readonly int $createdBy;

    /**
     * @param array $data 輸入資料
     * @throws \InvalidArgumentException 當必填欄位缺失或資料格式錯誤時
     */
    public function __construct(array $data)
    {
        // 驗證必填欄位
        $this->validateRequired(['ip_address', 'action', 'created_by'], $data);

        // 設定屬性
        $this->ipAddress = $this->getString($data, 'ip_address');
        $this->action = $this->getString($data, 'action');
        $this->reason = $this->getString($data, 'reason');
        $this->createdBy = $this->getInt($data, 'created_by');

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
        // 驗證 IP 位址格式（支援 CIDR 標記法）
        if (!$this->isValidIpOrCidr($this->ipAddress)) {
            throw new \InvalidArgumentException('無效的 IP 位址或 CIDR 格式');
        }

        // 驗證動作類型
        $allowedActions = ['allow', 'block'];
        if (!in_array($this->action, $allowedActions, true)) {
            throw new \InvalidArgumentException(
                sprintf('無效的動作類型，允許的類型: %s', implode(', ', $allowedActions))
            );
        }

        // 驗證原因長度（如果有提供）
        if ($this->reason !== null && strlen($this->reason) > 500) {
            throw new \InvalidArgumentException('原因說明不能超過 500 字元');
        }
    }

    /**
     * 驗證 IP 位址或 CIDR 格式
     * 
     * @param string $ip
     * @return bool
     */
    private function isValidIpOrCidr(string $ip): bool
    {
        // 檢查是否包含 CIDR 標記法
        if (strpos($ip, '/') !== false) {
            list($ipPart, $cidr) = explode('/', $ip, 2);

            // 驗證 IP 部分
            if (!filter_var($ipPart, FILTER_VALIDATE_IP)) {
                return false;
            }

            // 驗證 CIDR 部分
            $cidrInt = (int) $cidr;
            if (filter_var($ipPart, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $cidrInt >= 0 && $cidrInt <= 32;
            } elseif (filter_var($ipPart, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return $cidrInt >= 0 && $cidrInt <= 128;
            }

            return false;
        }

        // 單純的 IP 位址
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * 轉換為陣列格式（供 Repository 使用）
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'ip_address' => $this->ipAddress,
            'action' => $this->action,
            'reason' => $this->reason,
            'created_by' => $this->createdBy,
        ];
    }
}
