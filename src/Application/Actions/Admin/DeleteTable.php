<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\TablesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Slim\Views\Twig;

final class DeleteTable
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
		try {
            $table = $this->tablesRepository->find($request->getQueryParams()['id']);

            $this->tablesRepository->delete($table);
        } catch (ForeignKeyConstraintViolationException $e) {
            return $this->twig->render(
                $response,
                'admin/update_table.twig',
                [
                    'table' => $table,
                    'exception' => $e ?? null
                ]
            );
        }

        return $response->withHeader('Location', '/admin/tables')->withStatus(302);
	}
}