<?php

declare(strict_types=1);

namespace Domain\Enums;

enum MenuType: string
{
    case ALaCarte = 'a_la_carte';
    case Delivery = 'delivery';
    case Stuff = 'stuff';
    case TakeOut = 'take_out';
}