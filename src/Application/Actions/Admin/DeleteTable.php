<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\TablesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteTable
{
    private TablesRepository $tablesRepository;

    public function __construct(TablesRepository $tablesRepository)
    {
        $this->tablesRepository = $tablesRepository;
    }

	public function __invoke(Request $request, Response $response)
	{
		$table = $this->tablesRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		$this->tablesRepository->delete($table);

        return $response->withHeader('Location', '/admin/tables')->withStatus(302);
	}
}