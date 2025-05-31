<?php

namespace Application\GraphQl\Types;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

class DateType extends ScalarType
{
	//public $name = 'Date';

	public function serialize($value): string
	{
		if (! $value instanceof \DateTimeImmutable && ! $value instanceof \DateTime) {
			throw new InvariantViolation('DateTime is not an instance of DateTimeImmutable: ' . Utils::printSafe($value));
		}

		return $value->format('Y-m-d H:i:s');
	}

	public function parseValue($value): ?\DateTimeImmutable
	{
		return \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value) ?: null;
	}

	public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        // Note: throwing GraphQL\Error\Error vs \UnexpectedValueException to benefit from GraphQL
        // error location in query:
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
        }
        if (!\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $valueNode->value)) {
            throw new Error("Not a valid date", [$valueNode]);
        }
        return $valueNode->value;
    }
}