<?php

declare(strict_types=1);

namespace App\Domains\Security\Enums;

enum ActivityCategory: string
{
    case AUTHENTICATION = 'authentication';
    case CONTENT = 'content';
    case SECURITY = 'security';
    case ATTACHMENT = 'attachment';
    case SYSTEM = 'system';
}
