# PostController CRUD 完整實作報告

## 問題發現

檢查 `PostController` 時發現三個方法還在使用假資料，沒有實際操作資料庫：

1. ❌ `show()` - 回傳假的文章資料
2. ❌ `update()` - 沒有實際更新資料庫
3. ❌ `destroy()` - 沒有實際刪除資料

這與專案要求「所有管理員操作都直接與資料庫互動」不符。

---

## 修復內容

### 1. show() 方法 ✅

**修改前**：
```php
public function show(...) {
    $post = [
        'id' => $id,
        'title' => "貼文 #{$id}",
        'content' => "這是第 {$id} 篇貼文的內容",
    ];
    return $this->successResponse($post);
}
```

**修改後**：
```php
public function show(...) {
    // 從資料庫查詢
    $sql = "SELECT p.*, u.username as author
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.id = :id AND p.deleted_at IS NULL";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        return 404 error;
    }
    
    return $this->successResponse($post);
}
```

**功能**：
- ✅ 從資料庫查詢真實文章
- ✅ JOIN users 表取得作者資訊
- ✅ 過濾已刪除的文章
- ✅ 404 錯誤處理

---

### 2. update() 方法 ✅

**修改前**：
```php
public function update(...) {
    $post = [
        'id' => $id,
        'title' => $body['title'] ?? "更新的貼文 #{$id}",
        'content' => $body['content'] ?? '更新後的內容',
    ];
    return $this->successResponse($post, '貼文更新成功');
}
```

**修改後**：
```php
public function update(...) {
    // 手動解析 PUT/PATCH body
    $body = $request->getParsedBody();
    if (empty($body)) {
        $rawBody = (string) $request->getBody();
        $body = json_decode($rawBody, true);
    }
    
    // 檢查文章存在
    // ...
    
    // 動態建立更新欄位
    $updateFields = [];
    $params = [':id' => $id];
    
    if (isset($body['title'])) {
        $updateFields[] = 'title = :title';
        $params[':title'] = $body['title'];
    }
    // ... 其他欄位
    
    // 執行更新
    $sql = "UPDATE posts SET " . implode(', ', $updateFields) 
         . ", updated_at = datetime('now') WHERE id = :id";
    $stmt->execute($params);
    
    // 回傳更新後的資料
    return $this->successResponse($updatedPost, '貼文更新成功');
}
```

**功能**：
- ✅ 實際更新資料庫
- ✅ 支援部分更新（只更新提供的欄位）
- ✅ 支援的欄位：title, content, status, excerpt, publish_date
- ✅ 自動更新 updated_at 時間戳記
- ✅ 手動解析 PUT/PATCH body（解決 Slim 框架限制）
- ✅ 更新後回傳最新資料
- ✅ 404 錯誤處理

**特殊處理**：

**PUT/PATCH Body 解析**：
```php
// Slim 框架預設不解析 PUT/PATCH 的 JSON body
// 需要手動讀取和解析
$body = $request->getParsedBody();
if (empty($body)) {
    $rawBody = (string) $request->getBody();
    $body = json_decode($rawBody, true);
}
```

**發布日期處理**：
```php
if (isset($body['publish_date'])) {
    if (!empty($body['publish_date'])) {
        $date = new \DateTime($body['publish_date']);
        $updateFields[] = 'publish_date = :publish_date';
        $params[':publish_date'] = $date->format('Y-m-d H:i:s');
    } else {
        // 空值表示清除發布日期
        $updateFields[] = 'publish_date = NULL';
    }
}
```

---

### 3. destroy() 方法 ✅

**修改前**：
```php
public function destroy(...) {
    $result = [
        'deleted_id' => $id,
        'deleted_at' => date('c'),
    ];
    return $this->successResponse($result, '貼文刪除成功');
}
```

**修改後**：
```php
public function destroy(...) {
    // 檢查文章存在
    $checkSql = "SELECT id, title FROM posts 
                 WHERE id = :id AND deleted_at IS NULL";
    $post = $checkStmt->fetch();
    
    if (!$post) {
        return 404 error;
    }
    
    // 軟刪除（設定 deleted_at）
    $sql = "UPDATE posts SET deleted_at = datetime('now') WHERE id = :id";
    $stmt->execute([':id' => $id]);
    
    return $this->successResponse([
        'id' => $id,
        'title' => $post['title'],
        'deleted_at' => date('c'),
    ], '貼文刪除成功');
}
```

**功能**：
- ✅ 實作軟刪除（Soft Delete）
- ✅ 設定 deleted_at 時間戳記而非真正刪除
- ✅ 保留資料以便審計和恢復
- ✅ 列表查詢會自動過濾已刪除的文章
- ✅ 404 錯誤處理

**為什麼使用軟刪除**：
1. **資料安全**：誤刪可以恢復
2. **審計需求**：保留歷史記錄
3. **關聯完整性**：不破壞外鍵關聯
4. **法律合規**：某些法規要求保留資料

---

## 測試結果

### 1. 查詢單篇文章（show）

```bash
curl http://localhost:8080/api/posts/11 -H "Authorization: Bearer $TOKEN"
```

**回應**：
```json
{
  "success": true,
  "data": {
    "id": 11,
    "title": "定時發布測試",
    "content": "這篇文章有指定發布時間",
    "publish_date": "2025-10-15 14:30:00",
    "author": "admin",
    "created_at": "2025-10-06 18:37:20",
    "updated_at": null
  }
}
```

✅ **成功從資料庫讀取真實資料**

---

### 2. 更新文章（update）

```bash
curl -X PUT http://localhost:8080/api/posts/11 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"成功更新的標題"}'
```

**回應**：
```json
{
  "success": true,
  "message": "貼文更新成功",
  "data": {
    "id": 11,
    "title": "成功更新的標題",
    "updated_at": "2025-10-06 18:42:57"
  }
}
```

**資料庫驗證**：
```sql
SELECT id, title, updated_at FROM posts WHERE id = 11;
-- 結果：11 | 成功更新的標題 | 2025-10-06 18:42:57
```

✅ **成功更新資料庫**

---

### 3. 刪除文章（destroy）

```bash
curl -X DELETE http://localhost:8080/api/posts/8 \
  -H "Authorization: Bearer $TOKEN"
```

**回應**：
```json
{
  "success": true,
  "message": "貼文刪除成功",
  "data": {
    "id": 8,
    "title": "新增測試文章",
    "deleted_at": "2025-10-07T02:42:58+08:00"
  }
}
```

**資料庫驗證**：
```sql
SELECT id, title, deleted_at FROM posts WHERE id = 8;
-- 結果：8 | 新增測試文章 | 2025-10-06 18:42:58
```

**列表查詢**：
```bash
curl http://localhost:8080/api/posts -H "Authorization: Bearer $TOKEN"
```
- 總數：7 篇（減少 1 篇）
- 不包含 post ID 8
- 包含 post ID 11

✅ **軟刪除成功，文章不再出現在列表中**

---

## 完整的 CRUD 操作對照

| 操作 | 方法 | HTTP | 資料庫操作 | 快取 |
|------|------|------|-----------|------|
| **Create** | `store()` | POST | INSERT | ❌ 不使用 |
| **Read (List)** | `index()` | GET | SELECT (多筆) | ❌ 不使用 |
| **Read (Single)** | `show()` | GET | SELECT (單筆) | ❌ 不使用 |
| **Update** | `update()` | PUT/PATCH | UPDATE | ❌ 不使用 |
| **Delete** | `destroy()` | DELETE | UPDATE (軟刪除) | ❌ 不使用 |

---

## 技術特點

### 1. 直接資料庫連接
```php
$pdo = new PDO("sqlite:{$dbPath}");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```
- 不通過 Repository 層
- 不使用 Service 層
- 最直接的資料庫操作
- 適合管理員後台

### 2. 預處理語句（Prepared Statements）
```php
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
$stmt->execute([':id' => $id]);
```
- 防止 SQL 注入
- 提升安全性

### 3. 動態欄位更新
```php
$updateFields = [];
if (isset($body['title'])) {
    $updateFields[] = 'title = :title';
}
// 只更新提供的欄位
```
- 支援部分更新
- 靈活性高

### 4. 軟刪除模式
```php
UPDATE posts SET deleted_at = datetime('now') WHERE id = :id
```
- 資料保留
- 可恢復
- 審計友好

### 5. 錯誤處理
```php
try {
    // 資料庫操作
} catch (\Exception $e) {
    return $this->errorResponse('操作失敗: ' . $e->getMessage());
}
```
- 完整的異常處理
- 友好的錯誤訊息

---

## 與前台 API 的對比

| 特性 | PostController（管理員） | ApiPostController（前台） |
|------|------------------------|--------------------------|
| **路徑** | `/api/posts` | `/api/v1/posts` |
| **架構** | 直接 PDO | DDD (Repository + Service) |
| **快取** | ❌ 不使用 | ✅ 使用 |
| **目標** | 管理員即時操作 | 前台效能優化 |
| **複雜度** | 簡單直接 | 層次分明 |

---

## Git 提交記錄

```bash
git log --oneline -1
d2462e85 feat(backend): 完成 PostController 所有 CRUD 操作的資料庫整合
```

---

## 總結

### ✅ 完成項目

1. **show()** - 查詢單篇文章 ✅
2. **update()** - 更新文章 ✅
3. **destroy()** - 軟刪除文章 ✅
4. **所有操作都直接與資料庫互動** ✅
5. **不使用快取機制** ✅
6. **完整的錯誤處理** ✅

### 🎯 符合設計原則

- ✅ 管理員看到的資料始終是最新的
- ✅ 寫入操作直接寫入資料庫
- ✅ 讀取操作直接從資料庫查詢
- ✅ 軟刪除保留資料完整性
- ✅ SQL 注入防護（預處理語句）

### 📊 測試覆蓋

- ✅ CRUD 所有操作都已測試
- ✅ 資料庫驗證通過
- ✅ 錯誤情況處理正確
- ✅ 軟刪除機制正常

---

**完成時間**：2025-10-07 02:45  
**狀態**：✅ 所有 CRUD 操作完整實作並測試通過
