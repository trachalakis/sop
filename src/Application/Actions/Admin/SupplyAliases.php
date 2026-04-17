<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SupplyAliasesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class SupplyAliases
{
    public function __construct(
        private SupplyAliasesRepository $supplyAliasesRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $aliases = $this->supplyAliasesRepository->findBy([], ['description' => 'ASC']);

        // Build aliasEntries: alias_id => InvoiceEntry[] sorted by invoice date ASC
        $aliasEntries = [];
        foreach ($aliases as $alias) {
            $entries = $this->supplyAliasesRepository->getEntityManager()
                ->createQuery(
                    'SELECT e FROM Domain\Entities\InvoiceEntry e
                     JOIN e.invoice i
                     WHERE e.supplyAlias = :alias
                     ORDER BY i.date ASC'
                )
                ->setParameter('alias', $alias)
                ->getResult();
            $aliasEntries[$alias->getId()] = $entries;
        }

        return $this->twig->render($response, 'admin/supply_aliases.twig', [
            'aliases'      => $aliases,
            'aliasEntries' => $aliasEntries,
        ]);
    }
}
