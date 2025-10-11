# 🔐 密碼安全性強化待辦清單

> **目標**: 強制使用者使用更安全、更難被破解的密碼

## 📋 需求規格

### 密碼必須符合以下條件：
1. ✅ 至少 8 個字元
2. ✅ 包含英文字母（大小寫）
3. ✅ 包含數字
4. 🆕 不能是常見的英文單字
5. 🆕 不能是連續的英文字母（如 abc, xyz）
6. 🆕 不能全部是相同的字元（如 aaaa, 1111）
7. 🆕 可選：包含特殊符號
8. 🆕 不能包含使用者名稱或 email 的一部分

---

## 📝 待辦事項

### 階段一：後端密碼驗證強化

#### 1. 建立密碼驗證值物件 (Value Object)
- [ ] **檔案**: `backend/app/Shared/ValueObjects/SecurePassword.php`
- [ ] 建立 `SecurePassword` 類別
- [ ] 實作基本驗證：
  - [ ] 最小長度檢查（至少 8 字元）
  - [ ] 必須包含英文字母（大小寫）
  - [ ] 必須包含數字
- [ ] 實作進階驗證：
  - [ ] 檢查是否為連續字母（abc, def, xyz 等）
  - [ ] 檢查是否全部相同字元
  - [ ] 檢查是否包含常見單字
  - [ ] 檢查是否包含使用者資訊
- [ ] 實作熵值計算（密碼強度分數）
- [ ] 撰寫詳細的錯誤訊息

#### 2. 建立常見密碼黑名單
- [ ] **檔案**: `backend/resources/data/common-passwords.txt`
- [ ] 收集常見弱密碼列表（Top 10000）
  - [ ] password, 123456, qwerty 等
  - [ ] 常見英文單字
  - [ ] 鍵盤排列（qwerty, asdfgh）
  - [ ] 數字序列（123456, 654321）
- [ ] **檔案**: `backend/resources/data/common-words.txt`
- [ ] 收集常見英文單字
  - [ ] 3-8 字母的常見單字
  - [ ] 避免誤判（如 strong, secure）

#### 3. 建立密碼驗證服務
- [ ] **檔案**: `backend/app/Shared/Services/PasswordValidationService.php`
- [ ] 實作黑名單檢查邏輯
- [ ] 實作連續字元檢查
- [ ] 實作重複字元檢查
- [ ] 實作密碼強度評分
- [ ] 提供密碼建議

#### 4. 整合到 DTO 驗證
- [ ] **檔案**: `backend/app/Domains/Auth/DTOs/CreateUserDTO.php`
- [ ] 使用 `SecurePassword` 值物件
- [ ] 在建構時自動驗證密碼
- [ ] **檔案**: `backend/app/Domains/Auth/DTOs/UpdateUserDTO.php`
- [ ] 更新密碼時套用相同驗證

#### 5. 建立密碼驗證 API
- [ ] **檔案**: `backend/app/Http/Controllers/Auth/PasswordValidationController.php`
- [ ] 新增 `POST /api/auth/validate-password` 端點
- [ ] 即時驗證密碼強度
- [ ] 回傳詳細的驗證結果和建議

#### 6. 撰寫單元測試
- [ ] **檔案**: `backend/tests/Unit/ValueObjects/SecurePasswordTest.php`
- [ ] 測試所有驗證規則
- [ ] 測試邊界條件
- [ ] **檔案**: `backend/tests/Unit/Services/PasswordValidationServiceTest.php`
- [ ] 測試黑名單功能
- [ ] 測試連續字元偵測
- [ ] 測試重複字元偵測

---

### 階段二：前端密碼驗證強化

#### 7. 建立進階密碼驗證工具
- [ ] **檔案**: `frontend/js/utils/passwordValidator.js`
- [ ] 實作 `PasswordValidator` 類別
- [ ] 實作本地驗證邏輯：
  - [ ] 長度檢查
  - [ ] 字母數字檢查
  - [ ] 連續字元檢查
  - [ ] 重複字元檢查
  - [ ] 常見模式檢查
- [ ] 實作密碼強度計算
- [ ] 實作密碼建議生成器

#### 8. 建立密碼強度指示器組件
- [ ] **檔案**: `frontend/js/components/PasswordStrengthIndicator.js`
- [ ] 視覺化密碼強度（弱/中/強/很強）
- [ ] 即時顯示驗證結果
- [ ] 顯示具體的改進建議
- [ ] 顏色編碼（紅/黃/綠）
- [ ] 進度條動畫

#### 9. 建立常見密碼檢查服務
- [ ] **檔案**: `frontend/js/services/commonPasswordChecker.js`
- [ ] 實作前端黑名單檢查（Top 100-500）
- [ ] 使用 Bloom Filter 優化效能
- [ ] 或使用 Web Worker 異步檢查

#### 10. 整合到使用者表單
- [ ] **檔案**: `frontend/js/pages/admin/users.js`
- [ ] 整合密碼強度指示器
- [ ] 即時驗證並顯示錯誤
- [ ] 阻止提交弱密碼
- [ ] **檔案**: `frontend/js/pages/auth/register.js`（如有）
- [ ] 同樣的密碼驗證邏輯

#### 11. 更新 FormValidator
- [ ] **檔案**: `frontend/js/utils/validator.js`
- [ ] 擴充 `isStrongPassword` 方法
- [ ] 新增 `validateSecurePassword` 規則
- [ ] 支援自訂錯誤訊息
- [ ] 新增驗證規則：
  ```javascript
  ValidationRules.securePassword = (options) => ({
    rule: 'securePassword',
    params: options,
    message: '密碼不符合安全要求'
  })
  ```

#### 12. 建立密碼建議 UI
- [ ] **檔案**: `frontend/js/components/PasswordSuggestions.js`
- [ ] 顯示密碼要求清單
- [ ] 即時標記已符合/未符合的要求
- [ ] 提供密碼範例
- [ ] 提供「生成安全密碼」按鈕

---

### 階段三：UI/UX 優化

#### 13. 設計密碼輸入體驗
- [ ] **檔案**: `frontend/css/components/password-input.css`（或 Tailwind）
- [ ] 密碼顯示/隱藏切換按鈕
- [ ] 密碼強度視覺化
- [ ] 要求清單動畫
- [ ] 友善的錯誤提示

#### 14. 新增密碼生成器
- [ ] **檔案**: `frontend/js/utils/passwordGenerator.js`
- [ ] 實作安全密碼生成器
- [ ] 可自訂長度和複雜度
- [ ] 確保符合所有安全規則
- [ ] 提供「複製到剪貼簿」功能

#### 15. 多語言支援
- [ ] **檔案**: `frontend/js/i18n/zh-TW/password.js`
- [ ] 密碼錯誤訊息翻譯
- [ ] 密碼建議翻譯
- [ ] **檔案**: `frontend/js/i18n/en/password.js`
- [ ] 英文版本

---

### 階段四：測試與文件

#### 16. E2E 測試
- [ ] **檔案**: `tests/e2e/tests/08-password-security.spec.js`
- [ ] 測試弱密碼被拒絕
- [ ] 測試強密碼被接受
- [ ] 測試密碼強度指示器
- [ ] 測試密碼建議功能
- [ ] 測試密碼生成器

#### 17. 更新現有測試
- [ ] **檔案**: `tests/e2e/tests/07-user-management.spec.js`
- [ ] 更新密碼測試案例
- [ ] 使用符合新規則的密碼
- [ ] **檔案**: `tests/e2e/tests/02-auth.spec.js`
- [ ] 更新註冊/登入測試

#### 18. API 文件
- [ ] **檔案**: `docs/api/password-validation.md`
- [ ] 記錄密碼驗證規則
- [ ] 記錄 API 端點
- [ ] 提供範例請求/回應

#### 19. 使用者文件
- [ ] **檔案**: `docs/user-guide/password-security.md`
- [ ] 解釋密碼安全重要性
- [ ] 提供建立強密碼的建議
- [ ] FAQ 常見問題

---

### 階段五：進階功能（可選）

#### 20. 密碼歷史記錄
- [ ] **資料庫**: 新增 `password_history` 資料表
- [ ] 儲存舊密碼雜湊
- [ ] 防止重複使用最近 N 次的密碼
- [ ] 實作密碼輪換政策

#### 21. 密碼強度政策管理
- [ ] **檔案**: `backend/app/Domains/Settings/DTOs/PasswordPolicyDTO.php`
- [ ] 允許管理員設定密碼政策
- [ ] 最小長度、複雜度要求可配置
- [ ] 密碼有效期設定

#### 22. 洩漏密碼檢查
- [ ] **整合**: Have I Been Pwned API
- [ ] 檢查密碼是否曾被洩漏
- [ ] 異步檢查不阻塞使用者
- [ ] 隱私保護（k-anonymity）

#### 23. 二次驗證整合
- [ ] 強密碼 + 2FA 雙重保護
- [ ] 弱密碼強制啟用 2FA
- [ ] TOTP 或 SMS 驗證

---

## 🎯 優先順序

### P0 - 必須完成（第一週）
- [ ] 1. 建立密碼驗證值物件
- [ ] 2. 建立常見密碼黑名單
- [ ] 3. 建立密碼驗證服務
- [ ] 4. 整合到 DTO 驗證
- [ ] 7. 建立進階密碼驗證工具（前端）
- [ ] 11. 更新 FormValidator

### P1 - 高優先級（第二週）
- [ ] 5. 建立密碼驗證 API
- [ ] 8. 建立密碼強度指示器組件
- [ ] 10. 整合到使用者表單
- [ ] 12. 建立密碼建議 UI
- [ ] 16. E2E 測試

### P2 - 中優先級（第三週）
- [ ] 6. 撰寫單元測試
- [ ] 9. 建立常見密碼檢查服務
- [ ] 13. 設計密碼輸入體驗
- [ ] 14. 新增密碼生成器
- [ ] 17. 更新現有測試

### P3 - 低優先級（後續）
- [ ] 15. 多語言支援
- [ ] 18. API 文件
- [ ] 19. 使用者文件
- [ ] 20-23. 進階功能

---

## 📊 密碼驗證規則詳細說明

### 規則 1: 基本長度與組成
```
最小長度: 8 字元
必須包含: 
  - 至少 1 個小寫字母 (a-z)
  - 至少 1 個大寫字母 (A-Z)
  - 至少 1 個數字 (0-9)
建議: 至少 1 個特殊符號 (!@#$%^&*)
```

### 規則 2: 連續字元檢查
```
不允許:
  - 3+ 個連續英文字母: abc, ABC, xyz, XYZ
  - 3+ 個連續數字: 123, 456, 789
  - 3+ 個鍵盤序列: qwe, asd, zxc
  
檢查方式:
  - ASCII 碼連續性檢查
  - 鍵盤布局檢查（QWERTY, DVORAK）
```

### 規則 3: 重複字元檢查
```
不允許:
  - 3+ 個相同字元: aaa, 111, !!!
  - 2+ 個相同字元重複: abab, 1212
  
檢查方式:
  - 正則表達式: /(.)\1{2,}/
  - 模式重複: /(.{2,})\1+/
```

### 規則 4: 常見密碼黑名單
```
資料來源:
  - OWASP Top 10000 passwords
  - 常見英文單字字典
  - 流行文化詞彙
  
檢查方式:
  - 精確匹配
  - Levenshtein 距離（相似度）
  - 子字串匹配
```

### 規則 5: 個人資訊檢查
```
不允許包含:
  - 使用者名稱（完整或部分）
  - Email 地址（完整或部分）
  - 名字、姓氏
  
檢查方式:
  - 大小寫不敏感比對
  - 子字串匹配（長度 >= 3）
```

---

## 🔧 實作範例

### 後端 PHP 範例
```php
<?php
// backend/app/Shared/ValueObjects/SecurePassword.php

declare(strict_types=1);

namespace App\Shared\ValueObjects;

use App\Shared\Exceptions\ValidationException;

final class SecurePassword
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 128;

    public function __construct(
        private readonly string $value,
        private readonly ?string $username = null,
        private readonly ?string $email = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        $errors = [];

        // 長度檢查
        if (strlen($this->value) < self::MIN_LENGTH) {
            $errors[] = "密碼長度至少需要 " . self::MIN_LENGTH . " 個字元";
        }

        if (strlen($this->value) > self::MAX_LENGTH) {
            $errors[] = "密碼長度不能超過 " . self::MAX_LENGTH . " 個字元";
        }

        // 字母數字檢查
        if (!preg_match('/[a-z]/', $this->value)) {
            $errors[] = "密碼必須包含至少一個小寫字母";
        }

        if (!preg_match('/[A-Z]/', $this->value)) {
            $errors[] = "密碼必須包含至少一個大寫字母";
        }

        if (!preg_match('/[0-9]/', $this->value)) {
            $errors[] = "密碼必須包含至少一個數字";
        }

        // 連續字元檢查
        if ($this->hasSequentialChars()) {
            $errors[] = "密碼不能包含連續的英文字母或數字（如 abc, 123）";
        }

        // 重複字元檢查
        if ($this->hasRepeatingChars()) {
            $errors[] = "密碼不能包含重複的字元（如 aaa, 111）";
        }

        // 常見密碼檢查
        if ($this->isCommonPassword()) {
            $errors[] = "此密碼過於常見，請使用更安全的密碼";
        }

        // 個人資訊檢查
        if ($this->containsPersonalInfo()) {
            $errors[] = "密碼不能包含使用者名稱或電子郵件";
        }

        if (!empty($errors)) {
            throw ValidationException::fromMultipleErrors(['password' => $errors]);
        }
    }

    private function hasSequentialChars(): bool
    {
        $lower = strtolower($this->value);
        
        for ($i = 0; $i < strlen($lower) - 2; $i++) {
            $char1 = ord($lower[$i]);
            $char2 = ord($lower[$i + 1]);
            $char3 = ord($lower[$i + 2]);
            
            // 檢查連續遞增或遞減
            if (($char2 === $char1 + 1 && $char3 === $char2 + 1) ||
                ($char2 === $char1 - 1 && $char3 === $char2 - 1)) {
                return true;
            }
        }
        
        return false;
    }

    private function hasRepeatingChars(): bool
    {
        // 檢查 3+ 個相同字元
        return preg_match('/(.)\1{2,}/', $this->value) === 1;
    }

    private function isCommonPassword(): bool
    {
        // TODO: 載入黑名單並檢查
        $commonPasswords = $this->loadCommonPasswords();
        return in_array(strtolower($this->value), $commonPasswords, true);
    }

    private function containsPersonalInfo(): bool
    {
        $lower = strtolower($this->value);
        
        if ($this->username && strlen($this->username) >= 3) {
            if (str_contains($lower, strtolower($this->username))) {
                return true;
            }
        }
        
        if ($this->email) {
            $emailParts = explode('@', $this->email);
            if (isset($emailParts[0]) && strlen($emailParts[0]) >= 3) {
                if (str_contains($lower, strtolower($emailParts[0]))) {
                    return true;
                }
            }
        }
        
        return false;
    }

    private function loadCommonPasswords(): array
    {
        static $passwords = null;
        
        if ($passwords === null) {
            $file = __DIR__ . '/../../../resources/data/common-passwords.txt';
            if (file_exists($file)) {
                $passwords = array_map('trim', file($file));
                $passwords = array_map('strtolower', $passwords);
            } else {
                $passwords = [];
            }
        }
        
        return $passwords;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
```

### 前端 JavaScript 範例
```javascript
// frontend/js/utils/passwordValidator.js

export class PasswordValidator {
  static MIN_LENGTH = 8;
  static MAX_LENGTH = 128;

  /**
   * 驗證密碼並回傳結果
   */
  static validate(password, options = {}) {
    const { username = null, email = null } = options;
    const errors = [];
    const warnings = [];
    let score = 0;

    // 長度檢查
    if (password.length < this.MIN_LENGTH) {
      errors.push(`密碼長度至少需要 ${this.MIN_LENGTH} 個字元`);
    } else {
      score += 20;
    }

    if (password.length > this.MAX_LENGTH) {
      errors.push(`密碼長度不能超過 ${this.MAX_LENGTH} 個字元`);
    }

    // 包含小寫字母
    if (!/[a-z]/.test(password)) {
      errors.push('密碼必須包含至少一個小寫字母');
    } else {
      score += 15;
    }

    // 包含大寫字母
    if (!/[A-Z]/.test(password)) {
      errors.push('密碼必須包含至少一個大寫字母');
    } else {
      score += 15;
    }

    // 包含數字
    if (!/[0-9]/.test(password)) {
      errors.push('密碼必須包含至少一個數字');
    } else {
      score += 15;
    }

    // 包含特殊符號（加分項）
    if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
      score += 20;
    } else {
      warnings.push('建議包含至少一個特殊符號以增加安全性');
    }

    // 連續字元檢查
    if (this.hasSequentialChars(password)) {
      errors.push('密碼不能包含連續的英文字母或數字（如 abc, 123）');
      score -= 10;
    }

    // 重複字元檢查
    if (this.hasRepeatingChars(password)) {
      errors.push('密碼不能包含重複的字元（如 aaa, 111）');
      score -= 10;
    }

    // 常見密碼檢查
    if (this.isCommonPassword(password)) {
      errors.push('此密碼過於常見，請使用更安全的密碼');
      score -= 20;
    }

    // 個人資訊檢查
    if (this.containsPersonalInfo(password, username, email)) {
      errors.push('密碼不能包含使用者名稱或電子郵件');
      score -= 15;
    }

    // 長度加分
    if (password.length >= 12) score += 10;
    if (password.length >= 16) score += 10;

    // 確保分數在 0-100 之間
    score = Math.max(0, Math.min(100, score));

    return {
      isValid: errors.length === 0,
      score,
      strength: this.getStrengthLevel(score),
      errors,
      warnings,
      suggestions: this.getSuggestions(errors, warnings)
    };
  }

  static hasSequentialChars(password) {
    const lower = password.toLowerCase();
    
    for (let i = 0; i < lower.length - 2; i++) {
      const char1 = lower.charCodeAt(i);
      const char2 = lower.charCodeAt(i + 1);
      const char3 = lower.charCodeAt(i + 2);
      
      if ((char2 === char1 + 1 && char3 === char2 + 1) ||
          (char2 === char1 - 1 && char3 === char2 - 1)) {
        return true;
      }
    }
    
    return false;
  }

  static hasRepeatingChars(password) {
    return /(.)\1{2,}/.test(password);
  }

  static isCommonPassword(password) {
    const commonPasswords = [
      'password', 'password123', '12345678', 'qwerty', 
      'abc123', 'monkey', '1234567890', 'letmein',
      'trustno1', 'dragon', 'baseball', 'iloveyou',
      'master', 'sunshine', 'ashley', 'bailey'
    ];
    
    return commonPasswords.includes(password.toLowerCase());
  }

  static containsPersonalInfo(password, username, email) {
    const lower = password.toLowerCase();
    
    if (username && username.length >= 3) {
      if (lower.includes(username.toLowerCase())) {
        return true;
      }
    }
    
    if (email) {
      const emailPrefix = email.split('@')[0];
      if (emailPrefix && emailPrefix.length >= 3) {
        if (lower.includes(emailPrefix.toLowerCase())) {
          return true;
        }
      }
    }
    
    return false;
  }

  static getStrengthLevel(score) {
    if (score >= 80) return 'very-strong';
    if (score >= 60) return 'strong';
    if (score >= 40) return 'medium';
    if (score >= 20) return 'weak';
    return 'very-weak';
  }

  static getSuggestions(errors, warnings) {
    const suggestions = [];
    
    if (errors.some(e => e.includes('長度'))) {
      suggestions.push('使用更長的密碼（建議 12 個字元以上）');
    }
    
    if (errors.some(e => e.includes('字母') || e.includes('數字'))) {
      suggestions.push('混合使用大小寫字母、數字和特殊符號');
    }
    
    if (errors.some(e => e.includes('連續') || e.includes('重複'))) {
      suggestions.push('避免使用簡單的模式或重複字元');
    }
    
    if (errors.some(e => e.includes('常見'))) {
      suggestions.push('使用獨特的密碼組合，不要使用常見單字');
    }
    
    if (suggestions.length === 0 && warnings.length > 0) {
      suggestions.push('已經很好！可以加入特殊符號讓密碼更安全');
    }
    
    return suggestions;
  }
}
```

---

## 📈 成功指標

- [ ] 所有新使用者密碼符合安全規則
- [ ] 密碼破解時間從幾秒提升到數年
- [ ] 使用者理解密碼安全重要性
- [ ] 減少弱密碼使用率 90%+
- [ ] E2E 測試覆蓋率 95%+

---

## 🔗 參考資源

1. **OWASP Password Guidelines**
   https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html

2. **NIST Digital Identity Guidelines**
   https://pages.nist.gov/800-63-3/

3. **Have I Been Pwned API**
   https://haveibeenpwned.com/API/v3

4. **Common Password Lists**
   https://github.com/danielmiessler/SecLists/tree/master/Passwords

5. **zxcvbn Password Strength Estimator**
   https://github.com/dropbox/zxcvbn

