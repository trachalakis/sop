<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Application\GraphQl\Resolvers\FieldResolver;
use Domain\Repositories\PrintersRepository;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PrinterType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Printer',
            'fields' => function ()  {
                return [
                    'id' => Type::id(),
                    'name' => Type::string(),
                    'printerAddress' => Type::string(),
                    'isActive' => Type::boolean(),
                    'isReceiptPrinter' => Type::boolean(),
                    'isUtilityPrinter' => Type::boolean()
                ];
            },
            'resolveField' => function ($object, $args, $context, $info) {
                return (new FieldResolver)($object, $args, $context, $info);
            }
        ];
        parent::__construct($config);
    }
}