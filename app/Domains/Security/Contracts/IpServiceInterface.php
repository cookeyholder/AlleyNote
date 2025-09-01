<?php

declare(strict_types=1);

namespace App\Domains\Security\Contracts;

use App\Domains\Security\DTOs\CreateIpRuleDTO;
use App\Domains\Security\Models\IpList;

interface IpServiceInterface
{
    /**
     * 建立 IP 規則.
     *
     * @param CreateIpRuleDTO $dto IP 規則資料傳輸物件
     * @return IpList 建立的 IP 規則實體
     */
    public function createIpRule(CreateIpRuleDTO $dto): IpList;

    /**
     * 檢查 IP 是否被允許存取.
     *
     * @param string $ip 要檢查的 IP 位址
     * @return bool true 如果 IP 被允許存取，否則 false
     */
    public function isIpAllowed(string $ip): bool;

    /**
     * 根據類型取得 IP 規則清單.
     *
     * @param int $type 規則類型
     * @return array<IpList> IP 規則陣列
     */
    public function getRulesByType(int $type): array;
}
