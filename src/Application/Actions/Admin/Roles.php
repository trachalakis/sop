<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\RolesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Roles
{
    public function __construct(private Twig $twig, private RolesRepository $rolesRepository)
    {
    }

    public function __invoke(Request $request, Response $response)
    {
        $roles = $this->rolesRepository->findBy([], ['label' => 'asc']);

        return $this->twig->render($response, 'admin/roles.twig', ['roles' => $roles]);
    }
}
