# AlleyNote 前端安全檢查清單

## 📋 目錄

1. [概述](#概述)
2. [XSS 防護](#xss-防護)
3. [CSRF 防護](#csrf-防護)
4. [認證與授權](#認證與授權)
5. [資料驗證](#資料驗證)
6. [安全標頭](#安全標頭)
7. [第三方套件安全](#第三方套件安全)
8. [安全編碼實踐](#安全編碼實踐)
9. [部署前檢查清單](#部署前檢查清單)

---

## 概述

本文件列出 AlleyNote 前端開發過程中必須遵守的安全規範與檢查項目，確保應用程式免受常見的 Web 安全威脅。

### 安全威脅分類

| 威脅類型 | 風險等級 | 防護策略 |
|---------|---------|---------|
| XSS (跨站腳本攻擊) | 🔴 高 | 輸入淨化、CSP |
| CSRF (跨站請求偽造) | 🔴 高 | CSRF Token、SameSite Cookie |
| Injection | 🟠 中 | 輸入驗證、參數化查詢 |
| 敏感資料洩漏 | 🟠 中 | HTTPS、安全儲存 |
| 點擊劫持 | 🟡 低 | X-Frame-Options |
| 依賴套件漏洞 | 🟡 低 | 定期更新、掃描 |

---

## XSS 防護

### 1. 輸出編碼

**❌ 危險做法 - 直接插入 HTML**

```javascript
// 永遠不要這樣做！
element.innerHTML = userInput;
element.innerHTML = `<div>${userInput}</div>`;
```

**✅ 安全做法 - 使用 textContent**

```javascript
// 顯示純文字
element.textContent = userInput;

// 或建立文字節點
const textNode = document.createTextNode(userInput);
element.appendChild(textNode);
```

### 2. HTML 淨化

使用 **DOMPurify** 淨化 HTML 內容

**安裝**

```bash
npm install dompurify
```

**使用範例**

```javascript
import DOMPurify from 'dompurify';

/**
 * 淨化 HTML 內容（用於顯示 CKEditor 產生的內容）
 * @param {string} dirtyHTML - 未淨化的 HTML
 * @returns {string} 淨化後的 HTML
 */
function sanitizeHTML(dirtyHTML) {
  return DOMPurify.sanitize(dirtyHTML, {
    ALLOWED_TAGS: [
      'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
      'p', 'br', 'hr',
      'strong', 'em', 'u', 's', 'code', 'pre',
      'a', 'img',
      'ul', 'ol', 'li',
      'blockquote',
      'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ],
    ALLOWED_ATTR: [
      'href', 'src', 'alt', 'title', 'class', 'id',
    ],
    ALLOW_DATA_ATTR: false, // 不允許 data-* 屬性
  });
}

// 顯示文章內容
const articleContent = sanitizeHTML(post.content);
contentElement.innerHTML = articleContent;
```

### 3. URL 編碼

```javascript
/**
 * 安全的 URL 建構
 */
function buildURL(base, params) {
  const url = new URL(base);
  
  Object.entries(params).forEach(([key, value]) => {
    url.searchParams.set(key, value); // 自動編碼
  });
  
  return url.toString();
}

// 使用範例
const searchURL = buildURL('/posts', {
  search: userInput, // 自動編碼特殊字元
  page: 1,
});
```

### 4. 事件處理器安全

**❌ 危險做法**

```javascript
// 不要動態建立事件處理器字串
element.setAttribute('onclick', `doSomething('${userInput}')`);
```

**✅ 安全做法**

```javascript
// 使用 addEventListener
element.addEventListener('click', () => {
  doSomething(userInput);
});
```

---

## CSRF 防護

### 1. CSRF Token 實作

**自動在所有請求加入 CSRF Token**

```javascript
// src/api/interceptors/request.js
import { csrfManager } from '../../utils/csrfManager.js';

export function requestInterceptor(config) {
  // 對於 POST, PUT, PATCH, DELETE 請求加入 CSRF Token
  const needsCsrf = ['post', 'put', 'patch', 'delete'].includes(
    config.method?.toLowerCase()
  );
  
  if (needsCsrf) {
    const csrfToken = csrfManager.getToken();
    if (csrfToken) {
      config.headers['X-CSRF-TOKEN'] = csrfToken;
    } else {
      console.error('Missing CSRF Token!');
    }
  }
  
  return config;
}
```

### 2. SameSite Cookie

確保後端設定 Cookie 時使用 `SameSite` 屬性：

```http
Set-Cookie: session=abc123; SameSite=Strict; Secure; HttpOnly
```

前端檢查：

```javascript
// 開發環境檢查 CSRF Token 是否存在
if (import.meta.env.DEV) {
  if (!csrfManager.hasToken()) {
    console.warn('CSRF Token not found! Check backend cookie settings.');
  }
}
```

### 3. Double Submit Cookie 模式

```javascript
/**
 * 雙重提交 Cookie 驗證
 */
function generateCSRFToken() {
  // 產生隨機 Token
  const token = crypto.randomUUID();
  
  // 存到 Cookie
  document.cookie = `csrf_token=${token}; SameSite=Strict; Secure`;
  
  return token;
}

// 在請求中同時送出
function makeSecureRequest(url, data) {
  const token = getCookie('csrf_token') || generateCSRFToken();
  
  return axios.post(url, data, {
    headers: {
      'X-CSRF-TOKEN': token,
    },
  });
}
```

---

## 認證與授權

### 1. JWT Token 安全儲存

**🔴 不安全 - LocalStorage**

```javascript
// ❌ 容易受到 XSS 攻擊
localStorage.setItem('token', jwt);
```

**✅ 較安全 - SessionStorage**

```javascript
// ✅ 關閉瀏覽器即清除
sessionStorage.setItem('token', jwt);
```

**🔒 最安全 - HttpOnly Cookie（建議後端設定）**

```http
Set-Cookie: token=jwt_value; HttpOnly; Secure; SameSite=Strict
```

### 2. Token 過期處理

```javascript
/**
 * 檢查 Token 是否過期
 */
function isTokenExpired(token) {
  try {
    const payload = JSON.parse(atob(token.split('.')[1]));
    const expiresAt = payload.exp * 1000; // 轉為毫秒
    return Date.now() >= expiresAt;
  } catch {
    return true;
  }
}

/**
 * 自動重新整理 Token
 */
async function refreshTokenIfNeeded() {
  const token = tokenManager.getToken();
  
  if (!token || isTokenExpired(token)) {
    try {
      await authAPI.refresh();
    } catch (error) {
      // Token 無法重新整理，導向登入頁
      window.location.href = '/login';
    }
  }
}

// 定期檢查 Token（每 5 分鐘）
setInterval(refreshTokenIfNeeded, 5 * 60 * 1000);
```

### 3. 權限檢查

```javascript
/**
 * 權限檢查中介軟體
 */
function requireAuth(requiredRole = null) {
  return function(next) {
    // 檢查是否已登入
    if (!globalGetters.isAuthenticated()) {
      window.location.href = '/login';
      return;
    }
    
    // 檢查角色權限
    if (requiredRole) {
      const userRole = globalGetters.getUserRole();
      if (userRole !== requiredRole && userRole !== 'super_admin') {
        showToast('您沒有權限訪問此頁面', 'error');
        window.location.href = '/admin/dashboard';
        return;
      }
    }
    
    // 執行下一步
    next();
  };
}

// 使用範例
router.on('/admin/users', requireAuth('super_admin'), () => {
  loadUsersPage();
});
```

### 4. 敏感操作二次確認

```javascript
/**
 * 刪除操作需要確認
 */
async function deletePost(postId) {
  const confirmed = await showConfirmDialog({
    title: '確認刪除',
    message: '此操作無法復原，確定要刪除這篇文章嗎？',
    confirmText: '刪除',
    cancelText: '取消',
    type: 'danger',
  });
  
  if (!confirmed) {
    return;
  }
  
  try {
    await postsAPI.delete(postId);
    showToast('文章已刪除', 'success');
  } catch (error) {
    handleAPIError(error);
  }
}
```

---

## 資料驗證

### 1. 前端驗證（UX 優化）

```javascript
import validator from 'validator';

/**
 * 安全的輸入驗證
 */
const secureValidators = {
  /**
   * Email 驗證
   */
  email: (value) => {
    if (!value) return '電子郵件為必填';
    
    // 使用專業驗證庫
    if (!validator.isEmail(value)) {
      return '請輸入有效的電子郵件';
    }
    
    // 額外檢查長度
    if (value.length > 255) {
      return '電子郵件過長';
    }
    
    return true;
  },
  
  /**
   * 密碼強度驗證
   */
  password: (value) => {
    if (!value) return '密碼為必填';
    
    if (value.length < 8) {
      return '密碼至少需要 8 個字元';
    }
    
    if (value.length > 128) {
      return '密碼過長';
    }
    
    // 檢查是否包含大小寫字母、數字
    if (!validator.isStrongPassword(value, {
      minLength: 8,
      minLowercase: 1,
      minUppercase: 1,
      minNumbers: 1,
      minSymbols: 0,
    })) {
      return '密碼需包含大小寫字母和數字';
    }
    
    return true;
  },
  
  /**
   * URL 驗證
   */
  url: (value) => {
    if (!value) return true; // 選填
    
    if (!validator.isURL(value, {
      protocols: ['http', 'https'],
      require_protocol: true,
    })) {
      return '請輸入有效的 URL';
    }
    
    return true;
  },
  
  /**
   * 防止 SQL Injection 字元
   */
  noSQLInjection: (value) => {
    const dangerousPatterns = [
      /(\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i,
      /(--|;|\/\*|\*\/)/,
      /(\bOR\b|\bAND\b).*=.*=/i,
    ];
    
    for (const pattern of dangerousPatterns) {
      if (pattern.test(value)) {
        return '輸入包含不允許的字元';
      }
    }
    
    return true;
  },
};
```

### 2. 檔案上傳驗證

```javascript
/**
 * 安全的檔案上傳驗證
 */
class FileUploadValidator {
  constructor(options = {}) {
    this.allowedTypes = options.allowedTypes || ['image/jpeg', 'image/png', 'image/gif'];
    this.maxSize = options.maxSize || 5 * 1024 * 1024; // 5MB
    this.allowedExtensions = options.allowedExtensions || ['.jpg', '.jpeg', '.png', '.gif'];
  }

  /**
   * 驗證檔案
   */
  validate(file) {
    const errors = [];
    
    // 檢查檔案大小
    if (file.size > this.maxSize) {
      errors.push(`檔案大小不能超過 ${this.maxSize / 1024 / 1024}MB`);
    }
    
    // 檢查 MIME 類型
    if (!this.allowedTypes.includes(file.type)) {
      errors.push(`不支援的檔案類型: ${file.type}`);
    }
    
    // 檢查副檔名
    const extension = '.' + file.name.split('.').pop().toLowerCase();
    if (!this.allowedExtensions.includes(extension)) {
      errors.push(`不允許的副檔名: ${extension}`);
    }
    
    // 檢查檔案名稱（防止路徑遍歷攻擊）
    if (file.name.includes('..') || file.name.includes('/') || file.name.includes('\\')) {
      errors.push('檔案名稱包含不允許的字元');
    }
    
    return {
      valid: errors.length === 0,
      errors,
    };
  }

  /**
   * 驗證圖片尺寸
   */
  async validateImageDimensions(file, maxWidth = 4096, maxHeight = 4096) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      const url = URL.createObjectURL(file);
      
      img.onload = () => {
        URL.revokeObjectURL(url);
        
        if (img.width > maxWidth || img.height > maxHeight) {
          resolve({
            valid: false,
            errors: [`圖片尺寸不能超過 ${maxWidth}x${maxHeight}`],
          });
        } else {
          resolve({ valid: true, errors: [] });
        }
      };
      
      img.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error('無法讀取圖片'));
      };
      
      img.src = url;
    });
  }
}

// 使用範例
const validator = new FileUploadValidator({
  allowedTypes: ['image/jpeg', 'image/png'],
  maxSize: 2 * 1024 * 1024, // 2MB
});

async function handleFileUpload(file) {
  // 基本驗證
  const basicValidation = validator.validate(file);
  if (!basicValidation.valid) {
    showToast(basicValidation.errors.join(', '), 'error');
    return;
  }
  
  // 圖片尺寸驗證
  const dimensionValidation = await validator.validateImageDimensions(file);
  if (!dimensionValidation.valid) {
    showToast(dimensionValidation.errors.join(', '), 'error');
    return;
  }
  
  // 上傳檔案
  try {
    const result = await attachmentsAPI.upload(file);
    showToast('上傳成功', 'success');
    return result;
  } catch (error) {
    handleAPIError(error);
  }
}
```

---

## 安全標頭

### 1. Content Security Policy (CSP)

在 HTML 中設定 CSP（或由後端設定）：

```html
<meta http-equiv="Content-Security-Policy" 
      content="
        default-src 'self';
        script-src 'self' https://cdn.ckeditor.com https://cdn.tailwindcss.com;
        style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
        font-src 'self' https://fonts.gstatic.com;
        img-src 'self' data: https:;
        connect-src 'self' https://api.alleynote.com;
      ">
```

### 2. 其他安全標頭

```html
<!-- 防止點擊劫持 -->
<meta http-equiv="X-Frame-Options" content="DENY">

<!-- 防止 MIME 類型嗅探 -->
<meta http-equiv="X-Content-Type-Options" content="nosniff">

<!-- XSS 防護 -->
<meta http-equiv="X-XSS-Protection" content="1; mode=block">

<!-- Referrer 政策 -->
<meta name="referrer" content="strict-origin-when-cross-origin">
```

### 3. 檢查安全標頭

```javascript
/**
 * 檢查回應標頭（開發環境）
 */
function checkSecurityHeaders(response) {
  if (import.meta.env.DEV) {
    const requiredHeaders = [
      'X-Content-Type-Options',
      'X-Frame-Options',
      'X-XSS-Protection',
    ];
    
    requiredHeaders.forEach(header => {
      if (!response.headers[header.toLowerCase()]) {
        console.warn(`Missing security header: ${header}`);
      }
    });
  }
}
```

---

## 第三方套件安全

### 1. 定期掃描漏洞

```bash
# 檢查套件漏洞
npm audit

# 自動修復可修復的漏洞
npm audit fix

# 查看詳細報告
npm audit --json
```

### 2. 限制套件權限

```javascript
// vite.config.js
export default {
  build: {
    rollupOptions: {
      external: [
        // 排除不需要打包的套件
      ],
    },
  },
  server: {
    fs: {
      // 限制可訪問的檔案範圍
      strict: true,
      allow: ['./src', './public'],
    },
  },
};
```

### 3. 子資源完整性（SRI）

```html
<!-- 使用 CDN 時加入 integrity 屬性 -->
<script 
  src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"
  integrity="sha384-..."
  crossorigin="anonymous">
</script>
```

---

## 安全編碼實踐

### 1. 避免 eval() 與 Function()

```javascript
// ❌ 危險
eval(userInput);
new Function(userInput)();

// ✅ 使用安全的替代方案
JSON.parse(userInput); // 解析 JSON
```

### 2. 安全的動態屬性訪問

```javascript
// ❌ 危險 - 可能訪問原型鏈
function getProperty(obj, key) {
  return obj[key];
}

// ✅ 安全 - 使用 hasOwnProperty
function getProperty(obj, key) {
  if (Object.prototype.hasOwnProperty.call(obj, key)) {
    return obj[key];
  }
  return undefined;
}
```

### 3. 防止原型污染

```javascript
// ❌ 危險
function merge(target, source) {
  for (let key in source) {
    target[key] = source[key];
  }
}

// ✅ 安全
function merge(target, source) {
  for (let key in source) {
    if (Object.prototype.hasOwnProperty.call(source, key)) {
      // 防止 __proto__ 污染
      if (key === '__proto__' || key === 'constructor' || key === 'prototype') {
        continue;
      }
      target[key] = source[key];
    }
  }
}
```

### 4. 安全的 RegExp

```javascript
// ❌ 危險 - ReDoS 攻擊
const unsafeRegex = /^(a+)+$/;
unsafeRegex.test(userInput); // 可能造成 CPU 100%

// ✅ 安全 - 限制輸入長度
function safeRegexTest(pattern, input, maxLength = 1000) {
  if (input.length > maxLength) {
    return false;
  }
  return pattern.test(input);
}
```

---

## 部署前檢查清單

### 🔒 認證與授權

- [ ] JWT Token 使用 SessionStorage 或 HttpOnly Cookie 儲存
- [ ] Token 有適當的過期時間（建議 1 小時）
- [ ] 實作 Token 自動刷新機制
- [ ] 敏感頁面需要權限檢查
- [ ] 登出時清除所有本地資料

### 🛡️ XSS 防護

- [ ] 使用者輸入都經過 DOMPurify 淨化
- [ ] 不使用 `innerHTML` 插入使用者內容
- [ ] CKEditor 內容顯示前經過淨化
- [ ] URL 參數都經過編碼
- [ ] 不使用 `eval()` 或 `Function()`

### 🔐 CSRF 防護

- [ ] 所有 POST/PUT/DELETE 請求都包含 CSRF Token
- [ ] Cookie 設定 `SameSite=Strict` 或 `SameSite=Lax`
- [ ] 敏感操作需要二次確認

### 📝 資料驗證

- [ ] 前端驗證所有表單輸入
- [ ] 檔案上傳有大小和類型限制
- [ ] 圖片上傳驗證尺寸
- [ ] 防止 SQL Injection 字元

### 🔧 安全標頭

- [ ] 設定 CSP (Content-Security-Policy)
- [ ] 設定 X-Frame-Options
- [ ] 設定 X-Content-Type-Options
- [ ] 設定 X-XSS-Protection
- [ ] HTTPS 強制使用

### 📦 套件安全

- [ ] 執行 `npm audit` 無高風險漏洞
- [ ] 套件版本都已更新到最新穩定版
- [ ] 移除未使用的套件
- [ ] CDN 資源使用 SRI

### 🔍 程式碼審查

- [ ] 無敏感資訊（API Key、密碼）寫在程式碼中
- [ ] 無 console.log 敏感資料
- [ ] 錯誤訊息不洩漏系統資訊
- [ ] 無註解掉的敏感程式碼

### 🌐 網路安全

- [ ] 所有 API 請求使用 HTTPS
- [ ] 設定適當的 CORS 政策
- [ ] API 有速率限制
- [ ] 使用者操作有適當的防呆機制

---

## 總結

遵循本檢查清單，可以大幅提升 AlleyNote 前端的安全性。記住：

1. ✅ **永遠不信任使用者輸入**
2. ✅ **使用專業的安全庫（DOMPurify、validator.js）**
3. ✅ **定期更新依賴套件**
4. ✅ **使用 HTTPS**
5. ✅ **實作多層防護**

**安全是持續的過程，而非一次性的任務。**
