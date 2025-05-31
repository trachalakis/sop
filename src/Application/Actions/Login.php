<?php

declare(strict_types=1);

namespace Application\Actions;

use Domain\Repositories\UsersRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Login
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
		if ($request->getMethod() == 'POST') {
        	$requestParams = $request->getParsedBody();

        	$user = $this->usersRepository->findOneBy([
                'emailAddress' => $requestParams['emailAddress'],
                'isActive' => true
            ]);

            if ($user != null) {
                if (password_verify($requestParams['password'], $user->getPasswordHash())) {
                	$_SESSION['user'] = $user;

                    if (in_array('webmaster', $user->getRoles())) {
                        return $response->withHeader('Location', '/admin/')->withStatus(302);
                    }
                    if (in_array('terminal', $user->getRoles())) {
                        return $response->withHeader('Location', '/orders-app/')->withStatus(302);
                    }
                    if (in_array('employee', $user->getRoles())) {
                        return $response->withHeader('Location', '/users-app/')->withStatus(302);
                    }
                }
            }
        }

		return $this->twig->render($response, 'login.twig');
	}
}