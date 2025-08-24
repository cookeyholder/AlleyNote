# DTO 整合測試文件

## 概述

本文件說明 DTO（資料傳輸物件）與驗證器整合的測試架構和使用方式。

## 測試涵蓋範圍

### 1. DTO 驗證整合測試 (`DTOValidationIntegrationTest`)

#### 測試項目：
- **CreatePostDTO 驗證整合**：測試文章建立 DTO 的完整驗證流程
- **CreateAttachmentDTO 驗證整合**：測試附件建立 DTO 的驗證機制
- **RegisterUserDTO 驗證整合**：測試使用者註冊 DTO 的驗證規則
- **CreateIpRuleDTO 驗證整合**：測試 IP 規則建立 DTO 的驗證邏輯

#### 驗證功能：
- 必填欄位驗證
- 資料型別驗證
- 長度限制驗證
- 自訂規則驗證
- 中文錯誤訊息驗證
- JSON 序列化功能

### 2. DTO Controller 整合測試 (`DTOControllerIntegrationTest`)

#### 測試項目：
- **Controller 與 DTO 整合**：測試 DTO 在 Controller 中的使用
- **資料清理功能**：測試 DTO 的自動資料清理
- **型別轉換**：測試資料型別的自動轉換
- **批次操作**：測試 DTO 在批次處理中的效能
- **記憶體效率**：測試 DTO 的記憶體使用效率
- **序列化功能**：測試 DTO 的 JSON 序列化

## 測試資料範例

### CreatePostDTO 測試資料
```php
$validData = [
    'title' => '測試文章標題',
    'content' => '這是測試文章內容，應該要足夠長才能通過驗證。',
    'status' => 'draft',
    'user_id' => 1,
    'user_ip' => '192.168.1.1',
];
```

### CreateAttachmentDTO 測試資料
```php
$validData = [
    'post_id' => 1,
    'filename' => 'test-image.jpg',
    'original_name' => 'my-photo.jpg',
    'mime_type' => 'image/jpeg',
    'file_size' => 1024000,
    'storage_path' => 'uploads/test.jpg',
    'uploaded_by' => 1,
];
```

### RegisterUserDTO 測試資料
```php
$validData = [
    'username' => 'testuser',
    'email' => 'test@example.com',
    'password' => 'SecurePassword123!',
    'confirm_password' => 'SecurePassword123!',
    'user_ip' => '192.168.1.1',
];
```

### CreateIpRuleDTO 測試資料
```php
$validData = [
    'ip_address' => '192.168.1.1',
    'action' => 'allow',
    'reason' => '測試 IP 規則',
    'created_by' => 1,
];
```

## 執行測試

### 執行所有 DTO 整合測試
```bash
docker-compose exec web vendor/bin/phpunit tests/Integration/DTOs/
```

### 執行特定測試檔案
```bash
# DTO 驗證整合測試
docker-compose exec web vendor/bin/phpunit tests/Integration/DTOs/DTOValidationIntegrationTest.php

# DTO Controller 整合測試
docker-compose exec web vendor/bin/phpunit tests/Integration/DTOs/DTOControllerIntegrationTest.php
```

## 驗證規則

### 通用驗證規則
- `required`：必填欄位
- `string`：字串型別
- `integer`：整數型別
- `email`：電子郵件格式
- `boolean`：布林型別

### 自訂驗證規則
- `post_title`：文章標題驗證（長度、內容檢查）
- `post_content`：文章內容驗證（最小長度、內容檢查）
- `user_id`：使用者 ID 驗證（正整數）
- `ip_address`：IP 地址格式驗證
- `post_status`：文章狀態驗證（draft/published/archived）
- `rfc3339_datetime`：RFC3339 日期時間格式

## 錯誤處理

### ValidationException
- 當驗證失敗時拋出
- 包含詳細的錯誤訊息
- 支援繁體中文錯誤訊息
- 提供多欄位錯誤詳情

### 錯誤訊息範例
```
文章標題長度必須介於 1 和 255 個字元之間，且包含有效內容
文章內容長度不能少於 1 個字元，且必須包含有效內容
使用者 ID 必須是正整數
文章狀態必須是：draft（草稿）、published（已發布）或 archived（已封存）
```

## 效能考量

### 記憶體使用
- 每個 DTO 實例記憶體占用低於 10KB
- 批次處理 100 個 DTO 總記憶體低於 1MB
- 支援大量 DTO 並行處理

### 序列化效能
- 支援高效的 JSON 序列化
- 自動資料清理和型別轉換
- 保持資料完整性

## 最佳實踐

1. **資料驗證**：總是在 DTO 建構時進行完整驗證
2. **錯誤處理**：使用 try-catch 捕獲 ValidationException
3. **型別安全**：利用 PHP 的強型別系統確保資料安全
4. **記憶體管理**：在批次處理時注意記憶體使用
5. **測試覆蓋**：確保所有驗證規則都有對應測試

## 相依關係

- **Validator**：核心驗證引擎
- **ValidationResult**：驗證結果封裝
- **ValidationException**：驗證異常處理
- **BaseDTO**：DTO 基礎類別

## 未來擴展

1. 添加更多自訂驗證規則
2. 支援條件式驗證
3. 實作批次驗證最佳化
4. 添加異步驗證支援
5. 擴展國際化錯誤訊息
