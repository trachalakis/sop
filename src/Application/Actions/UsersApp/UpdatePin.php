<?php

declare(strict_types=1);

namespace Application\Actions\UsersApp;

use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdatePin
{
    private Twig $twig;

    private UsersRepository $usersRepository;

    public function __construct(
        UsersRepository $usersRepository,
        Twig $twig
    ) {
        $this->usersRepository = $usersRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
        $user = $this->usersRepository->find(($_SESSION['user'])->getId());

        

        if ($request->getMethod() == 'POST') {
            $requestParams = $request->getParsedBody();

            if ($user != null) {
                if (password_verify($requestParams['oldPin'], $user->getPasswordHash())) {
                    $user->setPassword($requestParams['newPin']);

                    $this->usersRepository->persist($user);


                    //$response->getBody()->write('Επι');
                    //return $response;
                    return $response->withHeader('Location', '/users-app/')->withStatus(302);
                }
            }
        }

        return $this->twig->render($response, 'users_app/update_pin.twig', ['user' => $user]);
    }
}