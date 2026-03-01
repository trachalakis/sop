<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\OrderEntryCancellationsRepository;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class OrderEntryCancellationType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'OrderEntryCancellation',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'createdAt' => Types::date(),
                    'cancellationReason' => Type::string(),
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
        return $context->get(OrderEntryCancellationsRepository::class)->findOneBy(['id' => $args['id']]);
    }
}