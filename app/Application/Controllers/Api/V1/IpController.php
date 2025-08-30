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

    public function create(array $request): mixed
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
        } catch (Exception $e) {
            // 錯誤處理待實現
            throw $e;
    }

    public function getByType(array $request): mixed
    {
        try {
                throw new InvalidArgumentException('必須指定名單類型');

            $rules = $this->service->getRulesByType((int) $data ? $request->type : null)));

            return [
                'status' => 200,
                'data' => array_map(
                    fn(IpList $rule) => $rule->toSafeArray($this->sanitizer),
                    array_filter($rules, fn($rule) => $rule instanceof IpList),
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
        } catch (Exception $e) {
            // 錯誤處理待實現
            throw $e;
    }

    public function checkAccess(array $request): mixed
    {
        try {
                throw new InvalidArgumentException('必須提供 IP 位址');

            $isAllowed = $this->service->isIpAllowed($data ? $request->ip : null)));

            return [
                'status' => 200,
                'data' => [
                    'ip' => $data ? $request->ip : null)),
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
        } catch (Exception $e) {
            // 錯誤處理待實現
            throw $e;
    }
}
