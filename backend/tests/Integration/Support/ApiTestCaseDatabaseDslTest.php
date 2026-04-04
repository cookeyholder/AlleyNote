<?php

declare(strict_types=1);

namespace Tests\Integration\Support;

use App\Application\Controllers\Api\V1\PostController;
use App\Domains\Auth\Contracts\AuthorizationServiceInterface;
use App\Domains\Post\Contracts\PostServiceInterface;
use App\Domains\Post\Models\Post;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Statistics\Services\PostViewStatisticsService;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Contracts\ValidatorInterface;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ApiTestCase;

class ApiTestCaseDatabaseDslTest extends ApiTestCase
{
    #[Test]
    public function singleConnectionPrincipleShouldBeSatisfied(): void
    {
        $this->assertSharedPdoConnection();
    }

    #[Test]
    public function assertDatabaseHasCanObserveApiWriteImmediately(): void
    {
        $postService = Mockery::mock(PostServiceInterface::class);
        $validator = Mockery::mock(ValidatorInterface::class);
        $sanitizer = Mockery::mock(OutputSanitizerInterface::class);
        $activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class)->shouldIgnoreMissing();
        $viewStatsService = Mockery::mock(PostViewStatisticsService::class);
        $authService = Mockery::mock(AuthorizationServiceInterface::class);

        $sanitizer->shouldReceive('sanitizeHtml')->andReturnUsing(static fn(string $value): string => $value);
        $sanitizer->shouldReceive('sanitizeRichText')->andReturnUsing(static fn(string $value): string => $value);
        $validator->shouldReceive('addRule')->zeroOrMoreTimes()->andReturnSelf();
        $validator->shouldReceive('addMessage')->zeroOrMoreTimes()->andReturnSelf();
        $validator->shouldReceive('validateOrFail')->zeroOrMoreTimes()->andReturnUsing(
            static fn(array $input): array => $input,
        );

        $postService->shouldReceive('createPost')->andReturnUsing(function (): Post {
            $now = date('Y-m-d H:i:s');
            $this->db->prepare(
                'INSERT INTO posts (uuid, seq_number, title, content, user_id, publish_date, created_at, updated_at, status)
                 VALUES (:uuid, :seq_number, :title, :content, :user_id, :publish_date, :created_at, :updated_at, :status)',
            )->execute([
                'uuid' => $this->generateTestUuid(),
                'seq_number' => random_int(1000, 9999),
                'title' => 'DSL API Write',
                'content' => 'dsl content',
                'user_id' => 1,
                'publish_date' => $now,
                'created_at' => $now,
                'updated_at' => $now,
                'status' => 1,
            ]);

            return new Post([
                'id' => 1,
                'title' => 'DSL API Write',
                'content' => 'dsl content',
                'user_id' => 1,
                'status' => 'published',
            ]);
        });
        $postService->shouldReceive('setTags')->zeroOrMoreTimes();

        $controller = new PostController(
            $postService,
            $validator,
            $sanitizer,
            $activityLogger,
            $viewStatsService,
            $authService,
        );

        $request = $this
            ->actingAs(['id' => 1, 'email' => 'dsl-write@example.com'])
            ->json('POST', '/api/posts', [
                'title' => 'DSL API Write',
                'content' => 'dsl content',
            ])
            ->withAttribute('user_id', 1);

        $controller->store($request, $this->createApiResponse());
        $this->assertDatabaseHas('posts', ['title' => 'DSL API Write']);
    }

    #[Test]
    public function assertDatabaseMissingShouldPassAfterDeleteScenario(): void
    {
        $this->db->exec(
            "INSERT INTO posts (uuid, seq_number, title, content, user_id, publish_date, created_at, updated_at, status)
             VALUES ('" . $this->generateTestUuid() . "', 2001, 'ToDelete', 'content', 1, datetime('now'), datetime('now'), datetime('now'), 1)",
        );

        $this->assertDatabaseHas('posts', ['title' => 'ToDelete']);

        $this->db->exec("DELETE FROM posts WHERE title = 'ToDelete'");
        $this->assertDatabaseMissing('posts', ['title' => 'ToDelete']);
    }

    #[Test]
    public function assertDatabaseMissingShouldPassAfterRollback(): void
    {
        $this->db->beginTransaction();
        $this->db->exec(
            "INSERT INTO posts (uuid, seq_number, title, content, user_id, publish_date, created_at, updated_at, status)
             VALUES ('" . $this->generateTestUuid() . "', 3001, 'RollbackPost', 'content', 1, datetime('now'), datetime('now'), datetime('now'), 1)",
        );
        $this->db->rollBack();

        $this->assertDatabaseMissing('posts', ['title' => 'RollbackPost']);
    }

    #[Test]
    public function nestedTransactionAndSavepointShouldKeepDslAssertionsCorrect(): void
    {
        $this->db->beginTransaction();
        $this->db->exec('SAVEPOINT nested_1');
        $this->db->exec(
            "INSERT INTO posts (uuid, seq_number, title, content, user_id, publish_date, created_at, updated_at, status)
             VALUES ('" . $this->generateTestUuid() . "', 4001, 'NestedPost', 'content', 1, datetime('now'), datetime('now'), datetime('now'), 1)",
        );

        $this->assertDatabaseHas('posts', ['title' => 'NestedPost']);

        $this->db->exec('ROLLBACK TO SAVEPOINT nested_1');
        $this->assertDatabaseMissing('posts', ['title' => 'NestedPost']);
        $this->db->rollBack();
    }
}
