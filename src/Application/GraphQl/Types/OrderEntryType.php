<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class OrderEntryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'OrderEntry',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'discount' => Type::float(),
                    'discountReason' => Type::string(),
                    'family' => Type::int(),
                    'timing' => Type::int(),
                    'quantity' => Type::int(),
                    'notes' => Type::string(),
                    'weight' => Type::int(),
                    'menuItem' => Types::menuItem(),
                    'menuItemPrice' => Type::float(),
                    'isPaid' => Type::boolean(),
                    'paymentMethod' => Type::string(),
                    'orderEntryCancellations' => Type::listOf(Types::orderEntryCancellation()),
                    'orderEntryExtras' => Type::listOf(Types::orderEntryExtra())
                ];
            },
            'resolveField' => function ($object, $args, $context, $info) {
                return (new FieldResolver)($object, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }
}