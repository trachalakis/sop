<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class UserType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'User',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'emailAddress' => Type::string(),
                    'fullName' => Type::string(),
                    'hourlyRate' => Type::float(),
                    'isActive' => Type::boolean(),
                    'roles' => Type::listOf(Type::string())
                ];
            },
            'resolveField' => function ($object, $args, $context, $info) {
                return (new FieldResolver)($object, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }
}