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

        $result = $this->repository->create($data);
        if (!$result instanceof IpList) {
            throw new \RuntimeException('建立 IP 規則失敗');
        }

        return $result;
    }

    public function isIpAllowed(string $ip): bool
    {
        if (!$this->isValidIpOrCidr($ip)) {
            throw new \InvalidArgumentException('無效的 IP 位址格式');
        }

        // 檢查是否在白名單中
        if ($this->repository->isWhitelisted($ip)) {
            return true;
        }

        // 檢查是否在黑名單中
        if ($this->repository->isBlacklisted($ip)) {
            return false;
        }

        // 預設允許存取
        return true;
    }

    private function isValidIpOrCidr(string $ip): bool
    {
        // 驗證一般 IP 位址
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        // 驗證 CIDR 格式
        if (preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}\/([0-9]|[1-2][0-9]|3[0-2])$/', $ip)) {
            return true;
        }

        return false;
    }

    public function getRulesByType(int $type): array
    {
        if (!in_array($type, [0, 1], true)) {
            throw new \InvalidArgumentException('無效的名單類型，必須是 0（黑名單）或 1（白名單）');
        }

        $rules = $this->repository->getByType($type);
        return array_filter($rules, fn($rule) => $rule instanceof IpList);
    }
}
