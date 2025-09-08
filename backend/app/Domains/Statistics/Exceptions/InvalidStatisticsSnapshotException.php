<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Exceptions;

use InvalidArgumentException;
use Throwable;

/**
 * 無效統計快照例外
 * 當統計快照參數不正確時拋出.
 */
class InvalidStatisticsSnapshotException extends InvalidArgumentException



{
    public function __construct(
        string $message = '無效的統計快照',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
