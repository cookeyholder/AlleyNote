<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\DTOs\BaseDTO;
use App\Shared\Exceptions\ValidationException;

/**
 * 忘記密碼請求 DTO.
 */
final class ForgotPasswordRequestDTO extends BaseDTO
{
    public readonly string $email;

    /**
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    public function __construct(ValidatorInterface $validator, array $data)
    {
        parent::__construct($validator);

        $validated = $this->validate($data);

        if (!isset($validated['email']) || !is_string($validated['email'])) {
            throw ValidationException::fromSingleError('email', '無效的電子郵件格式');
        }

        $this->email = strtolower(trim($validated['email']));
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
        ];
    }

    protected function getValidationRules(): array
    {
        return [
            'email' => 'required|string|email',
        ];
    }
}
