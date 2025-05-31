<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class SupplierType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Supplier',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'address' => Type::string(),
                    'name' => Type::string(),
                    'occupation' => Type::string(),
                    'taxOffice' => Type::string(),
                    'taxRegistrationNumber' => Type::string(),
                ];
            },
            'resolveField' => function ($object, $args, $context, $info) {
                return (new FieldResolver)($object, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }
}