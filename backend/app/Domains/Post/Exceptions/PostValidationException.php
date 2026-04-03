<?php

declare(strict_types=1);

namespace App\Domains\Post\Exceptions;

use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;

class PostValidationException extends ValidationException
{
    public function __construct(ValidationResult $validationResult, string $message = '')
    {
        parent::__construct($validationResult, $message);
    }

    public static function titleRequired(): self
    {
        $validationResult = ValidationResult::failure(
            ['title' => ['標題是必填欄位']],
            ['title' => ['required']],
        );

        return new self($validationResult, '貼文標題不能為空');
    }

    public static function titleTooLong(int $maxLength): self
    {
        $validationResult = ValidationResult::failure(
            ['title' => ["標題長度不能超過 {$maxLength} 個字元"]],
            ['title' => ['max_length']],
        );

        return new self($validationResult, '貼文標題過長');
    }

    public static function contentRequired(): self
    {
        $validationResult = ValidationResult::failure(
            ['content' => ['內容是必填欄位']],
            ['content' => ['required']],
        );

        return new self($validationResult, '貼文內容不能為空');
    }

    public static function contentTooLong(int $maxLength): self
    {
        $validationResult = ValidationResult::failure(
            ['content' => ["內容長度不能超過 {$maxLength} 個字元"]],
            ['content' => ['max_length']],
        );

        return new self($validationResult, '貼文內容過長');
    }

    public static function invalidCategory(string $category): self
    {
        $validationResult = ValidationResult::failure(
            ['category' => ["分類 '{$category}' 不在允許的清單中"]],
            ['category' => ['invalid']],
        );

        return new self($validationResult, '無效的貼文分類');
    }

    public static function invalidStatus(string $status): self
    {
        $validationResult = ValidationResult::failure(
            ['status' => ["狀態 '{$status}' 不是有效的狀態值"]],
            ['status' => ['invalid']],
        );

        return new self($validationResult, '無效的貼文狀態');
    }

    public static function invalidPublishDate(): self
    {
        $validationResult = ValidationResult::failure(
            ['publish_date' => ['發布日期格式不正確或為過去時間']],
            ['publish_date' => ['invalid_date']],
        );

        return new self($validationResult, '無效的發布日期');
    }

    /**
     * @param array<string, mixed> $errors
     */
    public static function multipleErrors(array $errors): self
    {
        $validationResult = ValidationResult::failure($errors);

        return new self($validationResult, '貼文資料包含多個錯誤');
    }

    public static function alreadyPublished(): self
    {
        $validationResult = ValidationResult::failure(
            ['status' => ['文章已經發佈']],
            ['status' => ['already_published']],
        );

        return new self($validationResult, '文章已經發佈');
    }

    public static function archivedCannotPublish(): self
    {
        $validationResult = ValidationResult::failure(
            ['status' => ['已封存的文章不能發佈']],
            ['status' => ['archived_cannot_publish']],
        );

        return new self($validationResult, '已封存的文章不能發佈');
    }

    public static function archivedCannotEdit(): self
    {
        $validationResult = ValidationResult::failure(
            ['status' => ['已封存的文章不能編輯']],
            ['status' => ['archived_cannot_edit']],
        );

        return new self($validationResult, '已封存的文章不能編輯');
    }

    public static function alreadyArchived(): self
    {
        $validationResult = ValidationResult::failure(
            ['status' => ['文章已經封存']],
            ['status' => ['already_archived']],
        );

        return new self($validationResult, '文章已經封存');
    }

    public static function titleEmpty(): self
    {
        $validationResult = ValidationResult::failure(
            ['title' => ['文章標題不能為空']],
            ['title' => ['empty']],
        );

        return new self($validationResult, '文章標題不能為空');
    }

    public static function contentEmpty(): self
    {
        $validationResult = ValidationResult::failure(
            ['content' => ['文章內容不能為空']],
            ['content' => ['empty']],
        );

        return new self($validationResult, '文章內容不能為空');
    }
}
