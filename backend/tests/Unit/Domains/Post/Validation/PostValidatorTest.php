<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Validation;

use App\Domains\Post\Validation\PostValidator;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * PostValidator 自訂驗證規則單元測試.
 */
#[CoversClass(PostValidator::class)]
final class PostValidatorTest extends UnitTestCase
{
    private PostValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new PostValidator();
    }

    /**
     * 建立規則參數（規則閉包以數字索引存取）.
     *
     * @return array<string, mixed>
     */
    private function params(string ...$values): array
    {
        $params = [];
        foreach ($values as $index => $value) {
            // 以字串鍵儲存，PHP 陣列會自動將數字字串鍵視為整數供規則閉包存取
            $params[(string) $index] = $value;
        }

        return $params;
    }

    #[Test]
    public function test_post_status規則(): void
    {
        $this->assertTrue($this->validator->checkRule('draft', 'post_status'));
        $this->assertTrue($this->validator->checkRule('published', 'post_status'));
        $this->assertTrue($this->validator->checkRule('archived', 'post_status'));
        // 空值允許通過，由 required 規則處理
        $this->assertTrue($this->validator->checkRule(null, 'post_status'));
        $this->assertTrue($this->validator->checkRule('', 'post_status'));

        $this->assertFalse($this->validator->checkRule('bogus', 'post_status'));
        $this->assertFalse($this->validator->checkRule(123, 'post_status'));
    }

    #[Test]
    public function test_rfc3339_datetime規則支援多種格式(): void
    {
        $this->assertTrue($this->validator->checkRule('2026-08-23T10:30:00+00:00', 'rfc3339_datetime'));
        // RFC3339_EXTENDED 為毫秒精度（3 位數）
        $this->assertTrue($this->validator->checkRule('2026-08-23T10:30:00.123+08:00', 'rfc3339_datetime'));
        $this->assertTrue($this->validator->checkRule('2026-08-23T10:30:00Z', 'rfc3339_datetime'));
        $this->assertTrue($this->validator->checkRule(null, 'rfc3339_datetime'));
        $this->assertTrue($this->validator->checkRule('', 'rfc3339_datetime'));

        // 微秒（6 位數）符合 Y-m-d\TH:i:s.uP 格式
        $this->assertTrue($this->validator->checkRule('2026-08-23T10:30:00.123456+08:00', 'rfc3339_datetime'));
        // 位數非 6 的微秒會因 round-trip 比對失敗
        $this->assertFalse($this->validator->checkRule('2026-08-23T10:30:00.12+08:00', 'rfc3339_datetime'));
        $this->assertFalse($this->validator->checkRule('2026/08/23 10:30:00', 'rfc3339_datetime'));
        $this->assertFalse($this->validator->checkRule('not-a-date', 'rfc3339_datetime'));
        $this->assertFalse($this->validator->checkRule(123, 'rfc3339_datetime'));
    }

    #[Test]
    public function test_post_title規則去除HTML後檢查長度(): void
    {
        $this->assertTrue($this->validator->checkRule('<p>有效標題</p>', 'post_title', $this->params('1', '255')));
        $this->assertTrue($this->validator->checkRule('A', 'post_title', $this->params('1', '255')));

        // 去除 HTML 後為空
        $this->assertFalse($this->validator->checkRule('<br>', 'post_title', $this->params('1', '255')));
        $this->assertFalse($this->validator->checkRule('', 'post_title', $this->params('1', '255')));
        // 超過最大長度
        $this->assertFalse($this->validator->checkRule(str_repeat('長', 256), 'post_title', $this->params('1', '255')));
        // 非字串型別
        $this->assertFalse($this->validator->checkRule(999, 'post_title', $this->params('1', '255')));
    }

    #[Test]
    public function test_post_content規則支援選填上限(): void
    {
        $this->assertTrue($this->validator->checkRule('<p>這是內容</p>', 'post_content', $this->params('1')));
        $this->assertTrue($this->validator->checkRule(str_repeat('文', 5000), 'post_content', $this->params('1')));

        // 去除標籤後為空字串
        $this->assertFalse($this->validator->checkRule('<br><br>', 'post_content', $this->params('1')));
        $this->assertFalse($this->validator->checkRule('太短超過限制的內容', 'post_content', $this->params('100')));
        $this->assertFalse($this->validator->checkRule(str_repeat('文', 11), 'post_content', $this->params('1', '10')));
        $this->assertFalse($this->validator->checkRule(null, 'post_content', $this->params('1')));
    }

    #[Test]
    public function test_user_id規則必須為正整數(): void
    {
        $this->assertTrue($this->validator->checkRule(1, 'user_id'));
        $this->assertTrue($this->validator->checkRule('42', 'user_id'));

        $this->assertFalse($this->validator->checkRule(0, 'user_id'));
        $this->assertFalse($this->validator->checkRule(-5, 'user_id'));
        $this->assertFalse($this->validator->checkRule('abc', 'user_id'));
        $this->assertFalse($this->validator->checkRule(null, 'user_id'));
        $this->assertFalse($this->validator->checkRule('', 'user_id'));
    }

    #[Test]
    public function test_ip_address規則支援版本限制(): void
    {
        $this->assertTrue($this->validator->checkRule('192.168.1.1', 'ip_address'));
        $this->assertTrue($this->validator->checkRule('2001:db8::1', 'ip_address'));

        $this->assertFalse($this->validator->checkRule('not-an-ip', 'ip_address'));
        $this->assertFalse($this->validator->checkRule(12345, 'ip_address'));
        // 版本限制
        $this->assertTrue($this->validator->checkRule('10.0.0.1', 'ip_address', $this->params('ipv4')));
        $this->assertFalse($this->validator->checkRule('2001:db8::1', 'ip_address', $this->params('ipv4')));
        $this->assertTrue($this->validator->checkRule('fe80::1', 'ip_address', $this->params('ipv6')));
        $this->assertFalse($this->validator->checkRule('192.168.1.1', 'ip_address', $this->params('ipv6')));
    }

    #[Test]
    public function test_publish_date_future規則草稿允許過去日期(): void
    {
        $pastDate = '2020-01-01T00:00:00+00:00';
        $futureDate = new DateTime('+1 day')->format(DateTime::RFC3339);

        // 空值允許通過
        $this->assertTrue($this->validator->checkRule(null, 'publish_date_future'));
        $this->assertTrue($this->validator->checkRule('', 'publish_date_future'));

        // 格式錯誤
        $this->assertFalse($this->validator->checkRule('31/01/2020', 'publish_date_future'));
        $this->assertFalse($this->validator->checkRule(12345, 'publish_date_future'));

        // 草稿狀態允許任何日期
        $this->assertTrue($this->validator->checkRule($pastDate, 'publish_date_future', $this->params('draft')));

        // 非草稿：過去日期不允許、未來日期允許
        $this->assertFalse($this->validator->checkRule($pastDate, 'publish_date_future', $this->params('published')));
        $this->assertTrue($this->validator->checkRule($futureDate, 'publish_date_future', $this->params('published')));
    }

    #[Test]
    public function test_getCreatePostRules回傳建立規則(): void
    {
        /** @var array<string, string> $rules */
        $rules = PostValidator::getCreatePostRules();

        $this->assertSame('required|post_title:1,255', $rules['title']);
        $this->assertSame('required|post_content:1', $rules['content']);
        $this->assertSame('required|user_id', $rules['user_id']);
        $this->assertSame('required|ip_address', $rules['user_ip']);
        $this->assertSame('boolean', $rules['is_pinned']);
        $this->assertSame('post_status', $rules['status']);
        $this->assertSame('rfc3339_datetime', $rules['publish_date']);
    }

    #[Test]
    public function test_getUpdatePostRules回傳更新規則(): void
    {
        /** @var array<string, string> $rules */
        $rules = PostValidator::getUpdatePostRules();

        $this->assertArrayNotHasKey('user_id', $rules);
        $this->assertArrayNotHasKey('user_ip', $rules);
        $this->assertSame('post_title:1,255', $rules['title']);
        $this->assertSame('post_content:1', $rules['content']);
    }

    #[Test]
    public function test_getDynamicUpdateRules只保留提供的欄位(): void
    {
        /** @var array<string, string> $rules */
        $rules = PostValidator::getDynamicUpdateRules([
            'title'         => '新標題',
            'unknown_field' => '忽略',
            'status'        => 'draft',
        ]);

        $this->assertSame(['title', 'status'], array_keys($rules));
        $this->assertSame('post_title:1,255', $rules['title']);
    }

    #[Test]
    public function test_validatePostData建立成功與失敗(): void
    {
        $validData = [
            'title'     => '<p>正常標題</p>',
            'content'   => '<p>這是一段正常的內容</p>',
            'user_id'   => 5,
            'user_ip'   => '127.0.0.1',
            'is_pinned' => false,
            'status'    => 'draft',
        ];

        $result = $this->validator->validatePostData($validData);

        $this->assertTrue($result->isValid());
        /** @var array<string, mixed> $validatedData */
        $validatedData = $result->getValidatedData();
        $this->assertSame(5, $validatedData['user_id']);

        $invalidResult = $this->validator->validatePostData([
            'title'   => '',
            'content' => '<p>內容</p>',
            'user_id' => -3,
            'user_ip' => 'invalid-ip',
        ]);

        $this->assertTrue($invalidResult->isInvalid());
        /** @var array<string, array<int, string>> $errors */
        $errors = $invalidResult->getErrors();
        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('user_id', $errors);
        $this->assertArrayHasKey('user_ip', $errors);
        // 自訂錯誤訊息已註冊
        $this->assertSame('使用者 ID 必須是正整數', $errors['user_id'][0]);
    }

    #[Test]
    public function test_validatePostData更新模式提供完整欄位時通過(): void
    {
        $result = $this->validator->validatePostData([
            'title'        => '更新後標題',
            'content'      => '<p>更新後內容</p>',
            'is_pinned'    => true,
            'status'       => 'published',
            'publish_date' => '2026-12-31T00:00:00+00:00',
        ], true);

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function test_validatePostData更新模式空資料會失敗(): void
    {
        // 更新規則的 title/content/is_pinned 不允許 null，空資料將驗證失敗；
        // 動態規則（getDynamicUpdateRules）即為解決此情境而存在
        $result = $this->validator->validatePostData([], true);

        $this->assertTrue($result->isInvalid());
    }
}
