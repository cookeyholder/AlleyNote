<?php

declare(strict_types=1);

namespace App\Shared\Enums;

/**
 * EventType enum.
 *
 * Defines common event types for logging and event-driven architecture.
 */
enum EventType: string
{
    case USER_ACTION = 'user_action';
    case SYSTEM = 'system';
    case ERROR = 'error';
}
