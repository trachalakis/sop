<?php

declare(strict_types=1);

namespace Domain\Enums;

enum OrderStatus: string
{
    case OPEN = 'OPEN';
    case CLOSED = 'CLOSED';
}