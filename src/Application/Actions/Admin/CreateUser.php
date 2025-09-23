<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\User;
use Domain\Repositories\UsersRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateUser
{
	private $twig;

    private $usersRepository;

    public function __construct(Twig $twig, UsersRepositoryInterface $usersRepository)
    {
        $this->twig = $twig;
        $this->usersRepository = $usersRepository;
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
                $requestData['roles']
            );
            $user->setMonthlyCredits(intval($requestData['monthlyCredits']));
            $user->setAllowedMenus($requestData['allowedMenus']);
            $user->setNotes($requestData['notes']);
            $this->usersRepository->persist($user);

            return $response->withHeader('Location', '/admin/users')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/create_user.twig');
	}
}