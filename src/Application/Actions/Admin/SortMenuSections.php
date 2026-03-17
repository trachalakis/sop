<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\MenuSectionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SortMenuSections
{
    private MenuSectionsRepository $menuSectionsRepository;

    public function __construct(
    	MenuSectionsRepository $menuSectionsRepository
    ) {
        $this->menuSectionsRepository = $menuSectionsRepository;
    }

    public function __invoke(Request $request, Response $response)
	{
		$menuSectionsIds = json_decode(file_get_contents('php://input'), true);
		$position = 1;

		foreach($menuSectionsIds as $menuSectionId) {
			$menuSection = $this->menuSectionsRepository->find($menuSectionId);
			$menuSection->setPosition($position++);
			$this->menuSectionsRepository->persist($menuSection);
		}

		$response->getBody()->write('ok');
		return $response;
	}
}
