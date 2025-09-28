<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum DatabaseAction: string
{
    case CREATE = 'create';
    case READ = 'read';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case VIEW = 'view';
}
