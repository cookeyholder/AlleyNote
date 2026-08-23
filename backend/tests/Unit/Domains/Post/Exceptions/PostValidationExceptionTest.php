<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Exceptions;

use App\Domains\Post\Exceptions\PostValidationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * PostValidationException 單元測試.
 */
#[CoversClass(PostValidationException::class)]
final class PostValidationExceptionTest extends UnitTestCase
{
    #[Test]
    public function test_建構子包裝驗證結果與訊息(): void
    {
        $result = ValidationResult::failure(['title' => ['錯誤']]);
        $exception = new PostValidationException($result, '自訂訊息');

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertSame('自訂訊息', $exception->getMessage());
        $this->assertSame($result, $exception->getValidationResult());
    }

    #[Test]
    public function test_titleRequired(): void
    {
        $exception = PostValidationException::titleRequired();

        $this->assertSame('貼文標題不能為空', $exception->getMessage());
        $this->assertArrayHasKey('title', $exception->getErrors());
        $this->assertSame(['required'], $exception->getFailedRules()['title']);
    }

    #[Test]
    public function test_titleTooLong(): void
    {
        $exception = PostValidationException::titleTooLong(255);

        /** @var array<string, array<int, string>> $errors */
        $errors = $exception->getErrors();
        $this->assertSame('貼文標題過長', $exception->getMessage());
        $this->assertStringContainsString('255', $errors['title'][0]);
        $this->assertSame(['max_length'], $exception->getFailedRules()['title']);
    }

    #[Test]
    public function test_contentRequired(): void
    {
        $exception = PostValidationException::contentRequired();

        $this->assertSame('貼文內容不能為空', $exception->getMessage());
        $this->assertArrayHasKey('content', $exception->getErrors());
    }

    #[Test]
    public function test_contentTooLong(): void
    {
        $exception = PostValidationException::contentTooLong(50000);

        /** @var array<string, array<int, string>> $errors */
        $errors = $exception->getErrors();
        $this->assertSame('貼文內容過長', $exception->getMessage());
        $this->assertStringContainsString('50000', $errors['content'][0]);
    }

    #[Test]
    public function test_invalidCategory(): void
    {
        $exception = PostValidationException::invalidCategory('未知分類');

        /** @var array<string, array<int, string>> $errors */
        $errors = $exception->getErrors();
        $this->assertSame('無效的貼文分類', $exception->getMessage());
        $this->assertStringContainsString('未知分類', $errors['category'][0]);
    }

    #[Test]
    public function test_invalidStatus(): void
    {
        $exception = PostValidationException::invalidStatus('bogus');

        /** @var array<string, array<int, string>> $errors */
        $errors = $exception->getErrors();
        $this->assertSame('無效的貼文狀態', $exception->getMessage());
        $this->assertStringContainsString('bogus', $errors['status'][0]);
    }

    #[Test]
    public function test_invalidPublishDate(): void
    {
        $exception = PostValidationException::invalidPublishDate();

        $this->assertSame('無效的發布日期', $exception->getMessage());
        $this->assertArrayHasKey('publish_date', $exception->getErrors());
        $this->assertSame(['invalid_date'], $exception->getFailedRules()['publish_date']);
    }

    #[Test]
    public function test_multipleErrors(): void
    {
        $errors = [
            'title'   => ['標題錯誤'],
            'content' => ['內容錯誤'],
        ];

        $exception = PostValidationException::multipleErrors($errors);

        $this->assertSame('貼文資料包含多個錯誤', $exception->getMessage());
        $this->assertSame($errors, $exception->getErrors());
    }

    #[Test]
    public function test_alreadyPublished(): void
    {
        $exception = PostValidationException::alreadyPublished();

        $this->assertSame('文章已經發佈', $exception->getMessage());
        $this->assertSame(['already_published'], $exception->getFailedRules()['status']);
    }

    #[Test]
    public function test_archivedCannotPublish(): void
    {
        $exception = PostValidationException::archivedCannotPublish();

        $this->assertSame('已封存的文章不能發佈', $exception->getMessage());
        $this->assertSame(['archived_cannot_publish'], $exception->getFailedRules()['status']);
    }

    #[Test]
    public function test_archivedCannotEdit(): void
    {
        $exception = PostValidationException::archivedCannotEdit();

        $this->assertSame('已封存的文章不能編輯', $exception->getMessage());
        $this->assertSame(['archived_cannot_edit'], $exception->getFailedRules()['status']);
    }

    #[Test]
    public function test_alreadyArchived(): void
    {
        $exception = PostValidationException::alreadyArchived();

        $this->assertSame('文章已經封存', $exception->getMessage());
        $this->assertSame(['already_archived'], $exception->getFailedRules()['status']);
    }

    #[Test]
    public function test_titleEmpty(): void
    {
        $exception = PostValidationException::titleEmpty();

        $this->assertSame('文章標題不能為空', $exception->getMessage());
        $this->assertSame(['empty'], $exception->getFailedRules()['title']);
    }

    #[Test]
    public function test_contentEmpty(): void
    {
        $exception = PostValidationException::contentEmpty();

        $this->assertSame('文章內容不能為空', $exception->getMessage());
        $this->assertSame(['empty'], $exception->getFailedRules()['content']);
    }
}
