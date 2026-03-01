<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\PoStringsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class PoStrings
{
    private Twig $twig;

    private PoStringsRepository $poStringsRepository;

    public function __construct(
        Twig $twig,
        PoStringsRepository $poStringsRepository
    ) {
        $this->twig = $twig;
        $this->poStringsRepository = $poStringsRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $poStrings = $this->poStringsRepository->findBy([], ['label' => 'asc']);

        return $this->twig->render($response, 'admin/po_strings.twig', ['poStrings' => $poStrings]);
    }
}