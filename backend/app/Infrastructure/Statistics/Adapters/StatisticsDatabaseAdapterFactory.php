<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Adapters;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Shared\Contracts\CacheServiceInterface;
use InvalidArgumentException;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * 統計資料庫適配器工廠.
 *
 * 建立和管理統計 Repository 的各種適配器，提供快取、日誌記錄、
 * 事務管理等額外功能的封裝層。
 *
 * 支援適配器的組合使用，例如同時啟用快取和日誌記錄功能。
 */
final class StatisticsDatabaseAdapterFactory
{
    public function __construct(
        private readonly StatisticsRepositoryInterface $baseRepository,
        private readonly ?CacheServiceInterface $cache = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?PDO $db = null,
    ) {}

    /**
     * 建立基礎適配器（無額外功能）.
     */
    public function createBase(): StatisticsRepositoryInterface
    {
        return $this->baseRepository;
    }

    /**
     * 建立帶快取功能的適配器.
     */
    public function createWithCache(): StatisticsRepositoryInterface
    {
        if ($this->cache === null) {
            throw new InvalidArgumentException('Cache service is required for cache adapter');
        }

        return new StatisticsRepositoryCacheAdapter($this->baseRepository, $this->cache);
    }

    /**
     * 建立帶日誌記錄功能的適配器.
     */
    public function createWithLogging(): StatisticsRepositoryInterface
    {
        if ($this->logger === null) {
            throw new InvalidArgumentException('Logger is required for logging adapter');
        }

        return new StatisticsRepositoryLoggingAdapter($this->baseRepository, $this->logger);
    }

    /**
     * 建立帶事務管理功能的適配器.
     */
    public function createWithTransaction(): StatisticsRepositoryInterface
    {
        if ($this->db === null) {
            throw new InvalidArgumentException('PDO connection is required for transaction adapter');
        }

        return new StatisticsRepositoryTransactionAdapter($this->baseRepository, $this->db);
    }

    /**
     * 建立組合適配器（快取 + 日誌記錄）.
     */
    public function createCachedWithLogging(): StatisticsRepositoryInterface
    {
        if ($this->cache === null) {
            throw new InvalidArgumentException('Cache service is required for cache adapter');
        }

        if ($this->logger === null) {
            throw new InvalidArgumentException('Logger is required for logging adapter');
        }

        $cachedAdapter = new StatisticsRepositoryCacheAdapter($this->baseRepository, $this->cache);

        return new StatisticsRepositoryLoggingAdapter($cachedAdapter, $this->logger);
    }

    /**
     * 建立組合適配器（事務 + 日誌記錄）.
     */
    public function createTransactionalWithLogging(): StatisticsRepositoryInterface
    {
        if ($this->db === null) {
            throw new InvalidArgumentException('PDO connection is required for transaction adapter');
        }

        if ($this->logger === null) {
            throw new InvalidArgumentException('Logger is required for logging adapter');
        }

        $transactionAdapter = new StatisticsRepositoryTransactionAdapter($this->baseRepository, $this->db);

        return new StatisticsRepositoryLoggingAdapter($transactionAdapter, $this->logger);
    }

    /**
     * 建立完整功能的適配器（快取 + 事務 + 日誌記錄）.
     */
    public function createFull(): StatisticsRepositoryInterface
    {
        if ($this->cache === null) {
            throw new InvalidArgumentException('Cache service is required for full adapter');
        }

        if ($this->logger === null) {
            throw new InvalidArgumentException('Logger is required for full adapter');
        }

        if ($this->db === null) {
            throw new InvalidArgumentException('PDO connection is required for full adapter');
        }

        // 構建適配器鏈：Base -> Cache -> Transaction -> Logging
        $cachedAdapter = new StatisticsRepositoryCacheAdapter($this->baseRepository, $this->cache);
        $transactionAdapter = new StatisticsRepositoryTransactionAdapter($cachedAdapter, $this->db);

        return new StatisticsRepositoryLoggingAdapter($transactionAdapter, $this->logger);
    }

    /**
     * 根據設定建立適配器.
     *
     * @param array{cache?: bool, logging?: bool, transaction?: bool} $config
     */
    public function createByConfig(array $config): StatisticsRepositoryInterface
    {
        $useCache = $config['cache'] ?? false;
        $useLogging = $config['logging'] ?? false;
        $useTransaction = $config['transaction'] ?? false;

        // 如果沒有啟用任何功能，返回基礎 Repository
        if (!$useCache && !$useLogging && !$useTransaction) {
            return $this->baseRepository;
        }

        // 建立適配器鏈
        $adapter = $this->baseRepository;

        // 先添加快取層（離資料最近）
        if ($useCache) {
            if ($this->cache === null) {
                throw new InvalidArgumentException('Cache service is required when cache is enabled');
            }
            $adapter = new StatisticsRepositoryCacheAdapter($adapter, $this->cache);
        }

        // 然後添加事務層
        if ($useTransaction) {
            if ($this->db === null) {
                throw new InvalidArgumentException('PDO connection is required when transaction is enabled');
            }
            $adapter = new StatisticsRepositoryTransactionAdapter($adapter, $this->db);
        }

        // 最後添加日誌記錄層（最外層）
        if ($useLogging) {
            if ($this->logger === null) {
                throw new InvalidArgumentException('Logger is required when logging is enabled');
            }
            $adapter = new StatisticsRepositoryLoggingAdapter($adapter, $this->logger);
        }

        return $adapter;
    }

    /**
     * 檢查是否可以建立指定類型的適配器.
     */
    public function canCreate(string $type): bool
    {
        return match ($type) {
            'base' => true,
            'cache' => $this->cache !== null,
            'logging' => $this->logger !== null,
            'transaction' => $this->db !== null,
            'cached_logging' => $this->cache !== null && $this->logger !== null,
            'transactional_logging' => $this->db !== null && $this->logger !== null,
            'full' => $this->cache !== null && $this->logger !== null && $this->db !== null,
            default => false,
        };
    }

    /**
     * 取得可用的適配器類型清單.
     *
     * @return array<string>
     */
    public function getAvailableTypes(): array
    {
        $types = ['base'];

        if ($this->cache !== null) {
            $types[] = 'cache';
        }

        if ($this->logger !== null) {
            $types[] = 'logging';
        }

        if ($this->db !== null) {
            $types[] = 'transaction';
        }

        if ($this->cache !== null && $this->logger !== null) {
            $types[] = 'cached_logging';
        }

        if ($this->db !== null && $this->logger !== null) {
            $types[] = 'transactional_logging';
        }

        if ($this->cache !== null && $this->logger !== null && $this->db !== null) {
            $types[] = 'full';
        }

        return $types;
    }
}
