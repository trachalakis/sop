<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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
		$table = $this->tablesRepository->find($request->getQueryParams()['id']);

		if ($request->getMethod() == 'POST') {
			try {
                $requestData = $request->getParsedBody();

                $table->setIsActive(boolval($requestData['isActive']));
                $table->setName($requestData['name']);
                $table->setPosition(intval($requestData['position']));

                $this->tablesRepository->persist($table);

                if (function_exists('apcu_clear_cache')) {
                    apcu_clear_cache();
                }

                return $response->withHeader('Location', '/admin/tables')->withStatus(302);
            } catch (UniqueConstraintViolationException $e) {
                $exception = $e;
            }
    	}

		return $this->twig->render(
            $response,
            'admin/update_table.twig', 
            [
                'table' => $table,
                'exception' => $exception ?? null
            ]
        );
	}
}