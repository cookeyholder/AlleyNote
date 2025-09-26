# 統計功能操作手冊

## 📘 文件目的
本手冊提供維運與開發團隊在日常管理統計模組時的標準作業流程，包括監控項目、快取管理、常見問題排除、異常處理、效能調整與緊急應變。遵循本手冊可確保統計功能穩定、可預測並維持高可用性。

## 🛠️ 日常監控指南
| 監控項目 | 指標/來源 | 正常範圍 | 警示門檻 | 處理建議 |
| -------- | --------- | -------- | -------- | -------- |
| 統計 API 平均回應時間 | APM / Nginx 日誌 | < 200 ms | > 400 ms | 檢查快取命中率、資料庫慢查詢 |
| 統計 API 錯誤率 | APM / Sentry | < 1% | > 3% | 檢查最新釋出、回滾或進行熱修復 |
| Redis 快取命中率 | Redis INFO stats | ≥ 85% | < 70% | 檢視快取 TTL、排程預熱狀態 |
| 快照計算指令成功率 | 任務排程日誌 | 100% | 任務連續失敗 | 檢查 `storage/logs/statistics/*.log` |
| MySQL 慢查詢筆數 | performance_schema | < 5/hr | > 15/hr | 檢查索引、調整批次大小 |
| 統計事件異常 | 結構化日誌 (ELK) | 0 | > 0 | 逐筆檢視事件 payload 與 listener |

### 監控工具建議
- **APM**：New Relic、Datadog、Elastic APM
- **日誌**：ELK Stack、Loki/Grafana
- **快取監控**：RedisInsight 或自建儀表板
- **排程監控**：Supervisor/EventBus 儀表板，或整合 Prometheus 指標

## 🔄 快取管理作業
### 快取標籤與鍵命名
- 全域前綴：`statistics:*`
- 常用標籤：`statistics`, `statistics:overview`, `statistics:posts`, `statistics:users`, `statistics:popular`, `statistics:prewarmed`

### 常用操作
```bash
# 檢查快取統計
php scripts/statistics-recalculation.php --type=overview --dry-run

# 預熱指定統計類型
php scripts/statistics-recalculation.php --type=posts --start-date="-7 days" --end-date="now" --force

# 清除特定標籤快取（需在應用程式內呼叫）
php artisan statistics:cache:flush --tags=statistics:posts
```

### 快取最佳實務
- 將預熱排程安排於流量較低時段（建議每日 03:00）
- 檢查 `StatisticsCacheService::getStats()` 提供的命中/失敗統計
- 快照建立成功後觸發 `StatisticsSnapshotCreated` 事件，確保快取同步更新

## ❓ 常見問題與排除流程
| 問題描述 | 可能原因 | 排除步驟 |
| -------- | -------- | -------- |
| API 回傳 503/504 | 快取失效 + 資料庫壓力 | 檢查 Redis 狀態 → 檢視 DB 慢查詢 → 啟動預熱腳本 |
| 統計數據落後 | 排程任務失敗或停用 | 檢查 Cron/Supervisor → 重新執行 `statistics:recalculation` → 檢查日誌 |
| 資料不一致 | 快照建立失敗或事件未觸發 | 檢查資料庫快照表 → 查閱 `statistics` 事件日誌 → 手動重建快照 |
| API 權限錯誤 | JWT 設定缺失或過期 | 確認 `.env` JWT 金鑰 → 重新發行管理員 Token |
| 快取命中率低 | 新版未調整快取策略 | 檢視 `config/statistics.php` → 調整 TTL → 重新預熱 |

## ⚠️ 統計資料異常處理程序
1. **偵測異常**：依監控告警或 BI 團隊回報啟動流程。
2. **確認影響範圍**：辨識受影響指標、日期區間、使用者群組。
3. **檢視快照與原始資料**：比較 `statistics_snapshots` 與來源表（`posts`、`users` 等）差異。
4. **執行回填或修正**：
   ```bash
   php scripts/statistics-recalculation.php --type=overview --start-date="2025-09-01" --end-date="2025-09-15" --force
   ```
5. **驗證結果**：透過 API Smoke Test 或直接查詢快照表確認數值。
6. **紀錄與追蹤**：於 `/docs/decision-log/` 登記事件來源、處理方式與預防措施。

## 🚀 效能調整建議
- **快取策略**：確保 `config/statistics.php` 中的 `cache.ttl` 與 `cache.prewarm` 配置符合流量模式。
- **分批計算**：在回填指令中調整 `--batch-size`，避免一次處理過多天數造成 DB 壓力。
- **索引維護**：定期檢視 `statistics_snapshots` 與 `posts` 上的統計相關索引是否碎裂。
- **查詢優化**：若慢查詢持續，評估建立 Materialized View 或以 event sourcing 方式寫入快照。
- **水平擴充**：評估將統計查詢轉移至讀取節點或專用報表資料庫。

## 🆘 緊急應變流程
1. **立即溝通**：通知產品/營運團隊統計功能異常，評估對外公告需求。
2. **暫時措施**：
   - 以快照備援資料提供 read-only 查詢
   - 暫時增加快取 TTL 減少資料庫壓力
   - 關閉或限縮高風險統計 API（透過 Feature Flag）
3. **技術排查**：
   - 檢視 `storage/logs/statistics/` 與 APM 錯誤
   - 以 `statistics:recalculation` 建立最新快照作為暫行資料
4. **復原與驗證**：
   - 確認快取命中率與 API 指標恢復
   - 進行完整回填並比對歷史資料
5. **事後檢討**：
   - 更新操作手冊與監控閾值
   - 若為程式缺陷，建立修復任務並排入衝刺

## 📎 附錄
- 統計回填指令指南：`docs/STATISTICS_RECALCULATION_GUIDE.md`
- 效能測試報告：`docs/STATISTICS_PERFORMANCE_REPORT.md`
- 資料庫遷移指南：`docs/STATISTICS_DATABASE_MIGRATION_GUIDE.md`
- 架構稽核與事件紀錄：`docs/ARCHITECTURE_AUDIT.md`、`/docs/decision-log/`

建議定期檢視本手冊並依最新系統狀態更新內容，確保統計模組持續符合營運需求。