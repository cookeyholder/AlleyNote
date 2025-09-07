<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Security\DTOs\CreateIpRuleDTO;
use App\Domains\Security\Models\IpList;
use App\Domains\Security\Services\IpService;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\ValidationException;
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
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function create(array $request): array
    {
        try {
            $dto = new CreateIpRuleDTO($this->validator, $request);
            $ipList = $this->service->createIpRule($dto);

            return [
                'status' => 201,
                'data' => $ipList->toSafeArray($this->sanitizer),
            ];
        } catch (ValidationException $e) {
            return [
                'status' => 400,
                'error' => $e->getMessage(),
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'status' => 400,
                'error' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            return [
                'status' => 500,
                'error' => '建立 IP 規則時發生錯誤',
            ];
        }
    }

    /**
     * 根據類型取得IP規則.
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function getByType(array $request): array
    {
        try {
            if (!isset($request['type'])) {
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
        } catch (InvalidArgumentException $e) {
            return [
                'status' => 400,
                'error' => $e->getMessage(),
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
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function checkAccess(array $request): array
    {
        try {
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
        } catch (InvalidArgumentException $e) {
            return [
                'status' => 400,
                'error' => $e->getMessage(),
            ];
        } catch (Exception $e) {
            return [
                'status' => 500,
                'error' => '檢查 IP 存取權限時發生錯誤',
            ];
        }
    }
}
