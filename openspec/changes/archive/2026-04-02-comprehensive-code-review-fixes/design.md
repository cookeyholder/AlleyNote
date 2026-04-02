## 開發流程規範

> 所有設計方案的實作嚴格遵循 **TDD（測試驅動開發）** 與 **高頻提交** 策略：
>
> 1. **TDD 迴圈**：Red（先寫失敗測試）→ Green（實作通過）→ Refactor（重構最佳化）
> 2. **高頻提交**：每個測試、實作、重構步驟獨立提交，提交訊息遵循 `type(scope): description` 格式
> 3. **品質把關**：每次提交前確認所有測試通過、PHPStan Level 10 無錯誤、PHP CS Fixer 格式化完成

---

## 1. 格式化文字淨化修復（RichTextProcessorService）

### 問題描述

`RichTextProcessorService::processContent()` 的所有分支（admin / extended / basic）皆直接 `return $content`，HTMLPurifier 從未被呼叫。

### 設計方案

```php
public function processContent(string $content, string $userLevel = 'basic'): string {
    $purifier = $this->getPurifierForLevel($userLevel);
    return $purifier->purify($content);
}

private function getPurifierForLevel(string $level): HTMLPurifier {
    return match($level) {
        'admin'    => $this->adminPurifier,
        'extended' => $this->extendedPurifier,
        default    => $this->basicPurifier,
    };
}
```

### HTMLPurifier 各級別設定

| 等級         | 允許標籤                                    | 禁止標籤                            |
| ------------ | ------------------------------------------- | ----------------------------------- |
| **basic**    | p, strong, em, br, ul, ol, li, a, img       | 其餘全部禁止                        |
| **extended** | basic + table, blockquote, code, pre, h1-h6 | script, iframe, object, embed, form |
| **admin**    | 完整標籤集                                  | script, iframe, object, embed, form |

### 效能考量

- 使用 HTMLPurifier 快取，路徑改為 `storage/cache/htmlpurifier`（非硬編碼 `/tmp`）
- 對已淨化內容使用 SHA-256 雜湊比對，避免重複淨化

---

## 2. 內容審核服務修復（ContentModerationService）

### 問題描述

所有審核邏輯被註解，`moderateContent()` 永遠回傳 `status: 'approved', confidence: 100`。

### 設計方案

恢復以下審核流程：

1. **敏感字詞過濾**：載入敏感字詞清單，使用高效字串匹配演算法
2. **XSS 特徵偵測**：偵測 script 標籤、事件處理器、javascript: 協定
3. **垃圾內容評分**：基於連結數量、重複內容、關鍵字密度計算 spam score
4. **品質檢查**：最小長度、標題-內容比例

```php
public function moderateContent(string $content): ModerationResult {
    $issues = [];
    $score  = 0;

    $xssResult = $this->detectXSS($content);
    if ($xssResult->detected) {
        $issues[] = $xssResult;
        $score   += 50;
    }

    $sensitiveResult = $this->detectSensitiveWords($content);
    if (!empty($sensitiveResult)) {
        $issues[] = $sensitiveResult;
        $score   += 30;
    }

    $spamScore = $this->calculateSpamScore($content);
    if ($spamScore > 0.7) {
        $issues[] = 'spam';
        $score   += 20;
    }

    return new ModerationResult(
        status:     $score > 50 ? 'rejected' : ($score > 20 ? 'review' : 'approved'),
        confidence: max(0, 100 - $score),
        issues:     $issues
    );
}
```

---

## 3. 消除 PostController 平行程式碼路徑

### 問題描述

`PostController.php`（649 行）使用直接 PDO 查詢，完全繞過 PostService、PostAggregate、驗證、淨化、快取與領域事件。

### 設計方案

**策略：刪除 PostController，統一使用 `Api/V1/PostController`**

1. 檢查所有路由是否已由 `Api/V1/PostController` 涵蓋
2. 若有遺漏功能，在領域服務層（PostService）補充
3. 從 `routes.php` 移除舊路由
4. 刪除 `PostController.php` 檔案

### 資料庫交易保護

在 `PostService::createPost()` 中包裝交易：

```php
public function createPost(CreatePostDTO $dto): Post {
    $this->db->beginTransaction();
    try {
        $post = $this->postRepository->create($dto);
        if (!empty($dto->tags)) {
            $this->postRepository->attachTags($post->getId(), $dto->tags);
        }
        $this->db->commit();
        return $post;
    } catch (\Throwable $e) {
        $this->db->rollBack();
        throw $e;
    }
}
```

---

## 4. JWT 安全強化

### 移除查詢字串 Token 支援

```php
// JwtAuthenticationMiddleware::extractToken()
private function extractToken(ServerRequestInterface $request): ?string {
    // 僅允許 Authorization Header
    $authHeader = $request->getHeaderLine('Authorization');
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $matches[1];
    }
    return null;
}
```

### display_errors 環境化

```php
// public/index.php
$displayErrors = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
ini_set('display_errors', $displayErrors ? '1' : '0');
ini_set('display_startup_errors', $displayErrors ? '1' : '0');
```

---

## 5. 前端 XSS 防護

### 一致性的 HTML 跳脫

在所有 `innerHTML` 注入點使用 `escapeHtml()`：

```javascript
// 建立統一的模板輔助函式
function safeHTML(strings, ...values) {
  return strings.reduce((result, str, i) => {
    const value = i < values.length ? escapeHtml(String(values[i])) : "";
    return result + str + value;
  }, "");
}

// 使用方式
const html = safeHTML`<h1>${post.title}</h1><p>${post.author}</p>`;
```

### Math.random() 修復

```javascript
// passwordGenerator.js
function secureRandom(max) {
  const array = new Uint32Array(1);
  crypto.getRandomValues(array);
  return array[0] % max;
}
```

### require() 修復

```javascript
// main.js — 改用 ES Module import
import { router } from "./utils/router.js";
window.navigateTo = (path) => {
  router.navigate(path);
};
```

---

## 6. JwtAuthorizationMiddleware 拆分

### 設計：策略模式 + 政策類別

```
app/Application/Middleware/
├── JwtAuthorizationMiddleware.php  （協調器，約 50 行）
└── Policies/
    ├── RolePolicy.php              （RBAC 檢查）
    ├── PermissionPolicy.php        （權限檢查）
    ├── OwnershipPolicy.php         （所有權檢查）
    ├── TimeAccessPolicy.php        （時間限制檢查）
    └── IpAccessPolicy.php          （IP 限制檢查）
```

```php
interface AuthorizationPolicy {
    public function check(ServerRequestInterface $request, array $context): AuthorizationResult;
}
```

`JwtAuthorizationMiddleware` 僅負責：

1. 收集所有政策
2. 依序執行檢查
3. 回傳第一個失敗結果或通過

---

## 7. Nginx 正式環境設定修復

### FastCGI 主機名稱

```nginx
# 將 fastcgi_pass php:9000 改為
fastcgi_pass web:9000;
```

### SSL 憑證路徑

```nginx
# 使用 Certbot 實際路徑
ssl_certificate     /etc/letsencrypt/live/${SSL_DOMAIN}/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/${SSL_DOMAIN}/privkey.pem;
```

### server_name 變數化

```nginx
server_name ${SSL_DOMAIN};
```

---

## 8. CSRF 中介層實作

```php
class CsrfMiddleware implements MiddlewareInterface {
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $token        = $request->getHeaderLine('X-CSRF-TOKEN');
            $sessionToken = $this->session->get('csrf_token');

            if (!hash_equals($sessionToken, $token)) {
                return new JsonResponse(['error' => '無效的 CSRF Token'], 403);
            }
        }
        return $handler->handle($request);
    }
}
```

在 `routes.php` 中為所有狀態變更路由新增 `csrf` 中介層。

---

## 9. CI 管線修復

### ci.yml

```yaml
# 移除 || true，讓 PHPStan 失敗時管線失敗
- name: 靜態分析
  run: composer analyse
```

### frontend-ci.yml

```yaml
# 移除 continue-on-error
- name: 安全審計
  run: npm audit --audit-level=moderate
```

---

## 10. 清理工作

### 移除檔案

| 檔案                                        | 原因                      |
| ------------------------------------------- | ------------------------- |
| `backend/test_private_key.pem`              | 測試私鑰不應提交          |
| `backend/test_public_key.pem`               | 測試公鑰不應提交          |
| `frontend/js/api/auth.js`                   | 遺留相容層                |
| `frontend/js/api/posts.js`                  | 遺留相容層                |
| `frontend/js/api/users.js`                  | 遺留相容層                |
| `frontend/js/api/statistics.js`             | 遺留相容層                |
| `frontend/js/components/CKEditorWrapper.js` | 與 RichTextEditor.js 重複 |
| `backend/tests/manual/`                     | 非正式 PHPUnit 測試       |

### .gitignore 更新

```
.env.testing
*.pem
!backend/keys/.gitkeep
```

### .env.example 更新

```
ADMIN_PASSWORD=<請產生強密碼>
```

---

## 11. 批次刪除 API

### 新增端點

```
DELETE /api/v1/posts/batch
請求本體：{ "ids": [1, 2, 3] }
```

### 實作

```php
public function batchDelete(BatchDeleteDTO $dto): BatchDeleteResult {
    $this->db->beginTransaction();
    try {
        $deleted = 0;
        foreach ($dto->ids as $id) {
            $this->postRepository->delete(new PostId($id));
            $deleted++;
        }
        $this->db->commit();
        return new BatchDeleteResult($deleted, count($dto->ids) - $deleted);
    } catch (\Throwable $e) {
        $this->db->rollBack();
        throw $e;
    }
}
```

---

## 12. 前端效能最佳化

### Tailwind 建置

- 使用 Tailwind CLI 取代 CDN
- 新增 `package.json` 指令稿：`npm run build:css`
- 輸出壓縮後的 CSS 檔案

### CDN SRI 雜湊

```html
<script
  src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
  integrity="sha384-..."
  crossorigin="anonymous"
></script>
```

### 非同步載入

```html
<script src="chart.js" defer></script>
<script src="dompurify.js" defer></script>
```
