<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\OrdersRepositoryInterface;
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
                    'createdAt' => Types::date(),
                    'discount' => Type::float(),
                    'discountReason' => Type::string(),
                    'family' => Type::int(),
                    'timing' => Type::int(),
                    'quantity' => Type::int(),
                    //'maxQuantity' => Type::int(),
                    'notes' => Type::string(),
                    //'price' => Type::float(),
                    'weight' => Type::int(),
                    //'maxWeight' => Type::int(),
                    'menuItem' => Types::menuItem(),
                    'menuItemPrice' => Type::float(),
                    'isPaid' => Type::boolean(),
                    //'isDeleted' => Type::boolean(),
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

    public function resolveType($rootValue, $args, $context, $info)
    {
        return $context->get(OrdersRepositoryInterface::class)->findOneBy(['id' => $args['id']]);
    }
}