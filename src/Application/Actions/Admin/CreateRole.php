<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Role;
use Domain\Repositories\RolesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateRole
{
    public function __construct(private Twig $twig, private RolesRepository $rolesRepository)
    {
    }

    public function __invoke(Request $request, Response $response)
    {
        if ($request->getMethod() === 'POST') {
            $requestData = $request->getParsedBody();

            $role = new Role(
                $requestData['name'],
                $requestData['label'],
                isset($requestData['minimumManHours']) && $requestData['minimumManHours'] !== '' ? floatval($requestData['minimumManHours']) : null
            );
            $this->rolesRepository->persist($role);

            return $response->withHeader('Location', '/admin/roles')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/create_role.twig');
    }
}
