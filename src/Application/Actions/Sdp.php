<?php

declare(strict_types=1);

namespace Application\Actions;

use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Monolog\Logger;
use Domain\Repositories\PrintJobsRepository;
use Domain\Enums\PrintJobStatus;


final class Sdp
{
    public function __construct(
        private PrintJobsRepository $printJobsRepository, 
        private Logger $logger
    ){}

	public function __invoke(Request $request, Response $response, $args)
	{
        $requestParams = $request->getParsedBody();

        if ($requestParams['ConnectionType'] == 'SetResponse') {
            $xml = simplexml_load_string($requestParams['ResponseFile']);
 
            if ($xml->ePOSPrint->PrintResponse->response['success'] == 'true') {
                $printJob = $this->printJobsRepository->find(
                    (int)$xml->ePOSPrint->Parameter->printjobid
                );
                $printJob->setStatus(PrintJobStatus::completed);
            
                $this->printJobsRepository->persist($printJob);
            }

            $response->getBody()->write('');
        }

        if ($requestParams['ConnectionType'] == 'GetRequest') {
            $printJob = $this->printJobsRepository->findOneBy([
                'printer' => $requestParams['ID'],
                'status' => PrintJobStatus::pending
            ]);

            if ($printJob != null) {
                $response->getBody()->write(
                    '<?xml version="1.0" encoding="utf-8"?>
                        <PrintRequestInfo Version="2.00">

                        <ePOSPrint>
                            <Parameter>
                            <devid>local_printer</devid>
                            <timeout>10000</timeout>
                            <printjobid>' . $printJob->getId() . '</printjobid>
                            </Parameter>
                            <PrintData>' . $printJob->getXml() . '</PrintData>
                        </ePOSPrint>

                        </PrintRequestInfo>'
                );
               
            } else {
                $response->getBody()->write('');
            }
        }
        
        return $response;
	}
}