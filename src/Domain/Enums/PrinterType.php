<?php

declare(strict_types=1);

namespace Domain\Enums;

enum PrinterType: string
{
    case SDP = 'server_direct_print';
    case CloudPRNT = 'cloud_prnt';
}