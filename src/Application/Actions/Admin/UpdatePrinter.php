<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Enums\PrinterType;
use Domain\Repositories\PrintersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdatePrinter
{
    public function __construct(
        private PrintersRepository $printersRepository,
        private Twig $twig
    ) {
    }

	public function __invoke(Request $request, Response $response)
	{
		$printer = $this->printersRepository->find($request->getQueryParams()['id']);

		if ($request->getMethod() == 'POST') {
			$requestData = $request->getParsedBody();

            $printer->setIsActive(boolval($requestData['isActive']));
            $printer->setName($requestData['name']);
            $printer->setPrinterType(PrinterType::from($requestData['printerType']));
            $printer->setIsUtilityPrinter(boolval($requestData['isUtilityPrinter'] ?? false));

			$this->printersRepository->persist($printer);

            if (function_exists('apcu_clear_cache')) {
            	apcu_clear_cache();
            }

            return $response->withHeader('Location', '/admin/printers')->withStatus(302);
    	}

		return $this->twig->render(
            $response, 
            'admin/update_printer.twig', 
            [
                'printer' => $printer,
                'printerTypes' => PrinterType::cases()
            ]
        );
	}
}