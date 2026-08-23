<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Exceptions;

use App\Shared\Exceptions\Validation\RequestValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tests\Support\UnitTestCase;

/**
 * RequestValidationException 測試.
 */
#[CoversClass(RequestValidationException::class)]
class RequestValidationExceptionTest extends UnitTestCase
{
    /**
     * 驗證例外訊息與欄位錯誤.
     *
     * @param array<string, mixed> $expectedErrors
     */
    private function assertException(
        RequestValidationException $exception,
        string $expectedMessage,
        array $expectedErrors = [],
    ): void {
        $this->assertSame($expectedMessage, $exception->getMessage());
        if ($expectedErrors === []) {
            return;
        }
        foreach ($expectedErrors as $field => $messages) {
            /** @var array<int, string> $castMessages */
            $castMessages = is_array($messages) ? $messages : [$messages];
            foreach ($castMessages as $index => $message) {
                $errors = $exception->getErrors();
                $this->assertArrayHasKey($field, $errors);
                /** @var array<int, string> $fieldErrors */
                $fieldErrors = $errors[$field];
                $this->assertSame($message, $fieldErrors[$index]);
            }
        }
    }

    #[Test]
    public function it_uses_default_message_when_only_errors_given(): void
    {
        $exception = new RequestValidationException('', ['name' => '名稱必填']);

        $this->assertException(
            $exception,
            '請求資料驗證失敗',
            ['name' => ['名稱必填']],
        );
        $this->assertFalse($exception->getValidationResult()->isValid());
    }

    #[Test]
    public function it_wraps_message_into_request_field_when_no_errors(): void
    {
        $exception = new RequestValidationException('請求內容不完整');

        $this->assertException(
            $exception,
            '請求內容不完整',
            ['request' => ['請求內容不完整']],
        );
    }

    #[Test]
    public function it_normalizes_scalar_errors_to_arrays(): void
    {
        $exception = new RequestValidationException('驗證失敗', [
            'email' => ['格式錯誤'],
            'age'   => '必須為數字',
        ]);

        $this->assertException($exception, '驗證失敗', [
            'email' => ['格式錯誤'],
            'age'   => ['必須為數字'],
        ]);
    }

    #[Test]
    public function invalid_json_factory_sets_fixed_message(): void
    {
        $exception = RequestValidationException::invalidJson();

        $this->assertException(
            $exception,
            '請求資料格式錯誤，必須為有效的 JSON 格式',
            ['request' => ['請求資料格式錯誤，必須為有效的 JSON 格式']],
        );
    }

    #[Test]
    public function missing_required_fields_factory_lists_each_field(): void
    {
        $exception = RequestValidationException::missingRequiredFields(['title', 'content']);

        $this->assertException($exception, '缺少必要欄位', [
            'title'   => ["欄位 'title' 為必填項目"],
            'content' => ["欄位 'content' 為必填項目"],
        ]);
    }

    #[Test]
    public function invalid_field_type_factory_reports_actual_type(): void
    {
        $exception = RequestValidationException::invalidFieldType('count', 'integer', [1, 2]);

        $this->assertException($exception, '欄位類型錯誤', [
            'count' => ["欄位 'count' 應為 integer 類型，實際為 array"],
        ]);
    }

    #[Test]
    public function field_length_factories_report_lengths(): void
    {
        $tooLong = RequestValidationException::fieldTooLong('nickname', 20, 35);
        $tooShort = RequestValidationException::fieldTooShort('password', 8, 3);

        $this->assertException($tooLong, '欄位長度超出限制', [
            'nickname' => ["欄位 'nickname' 長度不能超過 20 個字元，目前為 35 個字元"],
        ]);
        $this->assertException($tooShort, '欄位長度不足', [
            'password' => ["欄位 'password' 長度不能少於 8 個字元，目前為 3 個字元"],
        ]);
    }

    #[Test]
    public function email_and_url_factories_report_invalid_values(): void
    {
        $email = RequestValidationException::invalidEmail('email', 'not-an-email');
        $url = RequestValidationException::invalidUrl('website', 'ftp://bad');

        $this->assertException($email, '電子郵件格式錯誤', [
            'email' => ["'not-an-email' 不是有效的電子郵件格式"],
        ]);
        $this->assertException($url, 'URL 格式錯誤', [
            'website' => ["'ftp://bad' 不是有效的 URL 格式"],
        ]);
    }

    #[Test]
    public function invalid_date_factory_handles_scalar_and_non_scalar(): void
    {
        $scalar = RequestValidationException::invalidDate('published_at', '2026-13-99');
        $nonScalar = RequestValidationException::invalidDate('published_at', ['year' => 2026]);

        $this->assertException($scalar, '日期格式錯誤', [
            'published_at' => ["'2026-13-99' 不是有效的日期格式"],
        ]);
        $this->assertException($nonScalar, '日期格式錯誤', [
            'published_at' => ["'array' 不是有效的日期格式"],
        ]);
    }

    #[Test]
    public function value_not_in_list_factory_lists_allowed_values(): void
    {
        $exception = RequestValidationException::valueNotInList('status', 'paused', ['active', 'archived']);
        $objectValue = RequestValidationException::valueNotInList('status', new stdClass(), ['a', null]);

        $this->assertException($exception, '欄位值不在允許範圍內', [
            'status' => ["'paused' 不在允許的值清單中：active, archived"],
        ]);
        $this->assertException($objectValue, '欄位值不在允許範圍內', [
            'status' => ["'{}' 不在允許的值清單中：a, "],
        ]);
    }

    #[Test]
    public function numeric_range_error_factory_variants(): void
    {
        $both = RequestValidationException::numericRangeError('price', 9999, 1, 100);
        $minOnly = RequestValidationException::numericRangeError('price', 0, min: 1);
        $maxOnly = RequestValidationException::numericRangeError('price', 5000, max: 100);

        $this->assertException($both, '數值範圍錯誤', [
            'price' => ["欄位 'price' 的值 '9999' 超出允許範圍（範圍：1 - 100）"],
        ]);
        $this->assertException($minOnly, '數值範圍錯誤', [
            'price' => ["欄位 'price' 的值 '0' 超出允許範圍（最小值：1）"],
        ]);
        $this->assertException($maxOnly, '數值範圍錯誤', [
            'price' => ["欄位 'price' 的值 '5000' 超出允許範圍（最大值：100）"],
        ]);
    }

    #[Test]
    public function duplicate_value_and_custom_validation_factories(): void
    {
        $duplicate = RequestValidationException::duplicateValue('username', 'cookey');
        $custom = RequestValidationException::customValidation(['tags' => ['標籤過多']]);

        $this->assertException($duplicate, '值重複', [
            'username' => ["值 'cookey' 已存在，不能重複"],
        ]);
        $this->assertException($custom, '自定義驗證失敗', [
            'tags' => ['標籤過多'],
        ]);
    }

    #[Test]
    public function file_validation_factories_report_details(): void
    {
        $type = RequestValidationException::invalidFileType('file', 'image/bmp', ['image/png', 'image/jpeg']);
        $size = RequestValidationException::fileTooLarge('file', 15 * 1024 * 1024, 10 * 1024 * 1024);

        $this->assertException($type, '檔案類型不支援', [
            'file' => ["檔案類型 'image/bmp' 不被支援，允許的類型：image/png, image/jpeg"],
        ]);
        $this->assertException($size, '檔案大小超出限制', [
            'file' => ['檔案大小 15MB 超過限制 10MB'],
        ]);
    }
}
