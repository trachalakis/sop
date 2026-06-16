<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Homepage
{
    public function __construct(
        private Twig $twig
    ) { }

    public function __invoke(Request $request, Response $response)
    {
        // Visual shell only — the dashboard widgets render with sample data
        // for now; live data will be wired in a later pass.
        return $this->twig->render($response, 'admin/homepage.twig');
    }
}
