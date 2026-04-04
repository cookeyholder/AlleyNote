# 開發 Runbook

## 範圍

本文件為本機安裝、啟動與驗證流程的 canonical 指南。

## 環境需求

- Docker 20.10+
- Docker Compose 2+
- Git

## 快速開始

```bash
git clone https://github.com/cookeyholder/AlleyNote.git
cd AlleyNote
cp backend/.env.example backend/.env
docker compose up -d
```

預設連接埠：
- 前端：`http://localhost:3000`
- API（開發預設）：`http://localhost:8081`
- API（近正式覆寫）：`http://localhost:8080`

## 資料庫初始化

```bash
docker compose exec web php vendor/bin/phinx migrate
docker compose exec web php vendor/bin/phinx seed:run
php scripts/reset_admin.php
```

## 本地品質檢查（CI 模擬）

### 後端

```bash
docker compose exec -T web sh -lc 'cd /var/www/html && composer cs-check && composer analyse && vendor/bin/phpunit --no-coverage'
```

### E2E

```bash
cd tests/e2e
npm ci
npx playwright install --with-deps chromium
CI=true npm test
```

若本機沒有原生 `php`，可使用容器等價流程（先啟動臨時 API 服務，再跑 Playwright）：

```bash
docker run -d --name alleynote_e2e_php -p 18080:8080 -v "$PWD/backend":/var/www/html -w /var/www/html alleynote-web \
  sh -lc 'APP_ENV=development DB_DATABASE=/var/www/html/database/alleynote.sqlite3 JWT_PRIVATE_KEY_PATH=/var/www/html/keys/private.pem JWT_PUBLIC_KEY_PATH=/var/www/html/keys/public.pem php -S 0.0.0.0:8080 -t public'
node dev-server.js frontend --port 3000 --proxy /api:http://127.0.0.1:18080/api
cd tests/e2e && CI=true npm test
```

## BaseController 例外處理指引

- `BaseController` 已改為透過 `ExceptionRegistry` 統一解析例外對應 HTTP 狀態碼。
- Domain 例外優先實作 `ApiExceptionInterface`，可在例外類別中直接定義狀態碼。
- 若無法修改例外類別，改在 `ExceptionRegistry::createDefault()` 註冊映射。
- 控制器中不要再維護私有靜態例外對照表，統一走 `handleException()`。

## 測試規範（ApiTestCase）

- API 整合測試請優先繼承 `Tests\Support\ApiTestCase`。
- 請求建立統一使用：
  - `$this->json('POST', '/api/...', $payload)`
  - `$this->withHeaders([...])->json(...)`
  - `$this->actingAs($userId)`（真實 JWT）
- 資料庫斷言統一使用：
  - `$this->assertDatabaseHas($table, $attrs)`
  - `$this->assertDatabaseMissing($table, $attrs)`
- `ApiTestCase` 內建 JWT 測試環境快照/還原，避免污染其他測試；新增 helper 時必須維持此隔離性。

## 常用操作

```bash
# 啟動／停止／重啟
docker compose up -d
docker compose down
docker compose restart

# 查看日誌
docker compose logs -f
docker compose logs -f web
docker compose logs -f nginx
```

## 相關文件

- [CI/CD Runbook](CI_CD.md)
- [安全 Runbook](SECURITY.md)
- [API 文件](../api/README.md)
