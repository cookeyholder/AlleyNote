<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Routing;

use App\Infrastructure\Routing\Exceptions\RouteConfigurationException;
use App\Infrastructure\Routing\RouteValidator;
use stdClass;
use Tests\Support\UnitTestCase;

/**
 * RouteValidator 類別單元測試.
 */
class RouteValidatorTest extends UnitTestCase
{
    private RouteValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new RouteValidator();
    }

    /**
     * 測試有效路由配置驗證通過.
     */
    public function testValidateValidRoute(): void
    {
        $validConfigs = [
            [
                'methods' => 'GET',
                'path'    => '/users/{id}',
                'handler' => 'UserController@show',
            ],
            [
                'methods' => ['POST', 'PUT'],
                'path'    => '/users/{id}/update',
                'handler' => ['UserController', 'update'],
            ],
            [
                'methods' => ['DELETE'],
                'path'    => '/users/{id}',
                'handler' => static fn() => 'deleted',
            ],
        ];

        foreach ($validConfigs as $config) {
            $this->validator->validateRoute($config);
        }

        $registered = $this->validator->getRegisteredRoutes();
        $this->assertCount(4, $registered);
        $this->assertContains('GET:/users/{id}', $registered);
        $this->assertContains('POST:/users/{id}/update', $registered);
        $this->assertContains('PUT:/users/{id}/update', $registered);
        $this->assertContains('DELETE:/users/{id}', $registered);

        $this->validator->reset();
        $this->assertEmpty($this->validator->getRegisteredRoutes());
    }

    /**
     * 測試缺少必要欄位.
     */
    public function testMissingRequiredFields(): void
    {
        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('缺少必要欄位: handler');

        $this->validator->validateRoute([
            'methods' => 'GET',
            'path'    => '/test',
        ]);
    }

    /**
     * 測試空 HTTP 方法.
     */
    public function testEmptyHttpMethods(): void
    {
        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('HTTP 方法不能為空');

        $this->validator->validateRoute([
            'methods' => [],
            'path'    => '/test',
            'handler' => 'TestController@index',
        ]);
    }

    /**
     * 測試非字串 HTTP 方法.
     */
    public function testNonStringHttpMethod(): void
    {
        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('HTTP 方法必須是字串');

        $this->validator->validateRoute([
            'methods' => [123],
            'path'    => '/test',
            'handler' => 'TestController@index',
        ]);
    }

    /**
     * 測試無效 HTTP 方法.
     */
    public function testInvalidHttpMethod(): void
    {
        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('無效的 HTTP 方法: INVALID');

        $this->validator->validateRoute([
            'methods' => 'INVALID',
            'path'    => '/test',
            'handler' => 'TestController@index',
        ]);
    }

    /**
     * 測試非字串路徑.
     */
    public function testNonStringPath(): void
    {
        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('路由路径必須是字串');

        $this->validator->validateRoute([
            'methods' => 'GET',
            'path'    => 12345,
            'handler' => 'TestController@index',
        ]);
    }

    /**
     * 測試路徑非斜線開頭.
     */
    public function testPathNotStartingWithSlash(): void
    {
        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('路由路径必須以 "/" 開始');

        $this->validator->validateRoute([
            'methods' => 'GET',
            'path'    => 'users',
            'handler' => 'TestController@index',
        ]);
    }

    /**
     * 測試無效路徑參數名稱.
     */
    public function testInvalidPathParameterFormat(): void
    {
        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('無效的路由參數格式: {123invalid}');

        $this->validator->validateRoute([
            'methods' => 'GET',
            'path'    => '/users/{123invalid}',
            'handler' => 'TestController@index',
        ]);
    }

    /**
     * 測試無效字串格式的處理器 (無 @ 或缺少類別/方法).
     */
    public function testInvalidStringHandlerFormat(): void
    {
        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('處理器無效');

        $this->validator->validateRoute([
            'methods' => 'GET',
            'path'    => '/test',
            'handler' => 'InvalidHandlerWithoutAt',
        ]);
    }

    /**
     * 測試無效陣列格式的處理器.
     */
    public function testInvalidArrayHandlerFormat(): void
    {
        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('處理器無效');

        $this->validator->validateRoute([
            'methods' => 'GET',
            'path'    => '/test',
            'handler' => ['ControllerOnly'],
        ]);
    }

    /**
     * 測試無效物件格式的處理器.
     */
    public function testInvalidObjectHandler(): void
    {
        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('處理器無效');

        $this->validator->validateRoute([
            'methods' => 'GET',
            'path'    => '/test',
            'handler' => new stdClass(),
        ]);
    }

    /**
     * 測試重複路由定義.
     */
    public function testDuplicateRoute(): void
    {
        $this->validator->validateRoute([
            'methods' => 'GET',
            'path'    => '/duplicate',
            'handler' => 'TestController@index',
        ]);

        $this->expectException(RouteConfigurationException::class);
        $this->expectExceptionMessage('重複的路由定義: GET /duplicate');

        $this->validator->validateRoute([
            'methods' => 'GET',
            'path'    => '/duplicate',
            'handler' => 'TestController@index2',
        ]);
    }
}
