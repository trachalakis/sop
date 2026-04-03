<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\User;
use Domain\Repositories\RolesRepository;
use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateUser
{
    public function __construct(
        private Twig $twig,
        private UsersRepository $usersRepository,
        private RolesRepository $rolesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
	{
		if ($request->getMethod() == 'POST') {
            $requestData = $request->getParsedBody();

            $user = new User(
                boolval($requestData['isActive']),
                $requestData['emailAddress'],
                $requestData['password'],
                $requestData['fullName'],
                floatval($requestData['hourlyRate']),
                $requestData['roles'] ?? []
            );
            $user->setNotes($requestData['notes']);
            $this->usersRepository->persist($user);

            return $response->withHeader('Location', '/admin/users')->withStatus(302);
        }

        return $this->twig->render(
            $response,
            'admin/create_user.twig',
            ['userRoles' => $this->rolesRepository->findBy([], ['label' => 'asc'])]
        );
	}
}