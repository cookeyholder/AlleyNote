<?php

namespace App\Services;

use App\Services\CacheService;

class RateLimitService
{
    private $cacheService;
    private $maxAttempts;
    private $decayMinutes;

    public function __construct(CacheService $cacheService, int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->cacheService = $cacheService;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    public function tooManyAttempts(string $key): bool
    {
        return $this->attempts($key) >= $this->maxAttempts;
    }

    public function hit(string $key): int
    {
        $attempts = $this->attempts($key) + 1;
        $this->cacheService->put($key, $attempts, $this->decayMinutes * 60);
        return $attempts;
    }

    public function attempts(string $key): int
    {
        return (int) $this->cacheService->get($key, 0);
    }

    public function resetAttempts(string $key): bool
    {
        return $this->cacheService->delete($key);
    }

    public function remainingAttempts(string $key): int
    {
        return $this->maxAttempts - $this->attempts($key);
    }

    public function isAllowed(string $key): bool
    {
        if ($this->tooManyAttempts($key)) {
            return false;
        }

        $this->hit($key);
        return true;
    }
}
