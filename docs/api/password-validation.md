# 密碼驗證 API 文件

## 概述

本文件說明密碼驗證相關的 API 端點，包括密碼強度檢查、驗證規則和安全性要求。

---

## API 端點

### 1. 驗證密碼強度

**端點**: `POST /api/auth/validate-password`

**描述**: 即時驗證密碼強度和安全性，提供詳細的驗證結果和改進建議。

**請求標頭**:
```http
Content-Type: application/json
```

**請求參數**:

| 參數 | 類型 | 必填 | 描述 |
|------|------|------|------|
| `password` | string | 是 | 要驗證的密碼 |
| `username` | string | 否 | 使用者名稱（用於個人資訊檢查） |
| `email` | string | 否 | 電子郵件（用於個人資訊檢查） |

**請求範例**:
```json
{
  "password": "MySecure@Pass123",
  "username": "john_doe",
  "email": "john@example.com"
}
```

**成功回應**: `200 OK`
```json
{
  "valid": true,
  "score": 85,
  "strength": "strong",
  "checks": {
    "length": {
      "passed": true,
      "message": "密碼長度符合要求"
    },
    "lowercase": {
      "passed": true,
      "message": "包含小寫字母"
    },
    "uppercase": {
      "passed": true,
      "message": "包含大寫字母"
    },
    "numbers": {
      "passed": true,
      "message": "包含數字"
    },
    "special_chars": {
      "passed": true,
      "message": "包含特殊符號"
    },
    "sequential": {
      "passed": true,
      "message": "無連續字元"
    },
    "repeating": {
      "passed": true,
      "message": "無重複字元"
    },
    "common": {
      "passed": true,
      "message": "非常見密碼"
    },
    "personal_info": {
      "passed": true,
      "message": "不包含個人資訊"
    }
  },
  "suggestions": [
    "密碼強度良好，可以使用"
  ]
}
```

**失敗回應**: `200 OK` (驗證失敗但請求成功)
```json
{
  "valid": false,
  "score": 35,
  "strength": "weak",
  "checks": {
    "length": {
      "passed": false,
      "message": "密碼長度至少需要 8 個字元"
    },
    "lowercase": {
      "passed": true,
      "message": "包含小寫字母"
    },
    "uppercase": {
      "passed": false,
      "message": "密碼必須包含至少一個大寫字母"
    },
    "numbers": {
      "passed": false,
      "message": "密碼必須包含至少一個數字"
    },
    "sequential": {
      "passed": false,
      "message": "密碼不能包含連續的英文字母或數字（如 abc, 123）"
    },
    "common": {
      "passed": false,
      "message": "此密碼過於常見，請使用更安全的密碼"
    }
  },
  "suggestions": [
    "使用更長的密碼（建議 12 個字元以上）",
    "混合使用大小寫字母、數字和特殊符號",
    "避免使用簡單的模式或重複字元",
    "使用獨特的密碼組合，不要使用常見單字"
  ]
}
```

**錯誤回應**: `422 Unprocessable Entity`
```json
{
  "message": "驗證失敗",
  "errors": {
    "password": ["密碼欄位為必填"]
  }
}
```

---

## 密碼驗證規則

### 基本要求

密碼必須符合以下所有條件：

1. **最小長度**: 至少 8 個字元
2. **最大長度**: 不超過 128 個字元
3. **包含小寫字母**: 至少一個 (a-z)
4. **包含大寫字母**: 至少一個 (A-Z)
5. **包含數字**: 至少一個 (0-9)
6. **建議包含特殊符號**: `!@#$%^&*()_+-=[]{}|;:',.<>?/~`

### 進階安全檢查

1. **連續字元檢查**
   - 不允許 3 個以上連續的英文字母（例如：abc, xyz）
   - 不允許 3 個以上連續的數字（例如：123, 789）
   - 檢查正向和反向連續（abc 和 cba）

2. **重複字元檢查**
   - 不允許 3 個以上相同字元（例如：aaa, 111）
   - 不允許重複模式（例如：abab, 1212）

3. **常見密碼黑名單**
   - 比對超過 10,000 個常見弱密碼
   - 包括：password, 123456, qwerty 等
   - 常見英文單字
   - 鍵盤排列模式

4. **個人資訊檢查**
   - 不能包含使用者名稱（3 字元以上）
   - 不能包含電子郵件前綴（3 字元以上）
   - 大小寫不敏感比對

### 密碼強度評分

密碼強度評分範圍：0-100 分

| 分數範圍 | 強度等級 | 顏色代碼 | 描述 |
|---------|---------|---------|------|
| 0-19    | very-weak | 深紅色 | 極弱，不可使用 |
| 20-39   | weak    | 紅色   | 弱，建議改善 |
| 40-59   | medium  | 黃色   | 中等，可接受 |
| 60-79   | strong  | 淺綠色 | 強，良好 |
| 80-100  | very-strong | 深綠色 | 很強，優秀 |

**評分計算**:
- 長度達標：+20 分
- 包含小寫字母：+15 分
- 包含大寫字母：+15 分
- 包含數字：+15 分
- 包含特殊符號：+20 分
- 長度 ≥ 12：+10 分
- 長度 ≥ 16：+10 分
- 有連續字元：-10 分
- 有重複字元：-10 分
- 常見密碼：-20 分
- 包含個人資訊：-15 分

---

## 整合範例

### JavaScript (Fetch API)

```javascript
async function validatePassword(password, username = null, email = null) {
  try {
    const response = await fetch('/api/auth/validate-password', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        password,
        username,
        email
      })
    });

    const result = await response.json();
    
    if (result.valid) {
      console.log('密碼有效，強度:', result.strength);
    } else {
      console.log('密碼無效:', result.suggestions);
    }
    
    return result;
  } catch (error) {
    console.error('驗證失敗:', error);
    throw error;
  }
}

// 使用範例
validatePassword('MySecure@Pass123', 'john_doe', 'john@example.com')
  .then(result => {
    console.log('驗證結果:', result);
  });
```

### PHP (Guzzle)

```php
<?php

use GuzzleHttp\Client;

function validatePassword(string $password, ?string $username = null, ?string $email = null): array
{
    $client = new Client();
    
    $response = $client->post('http://your-domain.com/api/auth/validate-password', [
        'json' => [
            'password' => $password,
            'username' => $username,
            'email' => $email,
        ],
    ]);
    
    return json_decode($response->getBody()->getContents(), true);
}

// 使用範例
$result = validatePassword('MySecure@Pass123', 'john_doe', 'john@example.com');

if ($result['valid']) {
    echo "密碼有效，強度: " . $result['strength'];
} else {
    echo "密碼無效: " . implode(', ', $result['suggestions']);
}
```

### cURL

```bash
curl -X POST http://your-domain.com/api/auth/validate-password \
  -H "Content-Type: application/json" \
  -d '{
    "password": "MySecure@Pass123",
    "username": "john_doe",
    "email": "john@example.com"
  }'
```

---

## 前端整合

### 使用密碼驗證工具

前端提供了完整的密碼驗證工具和 UI 組件：

```javascript
import { PasswordValidator } from './utils/passwordValidator.js';
import { PasswordStrengthIndicator } from './components/PasswordStrengthIndicator.js';

// 初始化密碼強度指示器
const indicator = new PasswordStrengthIndicator(
  document.querySelector('#password-input'),
  document.querySelector('#strength-indicator')
);

// 本地驗證
const result = PasswordValidator.validate(password, {
  username: 'john_doe',
  email: 'john@example.com'
});

console.log('密碼強度:', result.strength);
console.log('驗證錯誤:', result.errors);
console.log('改進建議:', result.suggestions);
```

---

## 安全性建議

### 開發者建議

1. **雙重驗證**
   - 前端進行即時本地驗證（提供快速反饋）
   - 後端進行完整嚴格驗證（確保安全性）

2. **錯誤處理**
   - 不要在錯誤訊息中洩露過多資訊
   - 提供有幫助的改進建議
   - 記錄可疑的密碼嘗試

3. **密碼傳輸**
   - 始終使用 HTTPS
   - 不要在 URL 中傳遞密碼
   - 不要記錄明文密碼

4. **密碼儲存**
   - 使用 bcrypt 或 argon2 雜湊
   - 永遠不要儲存明文密碼
   - 使用足夠的 salt 和 cost factor

### 使用者建議

1. **建立強密碼的技巧**
   - 使用密碼管理器
   - 使用密碼生成器
   - 每個帳號使用不同密碼
   - 定期更換密碼

2. **避免的密碼模式**
   - 常見單字或短語
   - 個人資訊（生日、名字等）
   - 簡單的數字序列
   - 鍵盤排列模式

---

## 常見問題 (FAQ)

### Q1: 為什麼我的密碼被拒絕？

**A**: 密碼可能違反了以下一或多個規則：
- 長度不足（少於 8 字元）
- 缺少大寫字母、小寫字母或數字
- 包含連續字元（如 abc, 123）
- 包含重複字元（如 aaa, 111）
- 是常見的弱密碼
- 包含您的使用者名稱或電子郵件

查看驗證回應中的 `suggestions` 欄位以獲得具體改進建議。

### Q2: 如何生成一個安全的密碼？

**A**: 可以使用以下方法：
1. 使用系統提供的密碼生成器（UI 中的「生成密碼」按鈕）
2. 使用密碼管理器（如 1Password, LastPass）
3. 遵循以下規則手動建立：
   - 至少 12 個字元
   - 混合大小寫字母、數字和符號
   - 避免常見單字和模式
   - 不包含個人資訊

### Q3: 密碼驗證 API 有速率限制嗎？

**A**: 是的，為防止濫用：
- 未認證請求：每分鐘最多 10 次
- 已認證請求：每分鐘最多 60 次

超過限制將返回 429 (Too Many Requests) 錯誤。

### Q4: 如何自訂密碼政策？

**A**: 目前密碼政策是固定的。未來版本將支援管理員自訂密碼政策，包括：
- 最小長度要求
- 複雜度要求
- 密碼過期時間
- 密碼歷史記錄

### Q5: 支援哪些特殊符號？

**A**: 支援以下特殊符號：
```
! @ # $ % ^ & * ( ) _ + - = [ ] { } | ; : ' , . < > ? / ~ `
```

### Q6: 為什麼需要提供 username 和 email？

**A**: 這是為了防止密碼包含個人資訊，這會大大降低密碼安全性。例如：
- 如果使用者名稱是 "john_doe"，密碼不能包含 "john" 或 "doe"
- 如果 email 是 "mary@example.com"，密碼不能包含 "mary"

這些參數是可選的，但建議提供以獲得更嚴格的安全檢查。

---

## 版本歷史

### v1.0.0 (2025-10-11)
- ✅ 初始版本發布
- ✅ 基本密碼驗證規則
- ✅ 連續和重複字元檢查
- ✅ 常見密碼黑名單
- ✅ 個人資訊檢查
- ✅ 密碼強度評分
- ✅ 前端整合

### 未來計劃
- [ ] 密碼歷史記錄
- [ ] 可配置的密碼政策
- [ ] Have I Been Pwned 整合
- [ ] 多語言支援
- [ ] GraphQL API 支援

---

## 相關資源

- [密碼安全最佳實踐](../user-guide/password-security.md)
- [OWASP 密碼指南](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [NIST 數位身份指南](https://pages.nist.gov/800-63-3/)
- [密碼強度計算器](https://www.security.org/how-secure-is-my-password/)

---

**維護者**: AlleyNote 開發團隊  
**最後更新**: 2025-10-11  
**回饋**: 如有問題或建議，請開啟 Issue
