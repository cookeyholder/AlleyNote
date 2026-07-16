# Notification Interaction Testing Strategy

本文件說明通知系統 API 統一後的測試重點與驗證方式。

## 測試目標

- 確認頁面不再依賴原生 `alert()` 或 `confirm()` 作為正式 UI 互動
- 確認 Promise 型 confirm/notice 契約可被穩定等待
- 確認非阻斷提示、持續性狀態與欄位錯誤各自使用正確通道

## E2E 驗證重點

E2E 應優先驗證下列行為：

- 刪除、放棄變更等危險操作會出現自訂 confirm dialog
- 使用者取消 confirm 時，不應發送破壞性請求
- 使用者確認 confirm 時，後續 API 請求與成功提示應正常發生
- 成功或一般失敗訊息以 toast 顯示
- 離線或全域狀態改變時，以 banner 顯示而非短暫 toast
- 表單驗證錯誤應顯示在欄位附近，而不是只出現在 toast

## 建議選擇器

測試應盡量依賴穩定 UI 結構，而不是訊息字串本身：

- Toast: `.toast-item`
- Banner container: `#global-banner-container`
- Inline error: `[data-error-for="field-name"]`
- Modal actions: `[data-action="confirm"]`, `[data-action="cancel"]`, `[data-action="ok"]`

若未來需要更高穩定性，可再補 `data-testid`。

## 單元與整合測試重點

- `notification.confirm` 應 resolve `true` 或 `false`
- `notification.notice` 應在關閉後 resolve
- `notification.confirmDelete` 與 `notification.confirmDiscard` 應帶入預設文案與正確 tone
- `notification.inline.clearAll()` 應能清空表單錯誤
- `notification.banner.show()` 與 `hide()` 應維持單一活躍 banner

## 回歸風險檢查表

- 新頁面是否又直接 import `toast`
- 是否重新引入原生 `alert()` 或 `confirm()`
- 是否把欄位錯誤寫成 toast
- 是否在需要 `await` 的地方誤用 callback 型舊 API
- 舊式頁面的 adapter 是否仍能關閉、取消與 resolve Promise

## 本次變更的最低驗證

- 管理頁 posts、postEditor、users、roles、tags 的確認流程改為自訂 confirm
- 主站與公開頁的直接 toast import 改為 facade
- cache monitor 與 tag management 不再彈出瀏覽器原生對話框
