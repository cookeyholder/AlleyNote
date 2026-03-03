<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\DTOs\BaseDTO;
use App\Shared\Exceptions\ValidationException;

/**
 * 密碼重設 DTO.
 */
final class ResetPasswordDTO extends BaseDTO
{
    public readonly string $token;

    public readonly string $password;

    public readonly string $passwordConfirmation;

    /**
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    public function __construct(ValidatorInterface $validator, array $data)
    {
        parent::__construct($validator);

        $validated = $this->validate($data);

        if (!isset($validated['token']) || !is_string($validated['token'])) {
            throw ValidationException::fromSingleError('token', '無效的重設憑證');
        }

        if (!isset($validated['password']) || !is_string($validated['password'])) {
            throw ValidationException::fromSingleError('password', '無效的密碼格式');
        }

        if (!isset($validated['password_confirmation']) || !is_string($validated['password_confirmation'])) {
            throw ValidationException::fromSingleError('password_confirmation', '無效的密碼確認格式');
        }

        $this->token = trim($validated['token']);
        $this->password = $validated['password'];
        $this->passwordConfirmation = $validated['password_confirmation'];
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'password' => '[REDACTED]',
        ];
    }

    protected function getValidationRules(): array
    {
        return [
            'token' => 'required|string|min:20',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|same:password',
        ];
    }
}
