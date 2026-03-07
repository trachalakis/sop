<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SuppliesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteSupply
{
    private SuppliesRepository $suppliesRepository;

    public function __construct(SuppliesRepository $suppliesRepository)
    {
        $this->suppliesRepository = $suppliesRepository;
    }

	public function __invoke(Request $request, Response $response)
	{
		$supply = $this->suppliesRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		$this->suppliesRepository->delete($supply);

        return $response->withHeader('Location', '/admin/supplies')->withStatus(302);
	}
}