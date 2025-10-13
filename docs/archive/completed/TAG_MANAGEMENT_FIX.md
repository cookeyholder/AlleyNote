# 標籤管理功能修復報告

## 問題描述

標籤管理頁面顯示空白，並出現以下錯誤：
- 前端 API 請求路徑錯誤：`/api/api/tags`（重複了 `/api` 前綴）
- 後端 TagManagementService 使用了不存在的 Laravel `Illuminate\Support\Str` 類別

## 修復內容

### 1. 前端 API 路徑修復

**檔案：** `frontend/js/pages/admin/tags.js`

修改所有 API 請求路徑，移除重複的 `/api` 前綴：
- `GET /api/tags` → `GET /tags`
- `POST /api/tags` → `POST /tags`
- `PUT /api/tags/{id}` → `PUT /tags/{id}`
- `DELETE /api/tags/{id}` → `DELETE /tags/{id}`

因為 `baseURL` 已經設定為 `http://localhost:8080/api`，所以不需要在路徑中再加 `/api`。

### 2. 後端 Slug 生成功能實作

**檔案：** `backend/app/Domains/Post/Services/TagManagementService.php`

1. 移除對 `Illuminate\Support\Str` 的依賴
2. 實作自訂的 `generateSlug()` 私有方法：
   - 轉換為小寫
   - 替換空白為連字號
   - 移除特殊字元（保留中文、英文、數字、連字號）
   - 移除多餘的連字號
   - 移除頭尾的連字號
   - 若結果為空，則使用 `tag-{uniqid}` 作為預設值
3. 修復 PHPStan 類型檢查錯誤：
   - 處理 `preg_replace()` 可能返回 null 的情況
   - 移除不必要的 `is_string()` 檢查

### 3. E2E 測試啟用

**檔案：** `tests/e2e/tests/10-tag-management.spec.js`

啟用了以下原本被跳過的測試：
- ✅ 應該能夠成功新增標籤
- ✅ 應該能夠編輯標籤
- ✅ 應該能夠刪除標籤

## 測試結果

### 手動測試
- ✅ 標籤列表正常載入
- ✅ 新增標籤功能正常
- ✅ 編輯標籤功能正常
- ✅ 刪除標籤功能正常（需要確認對話框）

### 自動化測試
- ✅ PHP CS Fixer：通過（0 個錯誤）
- ✅ PHPStan Level 10：通過（0 個錯誤）
- ✅ E2E 測試：8/8 通過

### API 測試範例

```bash
# 建立標籤
curl -X POST http://localhost:8080/api/tags \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"name":"技術","description":"技術相關的文章標籤"}'

# 回應
{
  "success": true,
  "data": {
    "id": 1,
    "name": "技術",
    "slug": "技術",
    "description": "技術相關的文章標籤",
    "color": null,
    "usage_count": 0,
    "post_count": 0,
    "created_at": "2025-10-12T09:34:39+08:00",
    "updated_at": "2025-10-12T09:34:39+08:00"
  },
  "message": "標籤建立成功"
}
```

## 相關檔案

### 修改的檔案
- `frontend/js/pages/admin/tags.js` - 修復 API 路徑
- `backend/app/Domains/Post/Services/TagManagementService.php` - 實作 slug 生成
- `tests/e2e/tests/10-tag-management.spec.js` - 啟用測試

### 相關路由
- 前端路由：`/admin/tags`
- API 路由：`/api/tags`

### 相關 Controller
- `App\Application\Controllers\Api\V1\TagController`

## 注意事項

1. **API baseURL 配置**：前端的 `API_CONFIG.baseURL` 已包含 `/api` 前綴，所有 API 請求路徑不應再加 `/api`
2. **Slug 生成**：支援中文字元，會保留在 slug 中
3. **刪除確認**：刪除標籤時會顯示確認對話框，若標籤有關聯文章會提示數量

## 下一步建議

1. ✅ 標籤管理基本功能已完成
2. 🔲 考慮為 slug 新增中文轉拼音功能（可選）
3. 🔲 新增標籤顏色選擇器（目前欄位存在但前端未實作）
4. 🔲 新增標籤使用次數統計展示
