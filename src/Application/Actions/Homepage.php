<?php

declare(strict_types=1);

namespace Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Homepage
{
	private Twig $twig;

	public function __construct(Twig $twig)
	{
		$this->twig = $twig;
	}

	public function __invoke(Request $request, Response $response, $args)
	{
		$language = isset($args['language']) ? $args['language'] : 'en';
		
		return $this->twig->render(
			$response,
			'homepage.twig',
			['language' => $language]
		);
	}
}