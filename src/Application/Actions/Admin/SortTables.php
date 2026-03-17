<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\TablesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SortTables
{
    private TablesRepository $tablesRepository;

    public function __construct(
    	TablesRepository $tablesRepository
    ) {
        $this->tablesRepository = $tablesRepository;
    }

    public function __invoke(Request $request, Response $response)
	{
		$tableIds = json_decode(file_get_contents('php://input'), true);
		$position = 1;

		foreach($tableIds as $tableId) {
			$table = $this->tablesRepository->find($tableId);
			$table->setPosition($position++);
			$this->tablesRepository->persist($table);
		}

		$response->getBody()->write('ok');
		return $response;
	}
}
