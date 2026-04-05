<?php

declare(strict_types=1);

namespace Middleware;

use Application\Settings\Settings;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;
use Slim\Views\Twig;

final class Globals implements MiddlewareInterface
{
	public function __construct(
        private Settings $settings,
        private Twig $twig
    ) { }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $this->twig->offsetSet('siteName', $this->settings->get('siteName'));
        $this->twig->offsetSet('appMode', $this->settings->get('appMode'));
        $this->twig->offsetSet('currentUser', $_SESSION['user']);
        
        $response = $handler->handle($request);
        return $response;
    }
}