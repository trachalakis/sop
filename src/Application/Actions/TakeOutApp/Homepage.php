<?php

declare(strict_types=1);

namespace Application\Actions\TakeOutApp;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Homepage
{
    public function __construct(private Twig $twig) {}

    public function __invoke(Request $request, Response $response)
    {
        return $this->twig->render($response, 'take_out_app/homepage.twig');
    }
}
