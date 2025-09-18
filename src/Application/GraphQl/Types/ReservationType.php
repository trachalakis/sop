<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\ReservationsRepositoryInterface;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ReservationType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Reservation',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'name' => Type::string(),
                    'adults' => Type::int(),
                    'minors' => Type::int(),
                    'dateTime' => Types::date(),
                    'comments' => Type::string(),
                    'status' => Type::string(),
                    'telephoneNumber' => Type::string(),
                    'tables' => Type::listOf(Type::string())
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
        return $context->get(ReservationsRepositoryInterface::class)->findOneBy(['id' => $args['id']]);
    }
}