<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\LanguagesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Languages
{
    public function __construct(
        private Twig $twig,
        private LanguagesRepository $languagesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $languages = $this->languagesRepository->findBy([], ['name' => 'asc']);

        return $this->twig->render(
            $response, 
            'admin/languages.twig', 
            ['languages' => $languages]
        );
    }
}