<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Traits;

use PHPUnit\Framework\AssertionFailedError;
use Tests\Support\IntegrationTestCase;
use Tests\Support\Traits\DatabaseSnapshotTrait;

class DatabaseSnapshotTraitTest extends IntegrationTestCase
{
    use DatabaseSnapshotTrait;

    public function testCaptureRowShouldReturnAllColumnValues(): void
    {
        // Arrange
        $id = $this->insertTestPost(['title' => 'Snapshot Test']);

        // Act
        $snapshot = $this->captureRow('posts', $id);

        // Assert
        $this->assertIsArray($snapshot);
        $this->assertEquals('Snapshot Test', $snapshot['data']['title']);
        $this->assertEquals($id, $snapshot['data']['id']);
        $this->assertArrayHasKey('created_at', $snapshot['data']);
    }

    public function testAssertRowUnchangedShouldPassWhenNoChanges(): void
    {
        $id = $this->insertTestPost();
        $snapshot = $this->captureRow('posts', $id);

        // Act & Assert
        $this->assertRowUnchanged($snapshot);
    }

    public function testAssertRowUnchangedShouldFailWhenRowIsModified(): void
    {
        $id = $this->insertTestPost(['title' => 'Original']);
        $snapshot = $this->captureRow('posts', $id);

        // Modify
        $this->db->exec("UPDATE posts SET title = 'Modified' WHERE id = $id");

        // Assert - PHPUnit 的 fail() 丟出的是 AssertionFailedError
        $this->expectException(AssertionFailedError::class);
        $this->assertRowUnchanged($snapshot);
    }

    public function testAssertRowChangedOnlyShouldPassWhenOnlySpecificFieldsChange(): void
    {
        $id = $this->insertTestPost(['title' => 'A', 'views' => 0]);
        $snapshot = $this->captureRow('posts', $id);

        // Modify
        $this->db->exec("UPDATE posts SET title = 'B' WHERE id = $id");

        // Assert
        $this->assertRowChangedOnly($snapshot, ['title']);
    }

    public function testAssertRowChangedOnlyShouldFailIfOtherFieldsChanged(): void
    {
        $id = $this->insertTestPost(['title' => 'A', 'views' => 0]);
        $snapshot = $this->captureRow('posts', $id);

        // Modify both
        $this->db->exec("UPDATE posts SET title = 'B', views = 1 WHERE id = $id");

        // Assert
        $this->expectException(AssertionFailedError::class);
        $this->assertRowChangedOnly($snapshot, ['title']);
    }
}
