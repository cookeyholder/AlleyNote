<?php

namespace Tests\Integration;

use App\Controllers\AuthController;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;
use Mockery;
use Mockery\MockInterface;

class AuthControllerTest extends TestCase
{
    private AuthService|MockInterface $authService;
    private $request;
    private $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = Mockery::mock(AuthService::class);

        // 模擬 PSR-7 請求和回應物件
        $this->request = new class {
            private $body = [];
            private $headers = [];

            public function getParsedBody(): array
            {
                return $this->body;
            }

            public function withParsedBody(array $data): self
            {
                $this->body = $data;
                return $this;
            }

            public function getHeader(string $name): array
            {
                return $this->headers[$name] ?? [];
            }

            public function withHeader(string $name, string $value): self
            {
                $this->headers[$name] = [$value];
                return $this;
            }
        };

        $this->response = new class {
            private $status = 200;
            private $body = [];

            public function withStatus(int $code): self
            {
                $this->status = $code;
                return $this;
            }

            public function getStatusCode(): int
            {
                return $this->status;
            }

            public function withJson(array $data): self
            {
                $this->body = $data;
                return $this;
            }

            public function getBody(): array
            {
                return $this->body;
            }
        };
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /** @test */
    public function registerUserSuccessfully(): void
    {
        // 準備測試資料
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        // 設定請求資料
        $this->request->withParsedBody($userData);

        // 設定模擬行為
        $this->authService->shouldReceive('register')
            ->once()
            ->with($userData)
            ->andReturn([
                'id' => '1',
                'username' => 'testuser',
                'email' => 'test@example.com',
                'status' => 1
            ]);

        // 建立控制器並執行
        $controller = new AuthController($this->authService);
        $response = $controller->register($this->request, $this->response);

        // 驗證回應
        $this->assertEquals(201, $response->getStatusCode());
        $responseData = $response->getBody();
        $this->assertEquals('testuser', $responseData['data']['username']);
        $this->assertEquals('test@example.com', $responseData['data']['email']);
    }

    /** @test */
    public function returnValidationErrorsForInvalidRegistrationData(): void
    {
        // 準備無效的測試資料
        $invalidData = [
            'username' => '',
            'email' => 'invalid-email',
            'password' => '123'
        ];

        // 設定請求資料
        $this->request->withParsedBody($invalidData);

        // 設定模擬行為
        $this->authService->shouldReceive('register')
            ->once()
            ->with($invalidData)
            ->andThrow(new \InvalidArgumentException('無效的註冊資料'));

        // 建立控制器並執行
        $controller = new AuthController($this->authService);
        $response = $controller->register($this->request, $this->response);

        // 驗證回應
        $this->assertEquals(400, $response->getStatusCode());
        $responseData = $response->getBody();
        $this->assertArrayHasKey('error', $responseData);
    }

    /** @test */
    public function loginUserSuccessfully(): void
    {
        // 準備測試資料
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        // 設定請求資料
        $this->request->withParsedBody($credentials);

        // 設定模擬行為
        $this->authService->shouldReceive('login')
            ->once()
            ->with($credentials)
            ->andReturn([
                'success' => true,
                'message' => '登入成功',
                'user' => [
                    'id' => '1',
                    'username' => 'testuser',
                    'email' => 'test@example.com'
                ]
            ]);

        // 建立控制器並執行
        $controller = new AuthController($this->authService);
        $response = $controller->login($this->request, $this->response);

        // 驗證回應
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getBody();
        $this->assertTrue($responseData['success']);
        $this->assertEquals('登入成功', $responseData['message']);
    }

    /** @test */
    public function returnErrorForInvalidLogin(): void
    {
        // 準備測試資料
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        // 設定請求資料
        $this->request->withParsedBody($credentials);

        // 設定模擬行為
        $this->authService->shouldReceive('login')
            ->once()
            ->with($credentials)
            ->andReturn([
                'success' => false,
                'message' => '無效的認證資訊'
            ]);

        // 建立控制器並執行
        $controller = new AuthController($this->authService);
        $response = $controller->login($this->request, $this->response);

        // 驗證回應
        $this->assertEquals(401, $response->getStatusCode());
        $responseData = $response->getBody();
        $this->assertFalse($responseData['success']);
        $this->assertEquals('無效的認證資訊', $responseData['message']);
    }

    /** @test */
    public function logoutUserSuccessfully(): void
    {
        // 設定請求標頭（模擬已登入的使用者）
        $this->request->withHeader('Authorization', 'Bearer test-token');

        // 建立控制器並執行
        $controller = new AuthController($this->authService);
        $response = $controller->logout($this->request, $this->response);

        // 驗證回應
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = $response->getBody();
        $this->assertTrue($responseData['success']);
        $this->assertEquals('登出成功', $responseData['message']);
    }
}
