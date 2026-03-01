<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\LanguagesRepository;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class LanguageType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Language',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'name' => Type::string(),
                    'isoCode' => Type::string(),
                ];
            },
            'resolveField' => function ($object, $args, $context, $info) {
                return (new FieldResolver)($object, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }

    /*public function resolveType($rootValue, $args, $context, $info)
    {
        return $context->get(LanguagesRepository::class)->findOneBy(['id' => $args['id']]);
    }*/
}