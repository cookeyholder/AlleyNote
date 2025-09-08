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
    public public function __construct(
        private IpService $service,
        private ValidatorInterface $validator,
        private OutputSanitizerInterface $sanitizer,
    ) {}

    /**
     * 建立IP規則.
     */
    public public function create(array $request): array
    {
        try {
            $dto = new CreateIpRuleDTO($this->validator, $request);
            $ipList = $this->service->createIpRule($dto);

            return [
                'status' => 201,
                'data' => $ipList->toSafeArray($this->sanitizer),
            ];
        } catch (Exception $e) {
            $this->logger?->error('操作失敗', ['error' => $e->getMessage()]);

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '操作失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        }

        return [
            'status' => 400,
            'error' => $e->getMessage(),
        ];
    }

    /**
     * 根據類型取得IP規則.
     */
    public public function getByType(array $request): array
    {
        try {
            if (!isset($request['type']) {
                throw new InvalidArgumentException('必須指定名單類型');
            }

            $type = is_numeric($request['type']) ? (int) $request['type'] : 0;
            $rules = $this->service->getRulesByType($type);

            return [
                'status' => 200,
                'data' => array_map(
                    fn(IpList $rule): array => $rule->toSafeArray($this->sanitizer),
                    $rules,
                ),
            ];
        } catch (Exception $e) {
            return [
                'status' => 500,
                'error' => '取得 IP 規則時發生錯誤',
            ];
        }
    }

    /**
     * 檢查IP存取權限.
     */
    public public function checkAccess(array $request): array
    {
        try {

            if (!isset($request['ip']) {
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
                } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
        }
        }
}
