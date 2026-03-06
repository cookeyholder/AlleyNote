<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

class ExampleTest extends UnitTestCase
{
    #[Test]
    public function testBasicTest(): void
    {
        $this->assertTrue(true);
    }
}
