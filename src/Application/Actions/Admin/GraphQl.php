<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Application\GraphQl\Types\AdminQueryType;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Error\DebugFlag;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GraphQl
{
	public function __construct(private ContainerInterface $container)
    {
    }

    public function __invoke(Request $request, Response $response)
    {
		$queryParams = $request->getQueryParams();
		$useCachedResults = isset($queryParams['useCachedResults']);

		$key = sha1($request->getBody()->__toString());
		$fetched = false;

		if (function_exists('apcu_fetch')) {
			$output = apcu_fetch($key, $fetched);
		}

		if (!$useCachedResults || !$fetched || !function_exists('apcu_clear_cache')) {
			$result = \GraphQL\GraphQL::executeQuery(
				new Schema([
	        		'query' => new AdminQueryType
	        	]),
				$request->getBody()->__toString(),
				null,
				$this->container,
				null
			);
			$output = json_encode($result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE));

			if (function_exists('apcu_store')) {
            	apcu_store($key, $output, 43200);
            }
		}

		$response->getBody()->write($output);
		return $response->withHeader('Content-Type', 'application/json');
    }
}