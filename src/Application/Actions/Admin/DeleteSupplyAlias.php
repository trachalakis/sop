<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SupplyAliasesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteSupplyAlias
{
    public function __construct(
        private SupplyAliasesRepository $supplyAliasesRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $alias = $this->supplyAliasesRepository->find((int) ($params['id'] ?? 0));

        if ($alias !== null) {
            $this->supplyAliasesRepository->delete($alias);
        }

        return $response->withHeader('Location', '/admin/supply-aliases')->withStatus(302);
    }
}
