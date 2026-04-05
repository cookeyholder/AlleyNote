<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application\Controllers\Api\V1\AuthController;
use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\DTOs\LoginResponseDTO;
use App\Domains\Auth\Exceptions\UnauthorizedException;
use App\Domains\Auth\Services\UserManagementService;
use App\Domains\Auth\ValueObjects\TokenPair;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Shared\Config\EnvironmentConfig;
use App\Shared\Contracts\ValidatorInterface;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ApiTestCase;

class AuthControllerTest extends ApiTestCase
{
    private AuthenticationServiceInterface|MockInterface $authenticationService;

    private JwtTokenServiceInterface|MockInterface $jwtTokenService;

    private ValidatorInterface|MockInterface $validator;

    /** @var mixed */
    private $activityLoggingService;

    private UserRepositoryInterface|MockInterface $userRepository;

    private UserManagementService|MockInterface $userManagementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticationService = Mockery::mock(AuthenticationServiceInterface::class);
        $this->jwtTokenService = Mockery::mock(JwtTokenServiceInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->activityLoggingService = Mockery::mock(ActivityLoggingServiceInterface::class)->shouldIgnoreMissing();
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->userManagementService = Mockery::mock(UserManagementService::class);
        $this->validator->shouldReceive('addRule')->zeroOrMoreTimes()->andReturnSelf();
        $this->validator->shouldReceive('addMessage')->zeroOrMoreTimes()->andReturnSelf();
        $this->validator->shouldReceive('validateOrFail')->zeroOrMoreTimes()->andReturnUsing(
            static fn(array $input): array => $input,
        );
    }

    private function controller(): AuthController
    {
        /** @var AuthenticationServiceInterface $authenticationService */
        $authenticationService = $this->authenticationService;
        /** @var JwtTokenServiceInterface $jwtTokenService */
        $jwtTokenService = $this->jwtTokenService;
        /** @var ValidatorInterface $validator */
        $validator = $this->validator;
        /** @var ActivityLoggingServiceInterface $activityLoggingService */
        $activityLoggingService = $this->activityLoggingService;
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $this->userRepository;
        /** @var UserManagementService $userManagementService */
        $userManagementService = $this->userManagementService;

        return new AuthController(
            $authenticationService,
            $jwtTokenService,
            $validator,
            $activityLoggingService,
            $userRepository,
            $userManagementService,
            new EnvironmentConfig(),
        );
    }

    #[Test]
    public function registerUserSuccessfully(): void
    {
        $request = $this->json('POST', '/api/auth/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'confirm_password' => 'Password123!',
            'user_ip' => '127.0.0.1',
        ]);

        $this->userManagementService->shouldReceive('createUser')->once()->andReturn([
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'status' => 1,
        ]);

        $response = $this->controller()->register($request, $this->createApiResponse());
        $this->assertSame(201, $response->getStatusCode());
    }

    #[Test]
    public function loginUserSuccessfully(): void
    {
        $request = $this->json('POST', '/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $jwt = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIn0.signature';
        $tokens = new TokenPair($jwt, $jwt, new DateTimeImmutable('+1 hour'), new DateTimeImmutable('+30 days'));
        $loginResponse = new LoginResponseDTO(
            tokens: $tokens,
            userId: 1,
            userEmail: 'test@example.com',
            expiresAt: $tokens->getAccessTokenExpiresAt()->getTimestamp(),
            userName: 'test',
            roles: [],
            permissions: [],
        );

        $this->authenticationService
            ->shouldReceive('login')
            ->once()
            ->andReturn($loginResponse);

        $response = $this->controller()->login($request, $this->createApiResponse());
        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function loginShouldReturnUnauthorizedForInvalidCredentials(): void
    {
        $request = $this->json('POST', '/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrong',
        ]);

        $this->authenticationService
            ->shouldReceive('login')
            ->once()
            ->andThrow(new UnauthorizedException('Invalid credentials'));

        $response = $this->controller()->login($request, $this->createApiResponse());
        $this->assertSame(401, $response->getStatusCode());
    }

    #[Test]
    public function logoutUserSuccessfully(): void
    {
        $request = $this
            ->actingAs(['id' => 1, 'email' => 'test@example.com'])
            ->json('POST', '/api/auth/logout', [
                'refresh_token' => 'dummy',
                'logout_all_devices' => false,
            ])
            ->withAttribute('user_id', 1)
            ->withAttribute('access_token', 'access-token');

        $this->authenticationService->shouldReceive('logout')->once()->with(Mockery::any());

        $response = $this->controller()->logout($request, $this->createApiResponse());
        $this->assertSame(200, $response->getStatusCode());
    }
}
