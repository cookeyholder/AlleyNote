# 快取標籤系統部署與監控指南

## 概述

本指南提供 AlleyNote 快取標籤系統的部署配置、環境設定、監控方案和故障排除指南，確保系統在生產環境中穩定高效運行。

## 部署配置

### 1. 環境要求

#### 系統要求

- **PHP**: 8.4+
- **Redis**: 6.0+ （推薦 7.0+）
- **記憶體**: 最少 2GB （推薦 4GB+）
- **CPU**: 2 核心+ （推薦 4 核心+）
- **磁碟空間**: 10GB+ 可用空間

#### PHP 擴展要求

```bash
# 必需的 PHP 擴展
php -m | grep -E "(redis|igbinary|msgpack)"

# 如果缺少擴展，安裝方法：
# Ubuntu/Debian
sudo apt-get install php8.4-redis php8.4-igbinary php8.4-msgpack

# CentOS/RHEL
sudo yum install php84-redis php84-igbinary php84-msgpack
```

### 2. Redis 配置

#### Redis 伺服器配置

```conf
# /etc/redis/redis.conf

# 基本配置
bind 127.0.0.1
port 6379
timeout 0
tcp-keepalive 300

# 記憶體配置
maxmemory 2gb
maxmemory-policy allkeys-lru
maxmemory-samples 5

# 持久化配置（根據需要調整）
save 900 1
save 300 10
save 60 10000

# 快照配置
stop-writes-on-bgsave-error yes
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb

# 日誌配置
loglevel notice
logfile /var/log/redis/redis-server.log

# 安全配置
requirepass your_redis_password

# 效能調整
hash-max-ziplist-entries 512
hash-max-ziplist-value 64
list-max-ziplist-size -2
set-max-intset-entries 512
zset-max-ziplist-entries 128
zset-max-ziplist-value 64

# 快取標籤系統特殊配置
notify-keyspace-events Ex  # 啟用過期事件通知
```

#### Redis 叢集配置（高可用性）

```conf
# 主從複製配置
# Master 配置
bind 0.0.0.0
port 6379
requirepass master_password
masterauth master_password

# Slave 配置
bind 0.0.0.0
port 6380
replicaof 192.168.1.100 6379
masterauth master_password
requirepass replica_password
replica-read-only yes
```

### 3. 應用程式配置

#### 快取配置文件

```php
<?php
// config/cache.php

return [
    // 預設快取配置
    'default' => 'redis',

    // 快取儲存配置
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'prefix' => env('CACHE_PREFIX', 'alleynote'),
            'serializer' => 'igbinary', // 使用 igbinary 提升效能
            'compression' => true,       // 啟用壓縮
        ],

        'memory' => [
            'driver' => 'memory',
            'max_items' => 10000,
            'default_ttl' => 3600,
        ],
    ],

    // 標籤化快取配置
    'tagged_cache' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => env('TAGGED_CACHE_PREFIX', 'alleynote:tagged'),
        'default_ttl' => (int) env('TAGGED_CACHE_TTL', 3600),
        'max_tags_per_key' => 20,
        'tag_index_ttl' => 86400, // 標籤索引 TTL
        'enable_compression' => true,
        'compression_threshold' => 1024, // 大於 1KB 的資料才壓縮
    ],

    // 快取分組管理配置
    'group_manager' => [
        'max_groups' => (int) env('CACHE_MAX_GROUPS', 1000),
        'default_ttl' => (int) env('CACHE_GROUP_TTL', 3600),
        'cleanup_interval' => (int) env('CACHE_CLEANUP_INTERVAL', 3600),
        'auto_cascade' => env('CACHE_AUTO_CASCADE', true),
        'log_operations' => env('CACHE_LOG_OPERATIONS', true),
        'statistics_ttl' => 300, // 統計資料快取時間
    ],

    // 效能監控配置
    'monitoring' => [
        'enabled' => env('CACHE_MONITORING_ENABLED', true),
        'metrics_ttl' => 300,
        'alert_thresholds' => [
            'hit_rate_min' => 0.8,
            'memory_usage_max' => 0.9,
            'response_time_max' => 100, // ms
            'error_rate_max' => 0.01,
        ],
        'report_interval' => 3600, // 報告間隔（秒）
    ],
];
```

#### 資料庫連線配置

```php
<?php
// config/database.php

return [
    'redis' => [
        'client' => 'predis', // 或 'phpredis'

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'options' => [
                'prefix' => env('CACHE_PREFIX', 'alleynote:cache:'),
                'serializer' => 'igbinary',
                'compression' => 'gzip',
            ],
        ],

        'session' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', '2'),
        ],
    ],
];
```

#### 環境變數配置

```bash
# .env.production

# 快取配置
CACHE_DRIVER=redis
CACHE_PREFIX=alleynote_prod
CACHE_DEFAULT_TTL=3600

# 標籤化快取配置
TAGGED_CACHE_PREFIX=alleynote_prod:tagged
TAGGED_CACHE_TTL=3600
CACHE_MAX_GROUPS=2000
CACHE_GROUP_TTL=7200
CACHE_CLEANUP_INTERVAL=1800
CACHE_AUTO_CASCADE=true
CACHE_LOG_OPERATIONS=true

# Redis 配置
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=secure_redis_password
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2

# 監控配置
CACHE_MONITORING_ENABLED=true
LOG_LEVEL=info
```

### 4. 容器化部署

#### Docker Compose 配置

```yaml
# docker-compose.cache.yml
version: '3.8'

services:
  redis:
    image: redis:7-alpine
    container_name: alleynote-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis_data:/data
      - ./config/redis.conf:/usr/local/etc/redis/redis.conf
    environment:
      - REDIS_PASSWORD=${REDIS_PASSWORD}
    networks:
      - alleynote_network
    healthcheck:
      test: ["CMD", "redis-cli", "-a", "${REDIS_PASSWORD}", "ping"]
      interval: 30s
      timeout: 10s
      retries: 3

  redis-sentinel:
    image: redis:7-alpine
    container_name: alleynote-redis-sentinel
    restart: unless-stopped
    ports:
      - "26379:26379"
    command: redis-sentinel /usr/local/etc/redis/sentinel.conf
    volumes:
      - ./config/sentinel.conf:/usr/local/etc/redis/sentinel.conf
    depends_on:
      - redis
    networks:
      - alleynote_network

volumes:
  redis_data:

networks:
  alleynote_network:
    driver: bridge
```

#### Kubernetes 部署配置

```yaml
# kubernetes/cache-deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: redis-cache
  labels:
    app: redis-cache
spec:
  replicas: 1
  selector:
    matchLabels:
      app: redis-cache
  template:
    metadata:
      labels:
        app: redis-cache
    spec:
      containers:
      - name: redis
        image: redis:7-alpine
        ports:
        - containerPort: 6379
        command: ["redis-server", "--requirepass", "$(REDIS_PASSWORD)"]
        env:
        - name: REDIS_PASSWORD
          valueFrom:
            secretKeyRef:
              name: redis-secret
              key: password
        resources:
          requests:
            memory: "512Mi"
            cpu: "250m"
          limits:
            memory: "2Gi"
            cpu: "1000m"
        volumeMounts:
        - name: redis-config
          mountPath: /usr/local/etc/redis/redis.conf
          subPath: redis.conf
        - name: redis-data
          mountPath: /data
      volumes:
      - name: redis-config
        configMap:
          name: redis-config
      - name: redis-data
        persistentVolumeClaim:
          claimName: redis-pvc

---
apiVersion: v1
kind: Service
metadata:
  name: redis-service
spec:
  selector:
    app: redis-cache
  ports:
    - protocol: TCP
      port: 6379
      targetPort: 6379
  type: ClusterIP
```

## 監控設定

### 1. 系統監控

#### Prometheus 監控配置

```yaml
# prometheus/cache-monitoring.yml
global:
  scrape_interval: 15s

scrape_configs:
  - job_name: 'redis'
    static_configs:
      - targets: ['localhost:6379']
    metrics_path: /metrics
    scrape_interval: 5s

  - job_name: 'alleynote-cache'
    static_configs:
      - targets: ['localhost:8080']
    metrics_path: /metrics/cache
    scrape_interval: 10s

rule_files:
  - "cache_alerts.yml"

alerting:
  alertmanagers:
    - static_configs:
        - targets:
          - alertmanager:9093
```

#### Redis 監控配置

```php
<?php
// app/Monitoring/CacheMetricsCollector.php

namespace App\Monitoring;

use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\Services\CacheGroupManager;

class CacheMetricsCollector
{
    private TaggedCacheInterface $cache;
    private CacheGroupManager $groupManager;

    public function collectMetrics(): array
    {
        return [
            'cache_operations_total' => $this->getOperationCounts(),
            'cache_hit_rate' => $this->getHitRate(),
            'cache_memory_usage_bytes' => $this->getMemoryUsage(),
            'cache_response_time_seconds' => $this->getResponseTimes(),
            'cache_tag_count' => count($this->cache->getAllTags()),
            'cache_group_count' => $this->groupManager->getGroupStatistics()['total_groups'],
            'cache_error_rate' => $this->getErrorRate(),
        ];
    }

    public function exportPrometheusMetrics(): string
    {
        $metrics = $this->collectMetrics();
        
        $output = [];
        foreach ($metrics as $name => $value) {
            $output[] = "# TYPE {$name} gauge";
            $output[] = "{$name} {$value}";
        }
        
        return implode("\n", $output);
    }
}
```

### 2. 應用程式監控

#### 效能監控中介軟體

```php
<?php
// app/Middleware/CachePerformanceMiddleware.php

namespace App\Middleware;

class CachePerformanceMiddleware
{
    public function process(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // 記錄快取操作開始
        $this->recordCacheOperationStart($request);
        
        $response = $next($request);
        
        // 計算效能指標
        $executionTime = microtime(true) - $startTime;
        $memoryUsage = memory_get_usage(true) - $startMemory;
        
        // 記錄效能指標
        $this->recordPerformanceMetrics([
            'execution_time' => $executionTime,
            'memory_usage' => $memoryUsage,
            'request_path' => $request->getPathInfo(),
            'cache_operations' => $this->getCacheOperationCount(),
        ]);
        
        return $response;
    }
}
```

#### 日誌配置

```php
<?php
// config/logging.php

return [
    'channels' => [
        'cache' => [
            'driver' => 'single',
            'path' => storage_path('logs/cache.log'),
            'level' => env('CACHE_LOG_LEVEL', 'info'),
            'days' => 14,
        ],

        'cache_performance' => [
            'driver' => 'single',
            'path' => storage_path('logs/cache-performance.log'),
            'level' => 'debug',
            'days' => 7,
        ],

        'cache_errors' => [
            'driver' => 'single',
            'path' => storage_path('logs/cache-errors.log'),
            'level' => 'error',
            'days' => 30,
        ],
    ],
];
```

### 3. 監控儀表板

#### Grafana 儀表板配置

```json
{
  "dashboard": {
    "title": "AlleyNote Cache System",
    "panels": [
      {
        "title": "Cache Hit Rate",
        "type": "stat",
        "targets": [
          {
            "expr": "cache_hit_rate",
            "legendFormat": "Hit Rate"
          }
        ],
        "fieldConfig": {
          "defaults": {
            "unit": "percent",
            "min": 0,
            "max": 100
          }
        }
      },
      {
        "title": "Cache Operations per Second",
        "type": "graph",
        "targets": [
          {
            "expr": "rate(cache_operations_total[5m])",
            "legendFormat": "{{operation}}"
          }
        ]
      },
      {
        "title": "Memory Usage",
        "type": "graph",
        "targets": [
          {
            "expr": "cache_memory_usage_bytes",
            "legendFormat": "Memory Usage"
          }
        ],
        "fieldConfig": {
          "defaults": {
            "unit": "bytes"
          }
        }
      },
      {
        "title": "Response Time Distribution",
        "type": "heatmap",
        "targets": [
          {
            "expr": "cache_response_time_seconds",
            "legendFormat": "Response Time"
          }
        ]
      }
    ]
  }
}
```

## 部署流程

### 1. 預生產環境部署

```bash
#!/bin/bash
# deploy-staging.sh

set -e

echo "開始部署快取標籤系統到預生產環境..."

# 1. 檢查環境
echo "檢查環境配置..."
php artisan cache:check-requirements
php artisan cache:validate-config

# 2. 備份現有快取
echo "備份現有快取資料..."
php artisan cache:backup --env=staging

# 3. 更新程式碼
echo "更新應用程式程式碼..."
git pull origin develop
composer install --no-dev --optimize-autoloader

# 4. 執行快取系統初始化
echo "初始化快取系統..."
php artisan cache:clear
php artisan cache:tags:initialize
php artisan cache:groups:initialize

# 5. 執行測試
echo "執行快取系統測試..."
php artisan test --filter=Cache

# 6. 預熱快取
echo "預熱快取..."
php artisan cache:warmup

echo "預生產環境部署完成！"
```

### 2. 生產環境部署

```bash
#!/bin/bash
# deploy-production.sh

set -e

echo "開始部署快取標籤系統到生產環境..."

# 1. 預檢查
echo "執行預檢查..."
php artisan cache:pre-deployment-check

# 2. 建立維護模式
echo "啟用維護模式..."
php artisan down --message="系統升級中，預計5分鐘完成"

# 3. 備份
echo "備份生產資料..."
php artisan cache:backup --env=production
php artisan db:backup

# 4. 部署新版本
echo "部署新版本..."
git pull origin main
composer install --no-dev --optimize-autoloader

# 5. 資料庫遷移（如需要）
echo "執行資料庫遷移..."
php artisan migrate --force

# 6. 快取系統初始化
echo "初始化快取系統..."
php artisan cache:clear-all
php artisan cache:tags:migrate
php artisan cache:groups:migrate

# 7. 預熱關鍵快取
echo "預熱關鍵快取..."
php artisan cache:warmup --priority=high

# 8. 健康檢查
echo "執行健康檢查..."
php artisan cache:health-check

# 9. 關閉維護模式
echo "關閉維護模式..."
php artisan up

# 10. 部署後監控
echo "啟動部署後監控..."
php artisan cache:monitor --duration=30m

echo "生產環境部署完成！"
```

### 3. 回滾程序

```bash
#!/bin/bash
# rollback.sh

set -e

echo "開始快取系統回滾程序..."

# 1. 啟用維護模式
php artisan down --message="系統回滾中"

# 2. 恢復程式碼版本
echo "恢復程式碼到上一個版本..."
git reset --hard HEAD~1

# 3. 恢復快取資料
echo "恢復快取資料..."
php artisan cache:restore --backup=latest

# 4. 健康檢查
echo "執行健康檢查..."
php artisan cache:health-check

# 5. 關閉維護模式
php artisan up

echo "回滾完成！"
```

## 故障排除

### 1. 常見問題診斷

#### 快取連線問題

```php
<?php
// app/Console/Commands/CacheDiagnostics.php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CacheDiagnosticsCommand extends Command
{
    protected $signature = 'cache:diagnostics';
    protected $description = '診斷快取系統問題';

    public function handle(): void
    {
        $this->info('開始快取系統診斷...');

        // 檢查 Redis 連線
        $this->checkRedisConnection();

        // 檢查快取配置
        $this->checkCacheConfiguration();

        // 檢查標籤功能
        $this->checkTaggedCacheFeatures();

        // 檢查分組功能
        $this->checkGroupManagerFeatures();

        // 檢查效能指標
        $this->checkPerformanceMetrics();
    }

    private function checkRedisConnection(): void
    {
        try {
            $redis = app('redis');
            $redis->ping();
            $this->info('✓ Redis 連線正常');
        } catch (\Exception $e) {
            $this->error('✗ Redis 連線失敗: ' . $e->getMessage());
        }
    }

    private function checkCacheConfiguration(): void
    {
        $config = config('cache');
        
        if (!$config) {
            $this->error('✗ 快取配置遺失');
            return;
        }

        $this->info('✓ 快取配置正常');
        $this->table(['設定項', '值'], [
            ['預設驅動', $config['default']],
            ['標籤前綴', $config['tagged_cache']['prefix'] ?? 'N/A'],
            ['預設TTL', $config['tagged_cache']['default_ttl'] ?? 'N/A'],
        ]);
    }
}
```

#### 效能問題診斷

```php
<?php
// app/Console/Commands/CachePerformanceAnalysis.php

class CachePerformanceAnalysisCommand extends Command
{
    public function handle(): void
    {
        $this->info('開始快取效能分析...');

        // 檢查記憶體使用
        $this->analyzeMemoryUsage();

        // 檢查命中率
        $this->analyzeHitRate();

        // 檢查響應時間
        $this->analyzeResponseTime();

        // 檢查標籤使用情況
        $this->analyzeTagUsage();

        // 提供優化建議
        $this->provideOptimizationSuggestions();
    }

    private function analyzeMemoryUsage(): void
    {
        $redis = app('redis');
        $info = $redis->info('memory');
        
        $usedMemory = $info['used_memory_human'];
        $maxMemory = $info['maxmemory_human'] ?? 'unlimited';
        
        $this->table(['指標', '值'], [
            ['使用記憶體', $usedMemory],
            ['最大記憶體', $maxMemory],
            ['記憶體使用率', $this->calculateMemoryUsagePercentage($info)],
        ]);
    }
}
```

### 2. 監控告警

#### 告警規則配置

```yaml
# prometheus/cache_alerts.yml
groups:
  - name: cache_alerts
    rules:
      - alert: CacheHitRateLow
        expr: cache_hit_rate < 0.8
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "快取命中率過低"
          description: "快取命中率已低於80%，當前值：{{ $value }}"

      - alert: CacheMemoryHigh
        expr: cache_memory_usage_bytes / cache_memory_limit_bytes > 0.9
        for: 2m
        labels:
          severity: critical
        annotations:
          summary: "快取記憶體使用過高"
          description: "快取記憶體使用超過90%，當前值：{{ $value }}"

      - alert: CacheResponseTimeSlow
        expr: cache_response_time_seconds > 0.1
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "快取響應時間過慢"
          description: "快取響應時間超過100ms，當前值：{{ $value }}s"

      - alert: CacheErrorRateHigh
        expr: rate(cache_errors_total[5m]) > 0.01
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "快取錯誤率過高"
          description: "快取錯誤率超過1%，當前值：{{ $value }}"
```

### 3. 維護工具

#### 快取系統健康檢查

```php
<?php
// app/Console/Commands/CacheHealthCheck.php

class CacheHealthCheckCommand extends Command
{
    protected $signature = 'cache:health-check {--timeout=30}';
    protected $description = '執行快取系統健康檢查';

    public function handle(): void
    {
        $timeout = (int) $this->option('timeout');
        $this->info("執行快取健康檢查（超時：{$timeout}秒）...");

        $checks = [
            'Redis連線' => $this->checkRedisConnection(),
            '基本讀寫' => $this->checkBasicReadWrite(),
            '標籤功能' => $this->checkTaggedCache(),
            '分組功能' => $this->checkGroupManager(),
            '效能基準' => $this->checkPerformanceBenchmark(),
        ];

        $this->displayResults($checks);
    }

    private function checkRedisConnection(): bool
    {
        try {
            $redis = app('redis');
            $result = $redis->ping();
            return $result === 'PONG';
        } catch (\Exception $e) {
            $this->error("Redis連線失敗: {$e->getMessage()}");
            return false;
        }
    }

    private function checkBasicReadWrite(): bool
    {
        try {
            $cache = app(TaggedCacheInterface::class);
            $testKey = 'health_check_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);

            // 寫入測試
            $cache->put($testKey, $testValue, 60);

            // 讀取測試
            $retrieved = $cache->get($testKey);

            // 清理測試
            $cache->forget($testKey);

            return $retrieved === $testValue;
        } catch (\Exception $e) {
            $this->error("基本讀寫測試失敗: {$e->getMessage()}");
            return false;
        }
    }
}
```

## 安全設定

### 1. Redis 安全配置

```conf
# Redis 安全配置
requirepass your_strong_password
rename-command FLUSHDB ""
rename-command FLUSHALL ""
rename-command DEBUG ""
rename-command CONFIG "CONFIG_b835c3c5a1d41a5b"

# 網路安全
bind 127.0.0.1
protected-mode yes

# 慢查詢日誌
slowlog-log-slower-than 10000
slowlog-max-len 128
```

### 2. 應用程式安全

```php
<?php
// 快取鍵加密
class SecureCacheKeyGenerator
{
    private string $encryptionKey;

    public function __construct(string $encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;
    }

    public function generateSecureKey(string $baseKey, array $context = []): string
    {
        $data = $baseKey . serialize($context);
        return hash_hmac('sha256', $data, $this->encryptionKey);
    }
}

// 敏感資料加密存儲
class EncryptedCacheValue
{
    public function encrypt(mixed $value): string
    {
        return encrypt(serialize($value));
    }

    public function decrypt(string $encryptedValue): mixed
    {
        return unserialize(decrypt($encryptedValue));
    }
}
```

## 總結

本指南涵蓋了快取標籤系統的完整部署和監控方案。遵循這些指引可以確保：

1. **穩定部署**：通過詳細的配置和部署流程，確保系統穩定運行
2. **全面監控**：通過多層次的監控體系，及時發現和解決問題
3. **快速故障排除**：通過完整的診斷工具，快速定位和解決故障
4. **安全保障**：通過安全配置，保護快取系統免受攻擊

記住，部署是一個持續的過程，需要根據實際運行情況不斷優化和調整。