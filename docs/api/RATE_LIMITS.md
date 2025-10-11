# API 使用率限制說明

**版本**: 1.0.0  
**最後更新**: 2025-10-11

---

## 📖 目錄

1. [概述](#概述)
2. [限制級別](#限制級別)
3. [端點分類限制](#端點分類限制)
4. [認證要求](#認證要求)
5. [限制回應](#限制回應)
6. [提升限制](#提升限制)

---

## 概述

為了確保系統穩定性和公平性，AlleyNote API 對所有端點實施使用率限制（Rate Limiting）。限制基於以下維度：

- **每分鐘請求次數**
- **每小時請求次數**
- **每天請求次數**

限制會根據以下因素調整：
- 端點類型（讀取/寫入/認證）
- 使用者角色（訪客/一般使用者/管理員）
- 操作敏感度

---

## 限制級別

### 預設限制

| 時間範圍 | 未認證使用者 | 已認證使用者 | 管理員 |
|---------|------------|------------|--------|
| 每分鐘 | 30 | 60 | 200 |
| 每小時 | 300 | 1,000 | 5,000 |
| 每天 | 3,000 | 10,000 | 50,000 |

### 回應標頭

每個 API 回應都會包含以下限制相關的標頭：

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1633036800
```

| 標頭 | 說明 |
|-----|------|
| X-RateLimit-Limit | 時間窗口內的請求限制 |
| X-RateLimit-Remaining | 剩餘可用請求次數 |
| X-RateLimit-Reset | 限制重置時間（Unix 時間戳） |

---

## 端點分類限制

### 1. 認證端點（嚴格限制）

用於防止暴力破解和惡意註冊。

#### 登入端點 (POST /api/auth/login)

| 時間範圍 | 限制次數 | 說明 |
|---------|---------|------|
| 每分鐘 | 5 | 防止暴力破解 |
| 每小時 | 20 | - |
| 每天 | 100 | - |

**超過限制回應**:
```json
{
  "success": false,
  "error_code": "TOO_MANY_REQUESTS",
  "message": "登入請求過於頻繁，請 5 分鐘後再試",
  "retry_after": 300
}
```

#### 註冊端點 (POST /api/auth/register)

| 時間範圍 | 限制次數 | 說明 |
|---------|---------|------|
| 每分鐘 | 3 | 防止惡意註冊 |
| 每小時 | 10 | - |
| 每天 | 50 | - |

#### Token 刷新端點 (POST /api/auth/refresh)

| 時間範圍 | 限制次數 |
|---------|---------|
| 每分鐘 | 10 |
| 每小時 | 100 |
| 每天 | 500 |

---

### 2. 查詢端點（寬鬆限制）

用於資料查詢的 GET 端點。

#### 列表查詢 (GET /api/users, /api/posts, etc.)

| 時間範圍 | 未認證 | 已認證 | 管理員 |
|---------|-------|--------|--------|
| 每分鐘 | 30 | 100 | 200 |
| 每小時 | 500 | 2,000 | 5,000 |
| 每天 | 5,000 | 20,000 | 50,000 |

#### 詳細資訊查詢 (GET /api/users/{id}, etc.)

| 時間範圍 | 未認證 | 已認證 | 管理員 |
|---------|-------|--------|--------|
| 每分鐘 | 40 | 120 | 200 |
| 每小時 | 800 | 3,000 | 5,000 |
| 每天 | 8,000 | 30,000 | 50,000 |

---

### 3. 寫入端點（中等限制）

用於建立、更新、刪除資源的端點。

#### 建立資源 (POST /api/users, /api/posts, etc.)

| 時間範圍 | 限制次數 | 說明 |
|---------|---------|------|
| 每分鐘 | 20 | 防止濫用 |
| 每小時 | 200 | - |
| 每天 | 1,000 | - |

#### 更新資源 (PUT /api/users/{id}, etc.)

| 時間範圍 | 限制次數 |
|---------|---------|
| 每分鐘 | 30 |
| 每小時 | 300 |
| 每天 | 2,000 |

#### 刪除資源 (DELETE /api/users/{id}, etc.)

| 時間範圍 | 限制次數 | 說明 |
|---------|---------|------|
| 每分鐘 | 10 | 刪除操作較敏感 |
| 每小時 | 100 | - |
| 每天 | 500 | - |

---

### 4. 搜尋端點（中等限制）

搜尋操作通常較消耗資源。

#### 搜尋 (GET /api/users?search=, etc.)

| 時間範圍 | 未認證 | 已認證 | 管理員 |
|---------|-------|--------|--------|
| 每分鐘 | 10 | 30 | 100 |
| 每小時 | 100 | 500 | 2,000 |
| 每天 | 1,000 | 5,000 | 20,000 |

---

### 5. 檔案上傳端點（最嚴格限制）

檔案上傳消耗大量頻寬和儲存空間。

#### 上傳附件 (POST /api/attachments)

| 時間範圍 | 限制次數 | 說明 |
|---------|---------|------|
| 每分鐘 | 5 | 防止濫用儲存空間 |
| 每小時 | 30 | - |
| 每天 | 100 | - |

**額外限制**:
- 單檔大小上限: 10 MB
- 允許的檔案類型: 
  - 圖片: `image/jpeg`, `image/png`, `image/gif`
  - 文件: `application/pdf`

**範例請求**:
```bash
curl -X POST http://localhost:8080/api/attachments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/image.jpg"
```

---

## 認證要求

### 公開端點（無需認證）

以下端點可在未認證的情況下訪問，但仍受限制：

| 端點 | 方法 | 限制 |
|-----|------|------|
| /api/health | GET | 每分鐘 100 次 |
| /api/docs | GET | 每分鐘 60 次 |
| /api/docs/ui | GET | 每分鐘 60 次|
| /api/posts | GET | 每分鐘 30 次 |
| /api/posts/{id} | GET | 每分鐘 40 次 |
| /api/tags | GET | 每分鐘 30 次 |
| /api/settings | GET | 每分鐘 30 次 |

### 需要認證的端點

大多數寫入操作和管理功能需要認證：

| 操作類型 | 認證要求 | 權限要求 |
|---------|---------|---------|
| 查詢公開資源 | ❌ | - |
| 查詢個人資源 | ✅ | - |
| 建立資源 | ✅ | 對應權限 |
| 更新資源 | ✅ | 對應權限 |
| 刪除資源 | ✅ | 對應權限 |
| 管理員操作 | ✅ | Admin 角色 |

### Token 使用

**標準請求格式**:
```bash
curl -X GET http://localhost:8080/api/users \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

**Token 過期處理**:
1. 使用 Refresh Token 刷新
2. 或重新登入取得新 Token

---

## 限制回應

### 達到限制時

當達到使用率限制時，API 會回傳 429 狀態碼：

```json
{
  "success": false,
  "error_code": "TOO_MANY_REQUESTS",
  "message": "請求過於頻繁，請稍後再試",
  "retry_after": 60,
  "limit": {
    "window": "minute",
    "limit": 60,
    "remaining": 0,
    "reset_at": "2025-10-11T08:01:00Z"
  }
}
```

### 回應標頭範例

```http
HTTP/1.1 429 Too Many Requests
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1633036860
Retry-After: 60
Content-Type: application/json

{
  "success": false,
  "error_code": "TOO_MANY_REQUESTS",
  "message": "請求過於頻繁，請 1 分鐘後再試"
}
```

---

## 提升限制

### 聯繫管理員

如果預設限制無法滿足您的需求，可以：

1. **說明使用情境**: 描述您的應用場景和預期流量
2. **提供使用統計**: 提供當前的使用數據
3. **申請特殊配額**: 提交正式的配額提升申請

### 企業方案

企業用戶可享有更高的限制：

| 時間範圍 | 企業方案 |
|---------|---------|
| 每分鐘 | 500 |
| 每小時 | 15,000 |
| 每天 | 150,000 |

### 最佳實踐

#### 1. 實作指數退避（Exponential Backoff）

```javascript
async function fetchWithRetry(url, options, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      const response = await fetch(url, options);
      
      if (response.status === 429) {
        const retryAfter = parseInt(response.headers.get('Retry-After') || '60');
        const waitTime = Math.min(retryAfter * Math.pow(2, i), 300); // 最多等 5 分鐘
        
        console.log(`Rate limited. Waiting ${waitTime} seconds...`);
        await new Promise(resolve => setTimeout(resolve, waitTime * 1000));
        continue;
      }
      
      return response;
    } catch (error) {
      if (i === maxRetries - 1) throw error;
    }
  }
}
```

#### 2. 快取回應

對於不常變動的資料，實作客戶端快取：

```javascript
const cache = new Map();
const CACHE_TTL = 60000; // 1 分鐘

async function fetchWithCache(url, options) {
  const cacheKey = `${url}_${JSON.stringify(options)}`;
  const cached = cache.get(cacheKey);
  
  if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
    return cached.data;
  }
  
  const response = await fetch(url, options);
  const data = await response.json();
  
  cache.set(cacheKey, {
    data,
    timestamp: Date.now()
  });
  
  return data;
}
```

#### 3. 批次請求

盡可能使用批次 API 減少請求次數：

```javascript
// 不好的做法：多次請求
for (const userId of userIds) {
  await fetch(`/api/users/${userId}`);
}

// 好的做法：單次批次請求（如果 API 支援）
await fetch('/api/users/batch', {
  method: 'POST',
  body: JSON.stringify({ ids: userIds })
});
```

#### 4. 監控使用量

定期檢查 API 使用量，避免達到限制：

```javascript
function checkRateLimitHeaders(response) {
  const limit = response.headers.get('X-RateLimit-Limit');
  const remaining = response.headers.get('X-RateLimit-Remaining');
  const reset = response.headers.get('X-RateLimit-Reset');
  
  console.log(`Rate limit: ${remaining}/${limit}`);
  
  if (remaining < limit * 0.1) { // 剩餘少於 10%
    console.warn('Approaching rate limit!');
  }
}
```

---

## 疑難排解

### Q: 為什麼我的請求被限制了？

**A**: 檢查以下幾點：
1. 確認您的請求頻率是否超過限制
2. 檢查是否有多個客戶端使用同一個帳號
3. 確認是否有自動化腳本在背景執行

### Q: 如何知道何時可以再次請求？

**A**: 查看回應中的 `Retry-After` 標頭或 `reset_at` 欄位。

### Q: 我的 Token 會影響限制嗎？

**A**: 是的，已認證使用者有更高的限制。確保使用有效的 Token。

### Q: 限制是針對 IP 還是使用者？

**A**: 
- 未認證請求：基於 IP 位址
- 已認證請求：基於使用者 ID
- 這樣可以避免共享 IP（如公司網路）的影響

---

## 相關資源

- [API 使用指南](./API_USAGE_GUIDE.md)
- [錯誤碼說明](./ERROR_CODES.md)
- [開發者指南](./DEVELOPER_GUIDE.md)

---

**最後更新**: 2025-10-11  
**維護者**: AlleyNote 開發團隊
