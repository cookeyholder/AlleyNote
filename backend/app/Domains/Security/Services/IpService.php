<?php

declare(strict_types=1);

namespace App\Domains\Security\Services;

use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\IpRepositoryInterface;
use App\Domains\Security\Contracts\IpServiceInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\DTOs\CreateIpRuleDTO;
use App\Domains\Security\Enums\ActivityType;
use App\Domains\Security\Models\IpList;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class IpService implements IpServiceInterface
{
    public function __construct(
        private IpRepositoryInterface $repository,
        private ActivityLoggingServiceInterface $activityLogger,
    ) {}

    public function createIpRule(CreateIpRuleDTO $dto): IpList
    {
        // DTO 已經在建構時驗證過資料，這裡直接轉換為陣列
        $data = $dto->toArray();

        // 轉換 action 為內部使用的 type 欄位
        $isBlocked = $data['action'] === 'block';
        $data['type'] = $data['action'] === 'allow' ? 1 : 0; // 1=白名單，0=黑名單
        unset($data['action']); // 移除 action 欄位

        $result = $this->repository->create($data);
        if (!$result instanceof IpList) {
            throw new RuntimeException('建立 IP 規則失敗');
        }

        // 記錄 IP 封鎖/解封事件
        $this->logIpRuleEvent($result, $isBlocked);

        return $result;
    }

    public function isIpAllowed(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('無效的 IP 位址格式');
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
            throw new InvalidArgumentException('無效的名單類型，必須是 0（黑名單）或 1（白名單）');
        }

        return $this->repository->getByType($type);
    }

    /**
     * 記錄 IP 規則事件.
     */
    private function logIpRuleEvent(IpList $ipRule, bool $isBlocked): void
    {
        try {
            $activityType = $isBlocked ? ActivityType::IP_BLOCKED : ActivityType::IP_UNBLOCKED;
            $description = $isBlocked
                ? "IP 位址已被封鎖: {$ipRule->getIpAddress()}"
                : "IP 位址已被加入白名單: {$ipRule->getIpAddress()}";

            $dto = CreateActivityLogDTO::securityEvent(
                actionType: $activityType,
                description: $description,
                metadata: [
                    'ip_rule_id' => $ipRule->getId(),
                    'ip_address' => $ipRule->getIpAddress(),
                    'rule_type' => $isBlocked ? 'blacklist' : 'whitelist',
                    'created_at' => $ipRule->getCreatedAt(),
                ],
            );

            $this->activityLogger->log($dto);
        } catch (Exception $e) {
            // 記錄失敗不應影響主要業務邏輯，靜默處理
            error_log('Failed to log IP rule event: ' . $e->getMessage());
        }
    }
}
