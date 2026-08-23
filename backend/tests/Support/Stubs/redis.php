<?php

declare(strict_types=1);

/*
 * phpredis 擴充功能相容 stub.
 *
 * 容器與 CI 環境皆未安裝 phpredis，此檔在測試執行期提供最小的
 * Redis / RedisException 類別定義，讓型別提示與 Mockery 可正常運作。
 * 本檔已列入 phpstan-test-exclusions.neon 排除名單：若被靜態分析
 * 掃描，空的 Redis 定義會覆蓋擴充功能的真實簽名，使全專案所有
 * Redis 方法呼叫誤報為未定義方法。
 */

if (!class_exists('RedisException')) {
    class RedisException extends Exception {}
}

if (!class_exists('Redis')) {
    class Redis {}
}
