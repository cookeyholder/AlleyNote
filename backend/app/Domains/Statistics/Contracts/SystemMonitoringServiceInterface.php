<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

/**
 * 主機與系統狀態監控服務契約.
 */
interface SystemMonitoringServiceInterface
{
    /**
     * 取得主機與系統詳細健康與監控數據.
     *
     * @return array<string, mixed>
     */
    public function getSystemHealthStatus(): array;
}
