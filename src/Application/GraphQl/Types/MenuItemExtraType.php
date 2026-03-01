<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\MenuItemExtrasRepository;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class MenuItemExtraType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'MenuItemExtra',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'name' => Type::string(),
                    'price' => Type::float()
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
        return $context->get(MenuItemExtrasRepository::class)->findOneBy(['id' => $args['id']]);
    }
}