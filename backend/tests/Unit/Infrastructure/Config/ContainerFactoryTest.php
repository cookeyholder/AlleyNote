<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Config;

use App\Infrastructure\Config\ContainerFactory;
use Psr\Container\ContainerInterface;
use Tests\Support\UnitTestCase;

/**
 * ContainerFactory 單元測試.
 */
class ContainerFactoryTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        ContainerFactory::reset();
        parent::tearDown();
    }

    /**
     * 測試容器建立、單例性與重設.
     */
    public function testContainerFactory(): void
    {
        ContainerFactory::reset();
        $this->assertFalse(ContainerFactory::isInitialized());

        $container = ContainerFactory::create();
        $this->assertInstanceOf(ContainerInterface::class, $container);
        $this->assertTrue(ContainerFactory::isInitialized());

        $sameContainer = ContainerFactory::getInstance();
        $this->assertSame($container, $sameContainer);

        ContainerFactory::reset();
        $this->assertFalse(ContainerFactory::isInitialized());

        $newContainer = ContainerFactory::getInstance();
        $this->assertInstanceOf(ContainerInterface::class, $newContainer);
        $this->assertNotSame($container, $newContainer);
    }
}
