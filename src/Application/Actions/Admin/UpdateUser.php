<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\UsersRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateUser
{
	private Twig $twig;

    private UsersRepositoryInterface $usersRepository;

    public function __construct(Twig $twig, UsersRepositoryInterface $usersRepository)
    {
        $this->twig = $twig;
        $this->usersRepository = $usersRepository;
    }

    public function __invoke(Request $request, Response $response)
	{
		$requestData = $request->getParsedBody();
		$user = $this->usersRepository->findOneBy(['id' => $request->getQueryparams()['id']]);

		if ($request->getMethod() == 'POST') {
            $user->setIsActive(boolval($requestData['isActive']));
            $user->setEmailAddress($requestData['emailAddress']);
            if (!empty($requestData['password'])) {
            	$user->setPassword($requestData['password']);
            }
            $user->setFullName($requestData['fullName']);
            $user->setHourlyRate(floatval($requestData['hourlyRate']));
            $user->setMonthlyCredits(intval($requestData['monthlyCredits']));
            $user->setRoles($requestData['roles']);
            $user->setAllowedMenus($requestData['allowedMenus']);
            $user->setNotes($requestData['notes']);

            $this->usersRepository->persist($user);

            return $response->withHeader('Location', '/admin/users')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/update_user.twig',['user' => $user]);
	}
}