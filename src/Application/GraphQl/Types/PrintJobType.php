<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\PrintJobsRepository;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PrintJobType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'PrintJob',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'createdAt' => Types::date(),
                    'printer' => Type::string(),
                    'status' => Type::string(),
                    'xml' => Type::string(),
                ];
            },
            'resolveField' => function ($object, $args, $context, $info) {
                return (new FieldResolver)($object, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }
}