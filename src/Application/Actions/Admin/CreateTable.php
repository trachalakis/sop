<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Domain\Entities\Table;
use Domain\Repositories\TablesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateTable
{
	private $twig;

    private $tablesRepository;

    public function __construct(Twig $twig, TablesRepository $tablesRepository)
    {
        $this->twig = $twig;
        $this->tablesRepository = $tablesRepository;
    }

    public function __invoke(Request $request, Response $response)
	{
		if ($request->getMethod() == 'POST') {
            try {
                $tableData = $request->getParsedBody();

                $table = new Table;
                $table->setIsActive(boolval($tableData['isActive']));
                $table->setName($tableData['name']);

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
            'admin/create_table.twig',
            ['exception' => $exception ?? null]
        );
	}
}