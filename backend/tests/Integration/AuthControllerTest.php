<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application\Controllers\Api\V1\AuthController;
use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\Services\AuthService;
use App\Domains\Auth\Services\UserManagementService;
use App\Domains\Auth\ValueObjects\TokenPair;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Shared\Config\EnvironmentConfig;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;
use DateTimeImmutable;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\IntegrationTestCase;

class AuthControllerTest extends IntegrationTestCase
{
    private AuthService|MockInterface $authService;

    private AuthenticationServiceInterface|MockInterface $authenticationService;

    private JwtTokenServiceInterface|MockInterface $jwtTokenService;

    private ValidatorInterface|MockInterface $validator;

    private ActivityLoggingServiceInterface|MockInterface $activityLoggingService;

    private UserRepositoryInterface|MockInterface $userRepository;

    private UserManagementService|MockInterface $userManagementService;

    private ServerRequestInterface|MockInterface $request;

    private ResponseInterface|MockInterface $response;

    private StreamInterface|MockInterface $stream;

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
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->stream = Mockery::mock(StreamInterface::class);

        // 預設行為
        $this->request->shouldReceive('getParsedBody')->andReturn([])->byDefault();
        $this->request->shouldReceive('getHeaderLine')->andReturn('')->byDefault();
        $this->response->shouldReceive('withHeader')->andReturnSelf()->byDefault();
        $this->response->shouldReceive('withStatus')->andReturnSelf()->byDefault();
        $this->response->shouldReceive('getBody')->andReturn($this->stream)->byDefault();
        $this->stream->shouldReceive('write')->andReturn(0)->byDefault();
        $this->response->shouldReceive('getStatusCode')->andReturn(200)->byDefault();
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
        ];

        $this->request->shouldReceive('getParsedBody')->andReturn($userData);
        $this->validator->shouldReceive('validateOrFail')->andReturn($userData);

        $this->authenticationService->shouldReceive('register')
            ->once()
            ->andReturn([
                'id' => 1,
                'username' => 'testuser',
                'email' => 'test@example.com',
                'status' => 1,
            ]);

        $this->response->shouldReceive('withStatus')->with(201)->andReturnSelf();
        $this->response->shouldReceive('getStatusCode')->andReturn(201);

        $config = new EnvironmentConfig();

        $authService = $this->authService;
        $authenticationService = $this->authenticationService;
        $jwtTokenService = $this->jwtTokenService;
        $validator = $this->validator;
        $activityLoggingService = $this->activityLoggingService;
        $userRepository = $this->userRepository;
        $userManagementService = $this->userManagementService;

        assert($authService instanceof AuthService);
        assert($authenticationService instanceof AuthenticationServiceInterface);
        assert($jwtTokenService instanceof JwtTokenServiceInterface);
        assert($validator instanceof ValidatorInterface);
        assert($activityLoggingService instanceof ActivityLoggingServiceInterface);
        assert($userRepository instanceof UserRepositoryInterface);
        assert($userManagementService instanceof UserManagementService);

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

        $this->response->shouldReceive('withStatus')->with(400)->andReturnSelf();
        $this->response->shouldReceive('getStatusCode')->andReturn(400);

        $config = new EnvironmentConfig();

        $authService = $this->authService;
        $authenticationService = $this->authenticationService;
        $jwtTokenService = $this->jwtTokenService;
        $validator = $this->validator;
        $activityLoggingService = $this->activityLoggingService;
        $userRepository = $this->userRepository;
        $userManagementService = $this->userManagementService;

        assert($authService instanceof AuthService);
        assert($authenticationService instanceof AuthenticationServiceInterface);
        assert($jwtTokenService instanceof JwtTokenServiceInterface);
        assert($validator instanceof ValidatorInterface);
        assert($activityLoggingService instanceof ActivityLoggingServiceInterface);
        assert($userRepository instanceof UserRepositoryInterface);
        assert($userManagementService instanceof UserManagementService);

        // 建立控制器並執行
        $controller = new AuthController($authService, $authenticationService, $jwtTokenService, $validator, $activityLoggingService, $userRepository, $userManagementService, $config);
        $response = $controller->register($this->request, $this->response);

        $this->assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function loginUserSuccessfully(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'Password123!'];
        $this->request->shouldReceive('getParsedBody')->andReturn($credentials);
        $this->validator->shouldReceive('validateOrFail')->andReturn($credentials);

        $jwt = $this->getValidTestJwt();
        $tokens = new TokenPair($jwt, $jwt, new DateTimeImmutable('+1 hour'), new DateTimeImmutable('+30 days'));
        $this->authenticationService->shouldReceive('login')->once()->andReturn($tokens);
        $this->authenticationService->shouldReceive('getUserByEmail')->andReturn([
            'id' => 1, 'username' => 'test', 'email' => 'test@example.com',
        ]);

        $config = new EnvironmentConfig();

        $authService = $this->authService;
        $authenticationService = $this->authenticationService;
        $jwtTokenService = $this->jwtTokenService;
        $validator = $this->validator;
        $activityLoggingService = $this->activityLoggingService;
        $userRepository = $this->userRepository;
        $userManagementService = $this->userManagementService;

        assert($authService instanceof AuthService);
        assert($authenticationService instanceof AuthenticationServiceInterface);
        assert($jwtTokenService instanceof JwtTokenServiceInterface);
        assert($validator instanceof ValidatorInterface);
        assert($activityLoggingService instanceof ActivityLoggingServiceInterface);
        assert($userRepository instanceof UserRepositoryInterface);
        assert($userManagementService instanceof UserManagementService);

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
        $this->validator->shouldReceive('validateOrFail')->andReturn($credentials);

        $this->authenticationService->shouldReceive('login')->once()->andThrow(new InvalidArgumentException('Invalid credentials'));

        $this->response->shouldReceive('withStatus')->with(401)->andReturnSelf();
        $this->response->shouldReceive('getStatusCode')->andReturn(401);

        $config = new EnvironmentConfig();

        $authService = $this->authService;
        $authenticationService = $this->authenticationService;
        $jwtTokenService = $this->jwtTokenService;
        $validator = $this->validator;
        $activityLoggingService = $this->activityLoggingService;
        $userRepository = $this->userRepository;
        $userManagementService = $this->userManagementService;

        assert($authService instanceof AuthService);
        assert($authenticationService instanceof AuthenticationServiceInterface);
        assert($jwtTokenService instanceof JwtTokenServiceInterface);
        assert($validator instanceof ValidatorInterface);
        assert($activityLoggingService instanceof ActivityLoggingServiceInterface);
        assert($userRepository instanceof UserRepositoryInterface);
        assert($userManagementService instanceof UserManagementService);

        // 建立控制器並執行
        $controller = new AuthController($authService, $authenticationService, $jwtTokenService, $validator, $activityLoggingService, $userRepository, $userManagementService, $config);
        $response = $controller->login($this->request, $this->response);

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function logoutUserSuccessfully(): void
    {
        $config = new EnvironmentConfig();

        $authService = $this->authService;
        $authenticationService = $this->authenticationService;
        $jwtTokenService = $this->jwtTokenService;
        $validator = $this->validator;
        $activityLoggingService = $this->activityLoggingService;
        $userRepository = $this->userRepository;
        $userManagementService = $this->userManagementService;

        assert($authService instanceof AuthService);
        assert($authenticationService instanceof AuthenticationServiceInterface);
        assert($jwtTokenService instanceof JwtTokenServiceInterface);
        assert($validator instanceof ValidatorInterface);
        assert($activityLoggingService instanceof ActivityLoggingServiceInterface);
        assert($userRepository instanceof UserRepositoryInterface);
        assert($userManagementService instanceof UserManagementService);

        // 建立控制器並執行
        $controller = new AuthController($authService, $authenticationService, $jwtTokenService, $validator, $activityLoggingService, $userRepository, $userManagementService, $config);
        $response = $controller->logout($this->request, $this->response);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
