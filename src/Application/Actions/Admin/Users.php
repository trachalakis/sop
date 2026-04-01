<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Users
{
	public function __construct(private Twig $twig, private UsersRepository $usersRepository)
	{
	}

	public function __invoke(Request $request, Response $response)
	{
		$users = $this->usersRepository->findBy([], ['isActive' => 'desc', 'fullName' => 'asc']);

		return $this->twig->render($response, 'admin/users.twig', ['users' => $users]);
	}
}