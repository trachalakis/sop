<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\PrintersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Printers
{
    public function __construct(
        private PrintersRepository $printersRepository,
        private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $printers = $this->printersRepository->findAll();

        return $this->twig->render($response, 'admin/printers.twig', ['printers' => $printers]);
    }
}