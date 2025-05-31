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
    private Settings $settings;

    private Twig $twig;

	public function __construct(
        Settings $settings,
        Twig $twig
    ) {
        $this->settings = $settings;
        $this->twig = $twig;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $this->twig->offsetSet('siteName', $this->settings->get('siteName'));
        
        $response = $handler->handle($request);
        return $response;
    }
}