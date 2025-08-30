<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

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
 * DTO 驗證單元測試.
 *
 * 測試所有 DTO 類的驗證邏輯和邊界條件
 */
class DTOValidationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }

    /**
     * 測試 CreatePostDTO 所有驗證場景.
     */
    public function test_create_post_dto_validation_scenarios(): void
    {
        // 測試有效資料
        $validData = [
            'title' => '測試文章標題',
            'content' => '這是一篇測試文章的內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'is_anonymous' => false,
            'category' => 'general',
        ];

        $dto = new CreatePostDTO($this->validator, $validData);
        $this->assertInstanceOf(CreatePostDTO::class, $dto);

        // 測試必填欄位缺失
        $missingTitleData = $validData;
        unset((is_array($missingTitleData) ? $missingTitleData['title'] : (is_object($missingTitleData) ? $missingTitleData->title : null)));

        $this->expectException(ValidationException::class);
        new CreatePostDTO($this->validator, $missingTitleData);
    }

    /**
     * 測試 CreatePostDTO 標題驗證.
     */
    public function test_create_post_dto_title_validation(): void
    {
        $baseData = [
            'title' => '測試標題',
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
        ];

        // 測試空標題
        $emptyTitleData = $baseData;
        (is_array($emptyTitleData) ? $emptyTitleData['title'] : (is_object($emptyTitleData) ? $emptyTitleData->title : null)) = '';

        $this->expectException(ValidationException::class);
        new CreatePostDTO($this->validator, $emptyTitleData);
    }

    /**
     * 測試 CreatePostDTO 標題長度限制.
     */
    public function test_create_post_dto_title_length_limits(): void
    {
        $baseData = [
            'content' => '內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
        ];

        // 測試有效的最小長度標題
        $validMinTitleData = $baseData;
        (is_array($validMinTitleData) ? $validMinTitleData['title'] : (is_object($validMinTitleData) ? $validMinTitleData->title : null)) = 'A';
        $dto = new CreatePostDTO($this->validator, $validMinTitleData);
        $this->assertInstanceOf(CreatePostDTO::class, $dto);

        // 測試超過最大長度
        $tooLongTitleData = $baseData;
        (is_array($tooLongTitleData) ? $tooLongTitleData['title'] : (is_object($tooLongTitleData) ? $tooLongTitleData->title : null)) = str_repeat('很長的標題', 100); // 超過 255 字元

        $this->expectException(ValidationException::class);
        new CreatePostDTO($this->validator, $tooLongTitleData);
    }

    /**
     * 測試 CreatePostDTO 內容驗證.
     */
    public function test_create_post_dto_content_validation(): void
    {
        $baseData = [
            'title' => '測試標題',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
        ];

        // 測試內容為空
        $emptyContentData = $baseData;
        (is_array($emptyContentData) ? $emptyContentData['content'] : (is_object($emptyContentData) ? $emptyContentData->content : null)) = '';

        $this->expectException(ValidationException::class);
        new CreatePostDTO($this->validator, $emptyContentData);

        // 測試內容長度限制
        $validContentData = $baseData;
        (is_array($validContentData) ? $validContentData['content'] : (is_object($validContentData) ? $validContentData->content : null)) = str_repeat('內容', 5000); // 合理長度
        $dto = new CreatePostDTO($this->validator, $validContentData);
        $this->assertInstanceOf(CreatePostDTO::class, $dto);
    }

    /**
     * 測試 CreatePostDTO 使用者驗證.
     */
    public function test_create_post_dto_user_validation(): void
    {
        $baseData = [
            'title' => '測試標題',
            'content' => '內容',
            'user_ip' => '192.168.1.1',
        ];

        // 測試無效的 user_id
        $invalidUserIdData = $baseData;
        (is_array($invalidUserIdData) ? $invalidUserIdData['user_id'] : (is_object($invalidUserIdData) ? $invalidUserIdData->user_id : null)) = 'invalid';

        $this->expectException(ValidationException::class);
        new CreatePostDTO($this->validator, $invalidUserIdData);

        // 測試負數 user_id
        $negativeUserIdData = $baseData;
        (is_array($negativeUserIdData) ? $negativeUserIdData['user_id'] : (is_object($negativeUserIdData) ? $negativeUserIdData->user_id : null)) = -1;

        $this->expectException(ValidationException::class);
        new CreatePostDTO($this->validator, $negativeUserIdData);

        // 測試零 user_id
        $zeroUserIdData = $baseData;
        (is_array($zeroUserIdData) ? $zeroUserIdData['user_id'] : (is_object($zeroUserIdData) ? $zeroUserIdData->user_id : null)) = 0;

        $this->expectException(ValidationException::class);
        new CreatePostDTO($this->validator, $zeroUserIdData);
    }

    /**
     * 測試 CreatePostDTO IP 地址驗證.
     */
    public function test_create_post_dto_ip_validation(): void
    {
        $baseData = [
            'title' => '測試標題',
            'content' => '內容',
            'user_id' => 1,
        ];

        // 測試有效的 IPv4 地址
        $validIpv4Data = $baseData;
        (is_array($validIpv4Data) ? $validIpv4Data['user_ip'] : (is_object($validIpv4Data) ? $validIpv4Data->user_ip : null)) = '192.168.1.1';
        $dto = new CreatePostDTO($this->validator, $validIpv4Data);
        $this->assertInstanceOf(CreatePostDTO::class, $dto);

        // 測試有效的 IPv6 地址
        $validIpv6Data = $baseData;
        (is_array($validIpv6Data) ? $validIpv6Data['user_ip'] : (is_object($validIpv6Data) ? $validIpv6Data->user_ip : null)) = '2001:db8::1';
        $dto = new CreatePostDTO($this->validator, $validIpv6Data);
        $this->assertInstanceOf(CreatePostDTO::class, $dto);

        // 測試無效的 IP 地址
        $invalidIpData = $baseData;
        (is_array($invalidIpData) ? $invalidIpData['user_ip'] : (is_object($invalidIpData) ? $invalidIpData->user_ip : null)) = 'not-an-ip';

        $this->expectException(ValidationException::class);
        new CreatePostDTO($this->validator, $invalidIpData);
    }

    /**
     * 測試 UpdatePostDTO 部分更新驗證.
     */
    public function test_update_post_dto_partial_validation(): void
    {
        // 測試只更新標題
        $titleOnlyData = ['title' => '新標題'];
        $dto = new UpdatePostDTO($this->validator, $titleOnlyData);
        $this->assertInstanceOf(UpdatePostDTO::class, $dto);

        // 測試只更新內容
        $contentOnlyData = ['content' => '新內容'];
        $dto = new UpdatePostDTO($this->validator, $contentOnlyData);
        $this->assertInstanceOf(UpdatePostDTO::class, $dto);

        // 測試更新多個欄位
        $multipleFieldsData = [
            'title' => '新標題',
            'content' => '新內容',
        ];
        $dto = new UpdatePostDTO($this->validator, $multipleFieldsData);
        $this->assertInstanceOf(UpdatePostDTO::class, $dto);

        // 測試空資料（應該也能通過，因為是部分更新）
        $emptyData = [];
        $dto = new UpdatePostDTO($this->validator, $emptyData);
        $this->assertInstanceOf(UpdatePostDTO::class, $dto);
    }

    /**
     * 測試 UpdatePostDTO 欄位驗證規則.
     */
    public function test_update_post_dto_field_validation(): void
    {
        // 測試空的更新資料 - 應該可以通過
        $emptyData = [];
        $dto = new UpdatePostDTO($this->validator, $emptyData);
        $this->assertInstanceOf(UpdatePostDTO::class, $dto);

        // 測試有效的標題更新
        $validTitleData = ['title' => '有效標題'];
        $dto = new UpdatePostDTO($this->validator, $validTitleData);
        $this->assertInstanceOf(UpdatePostDTO::class, $dto);
    }

    /**
     * 測試 CreateAttachmentDTO 驗證.
     */
    public function test_create_attachment_dto_validation(): void
    {
        // 測試有效資料
        $validData = [
            'post_id' => 1,
            'filename' => 'test-file.jpg',
            'original_name' => 'original.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'storage_path' => 'uploads/test-file.jpg',
            'uploaded_by' => 1,
        ];

        $dto = new CreateAttachmentDTO($this->validator, $validData);
        $this->assertInstanceOf(CreateAttachmentDTO::class, $dto);

        // 測試必填欄位缺失
        $missingPostIdData = $validData;
        unset((is_array($missingPostIdData) ? $missingPostIdData['post_id'] : (is_object($missingPostIdData) ? $missingPostIdData->post_id : null)));

        $this->expectException(ValidationException::class);
        new CreateAttachmentDTO($this->validator, $missingPostIdData);
    }

    /**
     * 測試 CreateAttachmentDTO 檔案驗證.
     */
    public function test_create_attachment_dto_file_validation(): void
    {
        $baseData = [
            'post_id' => 1,
            'filename' => 'test.jpg',
            'original_name' => 'original.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'storage_path' => 'uploads/test.jpg',
            'uploaded_by' => 1,
        ];

        // 測試無效的檔案大小
        $invalidSizeData = $baseData;
        (is_array($invalidSizeData) ? $invalidSizeData['file_size'] : (is_object($invalidSizeData) ? $invalidSizeData->file_size : null)) = -1;

        $this->expectException(ValidationException::class);
        new CreateAttachmentDTO($this->validator, $invalidSizeData);

        // 測試無效的檔案名稱
        $invalidFilenameData = $baseData;
        (is_array($invalidFilenameData) ? $invalidFilenameData['filename'] : (is_object($invalidFilenameData) ? $invalidFilenameData->filename : null)) = 'file/with/slash.jpg';

        $this->expectException(ValidationException::class);
        new CreateAttachmentDTO($this->validator, $invalidFilenameData);
    }

    /**
     * 測試 RegisterUserDTO 驗證.
     */
    public function test_register_user_dto_validation(): void
    {
        // 測試有效資料
        $validData = [
            'username' => 'testuser123',
            'email' => 'test@example.com',
            'password' => 'SecurePass123',
            'confirm_password' => 'SecurePass123',
            'user_ip' => '192.168.1.1',
        ];

        $dto = new RegisterUserDTO($this->validator, $validData);
        $this->assertInstanceOf(RegisterUserDTO::class, $dto);

        // 測試必填欄位缺失
        $missingUsernameData = $validData;
        unset((is_array($missingUsernameData) ? $missingUsernameData['username'] : (is_object($missingUsernameData) ? $missingUsernameData->username : null)));

        $this->expectException(ValidationException::class);
        new RegisterUserDTO($this->validator, $missingUsernameData);
    }

    /**
     * 測試 RegisterUserDTO 使用者名稱驗證.
     */
    public function test_register_user_dto_username_validation(): void
    {
        $baseData = [
            'username' => 'validuser',
            'email' => 'test@example.com',
            'password' => 'SecurePass123',
            'confirm_password' => 'SecurePass123',
            'user_ip' => '192.168.1.1',
        ];

        // 測試使用者名稱太短
        $shortUsernameData = $baseData;
        (is_array($shortUsernameData) ? $shortUsernameData['username'] : (is_object($shortUsernameData) ? $shortUsernameData->username : null)) = 'ab';

        $this->expectException(ValidationException::class);
        new RegisterUserDTO($this->validator, $shortUsernameData);

        // 測試使用者名稱包含無效字元
        $invalidCharsData = $baseData;
        (is_array($invalidCharsData) ? $invalidCharsData['username'] : (is_object($invalidCharsData) ? $invalidCharsData->username : null)) = 'user@name';

        $this->expectException(ValidationException::class);
        new RegisterUserDTO($this->validator, $invalidCharsData);

        // 測試使用者名稱以數字開頭
        $numberStartData = $baseData;
        (is_array($numberStartData) ? $numberStartData['username'] : (is_object($numberStartData) ? $numberStartData->username : null)) = '123user';

        $this->expectException(ValidationException::class);
        new RegisterUserDTO($this->validator, $numberStartData);
    }

    /**
     * 測試 RegisterUserDTO 電子郵件驗證.
     */
    public function test_register_user_dto_email_validation(): void
    {
        $baseData = [
            'username' => 'testuser',
            'email' => 'valid@example.com',
            'password' => 'SecurePass123',
            'confirm_password' => 'SecurePass123',
            'user_ip' => '192.168.1.1',
        ];

        // 測試無效的電子郵件格式
        $invalidEmailData = $baseData;
        (is_array($invalidEmailData) ? $invalidEmailData['email'] : (is_object($invalidEmailData) ? $invalidEmailData->email : null)) = 'invalid-email';

        $this->expectException(ValidationException::class);
        new RegisterUserDTO($this->validator, $invalidEmailData);

        // 測試包含危險字元的電子郵件
        $dangerousEmailData = $baseData;
        (is_array($dangerousEmailData) ? $dangerousEmailData['email'] : (is_object($dangerousEmailData) ? $dangerousEmailData->email : null)) = 'user@<script>alert(1)</script>.com';

        $this->expectException(ValidationException::class);
        new RegisterUserDTO($this->validator, $dangerousEmailData);
    }

    /**
     * 測試 RegisterUserDTO 密碼驗證.
     */
    public function test_register_user_dto_password_validation(): void
    {
        $baseData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'user_ip' => '192.168.1.1',
        ];

        // 測試密碼太短
        $shortPasswordData = $baseData;
        (is_array($shortPasswordData) ? $shortPasswordData['password'] : (is_object($shortPasswordData) ? $shortPasswordData->password : null)) = '123';
        (is_array($shortPasswordData) ? $shortPasswordData['confirm_password'] : (is_object($shortPasswordData) ? $shortPasswordData->confirm_password : null)) = '123';

        $this->expectException(ValidationException::class);
        new RegisterUserDTO($this->validator, $shortPasswordData);

        // 測試密碼缺乏複雜度
        $weakPasswordData = $baseData;
        (is_array($weakPasswordData) ? $weakPasswordData['password'] : (is_object($weakPasswordData) ? $weakPasswordData->password : null)) = 'password';
        (is_array($weakPasswordData) ? $weakPasswordData['confirm_password'] : (is_object($weakPasswordData) ? $weakPasswordData->confirm_password : null)) = 'password';

        $this->expectException(ValidationException::class);
        new RegisterUserDTO($this->validator, $weakPasswordData);

        // 測試密碼確認不符
        $mismatchPasswordData = $baseData;
        (is_array($mismatchPasswordData) ? $mismatchPasswordData['password'] : (is_object($mismatchPasswordData) ? $mismatchPasswordData->password : null)) = 'SecurePass123';
        (is_array($mismatchPasswordData) ? $mismatchPasswordData['confirm_password'] : (is_object($mismatchPasswordData) ? $mismatchPasswordData->confirm_password : null)) = 'DifferentPass123';

        $this->expectException(ValidationException::class);
        new RegisterUserDTO($this->validator, $mismatchPasswordData);
    }

    /**
     * 測試 CreateIpRuleDTO 驗證.
     */
    public function test_create_ip_rule_dto_validation(): void
    {
        // 測試有效資料
        $validData = [
            'ip_address' => '192.168.1.1',
            'action' => 'block',
            'reason' => '惡意行為',
            'created_by' => 1,
        ];

        $dto = new CreateIpRuleDTO($this->validator, $validData);
        $this->assertInstanceOf(CreateIpRuleDTO::class, $dto);

        // 測試必填欄位缺失
        $missingIpData = $validData;
        unset((is_array($missingIpData) ? $missingIpData['ip_address'] : (is_object($missingIpData) ? $missingIpData->ip_address : null)));

        $this->expectException(ValidationException::class);
        new CreateIpRuleDTO($this->validator, $missingIpData);
    }

    /**
     * 測試 CreateIpRuleDTO IP 地址驗證.
     */
    public function test_create_ip_rule_dto_ip_validation(): void
    {
        $baseData = [
            'ip_address' => '192.168.1.1',
            'action' => 'block',
            'reason' => '測試',
            'created_by' => 1,
        ];

        // 測試 CIDR 格式
        $cidrData = $baseData;
        (is_array($cidrData) ? $cidrData['ip_address'] : (is_object($cidrData) ? $cidrData->ip_address : null)) = '192.168.1.0/24';
        $dto = new CreateIpRuleDTO($this->validator, $cidrData);
        $this->assertInstanceOf(CreateIpRuleDTO::class, $dto);

        // 測試無效的 IP 格式
        $invalidIpData = $baseData;
        (is_array($invalidIpData) ? $invalidIpData['ip_address'] : (is_object($invalidIpData) ? $invalidIpData->ip_address : null)) = 'not-an-ip';

        $this->expectException(ValidationException::class);
        new CreateIpRuleDTO($this->validator, $invalidIpData);

        // 測試無效的 CIDR 格式
        $invalidCidrData = $baseData;
        (is_array($invalidCidrData) ? $invalidCidrData['ip_address'] : (is_object($invalidCidrData) ? $invalidCidrData->ip_address : null)) = '192.168.1.0/999';

        $this->expectException(ValidationException::class);
        new CreateIpRuleDTO($this->validator, $invalidCidrData);
    }

    /**
     * 測試 CreateIpRuleDTO 規則類型驗證.
     */
    public function test_create_ip_rule_dto_rule_type_validation(): void
    {
        $baseData = [
            'ip_address' => '192.168.1.1',
            'action' => 'block',
            'reason' => '測試',
            'created_by' => 1,
        ];

        // 測試有效的規則類型
        $validTypes = ['allow', 'block', 'deny'];
        foreach ($validTypes as $type) {
            $validTypeData = $baseData;
            (is_array($validTypeData) ? $validTypeData['action'] : (is_object($validTypeData) ? $validTypeData->action : null)) = $type;
            $dto = new CreateIpRuleDTO($this->validator, $validTypeData);
            $this->assertInstanceOf(CreateIpRuleDTO::class, $dto);
        }

        // 測試無效的規則類型
        $invalidTypeData = $baseData;
        (is_array($invalidTypeData) ? $invalidTypeData['action'] : (is_object($invalidTypeData) ? $invalidTypeData->action : null)) = 'invalid_type';

        $this->expectException(ValidationException::class);
        new CreateIpRuleDTO($this->validator, $invalidTypeData);
    }

    /**
     * 測試所有 DTO 的資料清理功能.
     */
    public function test_dto_data_sanitization(): void
    {
        // 測試 CreatePostDTO 資料清理
        $dirtyPostData = [
            'title' => '  測試標題  ',
            'content' => '  內容  ',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
        ];

        $postDto = new CreatePostDTO($this->validator, $dirtyPostData);

        // 檢查屬性是否正確設定
        $this->assertEquals('測試標題', $postDto->title);
        $this->assertEquals('內容', $postDto->content);
        $this->assertIsInt($postDto->userId);
        $this->assertIsBool($postDto->isPinned);
    }

    /**
     * 測試 DTO 驗證效能基準.
     */
    public function test_dto_validation_performance(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // 測試大量 DTO 建立
        for ($i = 0; $i < 100; $i++) {
            $postData = [
                'title' => "測試文章標題 {$i}",
                'content' => "這是第 {$i} 篇測試文章的內容",
                'user_id' => $i + 1,
                'user_ip' => '192.168.1.' . ($i % 255 + 1),
                'is_anonymous' => $i % 2 === 0,
            ];

            $dto = new CreatePostDTO($this->validator, $postData);
            $this->assertInstanceOf(CreatePostDTO::class, $dto);
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // 計算效能指標
        $executionTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        // 效能斷言
        $this->assertLessThan(0.5, $executionTime, '100 個 DTO 驗證應該在 0.5 秒內完成');
        $this->assertLessThan(5 * 1024 * 1024, $memoryUsage, '記憶體使用量應該少於 5MB');
    }

    /**
     * 測試 DTO 序列化和反序列化.
     */
    public function test_dto_serialization(): void
    {
        $originalData = [
            'title' => '測試標題',
            'content' => '測試內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'is_anonymous' => false,
        ];

        $dto = new CreatePostDTO($this->validator, $originalData);

        // 測試 toArray
        $arrayData = $dto->toArray();
        $this->assertIsArray($arrayData);
        $this->assertEquals((is_array($originalData) && isset((is_array($originalData) ? $originalData['title'] : (is_object($originalData) ? $originalData->title : null)))) ? (is_array($originalData) ? $originalData['title'] : (is_object($originalData) ? $originalData->title : null)) : null, (is_array($arrayData) && isset((is_array($arrayData) ? $arrayData['title'] : (is_object($arrayData) ? $arrayData->title : null)))) ? (is_array($arrayData) ? $arrayData['title'] : (is_object($arrayData) ? $arrayData->title : null)) : null);

        // 測試 JSON 序列化
        $json = json_encode($dto);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals((is_array($originalData) && isset((is_array($originalData) ? $originalData['title'] : (is_object($originalData) ? $originalData->title : null)))) ? (is_array($originalData) ? $originalData['title'] : (is_object($originalData) ? $originalData->title : null)) : null, (is_array($decoded) && isset((is_array($decoded) ? $decoded['title'] : (is_object($decoded) ? $decoded->title : null)))) ? (is_array($decoded) ? $decoded['title'] : (is_object($decoded) ? $decoded->title : null)) : null);
    }

    /**
     * 測試 DTO 邊界條件.
     */
    public function test_dto_edge_cases(): void
    {
        // 測試極長的字串
        $extremeData = [
            'title' => str_repeat('測試', 100),
            'content' => str_repeat('內容', 1000),
            'user_id' => PHP_INT_MAX,
            'user_ip' => '255.255.255.255',
        ];

        // 某些極端值可能會觸發驗證錯誤，這是預期的
        try {
            $dto = new CreatePostDTO($this->validator, $extremeData);
            $this->assertInstanceOf(CreatePostDTO::class, $dto);
        } catch (ValidationException $e) {
            // 如果驗證失敗，檢查錯誤是否合理
            $this->assertNotEmpty($e->getErrors());
        }

        // 測試邊界值
        $boundaryData = [
            'title' => str_repeat('A', 255), // 最大允許長度
            'content' => '最小內容',
            'user_id' => 1,
            'user_ip' => '0.0.0.0',
        ];

        $dto = new CreatePostDTO($this->validator, $boundaryData);
        $this->assertInstanceOf(CreatePostDTO::class, $dto);
    }
}
