<?php

declare(strict_types=1);

namespace App\Domains\Post\Exceptions;

use App\Shared\Exceptions\ValidationException;

class PostValidationException extends ValidationException
{
    public function __construct(string $message = '', array $errors = [])
    {
        if (empty($message) && !empty($errors)) {
            $message = '貼文資料驗證失敗';
        }

        parent::__construct($message, $errors);
    }

    public static function titleRequired(): self
    {
        return new self('貼文標題不能為空', ['title' => '標題是必填欄位']);
    }

    public static function titleTooLong(int $maxLength): self
    {
        return new self('貼文標題過長', ['title' => "標題長度不能超過 {$maxLength} 個字元"]);
    }

    public static function contentRequired(): self
    {
        return new self('貼文內容不能為空', ['content' => '內容是必填欄位']);
    }

    public static function contentTooLong(int $maxLength): self
    {
        return new self('貼文內容過長', ['content' => "內容長度不能超過 {$maxLength} 個字元"]);
    }

    public static function invalidCategory(string $category): self
    {
        return new self('無效的貼文分類', ['category' => "分類 '{$category}' 不在允許的清單中"]);
    }

    public static function invalidStatus(string $status): self
    {
        return new self('無效的貼文狀態', ['status' => "狀態 '{$status}' 不是有效的狀態值"]);
    }

    public static function invalidPublishDate(): self
    {
        return new self('無效的發布日期', ['publish_date' => '發布日期格式不正確或為過去時間']);
    }

    public static function multipleErrors(array $errors): self
    {
        $message = '貼文資料包含多個錯誤';

        return new self($message, $errors);
    }
}
