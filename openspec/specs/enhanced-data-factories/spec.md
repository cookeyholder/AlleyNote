# enhanced-data-factories Specification

## Purpose

TBD - created by archiving change refactor-tests-to-match-project-state. Update Purpose after archive.

## Requirements

### Requirement: Persistent record creation

測試系統 MUST 提供一個強化的工廠介面，能夠將記錄直接持久化（寫入）至測試資料庫。

#### Scenario: Create a post in database

- **當** 開發者呼叫 `PostFactory::create(['title' => 'Real Post'])`
- **則** 系統應將記錄插入 `posts` 資料表，並回傳 ID 或插入的資料。

### Requirement: Default attribute resolution

工廠 MUST 為所有必要的欄位提供合理的預設值（若未明確提供）。

#### Scenario: Create post with defaults

- **當** 開發者呼叫 `PostFactory::create()`
- **則** 系統應自動產生有效的 UUID、時間戳記以及其他必要的欄位。
