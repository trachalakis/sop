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
	private Logger $logger;

    private PrintJobsRepository $printJobsRepository;

    public function __construct(PrintJobsRepository $printJobsRepository, Logger $logger)
    {
        $this->printJobsRepository = $printJobsRepository;
        $this->logger = $logger;
    }

	public function __invoke(Request $request, Response $response, $args)
	{
		
		
        $this->logger->debug(
            'SDP: ' . $request->getBody()
        );

        $requestParams = $request->getParsedBody();


        if ($requestParams['ConnectionType'] == 'SetResponse') {
            $xml = new \SimpleXMLElement($requestParams['ResponseFile']);

            $printJob = $this->printJobsRepository->find(
                $xml->PrintResponseInfo->ePOSPrint->Parameter->printjobid
            );

            $printJob->setStatus(PrintJobStatus::completed);

            $this->printJobsRepository->persist($printJob);
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
               
            }
        }
         return $response;
            /*$response->getBody()->write(
            '<?xml version="1.0" encoding="utf-8"?>
<PrintRequestInfo Version="2.00">

  <ePOSPrint>
    <Parameter>
      <devid>local_printer</devid>
      <timeout>10000</timeout>
      <printjobid>ABC123</printjobid>
    </Parameter>
    <PrintData>
      <epos-print xmlns="http://www.epson-pos.com/schemas/2011/03/epos-print">
                	<text lang="en"/>
        <text smooth="true"/>
        
        <text align="center"/>
        <barcode type="code39" hri="none" font="font_a" width="2" height="60">0001</barcode>
        <feed unit="30"/>
        <text align="left"/>
        <text>0001</text>
        <text>    03-19-2013 13:53:15&#10;</text>
        <text reverse="true"/>
        <text> Kitchen </text>
        <text reverse="false"/>
        <text>    </text>
        <text>[New Order] </text>
        <text>&#10;</text>
        <text width="1" height="2"/>
        <text>Seat: </text>
        <text width="2" height="2"/>
        <text>A-3</text>
        <text width="1" height="1"/>
        <text>&#10;</text>
        <text width="2" height="2"/>
        <text>2</text>
        <text width="1" height="2"/>
        <text>&#9;Alt Beer</text>
        <text width="1" height="1"/>
        <text>&#10;</text>
        <cut type="feed"/>
        
    </epos-print>
    </PrintData>
  </ePOSPrint>

</PrintRequestInfo>');
        }*/

  //      ob_start();
        //$connector = new DummyPrintConnector();
        //$printer = new Printer($connector, null);
        //$printer -> text("Hello World!\n");
        //$printer -> cut();
        //$printer -> close();
        //$out2 = $printer->getPrintBuffer();
//dd($out2);
//ob_end_clean();

		/*
        );*/
		//return $response;
	}
}