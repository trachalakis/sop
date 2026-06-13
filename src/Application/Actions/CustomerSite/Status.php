<?php

declare(strict_types=1);

namespace Application\Actions\CustomerSite;

use Domain\Repositories\TakeOutRequestsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;

final class Status
{
    public function __construct(
        private Twig $twig,
        private TakeOutRequestsRepository $takeOutRequestsRepository
    ) {}

    public function __invoke(Request $request, Response $response, array $args)
    {
        $takeOutRequest = $this->takeOutRequestsRepository->findOneByToken($args['token']);

        if ($takeOutRequest === null) {
            throw new HttpNotFoundException($request);
        }

        $entries = [];
        foreach ($takeOutRequest->getEntries() as $entry) {
            $extras = [];
            foreach ($entry->getExtras() as $extra) {
                $extras[] = $extra->getName();
            }
            $entries[] = [
                'quantity' => $entry->getQuantity(),
                'names' => [
                    'el' => $entry->getMenuItem()->getTranslation('el')?->getName() ?? '',
                    'en' => $entry->getMenuItem()->getTranslation('en')?->getName() ?? '',
                ],
                'extras' => $extras,
                'price' => $entry->getPrice(),
            ];
        }

        return $this->twig->render($response, 'customer_site/status.twig', [
            'token' => $takeOutRequest->getToken(),
            'customerName' => $takeOutRequest->getCustomerName(),
            'stateJson' => json_encode([
                'status' => $takeOutRequest->getStatus()->value,
                'etaMinutes' => $takeOutRequest->getEtaMinutes(),
                'ticketNumber' => $takeOutRequest->getOrder()?->getTicketNumber(),
                'entries' => $entries,
                'total' => $takeOutRequest->getTotal(),
            ]),
        ]);
    }
}
