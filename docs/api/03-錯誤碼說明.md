# API 錯誤碼說明文件

**版本**: 1.0.0  
**最後更新**: 2025-10-11

---

## 📖 目錄

1. [錯誤碼格式](#錯誤碼格式)
2. [通用錯誤碼](#通用錯誤碼)
3. [認證相關錯誤](#認證相關錯誤)
4. [使用者相關錯誤](#使用者相關錯誤)
5. [角色相關錯誤](#角色相關錯誤)
6. [權限相關錯誤](#權限相關錯誤)
7. [文章相關錯誤](#文章相關錯誤)
8. [標籤相關錯誤](#標籤相關錯誤)
9. [設定相關錯誤](#設定相關錯誤)
10. [附件相關錯誤](#附件相關錯誤)
11. [錯誤回應格式](#錯誤回應格式)

---

## 錯誤碼格式

### 命名規則

錯誤碼格式: `{模組}_{類型}_{描述}`

- **模組**: AUTH, USER, ROLE, PERM, POST, TAG, SETTING, SYSTEM
- **類型**: VALIDATION, NOT_FOUND, FORBIDDEN, UNAUTHORIZED, CONFLICT, ERROR
- **描述**: 具體的錯誤情況

### HTTP 狀態碼對應

| HTTP 狀態碼 | 說明 | 使用時機 |
|-----------|------|---------|
| 200 | OK | 請求成功 |
| 201 | Created | 資源建立成功 |
| 400 | Bad Request | 請求格式錯誤 |
| 401 | Unauthorized | 未授權（Token 無效或過期） |
| 403 | Forbidden | 禁止訪問（權限不足） |
| 404 | Not Found | 資源不存在 |
| 409 | Conflict | 資源衝突 |
| 422 | Unprocessable Entity | 資料驗證失敗 |
| 429 | Too Many Requests | 請求過於頻繁 |
| 500 | Internal Server Error | 伺服器內部錯誤 |
| 503 | Service Unavailable | 服務暫時無法使用 |

---

## 通用錯誤碼

### VALIDATION_ERROR

- **HTTP 狀態碼**: 422
- **說明**: 資料驗證失敗
- **可能原因**: 
  - 必填欄位缺失
  - 資料格式不正確
  - 資料長度超過限制
- **解決方式**: 檢查請求資料是否符合 API 規格

**範例回應**:
```json
{
  "success": false,
  "error_code": "VALIDATION_ERROR",
  "message": "資料驗證失敗",
  "errors": {
    "email": ["電子郵件格式不正確"],
    "password": ["密碼長度至少需要 8 個字元"]
  }
}
```

### NOT_FOUND

- **HTTP 狀態碼**: 404
- **說明**: 資源不存在
- **可能原因**: 
  - 資源 ID 不正確
  - 資源已被刪除
- **解決方式**: 確認資源 ID 是否正確

**範例回應**:
```json
{
  "success": false,
  "error_code": "NOT_FOUND",
  "message": "資源不存在"
}
```

### UNAUTHORIZED

- **HTTP 狀態碼**: 401
- **說明**: 未授權
- **可能原因**: 
  - 未提供 Token
  - Token 已過期
  - Token 無效
- **解決方式**: 重新登入取得有效的 Token

**範例回應**:
```json
{
  "success": false,
  "error_code": "UNAUTHORIZED",
  "message": "未授權，請先登入"
}
```

### FORBIDDEN

- **HTTP 狀態碼**: 403
- **說明**: 禁止訪問
- **可能原因**: 
  - 權限不足
  - 帳號被停用
- **解決方式**: 聯繫管理員取得適當權限

**範例回應**:
```json
{
  "success": false,
  "error_code": "FORBIDDEN",
  "message": "權限不足，無法執行此操作"
}
```

### TOO_MANY_REQUESTS

- **HTTP 狀態碼**: 429
- **說明**: 請求過於頻繁
- **可能原因**: 超過 API 使用率限制
- **解決方式**: 稍後再試，或聯繫管理員提升限制

**範例回應**:
```json
{
  "success": false,
  "error_code": "TOO_MANY_REQUESTS",
  "message": "請求過於頻繁，請稍後再試",
  "retry_after": 60
}
```

---

## 認證相關錯誤

### AUTH_INVALID_CREDENTIALS

- **HTTP 狀態碼**: 401
- **錯誤碼**: `AUTH_INVALID_CREDENTIALS`
- **說明**: 帳號或密碼錯誤
- **出現時機**: 登入時
- **解決方式**: 確認帳號密碼是否正確

**範例回應**:
```json
{
  "success": false,
  "error_code": "AUTH_INVALID_CREDENTIALS",
  "message": "帳號或密碼錯誤"
}
```

### AUTH_TOKEN_EXPIRED

- **HTTP 狀態碼**: 401
- **錯誤碼**: `AUTH_TOKEN_EXPIRED`
- **說明**: Token 已過期
- **出現時機**: 使用已過期的 Token
- **解決方式**: 使用 Refresh Token 刷新或重新登入

**範例回應**:
```json
{
  "success": false,
  "error_code": "AUTH_TOKEN_EXPIRED",
  "message": "Token 已過期",
  "expired_at": "2025-10-11T08:00:00Z"
}
```

### AUTH_TOKEN_INVALID

- **HTTP 狀態碼**: 401
- **錯誤碼**: `AUTH_TOKEN_INVALID`
- **說明**: Token 無效
- **出現時機**: Token 格式錯誤或被篡改
- **解決方式**: 重新登入取得新的 Token

### AUTH_USER_DISABLED

- **HTTP 狀態碼**: 403
- **錯誤碼**: `AUTH_USER_DISABLED`
- **說明**: 帳號已被停用
- **出現時機**: 使用已停用帳號的 Token
- **解決方式**: 聯繫管理員啟用帳號

---

## 使用者相關錯誤

### USER_NOT_FOUND

- **HTTP 狀態碼**: 404
- **錯誤碼**: `USER_NOT_FOUND`
- **說明**: 使用者不存在
- **出現時機**: 查詢、更新或刪除不存在的使用者
- **解決方式**: 確認使用者 ID 是否正確

**範例回應**:
```json
{
  "success": false,
  "error_code": "USER_NOT_FOUND",
  "message": "使用者不存在",
  "user_id": 999
}
```

### USER_ALREADY_EXISTS

- **HTTP 狀態碼**: 409
- **錯誤碼**: `USER_ALREADY_EXISTS`
- **說明**: 使用者已存在
- **出現時機**: 建立使用者時，使用者名稱或電子郵件已存在
- **解決方式**: 使用不同的使用者名稱或電子郵件

**範例回應**:
```json
{
  "success": false,
  "error_code": "USER_ALREADY_EXISTS",
  "message": "使用者已存在",
  "conflicts": {
    "username": "johndoe",
    "email": "john@example.com"
  }
}
```

### USER_EMAIL_EXISTS

- **HTTP 狀態碼**: 409
- **錯誤碼**: `USER_EMAIL_EXISTS`
- **說明**: 電子郵件已被使用
- **出現時機**: 註冊或更新使用者時
- **解決方式**: 使用其他電子郵件

### USER_USERNAME_EXISTS

- **HTTP 狀態碼**: 409
- **錯誤碼**: `USER_USERNAME_EXISTS`
- **說明**: 使用者名稱已被使用
- **出現時機**: 註冊或更新使用者時
- **解決方式**: 使用其他使用者名稱

### USER_CANNOT_DELETE_SELF

- **HTTP 狀態碼**: 403
- **錯誤碼**: `USER_CANNOT_DELETE_SELF`
- **說明**: 無法刪除自己的帳號
- **出現時機**: 嘗試刪除自己的帳號
- **解決方式**: 請其他管理員協助刪除

### USER_CANNOT_MODIFY_ADMIN

- **HTTP 狀態碼**: 403
- **錯誤碼**: `USER_CANNOT_MODIFY_ADMIN`
- **說明**: 無法修改管理員帳號
- **出現時機**: 非超級管理員嘗試修改管理員帳號
- **解決方式**: 只有超級管理員可以修改管理員帳號

---

## 角色相關錯誤

### ROLE_NOT_FOUND

- **HTTP 狀態碼**: 404
- **錯誤碼**: `ROLE_NOT_FOUND`
- **說明**: 角色不存在

### ROLE_ALREADY_EXISTS

- **HTTP 狀態碼**: 409
- **錯誤碼**: `ROLE_ALREADY_EXISTS`
- **說明**: 角色名稱已存在

### ROLE_IN_USE

- **HTTP 狀態碼**: 409
- **錯誤碼**: `ROLE_IN_USE`
- **說明**: 角色仍被使用中，無法刪除
- **解決方式**: 先將使用此角色的使用者移除或改為其他角色

**範例回應**:
```json
{
  "success": false,
  "error_code": "ROLE_IN_USE",
  "message": "角色仍被使用中，無法刪除",
  "users_count": 5
}
```

### ROLE_CANNOT_DELETE_SYSTEM

- **HTTP 狀態碼**: 403
- **錯誤碼**: `ROLE_CANNOT_DELETE_SYSTEM`
- **說明**: 無法刪除系統角色（如 admin, user）

---

## 權限相關錯誤

### PERMISSION_NOT_FOUND

- **HTTP 狀態碼**: 404
- **錯誤碼**: `PERMISSION_NOT_FOUND`
- **說明**: 權限不存在

### PERMISSION_DENIED

- **HTTP 狀態碼**: 403
- **錯誤碼**: `PERMISSION_DENIED`
- **說明**: 權限不足
- **解決方式**: 聯繫管理員取得適當權限

**範例回應**:
```json
{
  "success": false,
  "error_code": "PERMISSION_DENIED",
  "message": "權限不足，無法執行此操作",
  "required_permission": "users.delete"
}
```

---

## 文章相關錯誤

### POST_NOT_FOUND

- **HTTP 狀態碼**: 404
- **錯誤碼**: `POST_NOT_FOUND`
- **說明**: 文章不存在

### POST_ALREADY_PUBLISHED

- **HTTP 狀態碼**: 409
- **錯誤碼**: `POST_ALREADY_PUBLISHED`
- **說明**: 文章已發布，無法再次發布

### POST_NOT_PUBLISHED

- **HTTP 狀態碼**: 400
- **錯誤碼**: `POST_NOT_PUBLISHED`
- **說明**: 文章尚未發布

### POST_CANNOT_MODIFY

- **HTTP 狀態碼**: 403
- **錯誤碼**: `POST_CANNOT_MODIFY`
- **說明**: 無法修改此文章（可能是權限不足或文章已鎖定）

---

## 標籤相關錯誤

### TAG_NOT_FOUND

- **HTTP 狀態碼**: 404
- **錯誤碼**: `TAG_NOT_FOUND`
- **說明**: 標籤不存在

### TAG_ALREADY_EXISTS

- **HTTP 狀態碼**: 409
- **錯誤碼**: `TAG_ALREADY_EXISTS`
- **說明**: 標籤名稱已存在

### TAG_IN_USE

- **HTTP 狀態碼**: 409
- **錯誤碼**: `TAG_IN_USE`
- **說明**: 標籤仍被使用中，無法刪除

---

## 設定相關錯誤

### SETTING_NOT_FOUND

- **HTTP 狀態碼**: 404
- **錯誤碼**: `SETTING_NOT_FOUND`
- **說明**: 設定項目不存在

### SETTING_READONLY

- **HTTP 狀態碼**: 400
- **錯誤碼**: `SETTING_READONLY`
- **說明**: 此設定為唯讀，無法修改

---

## 附件相關錯誤

### ATTACHMENT_NOT_FOUND

- **HTTP 狀態碼**: 404
- **錯誤碼**: `ATTACHMENT_NOT_FOUND`
- **說明**: 附件不存在

### ATTACHMENT_UPLOAD_FAILED

- **HTTP 狀態碼**: 500
- **錯誤碼**: `ATTACHMENT_UPLOAD_FAILED`
- **說明**: 附件上傳失敗

### ATTACHMENT_SIZE_EXCEEDED

- **HTTP 狀態碼**: 400
- **錯誤碼**: `ATTACHMENT_SIZE_EXCEEDED`
- **說明**: 附件大小超過限制
- **解決方式**: 壓縮檔案或使用更小的檔案

**範例回應**:
```json
{
  "success": false,
  "error_code": "ATTACHMENT_SIZE_EXCEEDED",
  "message": "附件大小超過限制",
  "max_size": 10485760,
  "current_size": 15728640
}
```

### ATTACHMENT_TYPE_NOT_ALLOWED

- **HTTP 狀態碼**: 400
- **錯誤碼**: `ATTACHMENT_TYPE_NOT_ALLOWED`
- **說明**: 不支援的附件類型
- **解決方式**: 使用允許的檔案類型

**範例回應**:
```json
{
  "success": false,
  "error_code": "ATTACHMENT_TYPE_NOT_ALLOWED",
  "message": "不支援的附件類型",
  "allowed_types": ["image/jpeg", "image/png", "image/gif", "application/pdf"],
  "current_type": "application/x-msdownload"
}
```

---

## 錯誤回應格式

### 標準錯誤回應

所有錯誤回應都遵循以下格式：

```json
{
  "success": false,
  "error_code": "ERROR_CODE",
  "message": "錯誤訊息",
  "errors": {},
  "timestamp": "2025-10-11T08:00:00Z",
  "path": "/api/users",
  "request_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### 欄位說明

| 欄位 | 類型 | 說明 |
|-----|------|------|
| success | boolean | 固定為 false |
| error_code | string | 錯誤碼 |
| message | string | 錯誤訊息（繁體中文） |
| errors | object | 詳細錯誤資訊（可選） |
| timestamp | string | 錯誤發生時間（ISO 8601 格式） |
| path | string | 請求路徑 |
| request_id | string | 請求唯一識別碼（用於追蹤） |

### 驗證錯誤回應範例

```json
{
  "success": false,
  "error_code": "VALIDATION_ERROR",
  "message": "資料驗證失敗",
  "errors": {
    "username": [
      "使用者名稱為必填",
      "使用者名稱長度需介於 3-50 個字元"
    ],
    "email": [
      "電子郵件格式不正確"
    ],
    "password": [
      "密碼長度至少需要 8 個字元",
      "密碼必須包含至少一個數字"
    ]
  },
  "timestamp": "2025-10-11T08:00:00Z",
  "path": "/api/users",
  "request_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

---

## 錯誤處理最佳實踐

### 客戶端處理建議

1. **檢查 `success` 欄位**
   - 始終檢查 `success` 欄位判斷請求是否成功

2. **根據 `error_code` 處理**
   - 使用 `error_code` 而非 HTTP 狀態碼進行精確的錯誤處理
   - 不同的錯誤碼可能對應相同的 HTTP 狀態碼

3. **顯示友好的錯誤訊息**
   - 可以直接使用 `message` 欄位顯示給使用者
   - 或根據 `error_code` 顯示自訂的本地化訊息

4. **記錄 `request_id`**
   - 在回報問題時提供 `request_id` 有助於快速追蹤

### 範例程式碼

```javascript
try {
  const response = await fetch('/api/users', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(userData)
  });
  
  const data = await response.json();
  
  if (!data.success) {
    // 處理錯誤
    switch (data.error_code) {
      case 'USER_EMAIL_EXISTS':
        alert('此電子郵件已被使用，請使用其他電子郵件');
        break;
      case 'USER_USERNAME_EXISTS':
        alert('此使用者名稱已被使用，請使用其他名稱');
        break;
      case 'VALIDATION_ERROR':
        displayValidationErrors(data.errors);
        break;
      case 'UNAUTHORIZED':
        redirectToLogin();
        break;
      default:
        alert(data.message || '發生錯誤，請稍後再試');
    }
    return;
  }
  
  // 處理成功回應
  console.log('使用者建立成功:', data.data);
} catch (error) {
  console.error('網路錯誤:', error);
  alert('無法連接到伺服器，請檢查網路連線');
}
```

---

## 相關資源

- [API 使用指南](./API_USAGE_GUIDE.md)
- [開發者指南](./DEVELOPER_GUIDE.md)
- [API 使用率限制](./RATE_LIMITS.md)

---

**最後更新**: 2025-10-11  
**維護者**: AlleyNote 開發團隊
