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
    public function __construct(private Twig $twig, private TablesRepository $tablesRepository)
    {
    }

    public function __invoke(Request $request, Response $response)
	{
		if ($request->getMethod() == 'POST') {
            try {
                $requestData = $request->getParsedBody();

                $table = new Table;
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
            'admin/create_table.twig',
            ['exception' => $exception ?? null]
        );
	}
}