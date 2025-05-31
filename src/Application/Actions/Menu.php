<?php

declare(strict_types=1);

namespace Application\Actions;

use Domain\Entities\MenuSection;
use Domain\Repositories\MenuSectionsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Menu
{
	private Twig $twig;

	private MenuSectionsRepository $menuSections;

	public function __construct(Twig $twig, MenuSectionsRepositoryInterface $menuSectionsRepository)
	{
		$this->twig = $twig;
		$this->menuSectionsRepository = $menuSectionsRepository;
	}

	public function __invoke(Request $request, Response $response, $args)
	{
		$menuSections = $this->menuSectionsRepository->findBy(
			['isActive' => true, 'isPublic' => true],
			['position' => 'asc']
		);
		return $this->twig->render(
			$response,
			'menu.twig',
			[
				'menuSections' => $menuSections,
				'language' => $args['language']
			]
		);
	}
}