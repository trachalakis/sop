<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\TablesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateTable
{
    private TablesRepository $tablesRepository;

    private Twig $twig;

    public function __construct(
        TablesRepository $tablesRepository,
        Twig $twig
    ) {
        $this->tablesRepository = $tablesRepository;
        $this->twig = $twig;
    }

	public function __invoke(Request $request, Response $response)
	{
		$table = $this->tablesRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		if ($request->getMethod() == 'POST') {
			$tableData = $request->getParsedBody();

            $table->setIsActive(boolval($tableData['isActive']));
            $table->setName($tableData['name']);

			$this->tablesRepository->persist($table);

            if (function_exists('apcu_clear_cache')) {
            	apcu_clear_cache();
            }

            return $response->withHeader('Location', '/admin/tables')->withStatus(302);
    	}

		return $this->twig->render($response, 'admin/update_table.twig', ['table' => $table]);
	}
}