<?php

declare(strict_types=1);

namespace Domain\Enums;

enum MenuType: string
{
    case ALaCarte = 'a_la_carte';
    case Delivery = 'delivery';
    case Stuff = 'stuff';
    case TakeOut = 'take_out';

    public static function fromString(string $menuType): self
    {
        return match(true) {
            $menuType == 'a_la_carte' => self::ALaCarte,
            $menuType == 'delivery' => self::Delivery,
            $menuType == 'stuff' => self::Stuff,
            $menuType == 'take_out' => self::TakeOut,
        };
    }
}