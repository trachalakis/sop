<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\ScansRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class DeleteScan
{
	private $twig;

    private $scansRepository;

    public function __construct(Twig $twig, ScansRepositoryInterface $scansRepository)
    {
        $this->scansRepository = $scansRepository;
        $this->twig = $twig;
    }

	public function __invoke(Request $request, Response $response)
	{
		$scan = $this->scansRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		$this->scansRepository->delete($scan);

        return $response->withHeader('Location', '/admin/scans')->withStatus(302);
	}
}