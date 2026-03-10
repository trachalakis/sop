<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Scan;
use Domain\Repositories\UsersRepository;
use Domain\Repositories\ScansRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateScan
{
	private ScansRepository $scansRepository;

    private Twig $twig;

    private UsersRepository $usersRepository;

    public function __construct(
        ScansRepository $scansRepository,
        Twig $twig,
        UsersRepository $usersRepository
    ) {
        $this->twig = $twig;
        $this->usersRepository = $usersRepository;
        $this->scansRepository = $scansRepository;
    }

    public function __invoke(Request $request, Response $response)
	{
		if ($request->getMethod() == 'POST') {
            $scanData = $request->getParsedBody();

            $scanData['checkIn'] = strlen($scanData['checkIn']) > 0 ? new \Datetime($scanData['checkIn']) : null;
            $scanData['checkOut'] = strlen($scanData['checkOut']) > 0 ? new \Datetime($scanData['checkOut']) : null;

            $user = $this->usersRepository->findOneBy(['id' => $scanData['user']]);
            $scan = new Scan(
                $user->getHourlyRate() ?? 0,
                $scanData['checkIn'],
                $scanData['checkOut'],
                $user
            );
            $this->scansRepository->persist($scan);

            return $response->withHeader('Location', '/admin/scans')->withStatus(302);
        }

        $queryParams = $request->getQueryParams();
        if (isset($queryParams['userId'])) {
            $user = $this->usersRepository->findOneBy(['id' => $queryParams['userId']]);
        }

        $users = $this->usersRepository->findBy(['isActive' => true], ['fullName' => 'asc']);
        return $this->twig->render(
            $response,
            'admin/create_scan.twig',
            [
                'users' => $users,
                'preSelectedUser' => $user ?? null
            ]
        );
	}
}