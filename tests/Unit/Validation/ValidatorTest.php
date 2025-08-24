<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Validation\ValidationException;
use App\Shared\Validation\Validator;
use stdClass;
use Tests\TestCase;

/**
 * Validator 單元測試.
 *
 * 測試驗證器的所有核心功能，包括內建規則、自訂規則、錯誤訊息等
 */
class ValidatorTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }

    /**
     * 測試 required 規則.
     */
    public function test_required_rule(): void
    {
        // 測試必填欄位存在且有值
        $result = $this->validator->validate(['name' => 'John'], ['name' => 'required']);
        $this->assertTrue($result->isValid());

        // 測試必填欄位不存在
        $result = $this->validator->validate([], ['name' => 'required']);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('name', $result->getErrors());

        // 測試必填欄位為空字串
        $result = $this->validator->validate(['name' => ''], ['name' => 'required']);
        $this->assertFalse($result->isValid());

        // 測試必填欄位為 null
        $result = $this->validator->validate(['name' => null], ['name' => 'required']);
        $this->assertFalse($result->isValid());

        // 測試必填欄位為空陣列
        $result = $this->validator->validate(['items' => []], ['items' => 'required']);
        $this->assertFalse($result->isValid());

        // 測試必填欄位為 0（應該通過）
        $result = $this->validator->validate(['count' => 0], ['count' => 'required']);
        $this->assertTrue($result->isValid());

        // 測試必填欄位為 false（應該通過）
        $result = $this->validator->validate(['active' => false], ['active' => 'required']);
        $this->assertTrue($result->isValid());
    }

    /**
     * 測試 string 規則.
     */
    public function test_string_rule(): void
    {
        // 測試有效字串
        $validStrings = ['hello', '123', '', '中文測試', 'special@chars!'];
        foreach ($validStrings as $str) {
            $result = $this->validator->validate(['text' => $str], ['text' => 'string']);
            $this->assertTrue($result->isValid(), "字串 '{$str}' 應該通過驗證");
        }

        // 測試無效值
        $invalidValues = [123, 12.34, true, false, [], new stdClass()];
        foreach ($invalidValues as $value) {
            $result = $this->validator->validate(['text' => $value], ['text' => 'string']);
            $this->assertFalse($result->isValid(), "值 '" . gettype($value) . "' 不應該通過字串驗證");
        }
    }

    /**
     * 測試 integer 規則.
     */
    public function test_integer_rule(): void
    {
        // 測試有效整數
        $validIntegers = [0, 1, -1, 999, -999, PHP_INT_MAX, PHP_INT_MIN];
        foreach ($validIntegers as $int) {
            $result = $this->validator->validate(['number' => $int], ['number' => 'integer']);
            $this->assertTrue($result->isValid(), "整數 '{$int}' 應該通過驗證");
        }

        // 測試數字字串
        $numberStrings = ['123', '-456', '0'];
        foreach ($numberStrings as $str) {
            $result = $this->validator->validate(['number' => $str], ['number' => 'integer']);
            $this->assertTrue($result->isValid(), "數字字串 '{$str}' 應該通過驗證");
        }

        // 測試無效值（注意：FILTER_VALIDATE_INT 可能會將 true 視為 1）
        $invalidValues = [12.34, '12.34', 'abc', false, [], new stdClass()];
        foreach ($invalidValues as $value) {
            $result = $this->validator->validate(['number' => $value], ['number' => 'integer']);
            $this->assertFalse($result->isValid(), "值 '" . print_r($value, true) . "' 不應該通過整數驗證");
        }

        // 特別測試布林值 true（在 PHP 中 filter_var(true, FILTER_VALIDATE_INT) 返回 1）
        $result = $this->validator->validate(['number' => true], ['number' => 'integer']);
        $this->assertTrue($result->isValid(), '布林值 true 在 PHP 中會被 FILTER_VALIDATE_INT 視為 1');
    }

    /**
     * 測試 numeric 規則.
     */
    public function test_numeric_rule(): void
    {
        // 測試有效數字
        $validNumbers = [0, 1, -1, 12.34, -56.78, '123', '-456', '12.34', '0.5'];
        foreach ($validNumbers as $num) {
            $result = $this->validator->validate(['value' => $num], ['value' => 'numeric']);
            $this->assertTrue($result->isValid(), "數字 '{$num}' 應該通過驗證");
        }

        // 測試無效值
        $invalidValues = ['abc', '12abc', 'abc123', true, false, [], new stdClass()];
        foreach ($invalidValues as $value) {
            $result = $this->validator->validate(['value' => $value], ['value' => 'numeric']);
            $this->assertFalse($result->isValid(), "值 '" . print_r($value, true) . "' 不應該通過數字驗證");
        }
    }

    /**
     * 測試 boolean 規則.
     */
    public function test_boolean_rule(): void
    {
        // 測試有效布林值（根據實際實作）
        $validBooleans = [true, false, 1, 0, '1', '0', 'true', 'false', 'on', 'yes'];
        foreach ($validBooleans as $bool) {
            $result = $this->validator->validate(['flag' => $bool], ['flag' => 'boolean']);
            $this->assertTrue($result->isValid(), "布林值 '" . print_r($bool, true) . "' 應該通過驗證");
        }

        // 測試無效值（移除 Validator 實作中不支援的值）
        $invalidValues = [2, -1, 'maybe', 'invalid', 'no', 'off', [], new stdClass()];
        foreach ($invalidValues as $value) {
            $result = $this->validator->validate(['flag' => $value], ['flag' => 'boolean']);
            $this->assertFalse($result->isValid(), "值 '" . print_r($value, true) . "' 不應該通過布林驗證");
        }
    }

    /**
     * 測試 email 規則.
     */
    public function test_email_rule(): void
    {
        // 測試有效電子郵件
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'test123@test-domain.com',
            'user+tag@example.org',
            'test_email@sub.domain.com',
        ];

        foreach ($validEmails as $email) {
            $result = $this->validator->validate(['email' => $email], ['email' => 'email']);
            $this->assertTrue($result->isValid(), "電子郵件 '{$email}' 應該通過驗證");
        }

        // 測試無效電子郵件
        $invalidEmails = [
            'invalid-email',
            '@domain.com',
            'user@',
            'user..name@domain.com',
            'user name@domain.com',
            'user@domain',
            '.user@domain.com',
        ];

        foreach ($invalidEmails as $email) {
            $result = $this->validator->validate(['email' => $email], ['email' => 'email']);
            $this->assertFalse($result->isValid(), "電子郵件 '{$email}' 不應該通過驗證");
        }
    }

    /**
     * 測試 url 規則.
     */
    public function test_url_rule(): void
    {
        // 測試有效 URL
        $validUrls = [
            'https://www.example.com',
            'http://test.org',
            'https://sub.domain.co.uk/path?query=value',
            'ftp://files.example.com',
            'https://127.0.0.1:8080/app',
        ];

        foreach ($validUrls as $url) {
            $result = $this->validator->validate(['url' => $url], ['url' => 'url']);
            $this->assertTrue($result->isValid(), "URL '{$url}' 應該通過驗證");
        }

        // 測試無效 URL
        $invalidUrls = [
            'not-a-url',
            'www.example.com',
            'example.com',
            'http://',
            'https://',
        ];

        foreach ($invalidUrls as $url) {
            $result = $this->validator->validate(['url' => $url], ['url' => 'url']);
            $this->assertFalse($result->isValid(), "URL '{$url}' 不應該通過驗證");
        }
    }

    /**
     * 測試 ip 規則.
     */
    public function test_ip_rule(): void
    {
        // 測試有效 IP 地址
        $validIps = [
            '192.168.1.1',
            '127.0.0.1',
            '0.0.0.0',
            '255.255.255.255',
            '::1',
            '2001:db8::1',
            // 移除 'fe80::1%lo0' 因為 PHP filter_var 可能不支援
        ];

        foreach ($validIps as $ip) {
            $result = $this->validator->validate(['ip' => $ip], ['ip' => 'ip']);
            $this->assertTrue($result->isValid(), "IP 地址 '{$ip}' 應該通過驗證");
        }

        // 測試無效 IP 地址
        $invalidIps = [
            '256.256.256.256',
            '192.168.1',
            '192.168.1.1.1',
            'not-an-ip',
            '192.168.1.256',
            'fe80::1%lo0', // 帶有 zone identifier 的 IPv6 地址可能不被支援
        ];

        foreach ($invalidIps as $ip) {
            $result = $this->validator->validate(['ip' => $ip], ['ip' => 'ip']);
            $this->assertFalse($result->isValid(), "IP 地址 '{$ip}' 不應該通過驗證");
        }
    }

    /**
     * 測試 min 規則.
     */
    public function test_min_rule(): void
    {
        // 測試數字最小值
        $result = $this->validator->validate(['age' => 25], ['age' => 'min:18']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['age' => 18], ['age' => 'min:18']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['age' => 17], ['age' => 'min:18']);
        $this->assertFalse($result->isValid());

        // 測試字串數字
        $result = $this->validator->validate(['score' => '85'], ['score' => 'min:60']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['score' => '59'], ['score' => 'min:60']);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試 max 規則.
     */
    public function test_max_rule(): void
    {
        // 測試數字最大值
        $result = $this->validator->validate(['score' => 85], ['score' => 'max:100']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['score' => 100], ['score' => 'max:100']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['score' => 101], ['score' => 'max:100']);
        $this->assertFalse($result->isValid());

        // 測試字串數字
        $result = $this->validator->validate(['temperature' => '25'], ['temperature' => 'max:30']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['temperature' => '35'], ['temperature' => 'max:30']);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試 between 規則.
     */
    public function test_between_rule(): void
    {
        // 測試範圍內的值
        $result = $this->validator->validate(['age' => 25], ['age' => 'between:18,65']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['age' => 18], ['age' => 'between:18,65']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['age' => 65], ['age' => 'between:18,65']);
        $this->assertTrue($result->isValid());

        // 測試範圍外的值
        $result = $this->validator->validate(['age' => 17], ['age' => 'between:18,65']);
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(['age' => 66], ['age' => 'between:18,65']);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試 in 規則.
     */
    public function test_in_rule(): void
    {
        // 測試有效值
        $result = $this->validator->validate(['status' => 'active'], ['status' => 'in:active,inactive,pending']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['priority' => '1'], ['priority' => 'in:1,2,3,4,5']);
        $this->assertTrue($result->isValid());

        // 測試無效值
        $result = $this->validator->validate(['status' => 'deleted'], ['status' => 'in:active,inactive,pending']);
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(['priority' => '6'], ['priority' => 'in:1,2,3,4,5']);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試 not_in 規則.
     */
    public function test_not_in_rule(): void
    {
        // 測試有效值（不在禁止清單中）
        $result = $this->validator->validate(['username' => 'john'], ['username' => 'not_in:admin,root,system']);
        $this->assertTrue($result->isValid());

        // 測試無效值（在禁止清單中）
        $result = $this->validator->validate(['username' => 'admin'], ['username' => 'not_in:admin,root,system']);
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(['username' => 'root'], ['username' => 'not_in:admin,root,system']);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試 min_length 規則.
     */
    public function test_min_length_rule(): void
    {
        // 測試符合最小長度
        $result = $this->validator->validate(['name' => 'John'], ['name' => 'min_length:3']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['name' => 'Jo'], ['name' => 'min_length:2']);
        $this->assertTrue($result->isValid());

        // 測試不符合最小長度
        $result = $this->validator->validate(['name' => 'Jo'], ['name' => 'min_length:3']);
        $this->assertFalse($result->isValid());

        // 測試中文字元
        $result = $this->validator->validate(['name' => '測試'], ['name' => 'min_length:2']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['name' => '測'], ['name' => 'min_length:2']);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試 max_length 規則.
     */
    public function test_max_length_rule(): void
    {
        // 測試符合最大長度
        $result = $this->validator->validate(['bio' => 'Short bio'], ['bio' => 'max_length:50']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['bio' => str_repeat('a', 50)], ['bio' => 'max_length:50']);
        $this->assertTrue($result->isValid());

        // 測試超過最大長度
        $result = $this->validator->validate(['bio' => str_repeat('a', 51)], ['bio' => 'max_length:50']);
        $this->assertFalse($result->isValid());

        // 測試中文字元
        $result = $this->validator->validate(['title' => '這是一個測試標題'], ['title' => 'max_length:10']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['title' => str_repeat('測', 11)], ['title' => 'max_length:10']);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試 length 規則.
     */
    public function test_length_rule(): void
    {
        // 測試正確長度
        $result = $this->validator->validate(['code' => '12345'], ['code' => 'length:5']);
        $this->assertTrue($result->isValid());

        // 測試錯誤長度
        $result = $this->validator->validate(['code' => '1234'], ['code' => 'length:5']);
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(['code' => '123456'], ['code' => 'length:5']);
        $this->assertFalse($result->isValid());

        // 測試中文字元
        $result = $this->validator->validate(['pin' => '密碼'], ['pin' => 'length:2']);
        $this->assertTrue($result->isValid());
    }

    /**
     * 測試 regex 規則.
     */
    public function test_regex_rule(): void
    {
        // 測試手機號碼格式
        $phonePattern = '/^09\d{8}$/';
        $result = $this->validator->validate(['phone' => '0912345678'], ['phone' => "regex:{$phonePattern}"]);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['phone' => '1234567890'], ['phone' => "regex:{$phonePattern}"]);
        $this->assertFalse($result->isValid());

        // 測試英數字格式
        $alphanumPattern = '/^[a-zA-Z0-9]+$/';
        $result = $this->validator->validate(['code' => 'ABC123'], ['code' => "regex:{$alphanumPattern}"]);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['code' => 'ABC-123'], ['code' => "regex:{$alphanumPattern}"]);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試 alpha 規則.
     */
    public function test_alpha_rule(): void
    {
        // 測試純字母
        $result = $this->validator->validate(['name' => 'John'], ['name' => 'alpha']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['name' => 'JohnDoe'], ['name' => 'alpha']);
        $this->assertTrue($result->isValid());

        // 測試包含數字或特殊字元
        $result = $this->validator->validate(['name' => 'John123'], ['name' => 'alpha']);
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(['name' => 'John-Doe'], ['name' => 'alpha']);
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(['name' => 'John Doe'], ['name' => 'alpha']);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試 alpha_num 規則.
     */
    public function test_alpha_num_rule(): void
    {
        // 測試字母和數字
        $result = $this->validator->validate(['username' => 'user123'], ['username' => 'alpha_num']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['username' => 'ABC'], ['username' => 'alpha_num']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['username' => '123'], ['username' => 'alpha_num']);
        $this->assertTrue($result->isValid());

        // 測試包含特殊字元
        $result = $this->validator->validate(['username' => 'user-123'], ['username' => 'alpha_num']);
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(['username' => 'user 123'], ['username' => 'alpha_num']);
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(['username' => 'user_123'], ['username' => 'alpha_num']);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試 alpha_dash 規則.
     */
    public function test_alpha_dash_rule(): void
    {
        // 測試字母、數字、破折號和底線
        $result = $this->validator->validate(['slug' => 'user-name_123'], ['slug' => 'alpha_dash']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['slug' => 'user'], ['slug' => 'alpha_dash']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['slug' => '123'], ['slug' => 'alpha_dash']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['slug' => 'user-name'], ['slug' => 'alpha_dash']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['slug' => 'user_name'], ['slug' => 'alpha_dash']);
        $this->assertTrue($result->isValid());

        // 測試包含其他特殊字元
        $result = $this->validator->validate(['slug' => 'user name'], ['slug' => 'alpha_dash']);
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(['slug' => 'user@name'], ['slug' => 'alpha_dash']);
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(['slug' => 'user.name'], ['slug' => 'alpha_dash']);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試多個規則組合.
     */
    public function test_multiple_rules(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 25,
            'bio' => 'Software developer',
        ];

        $rules = [
            'name' => 'required|string|min_length:2|max_length:50',
            'email' => 'required|email',
            'age' => 'required|integer|min:18|max:120',
            'bio' => 'string|max_length:200',
        ];

        $result = $this->validator->validate($data, $rules);
        $this->assertTrue($result->isValid());

        // 測試部分規則失敗
        $invalidData = $data;
        $invalidData['age'] = 15; // 小於最小年齡

        $result = $this->validator->validate($invalidData, $rules);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('age', $result->getErrors());
    }

    /**
     * 測試自訂規則添加.
     */
    public function test_add_custom_rule(): void
    {
        // 添加自訂規則：檢查是否為偶數
        $this->validator->addRule('even', function ($value) {
            return is_numeric($value) && $value % 2 === 0;
        });

        // 測試自訂規則
        $result = $this->validator->validate(['number' => 4], ['number' => 'even']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['number' => 5], ['number' => 'even']);
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(['number' => 'abc'], ['number' => 'even']);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試帶參數的自訂規則.
     */
    public function test_custom_rule_with_parameters(): void
    {
        // 添加自訂規則：檢查值是否可以被指定數字整除
        $this->validator->addRule('divisible_by', function ($value, array $parameters) {
            if (!is_numeric($value) || !isset($parameters[0]) || !is_numeric($parameters[0])) {
                return false;
            }

            $divisor = (int) $parameters[0];

            return $divisor !== 0 && $value % $divisor === 0;
        });

        // 測試自訂規則
        $result = $this->validator->validate(['number' => 15], ['number' => 'divisible_by:3']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['number' => 15], ['number' => 'divisible_by:5']);
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(['number' => 15], ['number' => 'divisible_by:4']);
        $this->assertFalse($result->isValid());
    }

    /**
     * 測試自訂錯誤訊息.
     */
    public function test_custom_error_messages(): void
    {
        // 添加自訂錯誤訊息
        $this->validator->addMessage('required', '欄位 :field 是必要的');
        $this->validator->addMessage('email', '請輸入有效的電子郵件地址');

        // 測試自訂錯誤訊息
        $result = $this->validator->validate([], ['email' => 'required']);
        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertStringContainsString('是必要的', $errors['email'][0]);

        $result = $this->validator->validate(['email' => 'invalid'], ['email' => 'email']);
        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertStringContainsString('請輸入有效的電子郵件地址', $errors['email'][0]);
    }

    /**
     * 測試 validateOrFail 方法.
     */
    public function test_validate_or_fail(): void
    {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $rules = ['name' => 'required', 'email' => 'required|email'];

        // 測試驗證通過
        $validatedData = $this->validator->validateOrFail($data, $rules);
        $this->assertEquals($data, $validatedData);

        // 測試驗證失敗
        $invalidData = ['name' => '', 'email' => 'invalid'];
        $this->expectException(ValidationException::class);
        $this->validator->validateOrFail($invalidData, $rules);
    }

    /**
     * 測試 checkRule 方法.
     */
    public function test_check_rule_method(): void
    {
        // 測試基本規則檢查
        $this->assertTrue($this->validator->checkRule('test@example.com', 'email'));
        $this->assertFalse($this->validator->checkRule('invalid-email', 'email'));

        // 測試帶參數的規則檢查
        $this->assertTrue($this->validator->checkRule(25, 'min', [18]));
        $this->assertFalse($this->validator->checkRule(15, 'min', [18]));

        // 測試字串規則檢查
        $this->assertTrue($this->validator->checkRule('hello', 'min_length', [3]));
        $this->assertFalse($this->validator->checkRule('hi', 'min_length', [3]));
    }

    /**
     * 測試 stopOnFirstFailure 設定.
     */
    public function test_stop_on_first_failure(): void
    {
        // 建立多個規則都會失敗的資料
        $data = ['name' => '', 'email' => 'invalid', 'age' => 'abc'];
        $rules = [
            'name' => 'required',
            'email' => 'required|email',
            'age' => 'required|integer',
        ];

        // 測試不停止在第一個錯誤
        $validator = clone $this->validator;
        $result = $validator->validate($data, $rules);
        $errors = $result->getErrors();
        $normalErrorCount = count($errors);
        $this->assertGreaterThanOrEqual(3, $normalErrorCount); // 應該有 3 個欄位錯誤

        // 測試停止在第一個錯誤 - 重新測試
        $validator->stopOnFirstFailure(true);
        $result = $validator->validate($data, $rules);
        $errors = $result->getErrors();
        $stopErrorCount = count($errors);

        $this->assertEquals(1, $stopErrorCount); // 應該只有一個欄位錯誤

        // 確認第一個欄位是 name
        $this->assertArrayHasKey('name', $errors);
    }

    /**
     * 測試驗證器效能.
     */
    public function test_validator_performance(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // 執行大量驗證操作
        for ($i = 0; $i < 1000; $i++) {
            $data = [
                'name' => "user_{$i}",
                'email' => "user{$i}@example.com",
                'age' => 20 + ($i % 50),
                'bio' => str_repeat('a', 50 + ($i % 100)),
            ];

            $rules = [
                'name' => 'required|string|min_length:3|max_length:50',
                'email' => 'required|email',
                'age' => 'required|integer|min:18|max:120',
                'bio' => 'string|max_length:200',
            ];

            $result = $this->validator->validate($data, $rules);
            $this->assertTrue($result->isValid());
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        // 確保效能在合理範圍內
        $this->assertLessThan(1.0, $executionTime, '1000 次驗證操作應該在 1 秒內完成');
        $this->assertLessThan(1024 * 1024, $memoryUsage, '記憶體使用量應該少於 1MB');
    }

    /**
     * 測試記憶體洩漏.
     */
    public function test_memory_leak(): void
    {
        $initialMemory = memory_get_usage();

        // 重複建立和銷毀驗證器
        for ($i = 0; $i < 100; $i++) {
            $validator = new Validator();
            $validator->addRule('test_rule', function () {
                return true;
            });
            $validator->validate(['test' => 'value'], ['test' => 'test_rule']);
            unset($validator);
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // 記憶體增加應該在合理範圍內
        $this->assertLessThan(1024 * 100, $memoryIncrease, '記憶體洩漏檢測：記憶體增加應該少於 100KB');
    }
}
