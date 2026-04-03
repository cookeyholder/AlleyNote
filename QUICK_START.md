# AlleyNote 快速開始

目前維護中的完整安裝與開發流程，請使用：
- [docs/runbooks/DEVELOPMENT.md](docs/runbooks/DEVELOPMENT.md)

## 最小啟動流程

```bash
git clone https://github.com/cookeyholder/AlleyNote.git
cd AlleyNote
cp backend/.env.example backend/.env
docker compose up -d
docker compose exec web php vendor/bin/phinx migrate
docker compose exec web php vendor/bin/phinx seed:run
php scripts/reset_admin.php
```

預設端點：
- 前端：`http://localhost:3000`
- API（開發預設）：`http://localhost:8081`
