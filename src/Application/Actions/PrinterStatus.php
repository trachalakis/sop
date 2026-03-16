<?php

declare(strict_types=1);

namespace Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Monolog\Logger;


final class PrinterStatus
{
	private Logger $logger;

	public function __construct(Logger $logger)
	{
		$this->logger = $logger;
	}

	public function __invoke(Request $request, Response $response, $args)
	{
		$requestParams = $request->getParsedBody();
		
        //$this->logger->debug('STATUS: ' . $request->getBody());

        if ($requestParams['ConnectionType'] == 'SetStatus') {
            
            $xml = simplexml_load_string($requestParams['Status']);
            
            foreach ($xml->servicestatus as $status) {
                if ((string)$status['severity'] != 'INFO') {
                    //Do something with the error
                    //sort it out in the future
                    /*$this->logger->debug(
                        sprintf(
                            "Printer: %s | Service: %s | Message: %s",
                            $requestParams['ID'],
                            (string)$status['servicename'],
                            (string)$status['message'],
                        )
                    );*/           
                }
            }
        }
           
		$response->getBody()->write('');
		return $response;
	}
}