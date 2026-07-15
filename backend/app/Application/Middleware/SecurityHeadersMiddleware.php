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
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        // 調用 SecurityHeaderService 設定全域安全性 HTTP 標頭
        $this->headerService->setSecurityHeaders();
        $this->headerService->removeServerSignature();

        return $handler->handle($request);
    }

    public function getPriority(): int
    {
        return $this->priority;
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
