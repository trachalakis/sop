<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\RolesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class DeleteRole
{
    public function __construct(private RolesRepository $rolesRepository)
    {
    }

    public function __invoke(Request $request, Response $response)
    {
        $role = $this->rolesRepository->find($request->getQueryParams()['id']);
        $this->rolesRepository->delete($role);

        return $response->withHeader('Location', '/admin/roles')->withStatus(302);
    }
}
