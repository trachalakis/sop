<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\LanguagesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ToggleLanguage
{
    public function __construct(
        private LanguagesRepository $languagesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $language = $this->languagesRepository->find($request->getQueryParams()['id']);

        $language->setIsActive(!$language->getIsActive());

        $this->languagesRepository->persist($language);

        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        $response->getBody()->write('ok');
        return $response;
    }
}