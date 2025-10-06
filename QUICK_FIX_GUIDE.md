# AlleyNote 快速修復指南

## 🎯 問題：登入後點擊功能跳回登入頁

### 核心原因
**後端 API 端點未實作**，前端請求回傳 401，觸發自動登出。

---

## ⚡ 快速解決方案（3 選 1）

### 方案 A：實作後端 API（推薦，完整解決）

#### 步驟 1：實作 `/api/auth/me` API

```php
// backend/app/Application/Controllers/Api/V1/AuthController.php

public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
{
    try {
        // 從 JWT Token 取得使用者 ID
        $userId = $request->getAttribute('user_id'); // 假設中介層已解析
        
        // 查詢使用者資訊
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => '使用者不存在'
            ], 404);
        }
        
        // 返回使用者資訊
        return $this->jsonResponse($response, [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'user',
                'createdAt' => $user['created_at'] ?? null,
            ]
        ]);
        
    } catch (\Exception $e) {
        return $this->jsonResponse($response, [
            'success' => false,
            'error' => '取得使用者資訊失敗'
        ], 500);
    }
}
```

#### 步驟 2：在路由中註冊

```php
// backend/routes/api.php 或類似檔案
$router->get('/auth/me', [AuthController::class, 'me']);
```

#### 步驟 3：實作文章管理 API

```php
// backend/app/Application/Controllers/Api/V1/PostController.php

class PostController extends BaseController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // 暫時返回 Mock 數據
        $posts = [
            [
                'id' => 1,
                'title' => '歡迎使用 AlleyNote',
                'content' => '這是一個測試文章',
                'status' => 'published',
                'created_at' => '2025-01-06T10:00:00Z',
                'updated_at' => '2025-01-06T10:00:00Z',
            ],
            [
                'id' => 2,
                'title' => '系統功能介紹',
                'content' => 'AlleyNote 提供完整的公告管理功能',
                'status' => 'published',
                'created_at' => '2025-01-05T10:00:00Z',
                'updated_at' => '2025-01-05T10:00:00Z',
            ],
        ];
        
        return $this->jsonResponse($response, [
            'success' => true,
            'data' => $posts,
            'total' => count($posts),
            'page' => 1,
            'perPage' => 10
        ]);
    }
    
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? null;
        
        // Mock 數據
        $post = [
            'id' => $id,
            'title' => '測試文章',
            'content' => '<p>這是文章內容</p>',
            'status' => 'published',
            'tags' => [],
            'created_at' => '2025-01-06T10:00:00Z',
            'updated_at' => '2025-01-06T10:00:00Z',
        ];
        
        return $this->jsonResponse($response, [
            'success' => true,
            'data' => $post
        ]);
    }
}
```

---

### 方案 B：前端使用 Mock 數據（快速測試）

#### 修改 `frontend/src/api/modules/posts.js`

```javascript
import apiClient from '../client.js';
import { API_ENDPOINTS } from '../config.js';

// Mock 數據
const mockPosts = [
  {
    id: 1,
    title: '歡迎使用 AlleyNote',
    content: '這是一個測試文章',
    status: 'published',
    created_at: '2025-01-06T10:00:00Z'
  },
  {
    id: 2,
    title: '系統功能介紹',
    content: 'AlleyNote 提供完整的公告管理功能',
    status: 'draft',
    created_at: '2025-01-05T10:00:00Z'
  }
];

export const postsAPI = {
  async list(params = {}) {
    // 開發模式使用 Mock 數據
    if (import.meta.env.DEV || localStorage.getItem('use_mock') === 'true') {
      return new Promise(resolve => {
        setTimeout(() => {
          resolve({
            data: mockPosts,
            total: mockPosts.length,
            page: 1,
            perPage: 10
          });
        }, 500);
      });
    }
    
    // 正式環境調用真實 API
    const response = await apiClient.get(API_ENDPOINTS.POSTS.LIST, { params });
    return response.data;
  },
  
  async get(id) {
    if (import.meta.env.DEV || localStorage.getItem('use_mock') === 'true') {
      return new Promise(resolve => {
        setTimeout(() => {
          const post = mockPosts.find(p => p.id === parseInt(id));
          resolve({ data: post || mockPosts[0] });
        }, 300);
      });
    }
    
    const response = await apiClient.get(API_ENDPOINTS.POSTS.DETAIL(id));
    return response.data;
  }
};
```

#### 啟用 Mock 模式

在瀏覽器 Console 執行：
```javascript
localStorage.setItem('use_mock', 'true');
location.reload();
```

---

### 方案 C：改進 401 錯誤處理（臨時方案）

#### 修改 `frontend/src/api/interceptors/response.js`

```javascript
export function responseErrorInterceptor(error) {
  if (API_CONFIG.features.enableLogger) {
    console.error('[API Response Error]', error);
  }

  if (!error.response) {
    return Promise.reject(
      new APIError('NETWORK_ERROR', '網路連線失敗，請檢查您的網路連線', 0)
    );
  }

  const { status, data } = error.response;

  // 401 未授權 - 改用路由導航
  if (status === 401) {
    // 檢查是否為登入相關的 API
    const loginRelatedPaths = ['/auth/login', '/auth/register', '/auth/forgot-password'];
    const isLoginApi = loginRelatedPaths.some(path => error.config?.url?.includes(path));
    
    if (!isLoginApi) {
      // 非登入 API 才清除 Token 和導向登入頁
      tokenManager.removeToken();
      
      // 動態導入路由避免循環依賴
      import('../../router/index.js').then(({ router }) => {
        if (!window.location.pathname.includes('/login')) {
          router.navigate('/login');
        }
      });
    }
    
    return Promise.reject(new APIError('UNAUTHORIZED', '登入已過期，請重新登入', status));
  }

  // ... 其他錯誤處理
}
```

---

## 🧪 測試步驟

### 測試方案 A（後端 API）

```bash
# 1. 測試 /api/auth/me
curl -X GET http://localhost:8080/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"

# 2. 測試文章列表
curl -X GET http://localhost:8080/api/posts \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 測試方案 B（前端 Mock）

1. 開啟 http://localhost:8080/login
2. 登入（admin@example.com / password）
3. 在 Console 執行：`localStorage.setItem('use_mock', 'true')`
4. 重新載入頁面
5. 點擊「文章管理」
6. 應該可以看到 Mock 數據

### 測試方案 C（改進錯誤處理）

1. 重新建置前端：`npm run build`
2. 重啟 nginx：`docker compose restart nginx`
3. 登入系統
4. 點擊「文章管理」
5. 觀察是否還會跳回登入頁

---

## 📋 完整實作檢查清單

### 後端 API（推薦順序）

- [ ] **認證 API**
  - [ ] POST `/api/auth/login` ✅（已完成）
  - [ ] GET `/api/auth/me` ⚠️（必須）
  - [ ] POST `/api/auth/logout`
  - [ ] POST `/api/auth/refresh`
  - [ ] POST `/api/auth/forgot-password`
  - [ ] POST `/api/auth/reset-password`

- [ ] **文章 API**
  - [ ] GET `/api/posts` ⚠️（必須）
  - [ ] GET `/api/posts/:id` ⚠️（必須）
  - [ ] POST `/api/posts`
  - [ ] PUT `/api/posts/:id`
  - [ ] DELETE `/api/posts/:id`
  - [ ] PUT `/api/posts/:id/publish`
  - [ ] PUT `/api/posts/:id/draft`

- [ ] **標籤 API**
  - [ ] GET `/api/tags`
  - [ ] GET `/api/tags/:id`
  - [ ] POST `/api/tags`
  - [ ] PUT `/api/tags/:id`
  - [ ] DELETE `/api/tags/:id`

- [ ] **使用者 API**
  - [ ] GET `/api/users`
  - [ ] GET `/api/users/:id`
  - [ ] POST `/api/users`
  - [ ] PUT `/api/users/:id`
  - [ ] DELETE `/api/users/:id`

- [ ] **檔案上傳 API**
  - [ ] POST `/api/attachments/upload`
  - [ ] DELETE `/api/attachments/:id`

- [ ] **統計 API**
  - [ ] GET `/api/statistics/overview`
  - [ ] GET `/api/statistics/posts`
  - [ ] GET `/api/statistics/views`

---

## 💡 建議實作順序

### 第 1 天：核心認證（2-3 小時）
1. ✅ 實作 `/api/auth/me`
2. ✅ 實作 `/api/posts`（基礎版本）
3. ✅ 測試登入和頁面導航

### 第 2-3 天：文章管理（6-8 小時）
4. 完整的文章 CRUD
5. 檔案上傳功能
6. 標籤關聯

### 第 4-5 天：其他功能（8-10 小時）
7. 標籤管理
8. 使用者管理
9. 統計數據

### 第 6-7 天：測試和優化（5-8 小時）
10. 端到端測試
11. 效能優化
12. 文件更新

---

## 🎯 成功標準

### 基本功能（必須）
- [x] 使用者可以登入
- [ ] 登入後可以導航到所有頁面
- [ ] 文章列表可以正常顯示
- [ ] 可以新增、編輯、刪除文章

### 進階功能（建議）
- [ ] 標籤管理完整運作
- [ ] 使用者管理功能正常
- [ ] 統計數據正確顯示
- [ ] 檔案上傳功能正常

### 品質要求（優化）
- [ ] 所有 API 回應時間 < 500ms
- [ ] 前端無 Console 錯誤
- [ ] 響應式設計在所有裝置正常
- [ ] 完整的錯誤處理

---

## 📞 需要協助？

如果遇到問題，請檢查：

1. **JWT Token 是否正確**
   - 瀏覽器 DevTools → Application → Session Storage
   - 查看 `alleynote_token` 是否存在

2. **API 請求是否包含 Token**
   - 瀏覽器 DevTools → Network
   - 查看請求 Headers 是否有 `Authorization: Bearer ...`

3. **後端是否正確解析 Token**
   - 檢查後端日誌
   - 驗證 JWT 中介層是否正常運作

4. **資料庫是否正確初始化**
   - 檢查 users 表是否有資料
   - 檢查 refresh_tokens 表是否正確創建

---

**建立時間**：2025-01-06  
**最後更新**：2025-01-06  
**版本**：v1.0
