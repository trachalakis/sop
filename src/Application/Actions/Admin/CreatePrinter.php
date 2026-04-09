<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Printer;
use Domain\Enums\PrinterType;
use Domain\Repositories\PrintersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreatePrinter
{
    public function __construct(private Twig $twig, private PrintersRepository $printersRepository)
    {
    }

    public function __invoke(Request $request, Response $response)
	{
		if ($request->getMethod() == 'POST') {
            $requestData = $request->getParsedBody();

            $printer = new Printer;
            $printer->setIsActive(boolval($requestData['isActive']));
            $printer->setName($requestData['name']);
            $printer->setPrinterType(PrinterType::from($requestData['printerType']));
            $printer->setHasReceiptPrinter(false);
            $printer->setPrinterAddress('0.0.0.0');
            $printer->setIsUtilityPrinter(boolval($requestData['isUtilityPrinter'] ?? false));

            $this->printersRepository->persist($printer);

            if (function_exists('apcu_clear_cache')) {
                apcu_clear_cache();
            }

            return $response->withHeader('Location', '/admin/printers')->withStatus(302);
        }

        return $this->twig->render(
            $response, 
            'admin/create_printer.twig', 
            [
                'printerTypes' => PrinterType::cases()
            ]
        );
	}
}