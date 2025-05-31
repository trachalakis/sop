<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class OrderEntryGroupType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'OrderEntryGroup',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'createdAt' => Types::date(),
                    'notes' => Type::string(),
                    'orderEntries' => Type::listOf(Types::orderEntry()),
                    'order' => Types::order()
                ];
            },
            'resolveField' => function ($object, $args, $context, $info) {
                return (new FieldResolver)($object, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }
}