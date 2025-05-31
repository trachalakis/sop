<?php

declare(strict_types=1);

namespace Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Directions
{
	private Twig $twig;

	public function __construct(Twig $twig)
	{
		$this->twig = $twig;
	}

	public function __invoke(Request $request, Response $response, $args)
	{
		return $this->twig->render(
			$response,
			'directions.twig',
			['language' => $args['language']]
		);
	}
}