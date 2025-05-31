<?php

declare(strict_types=1);

namespace Middleware;

use Domain\Repositories\MenusRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;
use Slim\Views\Twig;

final class Menus implements MiddlewareInterface
{
    private MenusRepositoryInterface $menusRepository;

    private Twig $twig;

	public function __construct(
        MenusRepositoryInterface $menusRepository,
        Twig $twig
    ) {
        $this->menusRepository = $menusRepository;
        $this->twig = $twig;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $menus = $this->menusRepository->findBy(
            [], ['isActive' => 'desc', 'createdAt' => 'desc']
        );

        $this->twig->offsetSet('menus', $menus);
        
        $response = $handler->handle($request);
        return $response;
    }
}