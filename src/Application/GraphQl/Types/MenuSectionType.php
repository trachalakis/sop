<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\MenuSectionsRepository;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class MenuSectionType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'MenuSection',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'isActive' => Type::boolean(),
                    'translations' => Type::listOf(Types::menuSectionTranslation()),
                    'activeMenuItems' => Type::listOf(Types::menuItem()),
                ];
            },
            'resolveField' => function ($object, $args, $context, $info) {
                /*switch ($info->fieldName) {
                	case 'name': return $object->getTranslation('en')->getName();
                	default: return (new FieldResolver)($object, $args, $context, $info);
                }*/
                return (new FieldResolver)($object, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    public function resolveType($rootValue, $args, $context, $info)
    {
        return $context->get(MenuSectionsRepository::class)->findOneBy(['id' => $args['id']]);
    }
}