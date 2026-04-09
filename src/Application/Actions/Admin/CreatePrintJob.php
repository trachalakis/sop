<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTimeImmutable;
use Domain\Entities\PrintJob;
use Domain\Repositories\PrintJobsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Domain\Enums\PrintJobStatus;

final class CreatePrintJob
{
    public function __construct(private PrintJobsRepository $printJobsRepository)
    {
    }

    public function __invoke(Request $request, Response $response)
	{
		if ($request->getMethod() == 'POST') {
            $requestData = json_decode(file_get_contents("php://input"), true);

            $printJob = new PrintJob;
            $printJob->setPrinter($requestData['printer']);
            $printJob->setXml($requestData['xml']);
            $printJob->setStatus(PrintJobStatus::pending);
            $printJob->setCreatedAt(new DateTimeImmutable);

            $this->printJobsRepository->persist($printJob);

            $response->getBody()->write('ok');
			return $response;
        }
	}
}