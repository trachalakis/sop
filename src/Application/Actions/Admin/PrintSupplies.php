<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\PrintersRepository;
use Domain\Repositories\SupplyGroupsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class PrintSupplies
{
    public function __construct(
        private SupplyGroupsRepository $supplyGroupsRepository,
        private PrintersRepository $printersRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $supplyGroups = $this->supplyGroupsRepository->findBy([], ['name' => 'asc']);
        $printers = $this->printersRepository->findBy(['isActive' => true], ['name' => 'asc']);

        $supplyGroupsData = array_values(array_filter(array_map(function ($group) {
            $supplies = array_values(array_map(function ($supply) {
                return [
                    'id' => $supply->getId(),
                    'name' => $supply->getName(),
                    'priceUnit' => $supply->getPriceUnit(),
                ];
            }, $group->getSupplies()->toArray()));

            if (count($supplies) === 0) {
                return null;
            }

            return [
                'name' => $group->getName(),
                'supplies' => $supplies,
            ];
        }, $supplyGroups)));

        $printersData = array_values(array_map(function ($printer) {
            return [
                'id' => $printer->getId(),
                'name' => $printer->getName(),
                'printerAddress' => $printer->getPrinterAddress(),
                'isReceiptPrinter' => $printer->getIsReceiptPrinter(),
            ];
        }, $printers));

        return $this->twig->render(
            $response,
            'admin/print_supplies.twig',
            [
                'supplyGroupsJson' => json_encode($supplyGroupsData),
                'printersJson' => json_encode($printersData),
            ]
        );
    }
}
