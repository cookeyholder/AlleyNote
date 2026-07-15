# deprecated-code-removal Specification

## Purpose
TBD

## Requirements

### Requirement: 移除已廢棄的 Post::getViewCount()
系統必須從 `Post` 模型中移除已標記為 `@deprecated` 的 `getViewCount()` 方法。
`PostTest.php` 中的測試必須改用 `getViews()`。

#### Scenario: getViewCount 方法已移除
- **WHEN** 檢查 `Post` 模型
- **THEN** `getViewCount()` 不得存在於該類別中

#### Scenario: PostTest 改用 getViews
- **WHEN** 執行 `PostTest`
- **THEN** `setsDefaultValuesCorrectly` 測試必須呼叫 `$post->getViews()` 而非 `$post->getViewCount()`

### Requirement: 移除已廢棄的 PostRepository::findByUserId()
系統必須從 `PostRepository` 中移除 `findByUserId()` 方法。

#### Scenario: findByUserId 方法已移除
- **WHEN** 檢查 `PostRepository`
- **THEN** `findByUserId()` 不得存在於該類別中

### Requirement: 移除已廢棄的 TimezoneHelper::getCommonTimezones()
系統必須從 `TimezoneHelper` 中移除 `getCommonTimezones()` 方法。

#### Scenario: getCommonTimezones 方法已移除
- **WHEN** 檢查 `TimezoneHelper`
- **THEN** `getCommonTimezones()` 不得存在於該類別中

### Requirement: 移除已廢棄的 sanitize_post_array()
系統必須從 `functions.php` 中移除 `sanitize_post_array()` 函式。

#### Scenario: sanitize_post_array 函式已移除
- **WHEN** 檢查 `functions.php`
- **THEN** `sanitize_post_array()` 不得存在於該檔案中
