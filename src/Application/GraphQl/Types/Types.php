<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

class Types
{
    private static $types = [];

    public static function date()
    {
        return static::get(DateType::class);
    }

    public static function language()
    {
        return static::get(LanguageType::class);
    }

    public static function menu()
    {
        return static::get(MenuType::class);
    }
    
    public static function menuSection()
    {
        return static::get(MenuSectionType::class);
    }

    public static function menuSectionTranslation()
    {
        return static::get(MenuSectionTranslationType::class);
    }

    public static function menuItem()
    {
        return static::get(MenuItemType::class);
    }

    public static function menuItemTranslation()
    {
        return static::get(MenuItemTranslationType::class);
    }

    public static function menuItemExtra()
    {
        return static::get(MenuItemExtraType::class);
    }

    public static function table()
    {
        return static::get(TableType::class);
    }

    public static function order()
    {
        return static::get(OrderType::class);
    }

    public static function orderEntry()
    {
        return static::get(OrderEntryType::class);
    }

    public static function orderEntryGroup()
    {
        return static::get(OrderEntryGroupType::class);
    }

    public static function orderEntryCancellation()
    {
        return static::get(OrderEntryCancellationType::class);
    }

    public static function orderEntryExtra()
    {
        return static::get(OrderEntryExtraType::class);
    }

    public static function reservation()
    {
        return static::get(ReservationType::class);
    }

    public static function station()
    {
        return static::get(StationType::class);
    }

    public static function supplier()
    {
        return static::get(SupplierType::class);
    }

    public static function supply()
    {
        return static::get(SupplyType::class);
    }

    public static function user()
    {
        return static::get(UserType::class);
    }

    public static function get($className)
    {
        $parts = explode("\\", $className);
        $cacheName = strtolower(preg_replace('~Type$~', '', $parts[count($parts) - 1]));
        $type = null;

        if (!isset(self::$types[$cacheName])) {
            if (class_exists($className)) {
                $type = new $className();
            }

            self::$types[$cacheName] = $type;
        }

        $type = self::$types[$cacheName];

        if (!$type) {
            throw new \Exception("Unknown graphql type: " . $className);
        }
        return $type;
    }

}