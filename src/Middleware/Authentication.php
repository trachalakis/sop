<?php

declare(strict_types=1);

namespace Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Psr\Http\Server\MiddlewareInterface;

final class Authentication implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (!isset($_SESSION['user'])) {
            $response = new Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $response = $handler->handle($request);
        return $response;
    }
}