<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application\Controllers\Api\V1\AuthController;
use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\Exceptions\UnauthorizedException;
use App\Domains\Auth\Services\AuthService;
use App\Domains\Auth\Services\UserManagementService;
use App\Domains\Auth\ValueObjects\TokenPair;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Infrastructure\Http\Response;
use App\Shared\Config\EnvironmentConfig;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Support\IntegrationTestCase;

class AuthControllerTest extends IntegrationTestCase
{
    private AuthService|MockInterface $authService;

    private AuthenticationServiceInterface|MockInterface $authenticationService;

    private JwtTokenServiceInterface|MockInterface $jwtTokenService;

    private ValidatorInterface|MockInterface $validator;

    /** @var mixed */
    private $activityLoggingService;

    private UserRepositoryInterface|MockInterface $userRepository;

    private UserManagementService|MockInterface $userManagementService;

    private ServerRequestInterface|MockInterface $request;

    private ResponseInterface $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authService = Mockery::mock(AuthService::class);
        $this->authenticationService = Mockery::mock(AuthenticationServiceInterface::class);
        $this->jwtTokenService = Mockery::mock(JwtTokenServiceInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->activityLoggingService = Mockery::mock(ActivityLoggingServiceInterface::class)->shouldIgnoreMissing();
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->userManagementService = Mockery::mock(UserManagementService::class);

        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = new Response();

        // 預設行為
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1'])->byDefault();
        $this->request->shouldReceive('getParsedBody')->andReturn([])->byDefault();
        $this->request->shouldReceive('getHeaderLine')->andReturn('')->byDefault();
        $this->request->shouldReceive('getAttribute')->andReturn(null)->byDefault();
        $this->request->shouldReceive('getCookieParams')->andReturn([])->byDefault();
        $this->validator->shouldReceive('addRule')->zeroOrMoreTimes()->andReturnSelf();
        $this->validator->shouldReceive('addMessage')->zeroOrMoreTimes()->andReturnSelf();
    }

    private function getValidTestJwt(): string
    {
        return 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';
    }

    #[Test]
    public function registerUserSuccessfully(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'confirm_password' => 'Password123!',
            'user_ip' => '127.0.0.1',
        ];

        $this->request->shouldReceive('getParsedBody')->andReturn($userData);
        $this->validator->shouldReceive('validateOrFail')->andReturn($userData);

        $this->authService->shouldReceive('register')
            ->once()
            ->with(Mockery::any())
            ->andReturn([
                'id' => 1,
                'username' => 'testuser',
                'email' => 'test@example.com',
                'status' => 1,
            ]);

        $config = new EnvironmentConfig();

        /** @var AuthService $authService */
        $authService = $this->authService;
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

        // 建立控制器並執行
        $controller = new AuthController($authService, $authenticationService, $jwtTokenService, $validator, $activityLoggingService, $userRepository, $userManagementService, $config);
        $response = $controller->register($this->request, $this->response);

        // 驗證回應
        $this->assertEquals(201, $response->getStatusCode());
    }

    #[Test]
    public function returnValidationErrorsForInvalidRegistrationData(): void
    {
        $invalidData = ['username' => ''];
        $this->request->shouldReceive('getParsedBody')->andReturn($invalidData);

        $this->validator->shouldReceive('validateOrFail')
            ->andThrow(new ValidationException(new ValidationResult(false, ['username' => ['Required']])));

        $config = new EnvironmentConfig();

        /** @var AuthService $authService */
        $authService = $this->authService;
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

        // 建立控制器並執行
        $controller = new AuthController($authService, $authenticationService, $jwtTokenService, $validator, $activityLoggingService, $userRepository, $userManagementService, $config);
        $response = $controller->register($this->request, $this->response);

        $this->assertEquals(422, $response->getStatusCode());
    }

    #[Test]
    public function loginUserSuccessfully(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'Password123!'];
        $this->request->shouldReceive('getParsedBody')->andReturn($credentials);
        $this->validator->shouldReceive('validateOrFail')->andReturn($credentials);

        $jwt = $this->getValidTestJwt();
        $tokens = new TokenPair($jwt, $jwt, new DateTimeImmutable('+1 hour'), new DateTimeImmutable('+30 days'));
        
        $loginResponse = new \App\Domains\Auth\DTOs\LoginResponseDTO(
            tokens: $tokens,
            userId: 1,
            userEmail: 'test@example.com',
            expiresAt: $tokens->getAccessTokenExpiresAt()->getTimestamp(),
            userName: 'test',
            roles: [],
            permissions: []
        );

        $this->authenticationService->shouldReceive('login')
            ->once()
            ->with(Mockery::any(), Mockery::any())
            ->andReturn($loginResponse);

        $config = new EnvironmentConfig();

        /** @var AuthService $authService */
        $authService = $this->authService;
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

        // 建立控制器並執行
        $controller = new AuthController($authService, $authenticationService, $jwtTokenService, $validator, $activityLoggingService, $userRepository, $userManagementService, $config);
        $response = $controller->login($this->request, $this->response);

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function returnErrorForInvalidLogin(): void
    {
        $credentials = ['email' => 'wrong@example.com', 'password' => 'wrong'];
        $this->request->shouldReceive('getParsedBody')->andReturn($credentials);
        
        $this->authenticationService->shouldReceive('login')
            ->once()
            ->with(Mockery::any(), Mockery::any())
            ->andThrow(new UnauthorizedException('Invalid credentials'));

        $config = new EnvironmentConfig();

        /** @var AuthService $authService */
        $authService = $this->authService;
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

        // 建立控制器並執行
        $controller = new AuthController($authService, $authenticationService, $jwtTokenService, $validator, $activityLoggingService, $userRepository, $userManagementService, $config);
        $response = $controller->login($this->request, $this->response);

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function logoutUserSuccessfully(): void
    {
        $this->authenticationService->shouldReceive('logout')->once()->with(Mockery::any());
        
        $config = new EnvironmentConfig();

        /** @var AuthService $authService */
        $authService = $this->authService;
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

        // 建立控制器並執行
        $controller = new AuthController($authService, $authenticationService, $jwtTokenService, $validator, $activityLoggingService, $userRepository, $userManagementService, $config);
        $response = $controller->logout($this->request, $this->response);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
