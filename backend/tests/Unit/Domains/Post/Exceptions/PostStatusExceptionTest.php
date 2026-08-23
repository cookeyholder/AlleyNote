<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Exceptions;

use App\Domains\Post\Exceptions\PostStatusException;
use App\Shared\Exceptions\StateTransitionException;
use Tests\Support\UnitTestCase;

/**
 * PostStatusException 測試.
 */
final class PostStatusExceptionTest extends UnitTestCase
{
    public function test_default_constructor_uses_code_400(): void
    {
        $exception = new PostStatusException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
    }

    public function test_constructor_with_message_and_code(): void
    {
        $exception = new PostStatusException('自訂訊息', 422);

        $this->assertSame('自訂訊息', $exception->getMessage());
        $this->assertSame(422, $exception->getCode());
    }

    public function test_invalid_status_factory(): void
    {
        $exception = PostStatusException::invalidStatus('bogus');

        $this->assertInstanceOf(PostStatusException::class, $exception);
        $this->assertSame('無效的貼文狀態：bogus', $exception->getMessage());
    }

    public function test_cannot_transition_factory(): void
    {
        $exception = PostStatusException::cannotTransition('draft', 'archived');

        $this->assertSame(
            '無法將貼文狀態從「draft」變更為「archived」',
            $exception->getMessage(),
        );
    }

    public function test_cannot_publish_factory(): void
    {
        $exception = PostStatusException::cannotPublish('內容為空');

        $this->assertSame('無法發布貼文：內容為空', $exception->getMessage());
    }

    public function test_cannot_archive_factory(): void
    {
        $exception = PostStatusException::cannotArchive('已刪除');

        $this->assertSame('無法封存貼文：已刪除', $exception->getMessage());
    }

    public function test_cannot_delete_factory(): void
    {
        $exception = PostStatusException::cannotDelete('仍有附件');

        $this->assertSame('無法刪除貼文：仍有附件', $exception->getMessage());
    }

    public function test_extends_state_transition_exception(): void
    {
        $exception = PostStatusException::invalidStatus('x');

        $this->assertInstanceOf(StateTransitionException::class, $exception);
    }
}
