## Why

目前前端訊息互動同時混用 toast、自訂 modal、原生 alert、原生 confirm，而且部分頁面已經把確認流程當成 Promise 布林值使用，但底層 API 仍是 callback 風格。這讓使用者體驗不一致，也讓頁面層難以預測互動結果與維護擴充成本持續升高。

## What Changes

- 建立統一的通知與決策互動 API，讓頁面層透過單一入口觸發 toast、notice、confirm、banner 與 inline error。
- 將需要等待使用者回應的互動統一為 Promise 型介面，避免 callback 與 await 混用。
- 明確定義不同訊息類型的呈現通道與使用規則，避免成功訊息、危險操作、欄位錯誤與系統狀態彼此混用。
- 淘汰應用程式介面中的原生 alert 與 confirm 使用方式，並將 alert 語意替換為較溫和的 notice/acknowledge 模型。
- 納入現有主站前端與舊式管理頁面的遷移路徑，確保跨頁面互動模型一致。

## Capabilities

### New Capabilities

- `notification-system-api`: 定義前端通知與決策互動的統一 API、通道選擇規則與原生對話框淘汰要求。

### Modified Capabilities

- None.

## Impact

- Affected code: frontend/js/components, frontend/js/utils, frontend/js/pages/admin, backend/public, backend/config/routes 內嵌前端互動腳本。
- APIs: 前端互動 API 將收斂為單一 notification service/facade，既有直接呼叫 modal、alert、confirm 的頁面需遷移。
- Testing: E2E 與前端互動測試需改以自訂通知元件與 Promise 決策流程驗證，不再依賴原生對話框。
- UX: 使用者會看到更一致、較溫和且可預期的提示與確認流程。
