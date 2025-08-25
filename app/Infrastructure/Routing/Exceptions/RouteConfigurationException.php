<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Exceptions;

use Exception;

/**
 * 路由配置例外類別.
 *
 * 當路由配置檔案有問題或載入失敗時拋出此例外
 */
class RouteConfigurationException extends Exception
{
    /**
     * 建立檔案不存在的例外.
     */
    public static function fileNotFound(string $filePath): self
    {
        return new self("路由配置檔案不存在: {$filePath}");
    }

    /**
     * 建立檔案無法讀取的例外.
     */
    public static function unreadableFile(string $filePath): self
    {
        return new self("無法讀取路由配置檔案: {$filePath}");
    }

    /**
     * 建立無效路由定義的例外.
     */
    public static function invalidRouteDefinition(string $routeName, string $reason): self
    {
        return new self("路由 '{$routeName}' 定義無效: {$reason}");
    }

    /**
     * 建立重複路由的例外.
     */
    public static function duplicateRoute(string $method, string $path): self
    {
        return new self("重複的路由定義: {$method} {$path}");
    }

    /**
     * 建立無效處理器的例外.
     */
    public static function invalidHandler(string $routeName, mixed $handler): self
    {
        $type = is_object($handler) ? get_class($handler) : gettype($handler);

        return new self("路由 '{$routeName}' 的處理器無效: {$type}");
    }

    /**
     * 建立配置檔案語法錯誤的例外.
     */
    public static function syntaxError(string $filePath, string $error): self
    {
        return new self("路由配置檔案語法錯誤 ({$filePath}): {$error}");
    }
}
