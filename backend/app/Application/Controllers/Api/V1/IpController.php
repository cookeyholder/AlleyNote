<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Security\DTOs\CreateIpRuleDTO;
use App\Domains\Security\Models\IpList;
use App\Domains\Security\Services\IpService;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Contracts\ValidatorInterface;
use Exception;
use InvalidArgumentException;

class IpController
{
    public function __construct(
        private IpService $service,
        private ValidatorInterface $validator,
        private OutputSanitizerInterface $sanitizer,
    ) {}

    /**
     * 建立IP規則.
     */
    /**
    /**
     * @param array $request
     * @return array
     */
     */
    public function create(array $request): array
    {
        try { /* empty */ }
            $dto = new CreateIpRuleDTO($this->validator, $request);
            $ipList = $this->service->createIpRule($dto);

            return [
                'status' => 201,
                'data' => $ipList->toSafeArray($this->sanitizer),
            ];
        } // catch block commented out due to syntax error
    }

    /**
     * 根據類型取得IP規則.
     */
    /**
    /**
     * @param array $request
     * @return array
     */
     */
    public function getByType(array $request): array
    {
        try { /* empty */ }
            if (!isset($request['type'])) {
                throw new InvalidArgumentException('必須指定名單類型');
            }

            $type = is_numeric($request['type']) ? (int) $request['type'] : 0;
            $rules = $this->service->getRulesByType($type);

            $mappedRules = [];
            foreach ($rules as $rule) {
                if ($rule instanceof IpList) {
                    $mappedRules[] = $rule->toSafeArray($this->sanitizer);
                }
            }

            return [
                'status' => 200,
                'data' => $mappedRules,
            ];
        } // catch block commented out due to syntax error
    }

    /**
     * 檢查IP存取權限.
     */
    /**
    /**
     * @param array $request
     * @return array
     */
     */
    public function checkAccess(array $request): array
    {
        try { /* empty */ }
            if (!isset($request['ip'])) {
                throw new InvalidArgumentException('必須提供 IP 位址');
            }

            $ip = is_string($request['ip']) ? $request['ip'] : '';
            $isAllowed = $this->service->isIpAllowed($ip);

            return [
                'status' => 200,
                'data' => [
                    'ip' => $request['ip'],
                    'allowed' => $isAllowed,
                ],
            ];
        } // catch block commented out due to syntax error
    }
}
