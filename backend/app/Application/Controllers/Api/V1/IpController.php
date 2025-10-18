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
     * @param array<string, mixed> $request
     */
    public function create(array $request): array
    {
        try {
            $payload = $this->filterStringKeys($request);
            $dto = new CreateIpRuleDTO($this->validator, $payload);
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
     * @param array<string, mixed> $request
     */
    public function getByType(array $request): array
    {
        try {
            $payload = $this->filterStringKeys($request);
            $type = $this->toIntOrNull($payload['type'] ?? null);
            if ($type === null) {
                throw new InvalidArgumentException('必須指定名單類型');
            }

            $rules = $this->service->getRulesByType($type);

            return [
                'status' => 200,
                'data' => array_map(
                    fn(IpList $rule) => $rule->toSafeArray($this->sanitizer),
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
     * @param array<string, mixed> $request
     */
    public function checkAccess(array $request): array
    {
        try {
            $payload = $this->filterStringKeys($request);
            $ip = $this->toStringOrNull($payload['ip'] ?? null);
            if ($ip === null) {
                throw new InvalidArgumentException('必須提供 IP 位址');
            }

            $isAllowed = $this->service->isIpAllowed($ip);

            return [
                'status' => 200,
                'data' => [
                    'ip' => $ip,
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

    /**
     * @param array<mixed, mixed> $input
     * @return array<string, mixed>
     */
    private function filterStringKeys(array $input): array
    {
        $filtered = [];

        foreach ($input as $key => $value) {
            if (is_string($key)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    private function toIntOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function toStringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }
}
