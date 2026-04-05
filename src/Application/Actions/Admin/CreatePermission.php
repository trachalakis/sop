<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\UserPermission;
use Domain\Repositories\RolesRepository;
use Domain\Repositories\UserPermissionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreatePermission
{
    public function __construct(
        private Twig $twig,
        private UserPermissionsRepository $userPermissionsRepository,
        private RolesRepository $rolesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        if ($request->getMethod() === 'POST') {
            $requestData = $request->getParsedBody();

            $permission = new UserPermission();
            $permission->setPath($requestData['path']);
            $permission->setAllowedRoles($requestData['allowedRoles'] ?? []);
            $this->userPermissionsRepository->persist($permission);

            return $response->withHeader('Location', '/admin/permissions')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/create_permission.twig', [
            'roles' => $this->rolesRepository->findBy([], ['label' => 'asc']),
        ]);
    }
}
