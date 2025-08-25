<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Middleware;

use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 抽象中介軟體基礎類別.
 *
 * 提供中介軟體的基本實作，子類別只需實作核心的處理邏輯
 */
abstract class AbstractMiddleware implements MiddlewareInterface
{
    /**
     * 中介軟體名稱.
     */
    protected string $name;

    /**
     * 中介軟體優先順序.
     */
    protected int $priority;

    /**
     * 是否啟用.
     */
    protected bool $enabled;

    /**
     * 建構函式.
     *
     * @param string $name 中介軟體名稱
     * @param int $priority 優先順序（數值越小優先級越高）
     * @param bool $enabled 是否啟用
     */
    public function __construct(
        string $name = '',
        int $priority = 0,
        bool $enabled = true,
    ) {
        $this->name = $name ?: static::class;
        $this->priority = $priority;
        $this->enabled = $enabled;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (!$this->enabled || !$this->shouldProcess($request)) {
            return $handler->handle($request);
        }

        return $this->execute($request, $handler);
    }

    /**
     * 執行中介軟體邏輯.
     *
     * 子類別必須實作此方法
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @param RequestHandlerInterface $handler 請求處理器
     * @return ResponseInterface HTTP 回應物件
     */
    abstract protected function execute(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface;

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function shouldProcess(ServerRequestInterface $request): bool
    {
        return $this->enabled;
    }

    /**
     * 設定中介軟體優先順序.
     *
     * @param int $priority 優先順序
     */
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * 設定中介軟體名稱.
     *
     * @param string $name 名稱
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 啟用中介軟體.
     */
    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    /**
     * 停用中介軟體.
     */
    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * 檢查中介軟體是否啟用.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
