<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class DeleteUser
{
	private $twig;

    private $usersRepository;

    public function __construct(Twig $twig, UsersRepository $usersRepository)
    {
        $this->usersRepository = $usersRepository;
        $this->twig = $twig;
    }

	public function __invoke(Request $request, Response $response)
	{
		$user = $this->usersRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		$this->usersRepository->delete($user);

        return $response->withHeader('Location', '/admin/users')->withStatus(302);
	}
}