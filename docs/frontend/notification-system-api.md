# Notification System API

本文件定義 AlleyNote 前端通知系統的統一入口、通道選擇規則與遷移策略。

## 統一入口

前端頁面應透過 [frontend/js/utils/notification.js](frontend/js/utils/notification.js) 使用通知能力，而不是直接呼叫瀏覽器原生 alert 或 confirm，也不要再直接 import toast。

可用能力：

- `notification.success(message, duration)`
- `notification.error(message, duration)`
- `notification.warning(message, duration)`
- `notification.info(message, duration)`
- `notification.confirm(options)`
- `notification.confirmDelete(itemName, options)`
- `notification.confirmDiscard(options)`
- `notification.notice(options)`
- `notification.banner.show(message, type, options)`
- `notification.banner.hide()`
- `notification.inline.show(target, message, options)`
- `notification.inline.clear(target, options)`
- `notification.inline.clearAll(container, selector)`

## 通道選擇規則

使用者操作回饋依互動意圖選擇通道：

- `toast`: 非阻斷型提示，例如成功、一般失敗、短暫資訊
- `confirm`: 需要使用者做決策，例如刪除、清空、放棄變更
- `notice`: 需要使用者閱讀但不需要二選一決策
- `banner`: 持續存在的全域狀態，例如離線、同步異常、權限過期
- `inline`: 與欄位或表單直接相關的輸入錯誤

禁止事項：

- 不要在正式 UI 流程中直接使用原生 `alert()`
- 不要在正式 UI 流程中直接使用原生 `confirm()`
- 不要用 toast 取代表單欄位錯誤
- 不要用 notice 取代刪除確認

## Promise 契約

決策型互動一律 Promise 化：

- `notification.confirm(...)` resolve `true | false`
- `notification.confirmDelete(...)` resolve `true | false`
- `notification.confirmDiscard(...)` resolve `true | false`
- `notification.notice(...)` resolve `void`

這讓頁面可以穩定採用 `await` 模式，而不再混用 callback 與同步 dialog 心智模型。

範例：

```js
const confirmed = await notification.confirmDelete(post.title);
if (!confirmed) {
  return;
}

await apiClient.delete(`/posts/${post.id}`);
notification.success("文章已刪除");
```

## 元件映射

目前 facade 與底層元件的對應關係如下：

- `notification.toast.*` → [frontend/js/utils/toast.js](frontend/js/utils/toast.js)
- `notification.confirm` / `notice` → [frontend/js/components/Modal.js](frontend/js/components/Modal.js)
- `notification.banner.*` → [frontend/js/utils/banner.js](frontend/js/utils/banner.js)
- `notification.inline.*` → [frontend/js/utils/inlineErrors.js](frontend/js/utils/inlineErrors.js)

向後相容策略：

- [frontend/js/components/ConfirmationDialog.js](frontend/js/components/ConfirmationDialog.js) 保留相容包裝，但新程式碼應直接使用 facade
- [frontend/js/components/Modal.js](frontend/js/components/Modal.js) 保留 `alert()` alias，只作為舊呼叫的相容層；新程式碼應使用 `notice()`

## 遷移矩陣

已直接遷移到 facade 的主要頁面：

- 主站管理頁：posts、postEditor、users、roles、tags、settings、statistics、profile
- 公開頁：login、forgotPassword、post
- 共用模組：api client、dashboard layout、main entry

已以 adapter 過渡的舊式頁面：

- [backend/public/cache-monitor.html](backend/public/cache-monitor.html)
- [backend/config/routes/tag-management.php](backend/config/routes/tag-management.php)

## 命名策略

UI 領域中的一般告知不再使用 `alert` 作為正式語意。

- 舊名稱：`alert`
- 新名稱：`notice`

原因：

- `alert` 容易與瀏覽器原生 `alert()` 混淆
- `alert` 傾向傳達高警戒、強打斷的心智模型
- `notice` 更符合「溫和告知」的互動目標

## 後續擴充原則

- 新頁面一律從 facade 接入，不再新增 direct toast import
- 若需要新通道，先擴充 facade，再決定底層元件
- 若舊式頁面暫時無法共用前端模組，至少要維持 Promise confirm 與自訂 notice adapter
