<?php

declare(strict_types=1);

namespace Tests\Integration\DTOs;

use App\Application\Controllers\Api\V1\PostController;
use App\Shared\Validation\ValidationException;
use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\DTOs\UpdatePostDTO;
use App\Domains\Post\Models\Post;
use App\Domains\Post\Services\PostService;
use App\Services\Contracts\PostServiceInterface;
use App\Shared\Contracts\ValidatorInterface;


use App\Shared\Validation\Validator;
use Tests\TestCase;

/**
 * DTO 與 Controller 整合測試
 *
 * 測試 DTO 在 Controller 中的使用是否正常
 */
class DTOControllerIntegrationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new Validator();
    }

    /**
     * 測試 PostController 建立文章時的 DTO 整合
     */
    public function testPostControllerCreateWithValidDTO(): void
    {
        // 模擬有效的文章資料
        $validPostData = [
            'title' => '測試文章標題',
            'content' => '這是一篇測試文章的內容，應該要足夠長才能通過驗證要求。',
            'status' => 'draft',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
        ];

        // 測試 DTO 建立
        $dto = new CreatePostDTO($this->validator, $validPostData);

        $this->assertNotNull($dto);
        $this->assertEquals($validPostData['title'], $dto->toArray()['title']);
        $this->assertEquals($validPostData['content'], $dto->toArray()['content']);
        $this->assertEquals($validPostData['status'], $dto->toArray()['status']);
    }

    /**
     * 測試 PostController 更新文章時的 DTO 整合
     */
    public function testPostControllerUpdateWithValidDTO(): void
    {
        $updateData = [
            'title' => '更新後的標題',
            'content' => '更新後的內容，這應該要足夠長才能通過驗證。',
        ];

        $dto = new UpdatePostDTO($this->validator, $updateData);

        $this->assertNotNull($dto);
        $this->assertEquals($updateData['title'], $dto->toArray()['title']);
        $this->assertEquals($updateData['content'], $dto->toArray()['content']);
    }

    /**
     * 測試 Controller 處理 DTO 驗證錯誤
     */
    public function testControllerHandlesValidationErrors(): void
    {
        // 測試無效資料
        $invalidData = [
            'title' => '', // 空標題
            'content' => '', // 空內容
            'status' => 'invalid-status',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
        ];

        $this->expectException(\App\Shared\Validation\ValidationException::class);
        new CreatePostDTO($this->validator, $invalidData);
    }

    /**
     * 測試 DTO 資料清理功能
     */
    public function testDTODataSanitization(): void
    {
        $dirtyData = [
            'title' => '  測試標題  ', // 前後有空格
            'content' => '  這是測試內容，有多餘的空格  ',
            'status' => 'draft', // 使用有效的狀態值
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
        ];

        $dto = new CreatePostDTO($this->validator, $dirtyData);
        $cleanData = $dto->toArray();

        // 檢查資料是否被正確清理
        $this->assertEquals('測試標題', $cleanData['title']); // 空格應該被移除
        $this->assertEquals('這是測試內容，有多餘的空格', $cleanData['content']);
        $this->assertEquals('draft', $cleanData['status']);
    }

    /**
     * 測試 DTO 型別轉換
     */
    public function testDTOTypeConversion(): void
    {
        // 測試字串轉布林值
        $updateData = [
            'title' => '測試標題',
        ];

        $dto = new UpdatePostDTO($this->validator, $updateData);
        $this->assertNotNull($dto);
    }

    /**
     * 測試 DTO 在批次操作中的使用
     */
    public function testDTOInBatchOperations(): void
    {
        $batchData = [
            [
                'title' => '第一篇文章',
                'content' => '第一篇文章的內容，應該要足夠長才能通過驗證。',
                'status' => 'draft',
                'user_id' => 1,
                'user_ip' => '192.168.1.1',
            ],
            [
                'title' => '第二篇文章',
                'content' => '第二篇文章的內容，也應該要足夠長才能通過驗證。',
                'status' => 'published',
                'user_id' => 1,
                'user_ip' => '192.168.1.1',
            ],
        ];

        $dtos = [];
        foreach ($batchData as $data) {
            $dtos[] = new CreatePostDTO($this->validator, $data);
        }

        $this->assertCount(2, $dtos);
        $this->assertEquals('第一篇文章', $dtos[0]->toArray()['title']);
        $this->assertEquals('第二篇文章', $dtos[1]->toArray()['title']);
    }

    /**
     * 測試 DTO 的記憶體使用效率
     */
    public function testDTOMemoryEfficiency(): void
    {
        $memoryBefore = memory_get_usage();

        // 建立多個 DTO 實例
        $dtos = [];
        for ($i = 0; $i < 100; $i++) {
            $dtos[] = new CreatePostDTO($this->validator, [
                'title' => "測試標題 {$i}",
                'content' => "測試內容 {$i}，這應該要足夠長才能通過驗證要求。",
                'status' => 'draft',
                'user_id' => 1,
                'user_ip' => '192.168.1.1',
            ]);
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // 檢查記憶體使用是否合理（每個 DTO 不超過 10KB）
        $this->assertLessThan(1024 * 1024, $memoryUsed); // 總共不超過 1MB
        $this->assertCount(100, $dtos);
    }

    /**
     * 測試 DTO 的序列化和反序列化
     */
    public function testDTOSerialization(): void
    {
        $originalData = [
            'title' => '測試序列化',
            'content' => '測試 DTO 的序列化功能，這應該要足夠長才能通過驗證。',
            'status' => 'draft',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
        ];

        $dto = new CreatePostDTO($this->validator, $originalData);

        // 序列化為 JSON
        $json = json_encode($dto);
        $this->assertNotFalse($json);

        // 反序列化
        $decoded = json_decode($json, true);
        $this->assertEquals($originalData['title'], $decoded['title']);
        $this->assertEquals($originalData['content'], $decoded['content']);
        $this->assertEquals($originalData['status'], $decoded['status']);
    }
}
