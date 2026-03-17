<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\OrdersRepository;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class OrderType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Order',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'uuid' => Type::string(),
                    'status' => Type::string(),
                    'adults' => Type::int(),
                    'minors' => Type::int(),
                    'table' => Types::table(),
                    'notes' => Type::string(),
                    'price' => Type::float(),
                    'createdAt' => Types::date(),
                    'orderEntries' => Type::listOf(Types::orderEntry()),
                    'orderEntryGroups' => Type::listOf(Types::orderEntryGroup())
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
        return $context->get(OrdersRepository::class)->find($args['id']);
    }
}