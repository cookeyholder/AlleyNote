<?php

declare(strict_types=1);

/**
 * 路由系統簡單測試腳本.
 *
 * 這個腳本用於在沒有 Docker 環境的情況下測試路由系統的基本功能
 */
// 自動載入 Composer 依賴
require_once __DIR__ . '/././vendor/autoload.php';

// 引入路由系統類別
use App\Infrastructure\Routing\Core\Route;
use App\Infrastructure\Routing\Core\RouteCollection;
use App\Infrastructure\Routing\Core\Router;

// 建立一個模擬的 PSR-7 請求物件
class MockServerRequest



{
    public function __construct(
        private string $method,
        private string $path,
    ) {}

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): MockUri
    {
        return new MockUri($this->path);
    }
}

class MockUri



{
    }
    function __construct(private string $path) {}

    public function getPath(): string
    {
        return $this->path;
    }
}

echo '=== AlleyNote 路由系統測試 ===

';

try {
 /* empty */}
