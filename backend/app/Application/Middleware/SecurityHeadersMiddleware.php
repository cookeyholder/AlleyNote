<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domains\Security\Services\Headers\SecurityHeaderService;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    private const DEFAULT_PRIORITY = 1; // 優先級越高（數值越小）越早執行

    private const MIDDLEWARE_NAME = 'security-headers';

    public function __construct(
        private readonly SecurityHeaderService $headerService,
        private int $priority = self::DEFAULT_PRIORITY,
        private bool $enabled = true,
    ) {}

    /**
     * 處理全域安全性標頭設定與敏感資訊移除.
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $handler->handle($request);

        if (!$this->enabled) {
            return $response;
        }

        // 獲取安全標頭清單並套用到 PSR-7 Response 物件中 (符合 PSR-7 架構規範)
        $headers = $this->headerService->generateHeaders();
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        // 處理伺服器簽章與敏感資訊移除
        $response = $response->withoutHeader('X-Powered-By');
        if ($this->headerService->isServerSignatureEnabled()) {
            if (isset($headers['Server'])) {
                $response = $response->withHeader('Server', $headers['Server']);
            }
        } else {
            $response = $response->withoutHeader('Server');
        }

        return $response;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function shouldProcess(ServerRequestInterface $request): bool
    {
        return $this->enabled;
    }

    public function getName(): string
    {
        return self::MIDDLEWARE_NAME;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
