<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\TablesRepositoryInterface;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class TableType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Table',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'name' => Type::string()
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
        return $context->get(TablesRepositoryInterface::class)->findOneBy(['id' => $args['id']]);
    }
}