<?php

declare(strict_types=1);

namespace Application\GraphQl\Resolvers;

class FieldResolver
{
	public function __invoke($object, $args, $context, $info)
	{
		$method = 'get' . ucfirst($info->fieldName);
        return $object->$method();
	}
}