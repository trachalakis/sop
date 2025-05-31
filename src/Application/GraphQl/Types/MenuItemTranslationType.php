<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\MenuSectionsRepositoryInterface;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class MenuItemTranslationType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'MenuItemTranslation',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'name' => Type::string(),
                    'language' => Type::string(),
                ];
            },
            'resolveField' => function ($object, $args, $context, $info) {
                switch ($info->fieldName) {
                	case 'language': return $object->getLanguage()->getIsoCode();
                	default: return (new FieldResolver)($object, $args, $context, $info);
                }
            }
        ];
        parent::__construct($config);
    }
}