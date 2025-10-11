<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Application\Controllers\BaseController;
use App\Shared\Services\PasswordValidationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 密碼驗證控制器.
 */
class PasswordValidationController extends BaseController
{
    public function __construct(
        private readonly PasswordValidationService $validationService,
    ) {}

    /**
     * 驗證密碼強度.
     *
     * POST /api/auth/validate-password
     */
    public function validate(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (!is_array($data)) {
            $data = [];
        }

        /** @var string $password */
        $password = isset($data['password']) && is_string($data['password']) ? $data['password'] : '';
        /** @var string|null $username */
        $username = isset($data['username']) && is_string($data['username']) ? $data['username'] : null;
        /** @var string|null $email */
        $email = isset($data['email']) && is_string($data['email']) ? $data['email'] : null;

        $result = $this->validationService->validate($password, $username, $email);

        return $this->json($response, $result);
    }
}
