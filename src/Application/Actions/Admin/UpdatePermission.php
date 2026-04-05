<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\RolesRepository;
use Domain\Repositories\UserPermissionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdatePermission
{
    public function __construct(
        private Twig $twig,
        private UserPermissionsRepository $userPermissionsRepository,
        private RolesRepository $rolesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $permission = $this->userPermissionsRepository->find($request->getQueryParams()['id']);

        if ($request->getMethod() === 'POST') {
            $requestData = $request->getParsedBody();

            $permission->setPath($requestData['path']);
            $permission->setAllowedRoles($requestData['allowedRoles'] ?? []);
            $this->userPermissionsRepository->persist($permission);

            return $response->withHeader('Location', '/admin/permissions')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/update_permission.twig', [
            'permission' => $permission,
            'roles' => $this->rolesRepository->findBy([], ['label' => 'asc']),
        ]);
    }
}
