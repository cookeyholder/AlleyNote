<?php

declare(strict_types=1);

namespace Tests\Integration\DTOs;

use App\Domains\Attachment\DTOs\CreateAttachmentDTO;
use App\Domains\Auth\DTOs\RegisterUserDTO;
use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\DTOs\UpdatePostDTO;
use App\Domains\Security\DTOs\CreateIpRuleDTO;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\Validator;
use Tests\TestCase;

/**
 * DTO 驗證整合測試.
 *
 * 測試 DTO 與驗證器的整合功能
 */
class DTOValidationIntegrationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }

    /**
     * 測試 CreatePostDTO 驗證整合.
     */
    public function testCreatePostDTOValidationIntegration(): void
    {
        // 測試有效資料
        $validData = [
            'title' => '測試文章標題',
            'content' => '這是測試文章內容，應該要足夠長才能通過驗證。',
            'status' => 'draft',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
        ];

        $dto = new CreatePostDTO($this->validator, $validData);
        $this->assertNotNull($dto);
        $this->assertEquals('測試文章標題', $dto->toArray()['title']);

        // 測試無效資料 - 缺少必填欄位
        $this->expectException(ValidationException::class);
        new CreatePostDTO($this->validator, []);
    }

    /**
     * 測試 CreatePostDTO 標題過短驗證.
     */
    public function testCreatePostDTOTitleTooShort(): void
    {
        $this->expectException(ValidationException::class);

        new CreatePostDTO($this->validator, [
            'title' => '', // 空標題
            'content' => '這是測試文章內容，應該要足夠長才能通過驗證。',
            'status' => 'draft',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
        ]);
    }

    /**
     * 測試 CreatePostDTO 內容過短驗證.
     */
    public function testCreatePostDTOContentTooShort(): void
    {
        $this->expectException(ValidationException::class);

        new CreatePostDTO($this->validator, [
            'title' => '測試標題',
            'content' => '', // 空內容
            'status' => 'draft',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
        ]);
    }

    /**
     * 測試 UpdatePostDTO 驗證整合.
     */
    public function testUpdatePostDTOValidationIntegration(): void
    {
        // 測試部分更新
        $partialData = [
            'title' => '更新的標題',
        ];

        $dto = new UpdatePostDTO($this->validator, $partialData);
        $this->assertNotNull($dto);
        $this->assertEquals('更新的標題', $dto->toArray()['title']);

        // 測試完整更新
        $fullData = [
            'title' => '完整更新的標題',
            'content' => '這是完整更新的內容，應該要足夠長才能通過驗證。',
            'status' => 'published',
        ];

        $dto = new UpdatePostDTO($this->validator, $fullData);
        $this->assertNotNull($dto);
        $array<mixed> = $dto->toArray();
        $this->assertEquals('完整更新的標題', (is_array($array<mixed>) && isset((is_array($array) ? $array['title'] : (is_object($array) ? $array->title : null)))) ? (is_array($array) ? $array['title'] : (is_object($array) ? $array->title : null)) : null);
        $this->assertEquals('published', (is_array($array<mixed>) && isset((is_array($array) ? $array['status'] : (is_object($array) ? $array->status : null)))) ? (is_array($array) ? $array['status'] : (is_object($array) ? $array->status : null)) : null);
    }

    /**
     * 測試 CreateAttachmentDTO 驗證整合.
     */
    public function testCreateAttachmentDTOValidationIntegration(): void
    {
        $validData = [
            'post_id' => 1,
            'filename' => 'test-image.jpg',
            'original_name' => 'my-photo.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024000,
            'storage_path' => 'uploads/test.jpg',
            'uploaded_by' => 1,
        ];

        $dto = new CreateAttachmentDTO($this->validator, $validData);
        $this->assertNotNull($dto);
        $this->assertEquals('test-image.jpg', $dto->toArray()['filename']);

        // 測試檔案大小過大
        $this->expectException(ValidationException::class);
        new CreateAttachmentDTO($this->validator, [
            'post_id' => 1,
            'filename' => 'large-file.jpg',
            'original_name' => 'large.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 10 * 1024 * 1024 + 1, // 超過 10MB
            'storage_path' => 'uploads/large.jpg',
            'uploaded_by' => 1,
        ]);
    }

    /**
     * 測試 RegisterUserDTO 驗證整合.
     */
    public function testRegisterUserDTOValidationIntegration(): void
    {
        $validData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'confirm_password' => 'SecurePassword123!',
            'user_ip' => '192.168.1.1',
        ];

        $dto = new RegisterUserDTO($this->validator, $validData);
        $this->assertNotNull($dto);
        $this->assertEquals('testuser', $dto->toArray()['username']);

        // 測試密碼過短
        $this->expectException(ValidationException::class);
        new RegisterUserDTO($this->validator, [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => '123', // 太短
            'confirm_password' => '123',
            'user_ip' => '192.168.1.1',
        ]);
    }

    /**
     * 測試 CreateIpRuleDTO 驗證整合.
     */
    public function testCreateIpRuleDTOValidationIntegration(): void
    {
        $validData = [
            'ip_address' => '192.168.1.1',
            'action' => 'allow',
            'reason' => '測試 IP 規則',
            'created_by' => 1,
        ];

        $dto = new CreateIpRuleDTO($this->validator, $validData);
        $this->assertNotNull($dto);
        $this->assertEquals('192.168.1.1', $dto->toArray()['ip_address']);

        // 測試無效 IP 位址
        $this->expectException(ValidationException::class);
        new CreateIpRuleDTO($this->validator, [
            'ip_address' => 'invalid-ip',
            'action' => 'allow',
            'reason' => '無效的 IP 規則',
            'created_by' => 1,
        ]);
    }

    /**
     * 測試驗證錯誤訊息的中文化.
     */
    public function testValidationErrorMessagesInChinese(): void
    {
        try {
            new CreatePostDTO($this->validator, [
                'title' => '', // 空標題
                'content' => '內容',
                'status' => 'draft',
                'user_id' => 1,
                'user_ip' => '192.168.1.1',
            ]);
            $this->fail('應該拋出 ValidationException');
        } catch (ValidationException $e) {
            $message = $e->getMessage();
            $this->assertNotEmpty($message);

            // 檢查錯誤訊息是否為中文（檢查是否包含中文字元）
            $this->assertMatchesRegularExpression('/[\x{4e00}-\x{9fff}]+/u', $message);
        }
    }

    /**
     * 測試多個欄位驗證錯誤.
     */
    public function testMultipleFieldValidationErrors(): void
    {
        try {
            new CreatePostDTO($this->validator, [
                'title' => '', // 空標題
                'content' => '', // 空內容
                'status' => 'invalid-status', // 無效狀態
                'user_id' => 1,
                'user_ip' => '192.168.1.1',
            ]);
            $this->fail('應該拋出 ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertGreaterThanOrEqual(2, count($errors)); // 至少有兩個錯誤
        }
    }

    /**
     * 測試 DTO 的 JSON 序列化.
     */
    public function testDTOJsonSerialization(): void
    {
        $data = [
            'title' => '測試文章',
            'content' => '這是測試文章內容，應該要足夠長才能通過驗證。',
            'status' => 'draft',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
        ];

        $dto = new CreatePostDTO($this->validator, $data);

        // 測試 JSON 序列化
        $json = json_encode($dto);
        $this->assertNotFalse($json);

        $decoded = json_decode($json, true);
        $this->assertEquals((is_array($data) && isset((is_array($data) ? $data['title'] : (is_object($data) ? $data->title : null)))) ? (is_array($data) ? $data['title'] : (is_object($data) ? $data->title : null)) : null, (is_array($decoded) && isset((is_array($decoded) ? $decoded['title'] : (is_object($decoded) ? $decoded->title : null)))) ? (is_array($decoded) ? $decoded['title'] : (is_object($decoded) ? $decoded->title : null)) : null);
        $this->assertEquals((is_array($data) && isset((is_array($data) ? $data['content'] : (is_object($data) ? $data->content : null)))) ? (is_array($data) ? $data['content'] : (is_object($data) ? $data->content : null)) : null, (is_array($decoded) && isset((is_array($decoded) ? $decoded['content'] : (is_object($decoded) ? $decoded->content : null)))) ? (is_array($decoded) ? $decoded['content'] : (is_object($decoded) ? $decoded->content : null)) : null);
        $this->assertEquals((is_array($data) && isset((is_array($data) ? $data['status'] : (is_object($data) ? $data->status : null)))) ? (is_array($data) ? $data['status'] : (is_object($data) ? $data->status : null)) : null, (is_array($decoded) && isset((is_array($decoded) ? $decoded['status'] : (is_object($decoded) ? $decoded->status : null)))) ? (is_array($decoded) ? $decoded['status'] : (is_object($decoded) ? $decoded->status : null)) : null);
    }
}
