<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Contracts;

use OpenApi\Generator;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

class OpenApiInterfaceScanTest extends UnitTestCase
{
    #[Test]
    public function swaggerCanScanInterfaceAnnotations(): void
    {
        $openapi = new Generator()->generate([
            dirname(__DIR__, 4) . '/app/Application/Contracts',
        ]);
        $this->assertNotNull($openapi);

        /** @var array<string, mixed> $spec */
        $spec = json_decode($openapi->toJson(), true) ?? [];
        $paths = $spec['paths'] ?? [];

        $this->assertIsArray($paths);
        $this->assertArrayHasKey('/api/posts', $paths);
        $this->assertArrayHasKey('/api/auth/login', $paths);
    }
}
