<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class SupplyType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Supply',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'description' => Type::string(),
                    'name' => Type::string(),
                    'unit' => Type::string(),
                    'vatPercentage' => Type::float()
                ];
            },
            'resolveField' => function ($object, $args, $context, $info) {
                return (new FieldResolver)($object, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }
}