<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class DeleteUser
{
    public function __construct(private Twig $twig, private UsersRepository $usersRepository)
    {
    }

	public function __invoke(Request $request, Response $response)
	{
		$user = $this->usersRepository->find($request->getQueryParams()['id']);

		$this->usersRepository->delete($user);

        return $response->withHeader('Location', '/admin/users')->withStatus(302);
	}
}