<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\RolesRepository;
use Domain\Repositories\UserPermissionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Permissions
{
    public function __construct(
        private Twig $twig,
        private UserPermissionsRepository $userPermissionsRepository,
        private RolesRepository $rolesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $permissions = $this->userPermissionsRepository->findAll();

        $rolesMap = [];
        foreach ($this->rolesRepository->findBy([], ['label' => 'asc']) as $role) {
            $rolesMap[$role->getName()] = $role->getLabel();
        }

        return $this->twig->render($response, 'admin/permissions.twig', [
            'permissions' => $permissions,
            'rolesMap' => $rolesMap,
        ]);
    }
}
