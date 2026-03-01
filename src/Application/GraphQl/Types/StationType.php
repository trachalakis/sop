<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\StationsRepository;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class StationType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Station',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'name' => Type::string(),
                    'printerAddress' => Type::string(),
                    'isActive' => Type::boolean(),
                    'hasReceiptPrinter' => Type::boolean()
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
        return $context->get(StationsRepository::class)->findOneBy(['id' => $args['id']]);
    }
}