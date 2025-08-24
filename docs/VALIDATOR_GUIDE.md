# AlleyNote 驗證器使用指南

**版本**: v2.0  
**適用範圍**: AlleyNote 專案開發者

---

## 📋 目錄

1. [概述](#概述)
2. [驗證器架構](#驗證器架構)
3. [基本使用方法](#基本使用方法)
4. [內建驗證規則](#內建驗證規則)
5. [自定義驗證規則](#自定義驗證規則)
6. [錯誤訊息處理](#錯誤訊息處理)
7. [DTO 整合](#dto-整合)
8. [進階用法](#進階用法)
9. [最佳實踐](#最佳實踐)
10. [故障排除](#故障排除)

---

## 概述

AlleyNote 使用自建的驗證器系統來確保所有輸入資料的正確性和安全性。驗證器提供了豐富的內建規則、靈活的自定義規則機制，以及完整的繁體中文錯誤訊息支援。

### 主要特色

- ✅ **29 種內建驗證規則**: 涵蓋常見的資料驗證需求
- ✅ **繁體中文支援**: 完整的繁體中文錯誤訊息
- ✅ **自定義規則**: 支援註冊自定義驗證規則
- ✅ **鏈式驗證**: 支援多個規則鏈式驗證
- ✅ **條件式驗證**: 支援 `sometimes` 和 `nullable` 條件
- ✅ **檔案驗證**: 專門的檔案上傳驗證規則
- ✅ **資料庫驗證**: `unique` 和 `exists` 資料庫規則
- ✅ **型別安全**: 強型別驗證結果

---

## 驗證器架構

### 核心組件

```
ValidatorInterface
├── Validator (實作類別)
├── ValidationResult (驗證結果)
├── ValidationException (驗證例外)
└── ValidationRules (規則定義)
```

### 資料流

```
輸入資料 → Validator → ValidationRules → ValidationResult
    ↓
成功: 返回 ValidationResult (success = true)
失敗: 拋出 ValidationException 或返回 ValidationResult (success = false)
```

---

## 基本使用方法

### 1. 獲取驗證器實例

```php
<?php
// 方式一：透過 DI 容器
use AlleyNote\Validation\ValidatorInterface;

class SomeService 
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}
}

// 方式二：直接建立（不推薦）
use AlleyNote\Validation\Validator;

$validator = new Validator();
```

### 2. 基本驗證

```php
<?php
// 驗證單一欄位
$result = $validator->validate('email', 'user@example.com', ['required', 'email']);

if ($result->isValid()) {
    echo "驗證成功！";
} else {
    foreach ($result->getErrors() as $error) {
        echo $error . "\n";
    }
}

// 驗證多個欄位
$data = [
    'title' => 'AlleyNote 文章',
    'content' => '這是文章內容...',
    'author_id' => 123
];

$rules = [
    'title' => ['required', 'string', 'min_length:5', 'max_length:255'],
    'content' => ['required', 'string', 'min_length:10'],
    'author_id' => ['required', 'integer', 'min:1']
];

$result = $validator->validateData($data, $rules);
```

### 3. 拋出例外的驗證

```php
<?php
try {
    $validator->validateOrThrow('email', $email, ['required', 'email']);
    // 驗證通過，繼續執行
} catch (ValidationException $e) {
    // 處理驗證錯誤
    $errors = $e->getErrors();
    foreach ($errors as $field => $messages) {
        echo "{$field}: " . implode(', ', $messages) . "\n";
    }
}
```

---

## 內建驗證規則

### 基本規則

| 規則 | 說明 | 範例 | 錯誤訊息 |
|------|------|------|----------|
| `required` | 必填欄位 | `['required']` | "此欄位為必填" |
| `string` | 字串型別 | `['string']` | "必須為字串" |
| `integer` | 整數型別 | `['integer']` | "必須為整數" |
| `numeric` | 數值型別 | `['numeric']` | "必須為數值" |
| `boolean` | 布林型別 | `['boolean']` | "必須為布林值" |
| `array` | 陣列型別 | `['array']` | "必須為陣列" |

### 字串規則

| 規則 | 說明 | 範例 | 錯誤訊息 |
|------|------|------|----------|
| `min_length:n` | 最短長度 | `['min_length:5']` | "最少需要 5 個字元" |
| `max_length:n` | 最長長度 | `['max_length:255']` | "最多只能 255 個字元" |
| `alpha` | 只能是字母 | `['alpha']` | "只能包含字母" |
| `alpha_numeric` | 字母和數字 | `['alpha_numeric']` | "只能包含字母和數字" |
| `regex:pattern` | 正規表達式 | `['regex:/^[A-Z]/']` | "格式不正確" |

### 數值規則

| 規則 | 說明 | 範例 | 錯誤訊息 |
|------|------|------|----------|
| `min:n` | 最小值 | `['min:18']` | "不能小於 18" |
| `max:n` | 最大值 | `['max:100']` | "不能大於 100" |
| `in:val1,val2` | 在指定值中 | `['in:admin,user,guest']` | "必須為 admin, user, guest 其中之一" |
| `not_in:val1,val2` | 不在指定值中 | `['not_in:banned,deleted']` | "不能為 banned, deleted" |

### 格式規則

| 規則 | 說明 | 範例 | 錯誤訊息 |
|------|------|------|----------|
| `email` | 電子郵件格式 | `['email']` | "請輸入有效的電子郵件地址" |
| `url` | URL 格式 | `['url']` | "請輸入有效的 URL" |
| `ip` | IP 位址格式 | `['ip']` | "請輸入有效的 IP 位址" |
| `date` | 日期格式 | `['date']` | "請輸入有效的日期" |
| `date_format:format` | 指定日期格式 | `['date_format:Y-m-d']` | "日期格式必須為 Y-m-d" |

### 日期規則

| 規則 | 說明 | 範例 | 錯誤訊息 |
|------|------|------|----------|
| `before:date` | 早於指定日期 | `['before:2025-12-31']` | "必須早於 2025-12-31" |
| `after:date` | 晚於指定日期 | `['after:2025-01-01']` | "必須晚於 2025-01-01" |

### 檔案規則

| 規則 | 說明 | 範例 | 錯誤訊息 |
|------|------|------|----------|
| `file_required` | 檔案必填 | `['file_required']` | "請選擇檔案" |
| `file_max_size:size` | 檔案大小限制 | `['file_max_size:2048']` | "檔案大小不能超過 2MB" |
| `file_mime_types:types` | 檔案類型限制 | `['file_mime_types:image/jpeg,image/png']` | "只允許 JPEG, PNG 格式" |

### 資料庫規則

| 規則 | 說明 | 範例 | 錯誤訊息 |
|------|------|------|----------|
| `unique:table,column` | 唯一性檢查 | `['unique:users,email']` | "此電子郵件已被使用" |
| `exists:table,column` | 存在性檢查 | `['exists:posts,id']` | "指定的文章不存在" |

### 條件規則

| 規則 | 說明 | 範例 | 用途 |
|------|------|------|------|
| `sometimes` | 條件式驗證 | `['sometimes', 'email']` | 只在欄位存在時驗證 |
| `nullable` | 允許空值 | `['nullable', 'string']` | 允許 null 值但型別仍需正確 |
| `confirmed` | 確認欄位 | `['confirmed']` | 需要 field_confirmation 欄位相符 |

---

## 自定義驗證規則

### 1. 註冊自定義規則

```php
<?php
// 簡單規則
$validator->addRule('phone', function ($value) {
    return preg_match('/^\+?[0-9]{10,15}$/', $value);
}, '請輸入有效的電話號碼');

// 帶參數的規則
$validator->addRule('min_age', function ($value, $minAge) {
    $birthDate = new DateTime($value);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    return $age >= $minAge;
}, '年齡必須至少 {minAge} 歲');

// 使用自定義規則
$result = $validator->validate('phone', '+886912345678', ['required', 'phone']);
$result = $validator->validate('birthday', '1990-01-01', ['required', 'date', 'min_age:18']);
```

### 2. 複雜自定義規則

```php
<?php
// 自定義規則類別
class CustomValidationRules 
{
    public static function registerAll(ValidatorInterface $validator): void 
    {
        // 台灣身分證號驗證
        $validator->addRule('taiwan_id', [self::class, 'validateTaiwanId'], '請輸入有效的身分證號');
        
        // 信用卡號驗證
        $validator->addRule('credit_card', [self::class, 'validateCreditCard'], '請輸入有效的信用卡號');
        
        // 強密碼驗證
        $validator->addRule('strong_password', [self::class, 'validateStrongPassword'], 
            '密碼必須包含大小寫字母、數字和特殊字元，且至少 8 個字元');
    }
    
    public static function validateTaiwanId(string $value): bool 
    {
        if (!preg_match('/^[A-Z][12][0-9]{8}$/', $value)) {
            return false;
        }
        
        // 台灣身分證號校驗邏輯
        $letters = 'ABCDEFGHJKLMNPQRSTUVXYWZIO';
        $letterValue = strpos($letters, $value[0]) + 10;
        
        $sum = intval($letterValue / 10) + (($letterValue % 10) * 9);
        
        for ($i = 1; $i <= 8; $i++) {
            $sum += intval($value[$i]) * (9 - $i);
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        return $checkDigit == intval($value[9]);
    }
    
    public static function validateCreditCard(string $value): bool 
    {
        // Luhn 演算法驗證信用卡號
        $value = preg_replace('/\D/', '', $value);
        
        if (strlen($value) < 13 || strlen($value) > 19) {
            return false;
        }
        
        $sum = 0;
        $alternate = false;
        
        for ($i = strlen($value) - 1; $i >= 0; $i--) {
            $n = intval($value[$i]);
            
            if ($alternate) {
                $n *= 2;
                if ($n > 9) {
                    $n = ($n % 10) + 1;
                }
            }
            
            $sum += $n;
            $alternate = !$alternate;
        }
        
        return ($sum % 10) == 0;
    }
    
    public static function validateStrongPassword(string $value): bool 
    {
        return strlen($value) >= 8 &&
               preg_match('/[a-z]/', $value) &&
               preg_match('/[A-Z]/', $value) &&
               preg_match('/[0-9]/', $value) &&
               preg_match('/[^a-zA-Z0-9]/', $value);
    }
}

// 註冊自定義規則
CustomValidationRules::registerAll($validator);
```

### 3. 依賴其他服務的規則

```php
<?php
// 需要資料庫查詢的規則
$validator->addRule('post_belongs_to_user', function ($postId, $userId) use ($postRepository) {
    $post = $postRepository->findById($postId);
    return $post && $post->getUserId() === $userId;
}, '文章不屬於該使用者');

// 使用
$result = $validator->validate('post_id', 123, ['post_belongs_to_user:' . $currentUserId]);
```

---

## 錯誤訊息處理

### 1. 預設錯誤訊息

```php
<?php
// 驗證器已內建繁體中文錯誤訊息
$validator = new Validator();

// 預設訊息範例
$result = $validator->validate('email', 'invalid-email', ['email']);
echo $result->getFirstError(); // "請輸入有效的電子郵件地址"
```

### 2. 自定義錯誤訊息

```php
<?php
// 全域設定錯誤訊息
$validator->setErrorMessages([
    'required' => '這個欄位不能空白',
    'email' => '電子郵件格式錯誤',
    'min_length' => '至少要輸入 {min} 個字',
]);

// 針對特定欄位設定訊息
$customMessages = [
    'title.required' => '文章標題為必填項目',
    'title.max_length' => '文章標題不能超過 255 個字元',
    'content.required' => '文章內容不能為空',
];

$result = $validator->validateData($data, $rules, $customMessages);
```

### 3. 動態錯誤訊息

```php
<?php
// 錯誤訊息支援參數替換
$validator->addRule('between', function ($value, $min, $max) {
    return $value >= $min && $value <= $max;
}, '數值必須介於 {min} 到 {max} 之間');

// 使用時會自動替換 {min} 和 {max}
$result = $validator->validate('age', 15, ['between:18,65']);
// 錯誤訊息: "數值必須介於 18 到 65 之間"
```

### 4. 多語言支援

```php
<?php
class MultiLanguageValidator 
{
    private array $messages = [
        'zh-TW' => [
            'required' => '此欄位為必填',
            'email' => '請輸入有效的電子郵件地址',
        ],
        'en' => [
            'required' => 'This field is required',
            'email' => 'Please enter a valid email address',
        ],
    ];
    
    public function setLanguage(string $lang): void 
    {
        if (isset($this->messages[$lang])) {
            $this->validator->setErrorMessages($this->messages[$lang]);
        }
    }
}
```

---

## DTO 整合

### 1. BaseDTO 整合

```php
<?php
namespace AlleyNote\DTO;

use AlleyNote\Validation\ValidatorInterface;
use AlleyNote\Validation\ValidationException;

abstract class BaseDTO 
{
    protected array $data;
    protected ValidatorInterface $validator;
    
    public function __construct(array $data, ValidatorInterface $validator) 
    {
        $this->validator = $validator;
        $this->data = $data;
        $this->validate();
    }
    
    abstract protected function rules(): array;
    
    protected function validate(): void 
    {
        $result = $this->validator->validateData($this->data, $this->rules());
        
        if (!$result->isValid()) {
            throw new ValidationException($result->getErrors());
        }
    }
    
    protected function get(string $key, $default = null) 
    {
        return $this->data[$key] ?? $default;
    }
}
```

### 2. 具體 DTO 實作

```php
<?php
namespace AlleyNote\DTO;

class CreatePostDTO extends BaseDTO 
{
    protected function rules(): array 
    {
        return [
            'title' => ['required', 'string', 'min_length:5', 'max_length:255'],
            'content' => ['required', 'string', 'min_length:10'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'tags' => ['sometimes', 'array'],
            'is_published' => ['sometimes', 'boolean'],
            'publish_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ];
    }
    
    public function getTitle(): string 
    {
        return $this->get('title');
    }
    
    public function getContent(): string 
    {
        return $this->get('content');
    }
    
    public function getCategoryId(): ?int 
    {
        return $this->get('category_id');
    }
    
    public function getTags(): array 
    {
        return $this->get('tags', []);
    }
    
    public function isPublished(): bool 
    {
        return $this->get('is_published', false);
    }
    
    public function getPublishAt(): ?string 
    {
        return $this->get('publish_at');
    }
}
```

### 3. 檔案上傳 DTO

```php
<?php
namespace AlleyNote\DTO;

class CreateAttachmentDTO extends BaseDTO 
{
    protected function rules(): array 
    {
        return [
            'file' => ['file_required', 'file_max_size:10240', 'file_mime_types:image/jpeg,image/png,image/gif,application/pdf'],
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'description' => ['sometimes', 'string', 'max_length:500'],
        ];
    }
    
    public function getFile(): array 
    {
        return $this->get('file');
    }
    
    public function getPostId(): int 
    {
        return $this->get('post_id');
    }
    
    public function getDescription(): ?string 
    {
        return $this->get('description');
    }
}
```

### 4. 部分更新 DTO

```php
<?php
namespace AlleyNote\DTO;

class UpdatePostDTO extends BaseDTO 
{
    protected function rules(): array 
    {
        return [
            'title' => ['sometimes', 'string', 'min_length:5', 'max_length:255'],
            'content' => ['sometimes', 'string', 'min_length:10'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'tags' => ['sometimes', 'array'],
            'is_published' => ['sometimes', 'boolean'],
            'publish_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
    
    public function hasTitle(): bool 
    {
        return isset($this->data['title']);
    }
    
    public function hasContent(): bool 
    {
        return isset($this->data['content']);
    }
    
    // ... 其他方法
}
```

---

## 進階用法

### 1. 條件式驗證

```php
<?php
// 根據其他欄位的值決定驗證規則
$validator->addRule('required_if', function ($value, $field, $expectedValue) use ($data) {
    if (isset($data[$field]) && $data[$field] == $expectedValue) {
        return !empty($value);
    }
    return true;
}, '當 {field} 為 {expectedValue} 時此欄位為必填');

// 使用範例
$rules = [
    'payment_method' => ['required', 'in:credit_card,bank_transfer'],
    'credit_card_number' => ['required_if:payment_method,credit_card', 'credit_card'],
    'bank_account' => ['required_if:payment_method,bank_transfer'],
];
```

### 2. 巢狀陣列驗證

```php
<?php
// 驗證陣列中的每個元素
$validator->addRule('array_elements', function ($value, $rules) use ($validator) {
    if (!is_array($value)) {
        return false;
    }
    
    foreach ($value as $item) {
        $result = $validator->validateData($item, $rules);
        if (!$result->isValid()) {
            return false;
        }
    }
    
    return true;
}, '陣列元素驗證失敗');

// 使用範例
$rules = [
    'users' => ['required', 'array'],
    'users.*' => ['array_elements:name=required|email=required,email'],
];
```

### 3. 批次驗證

```php
<?php
class BatchValidator 
{
    public function __construct(private ValidatorInterface $validator) {}
    
    public function validateBatch(array $items, array $rules): array 
    {
        $results = [];
        $errors = [];
        
        foreach ($items as $index => $item) {
            try {
                $result = $this->validator->validateData($item, $rules);
                
                if ($result->isValid()) {
                    $results[$index] = $item;
                } else {
                    $errors[$index] = $result->getErrors();
                }
            } catch (ValidationException $e) {
                $errors[$index] = $e->getErrors();
            }
        }
        
        return [
            'valid' => $results,
            'invalid' => $errors,
            'total' => count($items),
            'valid_count' => count($results),
            'invalid_count' => count($errors),
        ];
    }
}
```

---

## 最佳實踐

### 1. 驗證規則組織

```php
<?php
// ✅ 好的做法 - 將相關規則組合
class PostValidationRules 
{
    public static function title(): array 
    {
        return ['required', 'string', 'min_length:5', 'max_length:255'];
    }
    
    public static function content(): array 
    {
        return ['required', 'string', 'min_length:10'];
    }
    
    public static function category(): array 
    {
        return ['sometimes', 'integer', 'exists:categories,id'];
    }
    
    public static function createRules(): array 
    {
        return [
            'title' => self::title(),
            'content' => self::content(),
            'category_id' => self::category(),
        ];
    }
    
    public static function updateRules(): array 
    {
        return [
            'title' => ['sometimes'] + self::title(),
            'content' => ['sometimes'] + self::content(),
            'category_id' => self::category(),
        ];
    }
}
```

### 2. 錯誤處理

```php
<?php
// ✅ 統一的錯誤處理
class ValidationErrorHandler 
{
    public static function handleApiError(ValidationException $e): array 
    {
        return [
            'success' => false,
            'message' => '資料驗證失敗',
            'errors' => $e->getErrors(),
            'error_code' => 'VALIDATION_FAILED',
        ];
    }
    
    public static function handleFormError(ValidationException $e): array 
    {
        $errors = [];
        
        foreach ($e->getErrors() as $field => $messages) {
            $errors[$field] = is_array($messages) ? $messages[0] : $messages;
        }
        
        return $errors;
    }
}
```

### 3. 效能優化

```php
<?php
// ✅ 快取驗證規則
class CachedValidator implements ValidatorInterface 
{
    private array $ruleCache = [];
    
    public function __construct(
        private ValidatorInterface $validator,
        private CacheInterface $cache
    ) {}
    
    public function validateData(array $data, array $rules, array $messages = []): ValidationResult 
    {
        $cacheKey = 'validation_rules_' . md5(serialize($rules));
        
        if (!isset($this->ruleCache[$cacheKey])) {
            $this->ruleCache[$cacheKey] = $this->cache->get($cacheKey, $rules);
        }
        
        return $this->validator->validateData($data, $this->ruleCache[$cacheKey], $messages);
    }
}
```

### 4. 測試支援

```php
<?php
// ✅ 驗證測試輔助類別
class ValidationTestHelper 
{
    public static function assertValidationPasses(ValidatorInterface $validator, array $data, array $rules): void 
    {
        $result = $validator->validateData($data, $rules);
        assert($result->isValid(), 'Validation should pass but failed: ' . json_encode($result->getErrors()));
    }
    
    public static function assertValidationFails(ValidatorInterface $validator, array $data, array $rules, array $expectedErrors = []): void 
    {
        $result = $validator->validateData($data, $rules);
        assert(!$result->isValid(), 'Validation should fail but passed');
        
        if (!empty($expectedErrors)) {
            foreach ($expectedErrors as $field => $expectedMessage) {
                $errors = $result->getErrors();
                assert(isset($errors[$field]), "Expected error for field '{$field}' not found");
                
                if (is_string($expectedMessage)) {
                    assert(in_array($expectedMessage, $errors[$field]), "Expected error message not found");
                }
            }
        }
    }
}
```

---

## 故障排除

### 1. 常見問題

#### 驗證規則不生效

```php
<?php
// ❌ 問題：規則名稱錯誤
$rules = ['requried', 'email']; // 拼寫錯誤

// ✅ 解決：檢查規則名稱
$rules = ['required', 'email'];
```

#### 自定義規則參數問題

```php
<?php
// ❌ 問題：參數解析錯誤
$validator->addRule('between', function ($value, $min, $max) {
    // $min 和 $max 可能是字串
    return $value >= $min && $value <= $max;
});

// ✅ 解決：正確處理參數型別
$validator->addRule('between', function ($value, $min, $max) {
    $min = (int) $min;
    $max = (int) $max;
    return $value >= $min && $value <= $max;
});
```

#### 檔案驗證失敗

```php
<?php
// ❌ 問題：$_FILES 結構問題
$rules = ['file' => ['file_required']];
$validator->validateData($_POST, $rules); // 檔案在 $_FILES 中

// ✅ 解決：正確傳遞檔案資料
$data = array_merge($_POST, $_FILES);
$validator->validateData($data, $rules);
```

### 2. 除錯技巧

```php
<?php
// 啟用詳細錯誤訊息
$validator->setDebugMode(true);

// 檢查驗證過程
$validator->addRule('debug_rule', function ($value) {
    error_log("Validating value: " . var_export($value, true));
    return true;
});

// 檢查規則是否已註冊
$registeredRules = $validator->getRegisteredRules();
var_dump($registeredRules);
```

---

## 參考資源

### 專案文件
- [ARCHITECTURE_IMPROVEMENT_COMPLETION.md](ARCHITECTURE_IMPROVEMENT_COMPLETION.md)
- [DI_CONTAINER_GUIDE.md](DI_CONTAINER_GUIDE.md)
- [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md)

### 測試檔案
- `tests/Unit/Validation/ValidatorTest.php` - 驗證器單元測試
- `tests/Unit/Validation/ValidationResultTest.php` - 驗證結果測試
- `tests/Unit/Validation/ValidationExceptionTest.php` - 驗證例外測試
- `tests/Unit/DTO/DTOValidationTest.php` - DTO 驗證測試

### 相關類別
- `src/Validation/ValidatorInterface.php` - 驗證器介面
- `src/Validation/Validator.php` - 主要驗證器實作
- `src/Validation/ValidationResult.php` - 驗證結果
- `src/Validation/ValidationException.php` - 驗證例外

---

## 常見問題 FAQ

**Q: 如何驗證巢狀陣列？**
A: 使用 `array` 規則配合自定義規則來驗證陣列元素。

**Q: 如何實作條件式驗證？**
A: 使用 `sometimes` 規則或建立自定義的 `required_if` 規則。

**Q: 檔案上傳驗證失敗怎麼辦？**
A: 確保將 `$_FILES` 合併到驗證資料中，並使用正確的檔案驗證規則。

**Q: 如何自定義錯誤訊息？**
A: 使用 `setErrorMessages()` 方法設定全域訊息，或在驗證時傳入自定義訊息陣列。

**Q: 驗證效能如何優化？**
A: 使用快取機制快取驗證規則，避免重複解析複雜規則。

---

*文件版本: v2.0*  
*維護者: AlleyNote 開發團隊*