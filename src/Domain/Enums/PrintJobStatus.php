<?php

declare(strict_types=1);

namespace Domain\Enums;

enum PrintJobStatus: string
{
    case pending = 'pending';
    case completed = 'completed';
}