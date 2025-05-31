<?php

declare(strict_types=1);

namespace Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class Logout
{
    public function __invoke(Request $request, Response $response)
	{
        unset($_SESSION['user']);

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}