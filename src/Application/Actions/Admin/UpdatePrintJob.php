<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Enums\PrintJobStatus;
use Domain\Repositories\PrintersRepository;
use Domain\Repositories\PrintJobsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdatePrintJob
{
    private PrintersRepository $printersRepository;

    private PrintJobsRepository $printJobsRepository;

    private Twig $twig;

    public function __construct(
        PrintersRepository $printersRepository,
        PrintJobsRepository $printJobsRepository,
        Twig $twig
    ) {
        $this->printersRepository = $printersRepository;
        $this->printJobsRepository = $printJobsRepository;
        $this->twig = $twig;
    }

	public function __invoke(Request $request, Response $response)
	{
		$printJob = $this->printJobsRepository->find($request->getQueryParams()['id']);

		if ($request->getMethod() == 'POST') {
            $requestData = $request->getParsedBody();

            $printJob->setStatus(PrintJobStatus::from($requestData['status']));
            $printJob->setPrinter($requestData['printer']);

            $this->printJobsRepository->persist($printJob);

            return $response->withHeader('Location', '/admin/print-jobs')->withStatus(302);
    	}

        $printers = $this->printersRepository->findBy([], ['name' => 'asc']);
		return $this->twig->render(
            $response,
            'admin/update_print_job.twig', 
            [
                'printJob' => $printJob,
                'printers' => $printers,
                'statuses' => PrintJobStatus::cases()
            ]
        );
	}
}