<?php

declare(strict_types=1);

namespace Domain\Enums;

enum PriceUnit: string
{
    case kg = 'kg';
    case item = 'item';
    case carton = 'carton';
    case lt = 'liters';
}