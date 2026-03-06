# 密碼安全功能文件

> 📚 AlleyNote 密碼安全系統完整說明

---

## 📋 目錄

- [功能概述](#功能概述)
- [密碼要求](#密碼要求)
- [API 端點](#api-端點)
- [實作架構](#實作架構)
- [使用指南](#使用指南)
- [測試資訊](#測試資訊)

---

## 功能概述

AlleyNote 實作了企業級密碼安全機制，強制使用者使用高強度密碼，有效防止帳號被破解。

### 核心特性

✅ **8 項密碼驗證規則**

- 最小長度 8 字元
- 必須包含大寫字母
- 必須包含小寫字母
- 必須包含數字
- 不能包含連續字母（abc, xyz）
- 不能全部相同字元（aaa, 111）
- 不能是常見弱密碼（10,000+ 黑名單）
- 不能包含使用者名稱或 email

✅ **前端密碼體驗**

- 即時密碼強度指示器
- 視覺化要求檢查清單
- 智能密碼生成器
- 密碼顯示/隱藏切換
- 複製到剪貼簿功能

✅ **安全保障**

- 10,000+ 常見弱密碼黑名單
- 3,000+ 常見英文單字檢查
- 密碼熵值計算
- 密碼強度評分（0-100）

---

## 密碼要求

### 基本要求

| 規則     | 說明            | 範例                         |
| -------- | --------------- | ---------------------------- |
| 最小長度 | 至少 8 個字元   | ❌ `Abc123` ✅ `Abc12345`    |
| 小寫字母 | 至少 1 個 (a-z) | ❌ `ABC12345` ✅ `Abc12345`  |
| 大寫字母 | 至少 1 個 (A-Z) | ❌ `abc12345` ✅ `Abc12345`  |
| 數字     | 至少 1 個 (0-9) | ❌ `Abcdefgh` ✅ `Abc12345`  |
| 特殊符號 | 建議但非必需    | ⚠️ `Abc12345` ✅ `Abc@12345` |

### 禁止的密碼模式

| 禁止類型   | 說明                   | 範例                            |
| ---------- | ---------------------- | ------------------------------- |
| 連續字母   | 連續的英文字母         | ❌ `Abc123abc` ❌ `Test123xyz`  |
| 相同字元   | 全部或大部分相同       | ❌ `Aaaa1111` ❌ `1111aaaa`     |
| 常見密碼   | 黑名單中的弱密碼       | ❌ `Password123` ❌ `Qwerty123` |
| 使用者資訊 | 包含使用者名稱或 email | ❌ `John123456` ❌ `john@test`  |
| 英文單字   | 常見英文單字           | ❌ `Strong123` ❌ `Secure999`   |

### 密碼強度評分

| 分數   | 等級   | 說明                   |
| ------ | ------ | ---------------------- |
| 0-30   | 非常弱 | 容易被破解，不建議使用 |
| 31-50  | 弱     | 可能被破解，建議加強   |
| 51-70  | 中等   | 基本安全，建議改善     |
| 71-90  | 強     | 較為安全               |
| 91-100 | 非常強 | 非常安全 ✅            |

---

## API 端點

### 密碼驗證 API

**端點**：`POST /api/auth/validate-password`

**用途**：即時驗證密碼強度，提供詳細的驗證結果與建議

**請求參數**：

```json
{
  "password": "Example#Pass123!",
  "username": "john_doe",
  "email": "john@example.com"
}
```

**回應格式**：

```json
{
  "success": true,
  "data": {
    "is_valid": true,
    "strength": {
      "score": 85,
      "level": "strong",
      "message": "密碼強度：強"
    },
    "checks": {
      "length": true,
      "uppercase": true,
      "lowercase": true,
      "number": true,
      "special_char": true,
      "not_common": true,
      "not_sequential": true,
      "not_repetitive": true,
      "no_user_info": true
    },
    "suggestions": ["建議加入更多特殊符號以提升安全性"]
  }
}
```

**錯誤回應**：

```json
{
  "success": false,
  "error": {
    "message": "密碼不符合安全要求",
    "details": ["密碼長度必須至少 8 個字元", "密碼必須包含至少一個數字"]
  }
}
```

---

## 實作架構

### 後端架構

```
backend/app/
├── Domains/Auth/
│   ├── Controllers/
│   │   └── PasswordValidationController.php  # 密碼驗證 API
│   ├── Services/
│   │   ├── PasswordSecurityService.php       # 密碼安全服務
│   │   └── PasswordManagementService.php     # 密碼管理服務
│   ├── Contracts/
│   │   └── PasswordSecurityServiceInterface.php
│   └── ValueObjects/
│       └── Password.php                       # 密碼值物件
│
├── Shared/
│   ├── Services/
│   │   └── PasswordValidationService.php     # 密碼驗證核心邏輯
│   └── ValueObjects/
│       └── SecurePassword.php                # 安全密碼值物件
│
└── Resources/
    └── data/
        ├── common-passwords.txt              # 10,000+ 弱密碼黑名單
        └── common-words.txt                  # 3,000+ 常見英文單字
```

### 前端架構

```
frontend/js/
├── utils/
│   ├── passwordValidator.js                  # 密碼驗證工具
│   └── passwordGenerator.js                  # 密碼生成器
│
├── components/
│   ├── PasswordStrengthIndicator.js         # 密碼強度指示器
│   └── PasswordRequirements.js              # 密碼要求檢查清單
│
└── pages/auth/
    ├── register.js                           # 註冊頁面
    └── changePassword.js                     # 修改密碼頁面
```

### 核心類別說明

#### 1. SecurePassword 值物件

**檔案**：`backend/app/Shared/ValueObjects/SecurePassword.php`

**功能**：

- 封裝密碼值與驗證邏輯
- 確保密碼符合所有安全要求
- 提供密碼強度評分

**範例**：

```php
use App\Shared\ValueObjects\SecurePassword;

try {
  $password = new SecurePassword('Example#Pass123!', 'john', 'john@test.com');
    echo "密碼強度：" . $password->getStrength();
} catch (InvalidPasswordException $e) {
    echo "密碼不符合要求：" . $e->getMessage();
}
```

#### 2. PasswordValidationService

**檔案**：`backend/app/Shared/Services/PasswordValidationService.php`

**功能**：

- 檢查常見弱密碼黑名單
- 檢查連續字元
- 檢查重複字元
- 計算密碼熵值
- 提供密碼建議

#### 3. PasswordSecurityService

**檔案**：`backend/app/Domains/Auth/Services/PasswordSecurityService.php`

**功能**：

- 密碼雜湊（bcrypt）
- 密碼驗證
- 密碼強度評估
- 密碼歷史記錄

---

## 使用指南

### 使用者端

**註冊時設定密碼**：

1. 訪問註冊頁面
2. 輸入密碼
3. 即時查看密碼強度指示器
4. 參考要求檢查清單
5. 使用密碼生成器（可選）
6. 完成註冊

**修改密碼**：

1. 登入帳號
2. 訪問「個人設定」
3. 選擇「修改密碼」
4. 輸入舊密碼
5. 輸入新密碼（需符合要求）
6. 確認修改

**使用密碼生成器**：

1. 點擊「生成安全密碼」按鈕
2. 系統自動生成 16 字元強密碼
3. 點擊「複製」按鈕
4. 將密碼貼上到密碼管理器

### 開發者端

**驗證密碼**：

```php
use App\Shared\Services\PasswordValidationService;

$service = new PasswordValidationService();
$result = $service->validate('Example#Pass123!', 'john', 'john@test.com');

if ($result['is_valid']) {
    echo "密碼強度：" . $result['strength']['score'];
} else {
    foreach ($result['errors'] as $error) {
        echo $error . "\n";
    }
}
```

**建立安全密碼**：

```php
use App\Shared\ValueObjects\SecurePassword;

$password = new SecurePassword(
  'Example#Pass123!',
    'john',        // 使用者名稱（可選）
    'john@test.com' // Email（可選）
);

$hashed = password_hash((string)$password, PASSWORD_BCRYPT);
```

**前端即時驗證**：

```javascript
import { PasswordValidator } from "./utils/passwordValidator.js";

const result = PasswordValidator.validate("Example#Pass123!", {
  username: "john",
  email: "john@test.com",
});

if (result.isValid) {
  console.log("密碼強度：", result.strength.score);
} else {
  console.log("錯誤：", result.errors);
}
```

---

## 測試資訊

### 單元測試

**測試檔案**：

- `backend/tests/Unit/ValueObjects/SecurePasswordTest.php`
- `backend/tests/Unit/Services/PasswordValidationServiceTest.php`
- `backend/tests/Unit/Services/PasswordSecurityServiceTest.php`

**執行測試**：

```bash
# 執行所有密碼相關測試
docker compose exec web ./vendor/bin/phpunit --filter Password

# 執行特定測試
docker compose exec web ./vendor/bin/phpunit tests/Unit/ValueObjects/SecurePasswordTest.php
```

**測試覆蓋**：

- ✅ 基本驗證規則測試
- ✅ 黑名單密碼測試
- ✅ 連續字元測試
- ✅ 重複字元測試
- ✅ 使用者資訊檢查測試
- ✅ 密碼強度評分測試
- ✅ 邊界條件測試

### E2E 測試

**測試場景**：

1. 註冊時使用弱密碼被拒絕
2. 註冊時使用強密碼成功
3. 密碼強度指示器正確顯示
4. 密碼生成器產生符合要求的密碼
5. 修改密碼功能正常運作

**執行 E2E 測試**：

```bash
cd tests/e2e
npm test -- tests/password-security.spec.js
```

---

## 相關文件

### 使用者文件

- [密碼安全使用指南](user-guide/password-security.md) - 使用者完整指南

### 開發文件

- [密碼安全完成報告](archive/completed/PASSWORD_SECURITY_COMPLETION_REPORT.md) - 開發完成報告
- [密碼安全最終報告](archive/completed/PASSWORD_SECURITY_FINAL_REPORT.md) - 最終驗收報告
- [密碼安全待辦清單](archive/completed/PASSWORD_SECURITY_TODO.md) - 開發過程記錄

---

## 常見問題

### Q: 為什麼密碼要求這麼嚴格？

A: 根據統計，81% 的資料洩露事件與弱密碼有關。嚴格的密碼要求能有效保護您的帳號安全。

### Q: 可以使用密碼管理器嗎？

A: 強烈建議！使用密碼管理器（如 1Password、LastPass、Bitwarden）可以：

- 自動生成高強度密碼
- 安全儲存所有密碼
- 自動填入登入表單

### Q: 密碼多久需要更換？

A: 建議：

- 定期更換（3-6 個月）
- 疑似洩露時立即更換
- 不要重複使用舊密碼

### Q: 什麼是好的密碼？

A: 好的密碼應該：

- ✅ 長度 12 字元以上
- ✅ 混合大小寫、數字、符號
- ✅ 不包含個人資訊
- ✅ 每個網站使用不同密碼
- ✅ 使用密碼管理器管理

### Q: 密碼生成器是否安全？

A: 系統內建的密碼生成器：

- ✅ 使用加密安全的隨機數生成
- ✅ 確保密碼符合所有要求
- ✅ 密碼長度 16 字元
- ✅ 不會儲存或傳輸生成的密碼

---

## 技術規格

### 密碼雜湊

- **演算法**：bcrypt
- **成本因子**：10（可調整）
- **鹽值**：自動生成（每個密碼不同）

### 黑名單資料

- **常見弱密碼**：10,000+ 筆
- **常見英文單字**：3,000+ 筆
- **來源**：
  - Have I Been Pwned 資料庫
  - NIST 建議列表
  - 常見密碼排行榜

### 效能考量

- 密碼驗證時間：< 50ms
- 黑名單查詢：使用 Hash Set，O(1) 時間複雜度
- 快取機制：驗證結果快取 5 分鐘

---

**📝 最後更新**：2025-10-14
**✅ 狀態**：生產就緒
**📧 問題回報**：[GitHub Issues](https://github.com/cookeyholder/AlleyNote/issues)
