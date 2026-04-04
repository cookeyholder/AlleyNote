## Context

目前專案已經有 toast 與自訂 modal 元件，但使用模式尚未收斂。主站前端部分頁面仍直接使用原生 confirm，舊式後台頁面仍使用原生 alert/confirm，而某些頁面又把自訂確認元件當成 Promise 布林值 await，形成 API 契約不一致的狀況。這不是單純視覺問題，而是互動模型與頁面整合邊界沒有統一。

受影響的介面主要分成兩類：

- 以 frontend/js 為主的模組化前端頁面
- 以 backend/public 或 backend/config/routes 內嵌腳本為主的舊式管理頁面

這次變更需要先定義跨兩類頁面的統一契約，避免未來又產生第二套通知模型。

## Goals / Non-Goals

**Goals:**

- 提供單一 notification API facade，讓頁面層不用直接依賴原生 alert/confirm 或底層 modal 實作。
- 將 confirm 與 notice 類互動統一成 Promise 型回傳，讓呼叫端可以穩定使用 await。
- 定義「什麼情境該用哪一種訊息通道」的規則，降低 toast、modal、banner、inline error 混用。
- 建立可漸進遷移的策略，先兼容現有頁面，再逐步淘汰舊呼叫方式。

**Non-Goals:**

- 不在此設計中重新打造整套視覺風格或品牌語言。
- 不處理後端安全告警、監控告警等非前端 UI notification 領域的 alert 命名。
- 不要求所有頁面在單一提交中完成全面翻修。

## Decisions

### 1. 以 notification facade 作為唯一入口

頁面層只應呼叫高層 API，例如：

- notification.toast.success(message)
- notification.toast.error(message)
- notification.confirm(options)
- notification.notice(options)
- notification.banner.show(options)

這個 facade 內部再決定對應 toast manager、modal component、page banner 或 inline presenter。這能避免頁面直接綁死特定 UI 元件與 DOM 細節。

替代方案：保留多個直接匯入點，例如 toast、modal、ConfirmationDialog 並存。

未採用原因：會持續讓頁面層自己決定互動模型，無法真正統一 API 與語意。

### 2. 所有需要等待使用者回應的互動一律 Promise 化

confirm 與 notice 都應回傳 Promise。confirm resolve 為 true 或 false；notice resolve 於使用者關閉或確認後完成。頁面層不再傳 callback 作為主要模式。

替代方案：延續 callback 風格，再額外包 Promise helper。

未採用原因：會讓新舊頁面持續混用兩種模式，也無法修正目前呼叫端已經自然採用 await 的趨勢。

### 3. 將訊息通道依「互動意圖」而非元件名稱分類

系統應先判斷意圖，再選擇呈現方式：

- 非阻斷狀態回饋：toast
- 需要使用者決策：confirm
- 需要閱讀但不屬於危急警報：notice
- 持續性系統狀態：banner
- 欄位或表單問題：inline error

替代方案：延續以 alert/success/error 命名驅動元件選擇。

未採用原因：這種分類只描述情緒，不描述互動需求，容易讓阻斷與非阻斷訊息混淆。

### 4. 原生 alert/confirm 視為遷移目標，不再作為正式 UI 方案

應用程式介面不再直接使用瀏覽器原生 alert 或 confirm。若舊頁面短期內無法導入完整 facade，至少要先透過 shim 或 adapter 對接統一 API，再由 adapter 決定底層實作。

替代方案：只規範新頁面，舊頁面暫時放任原生對話框存在。

未採用原因：會讓體驗長期維持分裂，也讓測試與文件需要同時覆蓋兩套行為。

### 5. 將 alert 語意收斂為 notice，避免名稱與心智模型倒退

在前端 UI 領域中，alert 這個詞會自然導向強打斷、強警報的心智模型，也容易與瀏覽器原生 alert 混淆。統一改用 notice 或 acknowledge 更符合「溫和告知」的設計目標。

替代方案：保留 alert 方法名稱，只更換視覺樣式。

未採用原因：名稱本身就會持續污染 API 語意，讓未來新功能重複走回舊模式。

## Risks / Trade-offs

- [遷移期間雙軌並存] → 以 facade 與 adapter 包住既有元件，先統一入口，再逐頁替換底層呼叫。
- [測試需要同步更新] → 在 E2E 與前端互動測試中，以自訂通知元件與 Promise 流程為主要驗證目標，避免依賴原生 dialog 行為。
- [舊式頁面載入模型不同] → 對 backend/public 與內嵌腳本頁面提供最小可用 adapter，避免強迫一次導入整套模組化前端。
- [通道規則過度複雜] → 先定義少量核心通道與明確使用準則，避免建立過度抽象的 notification framework。

## Migration Plan

1. 定義 notification facade 與 Promise 型契約。
2. 讓現有 toast/modal 元件透過 facade 暴露統一介面。
3. 先遷移已經出現 await 語意的頁面，修正契約不一致問題。
4. 再遷移仍使用原生 alert/confirm 的主站前端頁面。
5. 最後為舊式管理頁面補上 adapter 或對應元件，淘汰原生 dialog。
6. 更新測試與文件，將原生 alert/confirm 從正式互動模型中移除。

Rollback strategy:

- 若遷移造成重大流程阻塞，可暫時保留 facade 對舊元件的適配層，不回退 API 契約，只回退個別頁面接入時機。

## Open Questions

- backend/public 與 backend/config/routes 這類頁面，最適合共用主站前端 bundle，還是維持輕量 adapter 即可？
- notice 是否需要支援次要動作，例如「稍後處理」或「前往設定」？
- banner 是否應包含全域 queue/stack 管理，還是先只處理單一持續性狀態？
