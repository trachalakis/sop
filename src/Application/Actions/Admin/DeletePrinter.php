<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\PrintersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeletePrinter
{
    public function __construct(private PrintersRepository $printersRepository)
    {
    }

	public function __invoke(Request $request, Response $response)
	{
		$printer = $this->printersRepository->find($request->getQueryParams()['id']);

		$this->printersRepository->delete($printer);

        return $response->withHeader('Location', '/admin/printers')->withStatus(302);
	}
}