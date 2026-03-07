# database-state-snapshots Specification

## Purpose

TBD - created by archiving change refactor-tests-to-match-project-state. Update Purpose after archive.

## Requirements

### Requirement: Row level snapshot

測試系統 MUST 允許將資料庫資料列的目前狀態擷取為快照。

#### Scenario: Capture post state

- **當** 開發者呼叫 `captureRow('posts', 1)`
- **則** 系統應回傳一個陣列，代表 ID 為 1 的該筆資料列所有欄位值。

### Requirement: State match assertion

測試系統 MUST 提供一種方式，用於斷言資料列的目前狀態是否符合先前擷取的快照，並可選擇性提供允許變動的欄位清單。

#### Scenario: Assert row unchanged

- **當** 開發者呼叫 `assertRowUnchanged($snapshot)`
- **則** 系統應驗證資料庫中的所有欄位是否與快照完全一致。

#### Scenario: Assert row changed only for specific fields

- **當** 開發者呼叫 `assertRowChangedOnly($snapshot, ['title'])`
- **則** 若自快照擷取以來，除 `title` 以外的任何欄位發生變動，系統應判定失敗。
