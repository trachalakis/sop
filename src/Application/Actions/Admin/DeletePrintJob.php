<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\PrintJobsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeletePrintJob
{
    public function __construct(private PrintJobsRepository $printJobsRepository)
    {
    }

	public function __invoke(Request $request, Response $response)
	{
		$printJob = $this->printJobsRepository->find($request->getQueryParams()['id']);

		$this->printJobsRepository->delete($printJob);

        return $response->withHeader('Location', '/admin/print-jobs')->withStatus(302);
	}
}