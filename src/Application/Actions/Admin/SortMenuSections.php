<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenuSectionsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SortMenuSections
{
    private MenuSectionsRepositoryInterface $menuSectionsRepository;

    public function __construct(
    	MenuSectionsRepositoryInterface $menuSectionsRepository
    ) {
        $this->menuSectionsRepository = $menuSectionsRepository;
    }

    public function __invoke(Request $request, Response $response)
	{
		$menuSectionsIds = json_decode(file_get_contents('php://input'), true);
		$position = 1;

		foreach($menuSectionsIds as $menuSectionId) {
			$menuSection = $this->menuSectionsRepository->findOneBy(['id' => $menuSectionId]);
			$menuSection->setPosition($position);
			$this->menuSectionsRepository->persist($menuSection);

			$position++;
		}

		$response->getBody()->write('ok');
		return $response;
	}
}
