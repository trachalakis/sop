<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\PrintJobsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class PrintJobs
{
	private Twig $twig;

    //private PrintJobsRepository $printJobsRepository;

    public function __construct(
        Twig $twig,
        //PrintJobsRepository $printJobsRepository
    ) {
        $this->twig = $twig;
        //$this->printJobsRepository = $printJobsRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        //$tables = $this->tablesRepository->findBy([], ['isActive' => 'desc', 'name' => 'asc']);

        return $this->twig->render(
            $response, 
            'admin/print_jobs.twig' 
            //['tables' => $tables]
        );
    }
}