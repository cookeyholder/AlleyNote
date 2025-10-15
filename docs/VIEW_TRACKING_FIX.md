# 文章瀏覽追蹤修復報告

## 🎯 問題描述

用戶發現文章頁面的瀏覽次數顯示有兩個問題：
1. **瀏覽次數為 0 時不顯示**：文章14顯示 0 次瀏覽時，整個瀏覽次數欄位都被隱藏
2. **瀏覽未被記錄**：當文章被瀏覽時，瀏覽次數應該至少是 1，不應該是 0

## 🔍 根本原因分析

### 問題 1：顯示邏輯錯誤
```javascript
// 錯誤的條件判斷
${post.views ? `顯示瀏覽次數` : ''}
```
在 JavaScript 中，`0` 是假值（falsy），導致當 `views = 0` 時條件為 false。

### 問題 2：事件監聽器未註冊

**追蹤流程：**
1. ✅ 前端呼叫 `POST /api/posts/{id}/view`
2. ✅ `PostViewController` 接收請求
3. ✅ 發送 `PostViewed` 事件
4. ❌ **EventDispatcher 沒有註冊監聽器**
5. ❌ `PostViewedListener` 從未被調用
6. ❌ 瀏覽記錄未寫入資料庫

**發現過程：**
```bash
# API 回應成功
curl -X POST "http://localhost:8080/api/posts/14/view"
# {"success": true, "message": "已記錄瀏覽"}

# 但資料庫沒有記錄
sqlite3 "SELECT COUNT(*) FROM post_views WHERE post_id = 14;"
# 0
```

### 問題 3：views 欄位未更新

`PostViewStatisticsService::recordView()` 只插入 `post_views` 表，沒有更新 `posts.views` 欄位。

## ✅ 修復方案

### 修復 1：顯示邏輯（前端）

**文件**：`frontend/js/pages/public/post.js`

```javascript
// 修復前
${post.views ? `<div>👁️ ${post.views} 次瀏覽</div>` : ''}

// 修復後
${typeof post.views === 'number' ? `<div>👁️ ${post.views} 次瀏覽</div>` : ''}
```

**效果**：
- ✅ `views = 0` → 顯示「0 次瀏覽」
- ✅ `views = 45` → 顯示「45 次瀏覽」
- ✅ `views` 不存在 → 隱藏

### 修復 2：註冊事件監聽器（後端）

**文件**：`backend/app/Domains/Statistics/Providers/StatisticsServiceProvider.php`

```php
// 在 EventDispatcher 工廠中註冊監聽器
EventDispatcherInterface::class => \DI\factory(function (ContainerInterface $container) {
    $dispatcher = new SimpleEventDispatcher($logger);
    
    // 註冊 PostViewedListener
    $postViewedListener = $container->get(PostViewedListener::class);
    $dispatcher->listen('statistics.post.viewed', $postViewedListener);
    
    return $dispatcher;
}),
```

**效果**：
- ✅ `PostViewed` 事件被正確分派到監聽器
- ✅ `PostViewedListener::handle()` 被調用
- ✅ 瀏覽記錄寫入 `post_views` 表

### 修復 3：更新 views 欄位（後端）

**文件**：`backend/app/Domains/Statistics/Services/PostViewStatisticsService.php`

```php
public function recordView(...): bool
{
    $this->pdo->beginTransaction();
    
    try {
        // 1. 記錄到 post_views 表
        $stmt->execute([...]);
        
        // 2. 更新 posts.views 欄位
        $this->pdo->prepare('
            UPDATE posts
            SET views = views + 1
            WHERE id = :post_id
        ')->execute(['post_id' => $postId]);
        
        $this->pdo->commit();
        return true;
    } catch (\Throwable $e) {
        $this->pdo->rollBack();
        throw $e;
    }
}
```

**效果**：
- ✅ 使用資料庫交易確保一致性
- ✅ 同時更新兩個表
- ✅ 錯誤時回滾

## 📊 測試驗證

### 測試 1：瀏覽次數顯示
```bash
# 文章 5（45 次瀏覽）
✅ 顯示「👁️ 45 次瀏覽」

# 文章 14（0 次瀏覽 → 修復後）
✅ 顯示「👁️ 0 次瀏覽」（之前不顯示）
```

### 測試 2：瀏覽追蹤
```bash
# 第一次瀏覽
curl -X POST "/api/posts/14/view"
# ✅ 成功回應

# 檢查資料庫
sqlite3 "SELECT COUNT(*) FROM post_views WHERE post_id = 14"
# ✅ 1（之前是 0）

sqlite3 "SELECT views FROM posts WHERE id = 14"
# ✅ 1（之前是 0）

# 第二次瀏覽
curl -X POST "/api/posts/14/view"

# 檢查 API
curl "/api/posts/14" | jq '.data.views'
# ✅ 2
```

## 🎉 修復結果

### 提交記錄
1. `fix: 修復文章頁面瀏覽次數為0時不顯示的問題`
   - 前端顯示邏輯修復
   
2. `fix: 註冊 PostViewedListener 到 EventDispatcher`
   - 事件監聽器註冊
   
3. `fix: recordView 同時更新 posts.views 欄位`
   - 瀏覽次數更新邏輯

### 完整的瀏覽追蹤流程（修復後）

```
使用者瀏覽文章
    ↓
前端呼叫 POST /api/posts/{id}/view
    ↓
PostViewController 接收請求
    ↓
發送 PostViewed 事件
    ↓
EventDispatcher 分派事件 ✅
    ↓
PostViewedListener 處理事件 ✅
    ↓
PostViewStatisticsService::recordView() ✅
    ↓
┌─────────────────────┬──────────────────────┐
│ INSERT post_views   │ UPDATE posts.views   │ ✅
└─────────────────────┴──────────────────────┘
    ↓
前端重新載入文章
    ↓
顯示「👁️ N 次瀏覽」 ✅
```

## 📝 經驗教訓

1. **JavaScript 假值陷阱**：使用 `typeof` 檢查數字而非直接判斷真假值
2. **事件驅動架構**：確保監聽器被正確註冊到事件分派器
3. **資料一致性**：使用資料庫交易確保多表更新的原子性
4. **測試驗證**：不只測試 API 回應，也要確認資料庫狀態

## 🔧 建議改進

1. **效能優化**：考慮使用非同步佇列處理瀏覽統計
2. **去重邏輯**：防止同一 IP 在短時間內重複計數
3. **批次更新**：定期聚合 `post_views` 更新 `posts.views`（減少寫入）
4. **監控告警**：監控事件處理失敗率

修復日期：2025-10-15  
測試狀態：✅ 全部通過  
瀏覽追蹤：✅ 100% 正常工作
