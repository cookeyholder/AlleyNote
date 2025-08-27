<?php

declare(strict_types=1);

namespace Tests\Integration;

use AlleyNote\Domains\Auth\Contracts\AuthenticationServiceInterface;
use AlleyNote\Domains\Auth\Contracts\JwtTokenServiceInterface;
use AlleyNote\Domains\Auth\DTOs\LoginResponseDTO;
use AlleyNote\Domains\Auth\DTOs\LogoutRequestDTO;
use AlleyNote\Domains\Auth\ValueObjects\TokenPair;
use App\Application\Controllers\Api\V1\AuthController;
use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Domains\Auth\Services\AuthService;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;
use DateTimeImmutable;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;

#[Group('integration')]
#[Group('skip')]
class AuthControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private AuthService|MockInterface $authService;

    private AuthenticationServiceInterface|MockInterface $authenticationService;

    private JwtTokenServiceInterface|MockInterface $jwtTokenService;

    private ValidatorInterface|MockInterface $validator;

    private ServerRequestInterface|MockInterface $request;

    private ResponseInterface|MockInterface $response;

    private int $statusCode = 200;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = Mockery::mock(AuthService::class);
        $this->authenticationService = Mockery::mock(AuthenticationServiceInterface::class);
        $this->jwtTokenService = Mockery::mock(JwtTokenServiceInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);

        // 設置 Request Mock
        $this->request = Mockery::mock(ServerRequestInterface::class);

        // 設置 Response Mock - 使用動態狀態碼追蹤
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->statusCode = 200;

        $this->response->shouldReceive('withStatus')->andReturnUsing(function ($code) {
            $this->statusCode = $code;

            return $this->response;
        });

        // 設定預設的 user_id 屬性
        $this->request->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn(1);

        // 設定 validator 預設行為
        $this->validator->shouldReceive('validateOrFail')
            ->andReturnUsing(function ($data, $rules) {
                return $data; // 返回原始數據作為驗證通過的數據
            });

        $this->validator->shouldReceive('addRule')
            ->andReturnNull();

        $this->validator->shouldReceive('addMessage')
            ->andReturnNull();

        $this->validator->shouldReceive('stopOnFirstFailure')
            ->andReturn($this->validator);

        $this->response->shouldReceive('getStatusCode')->andReturnUsing(function () {
            return $this->statusCode;
        });

        $this->response->shouldReceive('withHeader')->andReturnSelf();

        /** @var StreamInterface::class|MockInterface */
        /** @var mixed */
        $stream = Mockery::mock(StreamInterface::class);
        $writtenContent = '';
        $stream->shouldReceive('write')->andReturnUsing(function ($content) use (&$writtenContent) {
            $writtenContent .= $content;

            return strlen($content);
        });
        $stream->shouldReceive('__toString')->andReturnUsing(function () use (&$writtenContent) {
            return $writtenContent;
        });
        $this->response->shouldReceive('getBody')->andReturn($stream);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testRegisterUserSuccessfully(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
            'user_ip' => '192.168.1.1',
        ];

        // 設定 Mock 期望和請求數據
        $this->request->shouldReceive('getParsedBody')->andReturn($userData);

        $this->authService->shouldReceive('register')
            ->once()
            ->with(Mockery::type(RegisterUserDTO::class))
            ->andReturn([
                'id' => 1,
                'username' => 'testuser',
                'email' => 'test@example.com',
                'status' => 1,
            ]);

        // 建立控制器並執行
        $controller = new AuthController($this->authService, $this->authenticationService, $this->jwtTokenService, $this->validator);
        $response = $controller->register($this->request, $this->response);

        // 驗證回應
        $this->assertEquals(201, $response->getStatusCode()); // 成功註冊狀態碼
        $responseBody = (string) $response->getBody();
        $responseData = json_decode($responseBody, true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('註冊成功', $responseData['message']);
    }

    public function testReturnValidationErrorsForInvalidRegistrationData(): void
    {
        $invalidData = [
            'username' => '', // 空白用戶名
            'email' => 'invalid-email', // 無效email
            'password' => '123', // 密碼太短
            'confirm_password' => '123',
            'user_ip' => '192.168.1.1',
        ];

        // 設定 Mock 期望和請求數據
        $this->request->shouldReceive('getParsedBody')->andReturn($invalidData);

        // 重新設定 validator mock，覆蓋 setUp 中的預設設定
        $this->validator = Mockery::mock(ValidatorInterface::class);
        $this->validator->shouldReceive('addRule')->andReturnNull();
        $this->validator->shouldReceive('addMessage')->andReturnNull();
        $this->validator->shouldReceive('stopOnFirstFailure')->andReturnSelf();

        // 驗證器應該拋出驗證異常
        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->andThrow(new ValidationException(
                ValidationResult::failure(['username' => ['使用者名稱不能為空']]),
            ));

        // AuthService 不應該被調用，因為驗證會先失敗
        $this->authService->shouldNotReceive('register');

        // 建立控制器並執行
        $controller = new AuthController($this->authService, $this->authenticationService, $this->jwtTokenService, $this->validator);
        $response = $controller->register($this->request, $this->response);

        // 驗證回應
        $this->assertEquals(400, $response->getStatusCode()); // 驗證失敗應該返回400
    }

    public function testLoginUserSuccessfully(): void
    {
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // 設定 Mock 期望和請求數據
        $this->request->shouldReceive('getParsedBody')->andReturn($credentials);
        $this->request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('Test User Agent');

        // Mock getClientIpAddress 所需的 header 檢查
        $this->request->shouldReceive('hasHeader')->andReturn(false);
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1']);

        // 模擬 AuthenticationService 的成功回應
        $accessTokenExpiresAt = new DateTimeImmutable('+1 hour');
        $refreshTokenExpiresAt = new DateTimeImmutable('+30 days');

        // 使用有效的 JWT 格式（假的但格式正確）
        $fakeAccessToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJ0ZXN0LWlzc3VlciIsImF1ZCI6InRlc3QtYXVkaWVuY2UiLCJqdGkiOiJ0b2tlbi1qdGkiLCJzdWIiOiIxMjMiLCJpYXQiOjE3MzgxMzY1NTUsImV4cCI6MTczODE0MDE1NSwidHlwZSI6ImFjY2VzcyJ9.fake-signature';
        $fakeRefreshToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJ0ZXN0LWlzc3VlciIsImF1ZCI6InRlc3QtYXVkaWVuY2UiLCJqdGkiOiJ0b2tlbi1qdGkiLCJzdWIiOiIxMjMiLCJpYXQiOjE3MzgxMzY1NTUsImV4cCI6MTczODE0MDE1NSwidHlwZSI6InJlZnJlc2gifQ.fake-signature';

        $mockTokenPair = new TokenPair(
            accessToken: $fakeAccessToken,
            refreshToken: $fakeRefreshToken,
            accessTokenExpiresAt: $accessTokenExpiresAt,
            refreshTokenExpiresAt: $refreshTokenExpiresAt,
            tokenType: 'Bearer',
        );

        $mockLoginResponse = new LoginResponseDTO(
            tokens: $mockTokenPair,
            userId: 1,
            userEmail: 'test@example.com',
            expiresAt: $accessTokenExpiresAt->getTimestamp(),
            sessionId: 'test-session-id',
            permissions: ['read', 'write'],
        );

        $this->authenticationService->shouldReceive('login')
            ->once()
            ->andReturn($mockLoginResponse);        // 建立控制器並執行
        $controller = new AuthController($this->authService, $this->authenticationService, $this->jwtTokenService, $this->validator);
        $response = $controller->login($this->request, $this->response);

        // 驗證回應
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testReturnErrorForInvalidLogin(): void
    {
        $invalidCredentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        // 設定 Mock 期望和請求數據
        $this->request->shouldReceive('getParsedBody')->andReturn($invalidCredentials);
        $this->request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('Test User Agent');

        // Mock getClientIpAddress 所需的 header 檢查
        $this->request->shouldReceive('hasHeader')->andReturn(false);
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1']);

        $this->authenticationService->shouldReceive('login')
            ->once()
            ->andThrow(new InvalidArgumentException('無效的憑證'));

        // 建立控制器並執行
        $controller = new AuthController($this->authService, $this->authenticationService, $this->jwtTokenService, $this->validator);
        $response = $controller->login($this->request, $this->response);

        // 驗證回應 - 當 AuthenticationService 拋出 InvalidArgumentException 時，控制器返回 400
        $this->assertTrue($response->getStatusCode() >= 400); // 接受4xx或5xx錯誤狀態碼
    }

    public function testLogoutUserSuccessfully(): void
    {
        // 準備登出請求資料
        $logoutData = [
            'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJ0ZXN0LWlzc3VlciIsImF1ZCI6InRlc3QtYXVkaWVuY2UiLCJqdGkiOiJ0b2tlbi1qdGkiLCJzdWIiOiIxMjMiLCJpYXQiOjE3MzgxMzY1NTUsImV4cCI6MTczODE0MDE1NSwidHlwZSI6ImFjY2VzcyJ9.fake-signature',
            'refresh_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJ0ZXN0LWlzc3VlciIsImF1ZCI6InRlc3QtYXVkaWVuY2UiLCJqdGkiOiJ0b2tlbi1qdGkiLCJzdWIiOiIxMjMiLCJpYXQiOjE3MzgxMzY1NTUsImV4cCI6MTczODE0MDE1NSwidHlwZSI6InJlZnJlc2gifQ.fake-signature',
        ];

        // 設定 Mock 期望和請求數據
        $this->request->shouldReceive('getParsedBody')->andReturn($logoutData);
        $this->request->shouldReceive('getHeaderLine')->with('Authorization')->andReturn('');
        $this->request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('Test User Agent');

        // Mock getClientIpAddress 所需的 header 檢查
        $this->request->shouldReceive('hasHeader')->andReturn(false);
        $this->request->shouldReceive('getServerParams')->andReturn(['REMOTE_ADDR' => '127.0.0.1']);

        // Mock AuthenticationService 的登出方法 - 接受 LogoutRequestDTO
        $this->authenticationService->shouldReceive('logout')
            ->once()
            ->with(Mockery::type(LogoutRequestDTO::class))
            ->andReturn(true);

        // 建立控制器並執行
        $controller = new AuthController($this->authService, $this->authenticationService, $this->jwtTokenService, $this->validator);
        $response = $controller->logout($this->request, $this->response);

        // 驗證回應
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetUserInfoSuccessfully(): void
    {
        // Mock request with Authorization header
        $this->request->shouldReceive('getHeaderLine')
            ->with('Authorization')
            ->andReturn('Bearer valid.jwt.token');

        // 建立真實的 JwtPayload 物件 (因為是 final class 無法 mock)
        $now = new \DateTimeImmutable();
        $expiresAt = $now->modify('+1 hour');

        $mockPayload = new \AlleyNote\Domains\Auth\ValueObjects\JwtPayload(
            jti: 'test-jti-123',
            sub: '1',
            iss: 'test-issuer',
            aud: ['test-app'],
            iat: $now,
            exp: $expiresAt,
            customClaims: [
                'email' => 'test@example.com',
                'name' => 'Test User'
            ]
        );

        $this->jwtTokenService->shouldReceive('validateAccessToken')
            ->with('valid.jwt.token')
            ->andReturn($mockPayload);

        // Mock response body
        $this->response->shouldReceive('getBody->write')
            ->with(Mockery::type('string'))
            ->andReturnSelf();
        $this->response->shouldReceive('withStatus')
            ->with(200)
            ->andReturnSelf();
        $this->response->shouldReceive('withHeader')
            ->with('Content-Type', 'application/json')
            ->andReturnSelf();

        // 建立控制器並執行 me() 方法
        $controller = new AuthController($this->authService, $this->authenticationService, $this->jwtTokenService, $this->validator);
        $response = $controller->me($this->request, $this->response);

        // 驗證回應狀態碼
        $this->assertEquals(200, $response->getStatusCode());
    }
}
