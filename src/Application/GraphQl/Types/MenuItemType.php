<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\MenuItemsRepository;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class MenuItemType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'MenuItem',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'availableQuantity' => Type::int(),
                    'trackAvailableQuantity' => Type::boolean(),
                    'isActive' => Type::boolean(),
                    'isPricePerKg' => Type::boolean(),
                    'isDrink' => Type::boolean(),
                    'price' => Type::float(),
                    'menuPosition' => Type::int(),
                    'translations' => Type::listOf(Types::menuItemTranslation()),
                    'menuItemExtras' => Type::listOf(Types::menuItemExtra()),
                    'stations' => Type::listOf(Types::station()),
                ];
            },
            'resolveField' => function ($object, $args, $context, $info) {
                return (new FieldResolver)($object, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    public function resolveType($rootValue, $args, $context, $info)
    {
        return $context->get(MenuItemsRepository::class)->findOneBy(['id' => $args['id']]);
    }
}