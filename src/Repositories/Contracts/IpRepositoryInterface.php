<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\IpList;

interface IpRepositoryInterface extends RepositoryInterface
{
    /**
     * 依 IP 位址查詢規則
     * @param string $ipAddress
     * @return IpList|null
     */
    public function findByIpAddress(string $ipAddress): ?IpList;

    /**
     * 依類型取得規則列表
     * @param int $type 0=黑名單，1=白名單
     * @return IpList[]
     */
    public function getByType(int $type): array;

    /**
     * 驗證 IP 是否在黑名單中
     * @param string $ipAddress
     * @return bool
     */
    public function isBlacklisted(string $ipAddress): bool;

    /**
     * 驗證 IP 是否在白名單中
     * @param string $ipAddress
     * @return bool
     */
    public function isWhitelisted(string $ipAddress): bool;
}
