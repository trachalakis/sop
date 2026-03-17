<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SuppliesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Slim\Views\Twig;

final class DeleteSupply
{
    private SuppliesRepository $suppliesRepository;

    private Twig $twig;

    public function __construct(
        SuppliesRepository $suppliesRepository,
        Twig $twig    
    ) {
        $this->suppliesRepository = $suppliesRepository;
        $this->twig = $twig;
    }

	public function __invoke(Request $request, Response $response)
	{
		try {
            $supply = $this->suppliesRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

            $this->suppliesRepository->delete($supply);
        } catch (ForeignKeyConstraintViolationException $e) {
            return $this->twig->render(
                $response,
                'admin/update_supply.twig',
                [
                    'supply' => $supply,
                    'exception' => $e ?? null
                ]
            );
        }

        return $response->withHeader('Location', '/admin/supplies')->withStatus(302);
	}
}