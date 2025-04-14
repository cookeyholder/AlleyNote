<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\IpRepositoryInterface;
use App\Models\IpList;

class IpService
{
    public function __construct(
        private IpRepositoryInterface $repository
    ) {}

    public function createIpRule(array $data): IpList
    {
        // 驗證 IP 位址格式
        if (!$this->isValidIpOrCidr($data['ip_address'])) {
            throw new \InvalidArgumentException('無效的 IP 位址格式');
        }

        // 驗證名單類型
        if (!isset($data['type']) || !in_array($data['type'], [0, 1], true)) {
            throw new \InvalidArgumentException('無效的名單類型，必須是 0（黑名單）或 1（白名單）');
        }

        return $this->repository->create($data);
    }

    public function isIpAllowed(string $ip): bool
    {
        // 檢查 IP 格式
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('無效的 IP 位址格式');
        }

        // 優先檢查白名單
        if ($this->repository->isWhitelisted($ip)) {
            return true;
        }

        // 檢查是否在黑名單中
        if ($this->repository->isBlacklisted($ip)) {
            return false;
        }

        // 預設允許（不在任何名單中）
        return true;
    }

    public function getRulesByType(int $type): array
    {
        // 驗證類型值
        if (!in_array($type, [0, 1], true)) {
            throw new \InvalidArgumentException('無效的名單類型');
        }

        return $this->repository->getByType($type);
    }

    private function isValidIpOrCidr(string $ipAddress): bool
    {
        // 檢查是否為有效的 IP 位址
        if (filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return true;
        }

        // 檢查是否為有效的 CIDR 格式
        if (preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}\/([0-9]|[1-2][0-9]|3[0-2])$/', $ipAddress)) {
            // 進一步驗證 IP 部分是否有效
            $ip = explode('/', $ipAddress)[0];
            return filter_var($ip, FILTER_VALIDATE_IP) !== false;
        }

        return false;
    }
}
