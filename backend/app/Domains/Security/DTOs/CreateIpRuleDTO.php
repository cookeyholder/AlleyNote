<?php

declare(strict_types=1);

namespace App\Domains\Security\DTOs;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\DTOs\BaseDTO;
use App\Shared\Exceptions\ValidationException;

/**
 * 建立 IP 規則的資料傳輸物件.
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
     * @param ValidatorInterface $validator 驗證器實例
     * @param array<string, mixed> $data 輸入資料
     *                                   * @throws ValidationException 當驗證失敗時
     */
    public function __construct(ValidatorInterface $validator, array $data)
    {
        parent::__construct($validator);

        // 添加 IP 規則專用驗證規則
        $this->addIpRuleValidationRules();

        // 驗證資料
        $validatedData = $this->validate($data);

        // 設定屬性
        $ipAddressValue = $validatedData['ip_address'] ?? '';
        $this->ipAddress = is_string($ipAddressValue) ? trim($ipAddressValue) : '';

        $actionValue = $validatedData['action'] ?? '';
        $this->action = is_string($actionValue) ? strtolower(trim($actionValue)) : '';

        $reasonValue = $validatedData['reason'] ?? null;
        $this->reason = (isset($reasonValue) && is_string($reasonValue)) ? trim($reasonValue) : null;

        $createdByValue = $validatedData['created_by'] ?? 0;
        $this->createdBy = is_numeric($createdByValue) ? (int) $createdByValue : 0;
    }

    /**
     * 添加 IP 規則專用驗證規則.
     */
    private function addIpRuleValidationRules(): void
    {
        // IP 地址或 CIDR 驗證規則
        $this->validator->addRule('ip_or_cidr', function ($value) {
            if (!is_string($value)) {
                return false;
            }

            $ip = trim($value);

            // 檢查是否為空
            if (empty($ip)) {
                return false;
            }

            // 檢查是否包含 CIDR 標記法
            if (strpos($ip, '/') !== false) {
                $parts = explode('/', $ip, 2);
                if (count($parts) !== 2) {
                    return false;
                }

                [$ipPart, $cidr] = $parts;

                // 驗證 IP 部分
                if (!filter_var($ipPart, FILTER_VALIDATE_IP)) {
                    return false;
                }

                // 驗證 CIDR 部分
                if (!is_numeric($cidr)) {
                    return false;
                }

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
        });

        // IP 規則動作驗證
        $this->validator->addRule('ip_action', function ($value) {
            if (!is_string($value)) {
                return false;
            }

            $action = strtolower(trim($value));
            $allowedActions = ['allow', 'block', 'deny'];

            return in_array($action, $allowedActions, true);
        });

        // 原因說明驗證規則（可選）
        $this->validator->addRule('ip_reason', function ($value, array $parameters) {
            if ($value === null || $value === '') {
                return true; // 原因是可選的
            }

            if (!is_string($value)) {
                return false;
            }

            $reason = trim($value);
            $maxLength = $parameters[0] ?? 500;

            // 檢查長度
            if (mb_strlen($reason, 'UTF-8') > $maxLength) {
                return false;
            }

            // 檢查是否包含有效內容
            if (empty($reason)) {
                return true; // 空字串也是有效的（可選欄位）
            }

            return true;
        });

        // 建立者 ID 驗證規則
        $this->validator->addRule('created_by', function ($value) {
            return is_numeric($value) && (int) $value > 0;
        });

        // 添加繁體中文錯誤訊息
        $this->validator->addMessage('ip_or_cidr', 'IP 地址格式不正確，請輸入有效的 IP 地址或 CIDR 格式（例如：192.168.1.1 或 192.168.1.0/24）');
        $this->validator->addMessage('ip_action', 'IP 規則動作必須是：allow（允許）、block（阻擋）或 deny（拒絕）');
        $this->validator->addMessage('ip_reason', '原因說明長度不能超過 :max 個字元');
        $this->validator->addMessage('created_by', '建立者 ID 必須是正整數');
    }

    /**
     * 取得驗證規則.
     */
    protected function getValidationRules(): array
    {
        return [
            'ip_address' => 'required|string|ip_or_cidr',
            'action' => 'required|string|ip_action',
            'reason' => 'ip_reason:500',
            'created_by' => 'required|created_by',
        ];
    }

    /**
     * 轉換為陣列格式（供 Repository 使用）.
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

    /**
     * 檢查是否為 CIDR 格式.
     */
    public function isCidrFormat(): bool
    {
        return strpos($this->ipAddress, '/') !== false;
    }

    /**
     * 取得 IP 部分（去除 CIDR 後綴）.
     */
    public function getIpPart(): string
    {
        if ($this->isCidrFormat()) {
            return explode('/', $this->ipAddress, 2)[0];
        }

        return $this->ipAddress;
    }

    /**
     * 取得 CIDR 後綴（如果有的話）.
     */
    public function getCidrSuffix(): ?int
    {
        if ($this->isCidrFormat()) {
            return (int) explode('/', $this->ipAddress, 2)[1];
        }

        return null;
    }

    /**
     * 檢查是否為 IPv4 地址
     */
    public function isIpv4(): bool
    {
        $ip = $this->getIpPart();

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * 檢查是否為 IPv6 地址
     */
    public function isIpv6(): bool
    {
        $ip = $this->getIpPart();

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * 檢查是否為私有 IP 地址
     */
    public function isPrivateIp(): bool
    {
        $ip = $this->getIpPart();

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }

    /**
     * 檢查是否為本地回環地址
     */
    public function isLoopback(): bool
    {
        $ip = $this->getIpPart();

        if ($this->isIpv4()) {
            return str_starts_with($ip, '127.');
        } elseif ($this->isIpv6()) {
            return $ip === '::1';
        }

        return false;
    }

    /**
     * 取得 IP 規則的描述性名稱.
     */
    public function getDisplayName(): string
    {
        $type = $this->isIpv4() ? 'IPv4' : ($this->isIpv6() ? 'IPv6' : 'IP');
        $format = $this->isCidrFormat() ? ' 網段' : ' 地址';
        $action = $this->action === 'allow' ? '允許' : ($this->action === 'block' ? '阻擋' : '拒絕');

        return $action . ' ' . $type . $format . '：' . $this->ipAddress;
    }

    /**
     * 取得 IP 規則的詳細資訊.
     */
    public function getDetailedInfo(): array
    {
        return [
            'ip_address' => $this->ipAddress,
            'ip_part' => $this->getIpPart(),
            'cidr_suffix' => $this->getCidrSuffix(),
            'action' => $this->action,
            'reason' => $this->reason,
            'is_cidr' => $this->isCidrFormat(),
            'is_ipv4' => $this->isIpv4(),
            'is_ipv6' => $this->isIpv6(),
            'is_private' => $this->isPrivateIp(),
            'is_loopback' => $this->isLoopback(),
            'display_name' => $this->getDisplayName(),
            'created_by' => $this->createdBy,
        ];
    }
}
