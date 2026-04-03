<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Enums;

enum StatisticsType: string
{
    case OVERVIEW = 'overview';
    case POSTS = 'posts';
    case USERS = 'users';
    case TRENDS = 'trends';
    case SOURCES = 'sources';
}
