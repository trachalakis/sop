<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\EmailAddress;
use Domain\Repositories\SuppliersRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateSupplier
{
	private SuppliersRepositoryInterface $suppliersRepository;

    private Twig $twig;

    public function __construct(Twig $twig, SuppliersRepositoryInterface $suppliersRepository)
    {
        $this->suppliersRepository = $suppliersRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
	{
		$requestData = $request->getParsedBody();
		$supplier = $this->suppliersRepository->findOneBy(['id' => $request->getQueryparams()['id']]);

		if ($request->getMethod() == 'POST') {
            $supplier->setName($requestData['name']);
            $supplier->setAddress($requestData['address']);
            $supplier->setOccupation($requestData['occupation']);
            $supplier->setTaxOffice($requestData['taxOffice']);
            $supplier->setTaxRegistrationNumber($requestData['taxRegistrationNumber']);
            $supplier->setEmailAddress($requestData['emailAddress']);

            $this->suppliersRepository->persist($supplier);

            return $response->withHeader('Location', '/admin/suppliers')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/update_supplier.twig',['supplier' => $supplier]);
	}
}