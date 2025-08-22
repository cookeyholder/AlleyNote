<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\IpRepositoryInterface;
use App\Models\IpList;
use App\DTOs\IpManagement\CreateIpRuleDTO;

class IpService
{
    public function __construct(
        private IpRepositoryInterface $repository
    ) {}

    public function createIpRule(CreateIpRuleDTO $dto): IpList
    {
        // DTO 已經在建構時驗證過資料，這裡直接轉換為陣列
        $data = $dto->toArray();

        // 轉換 action 為內部使用的 type 欄位
        $data['type'] = $data['action'] === 'allow' ? 1 : 0; // 1=白名單，0=黑名單
        unset($data['action']); // 移除 action 欄位

        $result = $this->repository->create($data);
        if (!$result instanceof IpList) {
            throw new \RuntimeException('建立 IP 規則失敗');
        }

        return $result;
    }

    public function isIpAllowed(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
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

    public function getRulesByType(int $type): array
    {
        if (!in_array($type, [0, 1], true)) {
            throw new \InvalidArgumentException('無效的名單類型，必須是 0（黑名單）或 1（白名單）');
        }

        $rules = $this->repository->getByType($type);
        return array_filter($rules, fn($rule) => $rule instanceof IpList);
    }
}
