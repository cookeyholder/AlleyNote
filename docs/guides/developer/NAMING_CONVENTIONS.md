# AlleyNote 專案命名規範

**版本**: v4.0
**更新日期**: 2025-09-03
**架構**: 前後端分離 (Vue.js 3 + PHP 8.4.12 DDD)
**系統版本**: Docker 28.3.3, Docker Compose v2.39.2

> 本文件定義了 AlleyNote 前後端分離專案的程式碼命名規範，遵循 PSR 標準、現代 PHP 8.4.12 開發最佳實踐，以及 TypeScript Composition API 規範。

## 📋 後端基本命名規則 (PHP 8.4.12)

### 1. 類別、介面、Trait 和抽象類別
- **規則**：使用 `UpperCamelCase`（PascalCase）
- **範例**：
  ```php
  class AnnouncementService
  interface UserRepositoryInterface
  abstract class BaseApiController
  abstract class AbstractMiddleware
  enum PostStatus: string
  ```

### 2. 變數、屬性、方法和函式 (PHP 8.4.12 特性)
- **規則**：使用 `lowerCamelCase`
- **範例**：
  ```php
  private $userName;
  protected readonly string $createdAt;  // PHP 8.4.12 readonly

  public function getUserData(): array
  private function processApiRequest(): void

  // PHP 8.4.12 Property Hooks
  public string $name {
      get => strtoupper($this->name);
      set => $this->name = trim($value);
  }
  ```

### 3. 常數
- **規則**：使用 `UPPER_SNAKE_CASE`
- **範例**：
  ```php
  private const CACHE_TTL = 3600;
  private const API_SELECT_FIELDS = 'id, title, content';
  public const MAX_FILE_SIZE = 1024 * 1024;
  public const API_VERSION = '4.0';
  ```

## 🎯 後端特殊命名規範

### 介面命名
- **規則**：以 `Interface` 結尾
- **範例**：
  ```php
  interface AnnouncementRepositoryInterface
  interface AuthServiceInterface
  interface ApiValidatorInterface
  interface JwtTokenServiceInterface
  ```

### 抽象類別命名
- **規則**：可使用 `Abstract` 開頭或 `Base` 開頭
- **優先考量**：根據語意選擇更清楚的命名
- **範例**：
  ```php
  // 語意化命名優先
  abstract class BaseController
  abstract class BaseDTO

  // 也可以使用 Abstract 前綴
  abstract class AbstractMiddleware
  ```

### Trait 命名
- **規則**：使用語意化命名，不強制 `Trait` 後綴
- **範例**：
  ```php
  trait Cacheable
  trait Timestampable
  trait Loggable
  ```

### 例外類別命名
- **規則**：以 `Exception` 結尾
- **範例**：
  ```php
  class PostNotFoundException extends NotFoundException
  class ValidationException extends Exception
  class JwtException extends Exception
  ```

## 📁 檔案和目錄命名

### 檔案命名
- **規則**：與類別名稱相同，使用 `UpperCamelCase.php`
- **範例**：
  ```
  PostService.php
  UserRepository.php
  AbstractMiddleware.php
  ```

### 目錄命名
- **規則**：使用 `UpperCamelCase`
- **範例**：
  ```
  app/Domains/Post/
  app/Application/Controllers/
  app/Infrastructure/Database/
  ```

## 🔧 實作細節

### 資料庫相關
- **表格欄位**：在 SQL 查詢中使用 `snake_case`
- **屬性對映**：在 PHP 中轉換為 `lowerCamelCase`
- **範例**：
  ```php
  // SQL 查詢
  private const POST_SELECT_FIELDS = 'id, uuid, seq_number, user_id, created_at';

  // PHP 屬性
  private $seqNumber;
  private $userId;
  private $createdAt;
  ```

### 設定和環境變數
- **設定鍵**：使用 `snake_case`
- **常數**：使用 `UPPER_SNAKE_CASE`
- **範例**：
  ```php
  // 設定檔
  'jwt_secret' => env('JWT_SECRET'),
  'cache_ttl' => 3600,

  // 常數
  private const JWT_ALGORITHM = 'HS256';
  private const DEFAULT_CACHE_TTL = 3600;
  ```

## ✅ 程式碼品質檢查

### PHP CS Fixer 規則
專案使用 PHP CS Fixer 來自動檢查和修復命名相關的問題：

```php
// .php-cs-fixer.dist.php 中的相關規則
'class_reference_name_casing' => true,
'constant_case' => true,
'function_declaration' => ['closure_function_spacing' => 'one'],
```

### 檢查指令
```bash
# 檢查程式碼風格
docker compose exec -T web ./vendor/bin/php-cs-fixer check --diff

# 自動修復程式碼風格
docker compose exec -T web ./vendor/bin/php-cs-fixer fix
```

## 📚 參考標準

- [PSR-1: Basic Coding Standard](https://www.php-fig.org/psr/psr-1/)
- [PSR-4: Autoloader](https://www.php-fig.org/psr/psr-4/)
- [PSR-12: Extended Coding Style](https://www.php-fig.org/psr/psr-12/)
- PHP CS Fixer 官方文件

## 🔄 更新記錄

- **2025-08-30**：建立初版命名規範文件
- **2025-08-30**：確認專案已符合所有命名規則

---

> 💡 **重要提醒**：本規範優先考慮程式碼的可讀性和語意清楚，而非僵硬的命名後綴規則。當有疑問時，選擇最能表達程式碼意圖的命名方式。
