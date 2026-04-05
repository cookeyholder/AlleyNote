## 1. API Contract Design

- [x] 1.1 定義 notification facade 的公開介面，涵蓋 toast、confirm、notice、banner 與 inline error 的責任邊界
- [x] 1.2 定義 confirm 與 notice 的 Promise 型回傳契約，明確規範確認、取消、關閉等結果
- [x] 1.3 制定訊息通道選擇規則，區分非阻斷提示、決策互動、持續狀態與表單錯誤

## 2. Component and Adapter Strategy

- [x] 2.1 盤點現有 toast、modal、ConfirmationDialog 與原生 alert/confirm 使用點，標記需直接遷移與可透過 adapter 過渡的區域
- [x] 2.2 設計 facade 與既有元件的映射關係，決定哪些能力沿用現有元件、哪些能力需要補齊
- [x] 2.3 設計舊式管理頁面的 adapter 或 shim 方案，確保 backend/public 與內嵌腳本頁面可接入統一契約

## 3. Migration Planning

- [x] 3.1 依風險排序遷移頁面，優先處理目前已以 await 使用確認流程但底層契約不一致的頁面
- [x] 3.2 規劃原生 alert/confirm 淘汰名單與替代方案，涵蓋主站前端與舊式管理頁面
- [x] 3.3 定義 alert 到 notice 的命名遷移策略，避免新舊 API 語意並存

## 4. Validation and Documentation

- [x] 4.1 更新測試策略，定義如何驗證自訂通知元件與 Promise 型決策流程
- [x] 4.2 補充開發文件，說明何時使用 toast、confirm、notice、banner 與 inline error
- [x] 4.3 驗證 OpenSpec artifacts 之間的一致性，確認 proposal、design、specs 與 tasks 可支撐 apply 階段
