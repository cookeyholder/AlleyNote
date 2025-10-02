# 統計資料庫遷移指南

## 📘 目的
本指南說明如何在 AlleyNote 專案中導入統計功能所需的資料庫結構調整，包括部署前檢查、資料備份、遷移與回滾流程，以及生產環境的注意事項。依循本文件可降低資料遺失風險，並確保統計模組在上線後維持穩定效能。

## 🔧 遷移前準備
- 確認目前分支為 `feature/statistics-service` 並已同步最新程式碼。
- 確保 `docker compose` 服務運作正常（至少包含 `web`、`db`、`redis` 容器）。
- 驗證環境變數：
  - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
  - `CACHE_DRIVER`（預設 `redis`）
  - `APP_ENV`, `APP_DEBUG`
- 建議先執行以下檢查，確保程式碼品質維持在基準線：

```bash
docker compose exec -T web ./vendor/bin/php-cs-fixer check
docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G
docker compose exec -T web ./vendor/bin/phpunit --testsuite=Unit
```

> **注意**：若測試環境尚未配置 JWT 相關金鑰與 DI 綁定，可先聚焦單元測試或以 `--group statistics` 限縮統計模組測試範圍。

## 💾 資料備份建議
| 類型 | 建議方式 | 說明 |
| ---- | -------- | ---- |
| 資料庫完整備份 | `mysqldump` 或雲端備份機制 | 最少保留三份：正式、預備、離線備份，並驗證可回復性 |
| 統計相關表備份 | 僅匯出 `statistics_snapshots`、`posts` | 減少回復時間，建議透過 `SELECT INTO OUTFILE` 或 `mysqldump --where` |
| 應用程式設定 | 備份 `.env`、`config/` | 確保任何設定調整可快速復原 |
| 監控與告警設定 | 匯出 Grafana / Prometheus 儀表板 | 方便遷移後比對效能差異 |

備份完成後，請記錄備份檔案位置與校驗值（checksum），並將資訊同步給維運人員。

## 🚀 遷移流程
1. **鎖定版本**
   - 確保部署用的 Docker 映像與程式碼標籤一致。
   - 在 Git 上建立標籤：`git tag statistics-migration-prep`

2. **套用資料庫變更**
   - 切換至專案根目錄，執行 Phinx 遷移：

```bash
docker compose exec -T web ./vendor/bin/phinx migrate -e production
```

   - 本次遷移涵蓋：
     - `posts` 表新增來源追蹤欄位 `creation_source`, `creation_source_detail`
     - `statistics_snapshots` 表與相關索引結構
     - 快取預熱所需的排程與支援資料表（若有）

3. **資料回填與預熱（選擇性，但建議）**
   - 回填既有文章的來源欄位：

```bash
docker compose exec -T web php backend/scripts/statistics-recalculation.php --type=posts --start-date="-30 days" --end-date="now" --batch-size=7
```

   - 預熱快取：

```bash
docker compose exec -T web php backend/scripts/statistics-recalculation.php --type=overview --dry-run=false --force
```

4. **稽核與驗證**
   - 透過 `SELECT COUNT(*) FROM statistics_snapshots;` 確認快照資料量。
   - 檢查 `posts` 表新欄位是否具預期值（例如 `web`、`api`）。
   - 執行統計 API 驗證：

```bash
docker compose exec -T web php tests/cli/statistics_smoke_test.php
```

   - 若使用 API Gateway，記得同步更新快取或版本號。

5. **釋出通知**
   - 更新部署紀錄與決策文件 `/docs/decision-log/`。
   - 通知前端或 BI 團隊統計資料的可用時程。

## 🔄 回滾程序
1. 確認備份檔案可讀：`mysql --host=... --user=... < backup.sql`（使用 staging 環境驗證）。
2. 回滾 Phinx 遷移：

```bash
docker compose exec -T web ./vendor/bin/phinx rollback -e production -t 0
```

3. 重新載入備份資料：

```bash
mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < backup.sql
```

4. 清除統計快取與暫存檔：

```bash
docker compose exec -T web php backend/scripts/statistics-recalculation.php --type=overview --force --dry-run
rm -rf backend/storage/statistics/*
```

5. 驗證回滾後應用程式狀態，特別是統計相關 API 是否回復至舊版行為。

## ⚙️ 效能影響評估
| 項目 | 影響 | 緩解策略 |
| ---- | ---- | -------- |
| 寫入量 | `posts` 表新增欄位，寫入成本增加約 3% | 使用批次寫入或延後更新；確保索引設計合理 |
| 查詢效能 | `statistics_snapshots` 查詢需要 JSON 解析 | 建議將常用欄位投影至額外欄位或 Materialized View |
| 快取 | 新增統計快取，Redis 記憶體使用提高 | 監控快取命中率，必要時調整 TTL 或增加 Redis 記憶體 |
| 應用程式啟動時間 | DI 初始化新增統計相關服務 | 使用延遲載入（lazy loading）或服務提供者分組 |
| 維護成本 | 回填與預熱需額外批次任務 | 將 Cron 任務排程於離峰時間，並設定告警

建議在 staging 與 production 上各進行一次「前後對照」壓測，監測指標包含：
- 統計 API 平均回應時間（目標 < 200 ms）
- Redis 命中率（目標 ≥ 85%）
- MySQL 慢查詢（目標每小時 < 5 筆）

## ✅ 生產環境部署檢查清單
- [ ] 完成資料備份並驗證回復流程
- [ ] 確認 `.env.production` 已新增統計相關設定（快取、排程）
- [ ] 於 staging 完成遷移、回填與煙霧測試
- [ ] 效能壓測結果達到門檻並已記錄在 `docs/STATISTICS_PERFORMANCE_REPORT.md`
- [ ] 監控告警閾值已調整（統計 API、Redis、MySQL）
- [ ] 排程任務（回填、快照計算）已設定於 Cron / Supervisor
- [ ] 事件監控與日誌指標已更新（StatisticsSnapshotCreated, PostViewed）
- [ ] 完成決策紀錄與發布通知

## 📎 相關資源
- [`docs/STATISTICS_RECALCULATION_GUIDE.md`](./STATISTICS_RECALCULATION_GUIDE.md)
- [`docs/STATISTICS_PERFORMANCE_REPORT.md`](./STATISTICS_PERFORMANCE_REPORT.md)
- [`docs/ARCHITECTURE_AUDIT.md`](./ARCHITECTURE_AUDIT.md)
- [`backend/database/migrations`](../backend/database/migrations)

依循上述步驟可降低部署失誤風險，並確保統計功能在正式環境穩定運作。如需進一步協助，請於 `/docs/decision-log/` 紀錄問題後發起技術討論。
