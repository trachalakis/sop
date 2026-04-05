<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\UserPermissionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class DeletePermission
{
    public function __construct(
        private Twig $twig,
        private UserPermissionsRepository $userPermissionsRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $permission = $this->userPermissionsRepository->find($request->getQueryParams()['id']);

        $this->userPermissionsRepository->delete($permission);

        return $response->withHeader('Location', '/admin/permissions')->withStatus(302);
    }
}
