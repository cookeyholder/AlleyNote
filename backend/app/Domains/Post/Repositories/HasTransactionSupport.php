<?php

declare(strict_types=1);

namespace App\Domains\Post\Repositories;

use Throwable;

trait HasTransactionSupport
{
    private function executeInTransaction(callable $callback): mixed
    {
        $this->db->beginTransaction();

        try {
            $result = $callback();
            $this->db->commit();

            return $result;
        } catch (Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }
    }
}
