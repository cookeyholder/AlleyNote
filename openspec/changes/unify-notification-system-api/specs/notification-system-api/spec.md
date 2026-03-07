# notification-system-api Specification

## Purpose

定義前端通知與決策互動的統一 API，讓使用者在不同頁面都能獲得一致、溫和且可預期的訊息體驗，並讓頁面層使用單一契約整合 toast、confirm、notice、banner 與 inline error。

## Requirements

### Requirement: Notification interactions must use a unified API facade

系統的前端 UI MUST 透過統一的 notification API facade 觸發通知與決策互動，而不是由頁面直接依賴原生 dialog 或底層元件實作。

#### Scenario: Page triggers a user-facing notification

- **WHEN** 任一頁面需要顯示成功訊息、錯誤訊息、確認操作或告知訊息
- **THEN** 該頁面必須透過統一 notification API 呼叫對應能力
- **AND** 頁面程式碼不得直接以正式互動方案依賴瀏覽器原生 alert 或 confirm

### Requirement: Decision-based interactions must return Promise results

所有需要等待使用者回應的互動 MUST 提供 Promise 型契約，讓呼叫端可以以一致方式等待結果。

#### Scenario: Confirm destructive action

- **WHEN** 頁面要求使用者確認刪除、清空、放棄變更或其他決策性操作
- **THEN** confirm 類 API 必須回傳 Promise
- **AND** 使用者確認時 Promise resolve 為 true
- **AND** 使用者取消或關閉時 Promise resolve 為 false

#### Scenario: Acknowledge notice message

- **WHEN** 頁面需要使用者閱讀一則 notice 但不要求二選一決策
- **THEN** notice 類 API 必須回傳 Promise
- **AND** Promise 會在使用者完成關閉或確認後 resolve

### Requirement: Message channels must be selected by interaction intent

系統 MUST 依互動意圖選擇通知通道，避免阻斷與非阻斷訊息混用。

#### Scenario: Non-blocking feedback

- **WHEN** 操作完成、載入狀態改變或一般錯誤需要短暫提示
- **THEN** 系統必須使用非阻斷型通道，例如 toast
- **AND** 使用者不需先關閉提示才能繼續操作

#### Scenario: Persistent system state

- **WHEN** 系統需要顯示持續性的全域狀態，例如離線、同步延遲或權限過期
- **THEN** 系統必須使用持續可見的通道，例如 banner
- **AND** 該狀態不得只依賴短暫 toast 顯示

#### Scenario: Form validation problem

- **WHEN** 錯誤直接對應到特定欄位或表單輸入
- **THEN** 系統必須在欄位或表單上下文中顯示 inline error
- **AND** 不得僅以 toast 取代表單錯誤呈現

### Requirement: Alert semantics must be replaced by notice semantics in UI flows

前端 UI 流程 MUST 以 notice 或 acknowledge 語意取代 alert 作為一般告知模型，避免將溫和提示誤建模為強警報或原生 alert。

#### Scenario: Informational interruption

- **WHEN** 系統需要暫時中斷流程並請使用者讀取一則非危急訊息
- **THEN** UI API 應以 notice 或 acknowledge 類能力表達該互動
- **AND** 該能力不得以原生 alert 作為正式使用者體驗方案

### Requirement: Legacy pages must have a migration-compatible integration path

系統 MUST 提供可供舊式管理頁面接入的相容整合方式，讓新舊頁面都能朝相同 API 契約遷移。

#### Scenario: Legacy admin page triggers confirmation

- **WHEN** backend/public 或內嵌腳本頁面需要顯示確認或通知互動
- **THEN** 該頁面必須能透過 adapter、shim 或等效整合層接入統一 notification API 契約
- **AND** 該整合方式必須支援與主站前端一致的 Promise 型決策流程
