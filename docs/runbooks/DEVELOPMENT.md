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
