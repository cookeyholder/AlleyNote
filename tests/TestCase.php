<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Mockery;
use Psr\Http\Message\ResponseInterface;

abstract class TestCase extends BaseTestCase
{
    /**
     * 初始化測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createResponseMock(): ResponseInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('withJson')
            ->andReturnUsing(function ($data) use ($response) {
                return $response;
            });
        $response->shouldReceive('withStatus')
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->andReturnSelf();
        return $response;
    }

    /**
     * 清理測試環境
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        if ($container = Mockery::getContainer()) {
            $container->mockery_close();
        }
    }
}
