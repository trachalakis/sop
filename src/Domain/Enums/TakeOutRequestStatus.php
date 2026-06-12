<?php

declare(strict_types=1);

namespace Domain\Enums;

enum TakeOutRequestStatus: string
{
    case Pending = 'PENDING';
    case Accepted = 'ACCEPTED';
    case Rejected = 'REJECTED';
}
